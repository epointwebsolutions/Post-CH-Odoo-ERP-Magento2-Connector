<?php

namespace Epoint\SwisspostApi\Helper\Api\Curl;

Use Epoint\SwisspostApi\Helper\Api\Curl\Result as SwissPostResult;
Use Epoint\SwisspostApi\Helper\Api\Client as SwissPostClient;
use Epoint\SwisspostApi\Helper\LoggerTrait;
use Epoint\SwisspostApi\Helper\Email as EmailHelper;

/**
 * Curl Client SwissPost
 *
 */
class Client extends SwissPostClient
{
    /**
     * Trait logger
     */
    use LoggerTrait;

    /**
     * Curl connect timeout.
     *
     * @const CONNET_TIMEOUT
     */
    const CONNECT_TIMEOUT = 5;

    /**
     * Curl response timeout
     *
     * @const RESPONSE_TIMEOUT
     */
    private $responseTimeout = 15;

    /**
     * Session id provided from json response.
     *
     * @var string
     */
    private $session_id = '';

    /**
     * md5 connection hash
     *
     * @var string
     */
    private $md5 = '';

    private $baseLocation = '';

    private $login = '';

    private $password = '';

    private $db = '';

    private $shopIdent = '';

    private $jsonrpc = '2.0';

    private $tmpDir = '/tmp/';

    private $curlResource;

    /**
     * session cookie path
     *
     * @var string
     */
    private $cookieFilePath = '';

    /**
     * @var EmailHelper
     */
    protected $emailHelper;

    /**
     * Implement constructor
     *
     * @param $options
     */
    public function __construct($options)
    {
        //Get Object Manager Instance
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        // Email helper
        $this->emailHelper = $objectManager->get(EmailHelper::class);

        // Service entry point
        if ($options['base_location']) {
            $this->baseLocation = $options['base_location'];
        }
        // Set tmp dir.
        if ($options['tmp_dir']) {
            $this->tmpDir = $options['tmp_dir'];
        }
        // Connect username.
        if ($options['login']) {
            $this->login = $options['login'];
        }
        // Connect password.
        if ($options['password']) {
            $this->password = $options['password'];
        }
        if ($options['db']) {
            $this->db = $options['db'];
        }
        if ($options['shop_ident']) {
            $this->shopIdent = $options['shop_ident'];
        }
        if ($options['jsonrpc']) {
            $this->jsonrpc = $options['jsonrpc'];
        }
        if (isset($options['Logger'])) {
            $this->logger = $options['Logger'];
        }
        if (isset($options['timeout'])) {
            $this->responseTimeout = $options['timeout'];
        }
        // Missing cookie file? create new one.
        if (!$this->cookieFilePath) {
            $this->reuseSession();
        }
        // Check if file exists.
        if (file_exists($this->cookieFilePath)) {
            if (!is_writable($this->cookieFilePath)) {
                throw new \Exception('The cookie path is not writable, please set it properly.');
            }
        }
        if (!function_exists('curl_init')) {
            throw new \Exception('The php library curl is missing, please configure it.');
        }
    }

    /**
     *Set Cookiet file path.
     */
    public function setCoookieFilePath()
    {
        $hash = md5($this->baseLocation . '-' . $this->login . '-' . $this->shopIdent);
        $this->cookieFilePath = $this->tmpDir . DIRECTORY_SEPARATOR . 'epoint_swisspost_cookie_' . $hash . '.txt';
    }

    /**
     * Try to reuse session, stored local.
     */
    private function reuseSession()
    {
        // Try to recover sid from cookie.
        $cookies = $this->extractCookies();
        if ($cookies) {
            foreach ($cookies as $variable) {
                if ($variable['name'] == 'sid') {
                    if ($variable['value']) {
                        $this->session_id = $variable['value'];
                        $this->md5 = md5(
                            $this->session_id,
                            file_get_contents($this->cookieFilePath)
                        );
                    }
                }
            }
        }
    }

    /**
     * Close resource on destruct
     */
    public function __destruct()
    {
        if ($this->curlResource) {
            curl_close($this->curlResource);
        }
        // Remove cookie file.
        if ($this->cookieFilePath && file_exists($this->cookieFilePath)) {
            @unlink($this->cookieFilePath);
        }
    }

    /**
     * Call the service
     *
     * @param       $method_url
     * @param array $data
     *
     * @return SwissPostResult
     */
    public function call($method, $data = [])
    {
        try {
            // Current auth is invalid ? try to auth.
            if ($this->needsToConnect()) {
                if (!$this->connect()) {
                    $debug = $this->debug[sizeof($this->debug) - 1];
                    $result_data = $this->results[sizeof($this->results) - 1];
                    return new SwissPostResult($result_data, $debug);
                }
            }
            // Add global params
            $data['session_id'] = $this->session_id;
            $data['shop_ident'] = $this->shopIdent;
            $result_data = $this->__callService($method, $data);
            // Return result object.
            $debug = $this->debug[sizeof($this->debug) - 1];
            $result = new SwissPostResult($result_data, $debug);
        } catch (\Exception $e) {
            // Output exception
            $this->logException($e);
            $result_data = [];
            $debug = $this->debug[sizeof($this->debug) - 1];
            $debug['exception'] = $e->getMessage();
            $result = new SwissPostResult($result_data, $debug);
        }
        // Sending email with the request/result to defined emails from config
        if ($this->emailHelper && $this->emailHelper->isLoggingEnabled()) {
            $this->emailHelper->send($result);
        }

        return $result;
    }


    /**
     * Check if is needed to reconnect.
     *
     * @return bool
     */
    public function needsToConnect()
    {
        // Check if the current session id, is same with cookie session id.
        if (!$this->session_id || !$this->md5 || !file_exists($this->cookieFilePath)
            || $this->md5 != md5(
                $this->session_id,
                file_get_contents($this->cookieFilePath)
            )
        ) {

            return true;
        }
        return false;
    }

    /**
     * Call Api service
     *
     * @param $method_url
     * @param $data
     *
     * @return array|mixed
     */
    private function __callService($method, $data)
    {
        $start = microtime(true);
        // Build base service data.
        $args = [
            "jsonrpc" => $this->jsonrpc,
            "id"      => "" . rand(0, 10000),
            "method"  => 'call',
            "params"  => $data,
        ];
        $url = rtrim($this->baseLocation, '/') . '/' . $method;
        $output = $this->curl($url, $args);
        $end = microtime(true);
        $this->debug(
            sprintf(
                __('SwissPost API call: %s, request: %s, response: %s, duration: %s'),
                $url, json_encode($args), $output, round($end - $start, 4)
            )
        );
        $result = [];
        if ($output) {
            $result = json_decode($output, true);
        }
        if (!is_array($result)) {
            $this->warning(
                sprintf(
                    __('Error getting json content from SwissPost API: %s'),
                    $output
                )
            );
        }
        return $result;

    }

    /**
     * Get curl resource
     *
     * @return curl resource.
     */
    private function getCurlResource()
    {
        if (!$this->curlResource) {
            $cookie = $this->cookieFilePath;
            $this->curlResource = curl_init();
            curl_setopt($this->curlResource, CURLOPT_POST, 1);
            curl_setopt($this->curlResource, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
            curl_setopt($this->curlResource, CURLOPT_TIMEOUT, $this->responseTimeout);
            curl_setopt($this->curlResource, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->curlResource, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($this->curlResource, CURLOPT_ENCODING, 1);
            curl_setopt($this->curlResource, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->curlResource, CURLOPT_COOKIESESSION, true);
            curl_setopt($this->curlResource, CURLOPT_COOKIEJAR, $cookie);
            curl_setopt($this->curlResource, CURLOPT_COOKIEFILE, $cookie);
        }

        return $this->curlResource;
    }

    /**
     * Curl service url
     *
     * @param       $url
     * @param array $args
     *
     * @return bool|mixed
     */
    public function curl($url, $args = [])
    {
        // init the debug.
        $debug = [
            'url'      => $url,
            'error_no' => -1,
        ];
        // Call.
        try {
            $data = json_encode($args);
            $this->curlResource = $this->getCurlResource();
            curl_setopt($this->curlResource, CURLOPT_URL, $url);
            curl_setopt($this->curlResource, CURLOPT_POSTFIELDS, $data);
            $headers = [
                'Content-type: application/json',
            ];
            curl_setopt($this->curlResource, CURLOPT_HTTPHEADER, $headers);
            $result = curl_exec($this->curlResource);
            $information = curl_getinfo($this->curlResource);

            $debug = [
                'url'    => $url,
                'data'   => print_r(json_decode($data, true), 1),
                'result' => $result,
            ];

            if ($result === false) {
                $debug['error_no'] = curl_errno($this->curlResource);
                throw new \Exception(
                    sprintf(
                        "cURL error, no %s, error: %s, info: %s",
                        curl_errno($this->curlResource),
                        curl_error($this->curlResource), json_encode($information)
                    )
                );
            }

            $this->debug[] = $debug;

            return $result;
        } catch (\Exception $e) {
            $debug['error_no'] = curl_errno($this->curlResource);
        }
        // attach debug.
        $this->debug[] = $debug;
        return false;
    }

    /**
     * Get result
     *
     * @param $result
     *
     * @return array
     */
    public function getResult($result)
    {
        return isset($result['result']) ? $result['result'] : [];
    }

    /**
     * Connect to API set cookie and
     *
     * @return bool
     */
    public function connect()
    {

        $result = $this->__callService(
            'web/session/get_session_info', [
            'session_id' => null,
            'context'    => new \StdClass(),
        ], 0
        );
        $this->results[] = $result;
        // Validate response.
        if (isset($result['error'])) {
            $this->debug(
                sprintf(
                    __('Error connecting to SwissPost API: %s'),
                    $result['error']
                )
            );
            return false;
        }
        $data = $this->getResult($result);
        if (isset($data['session_id'])) {
            $this->session_id = $data['session_id'];
        }
        $this->md5 = md5(
            $this->session_id,
            @file_get_contents($this->cookieFilePath)
        );
        // Call auth
        $result = $this->__callService(
            'web/session/authenticate', [
            'db'            => $this->db,
            'login'         => $this->login,
            'password'      => $this->password,
            'base_location' => $this->baseLocation,
            'session_id'    => $this->session_id,
        ],
            0
        );
        $this->results[] = $result;
        // Validate response.
        if (isset($result['error'])) {
            $this->debug(
                sprintf(
                    __('Error authenticate to SwissPost API: %s'),
                    $result['error']
                )
            );
            return false;
        }
        $data = $this->getResult($result);
        if ($data && isset($data['uid']) && $data['uid']) {
            return true;
        }

        return false;
    }

    /**
     * Extract any cookies found from the cookie file. This function expects to
     * get a string containing the contents of the cookie file which it will
     * then attempt to extract and return any cookies found within.
     *
     * @return array
     */
    private function extractCookies()
    {
        $string = @file_get_contents($this->cookieFilePath);
        $lines = explode(PHP_EOL, $string);
        $cookies = [];
        foreach ($lines as $line) {
            $cookie = [];
            // detect httponly cookies and remove #HttpOnly prefix
            if (substr($line, 0, 10) == '#HttpOnly_') {
                $line = substr($line, 10);
                $cookie['httponly'] = true;
            } else {
                $cookie['httponly'] = false;
            }
            // we only care for valid cookie def lines
            if (strlen($line) > 0 && $line[0] != '#'
                && substr_count(
                    $line,
                    "\t"
                ) == 6
            ) {
                // get tokens in an array
                $tokens = explode("\t", $line);
                // trim the tokens
                $tokens = array_map('trim', $tokens);
                // Extract the data
                // The domain that created AND can read the variable.
                $cookie['domain'] = $tokens[0];
                // A TRUE/FALSE value indicating if all machines within a given domain can access the variable.
                $cookie['flag'] = $tokens[1];
                // The path within the domain that the variable is valid for.
                $cookie['path'] = $tokens[2];
                // A TRUE/FALSE value indicating if a secure connection with the domain is needed to access the variable.
                $cookie['secure'] = $tokens[3];
                // The UNIX time that the variable will expire on.
                $cookie['expiration-epoch'] = $tokens[4];
                // The name of the variable.
                $cookie['name'] = urldecode($tokens[5]);
                // The value of the variable.
                $cookie['value'] = urldecode($tokens[6]);
                // Convert date to a readable format
                $cookie['expiration'] = date('Y-m-d h:i:s', $tokens[4]);
                // Record the cookie.
                $cookies[] = $cookie;
            }
        }
        return $cookies;
    }

    /**
     * @inheritdoc
     */
    public function getLastResult()
    {
        $result_data = $this->results[count($this->results) - 1];
        $debug = $this->debug[count($this->results) - 1];
        return new SwissPostResult($result_data, $debug);
    }

}

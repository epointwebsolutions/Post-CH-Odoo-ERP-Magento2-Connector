<?php

namespace Epoint\SwisspostApi\Helper\Api;

/**
 * Result wrapper
 *
 */
abstract class Result
{

    /**
     * Result of Client call
     *
     * @var array
     */
    private $result = array();

    /**
     * Error data.
     *
     * @var array
     */
    private $error = array();

    /**
     * Debug data.
     *
     * @var arrayD
     */
    protected $debug = array();

    /**
     * Flag if result is ok.
     *
     * @var bool
     */
    private $isOK = false;


    /**
     * Check if the result is timeout
     *
     * @return array
     */
    public abstract function isTimeout();

    /**
     * Constructor
     *
     * @param array $result
     * @param array $debug
     */
    public function __construct($result = array(), $debug = array())
    {
        // Default, the result is a failure.
        $this->isOK = false;
        if (isset($result['result'])) {
            $this->result = $result['result'];
            if ($this->result && is_array($this->result)) {
                if (!isset($this->result['status']) && is_array($this->result)) {
                    $this->isOK = true;
                }
                if (@strtolower($this->result['status']) == 'ok') {
                    $this->isOK = true;
                }
            }
        }
        // get error
        if (!$this->isOK) {
            if (isset($result['result']['comment'])) {
                $this->error[] = $result['result']['comment'];
            } else {
                $this->error[] = 'Invalid result data:' . json_encode($result);
            }
        }
        // Add debug message.
        $this->debug = $debug;
    }

    /**
     * Check if the call response is ok
     *
     * @return bool
     */
    public function isOK()
    {
        return $this->isOK;
    }

    /**
     * Get result
     *
     * @param string $field
     *
     * @return array
     */
    public function get($field = '')
    {
        if ($this->result) {
            if ($field) {
                if (isset($this->result[$field])) {
                    return $this->result[$field];
                }
            } else {
                return $this->result;
            }
        }

        return array();
    }

    /**
     * Get result error
     *
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Get result debug
     *
     * @return array
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * Get result debug as string
     *
     * @return string
     */
    public function getDebugMessage()
    {
        ob_start();
        print_r($this->debug);
        return ob_get_clean();
    }


    /**
     * Check if the result is timeout
     *
     * @return array
     */
    public function isValidAPIError()
    {
        if (isset($this->result['status']) && strtolower($this->result['status']) == 'error') {
            return true;
        }
        return false;
    }
}

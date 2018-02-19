<?php

namespace Epoint\SwisspostApi\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Mail\Template\TransportBuilder;

class Email extends Data
{
    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @const XML_PATH_DEBUG_EMAILS
     */
    const XML_PATH_DEBUG_EMAILS = 'logging/debug_logging_emails';

    /**
     * @const XML_PATH_ERROR_EMAILS
     */
    const XML_PATH_ERROR_EMAILS = 'logging/error_logging_emails';

    /**
     * Email constructor.
     *
     * @param Context                $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface  $storeManager
     * @param TransportBuilder       $transportBuilder
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder
    ) {
        parent::__construct($context, $objectManager, $storeManager);
        $this->transportBuilder = $transportBuilder;
    }

    /**
     * Check if the cron is enabled or not.
     *
     * @return bool
     */
    public function isLoggingEnabled()
    {
        return $this->getConfigValue(self::XML_PATH . 'logging/enable') ? true : false;
    }

    /**
     * Sending email with request/response data  to the define config emails
     *
     * @param Api\Curl\Result $result
     */
    public function send(\Epoint\SwisspostApi\Helper\Api\Curl\Result $result)
    {
        // Setting the default email subject
        $subject = __("Odoo API successful request.");
        // Because the debug logging level will receive all the messages
        // the starting list will include all the config defined emails
        $emailList = $this->getEmailFromConfig(self::XML_PATH_DEBUG_EMAILS);
        if (!$result->isOK()) {
            // Updating the email subject
            $subject = __("Odoo API request failed.");
            // Having a fail request we need to send emails to the defined email list, for error level as well
            $emailList = array_unique(array_merge($this->getEmailFromConfig(self::XML_PATH_ERROR_EMAILS), $emailList));
        }

        // Checking to see if logging is enabled
        if ($this->isLoggingEnabled() && count($emailList) > 0) {
            foreach ($emailList as $email) {
                // Setting the variables
                $emailTemplateVariables = [];
                $emailTemplateVariables['debug_message'] = $this->pretty_json($result->getDebugMessage());
                $emailTemplateVariables['result'] = $this->pretty_json(json_encode($result->get('values')));
                $emailTemplateVariables['subject'] = $subject;
                $postObject = new \Magento\Framework\DataObject();
                $postObject->setData($emailTemplateVariables);

                $storeId = $this->storeManager->getStore()->getId();
                $transport = $this->transportBuilder->setTemplateIdentifier('logging_email_template')
                    ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
                    ->setTemplateVars(
                        [
                            'data' => $postObject,
                        ]
                    )
                    ->setFrom('general')
                    // you can config general email address in Store -> Configuration -> General -> Store Email Addresses
                    ->addTo('' . $email, $this->storeManager->getStore()->getName() . " Store")
                    ->setReplyTo($email)
                    ->getTransport();
                $transport->sendMessage();
            }
        }
    }

    /**
     * @param string $json
     *   JSON string.
     *
     * @return string
     *   The formatted string.
     *
     * @see http://snipplr.com/view/60559/prettyjson
     */
    public function pretty_json($json)
    {
        $result = '';
        $pos = 0;
        $strLen = strlen($json);
        $indentStr = '  ';
        $newLine = "\n";
        $prevChar = '';
        $outOfQuotes = true;

        for ($i = 0; $i <= $strLen; $i++) {
            // Grab the next character in the string.
            $char = substr($json, $i, 1);
            // Are we inside a quoted string?
            if ($char === '"' && $prevChar !== '\\') {
                $outOfQuotes = !$outOfQuotes;
                // If this character is the end of an element,
                // output a new line and indent the next line.
            } else {
                if (($char === '}' || $char === ']') && $outOfQuotes) {
                    $result .= $newLine;
                    $pos--;
                    for ($j = 0; $j < $pos; $j++) {
                        $result .= $indentStr;
                    }
                }
            }
            // Add the character to the result string.
            $result .= $char;
            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char === ',' || $char === '{' || $char === '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char === '{' || $char === '[') {
                    $pos++;
                }
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
            $prevChar = $char;
        }
        return $result;
    }
}
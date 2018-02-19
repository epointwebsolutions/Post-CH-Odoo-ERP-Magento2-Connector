<?php

namespace Epoint\SwisspostApi\Helper;

use Magento\Store\Model\ScopeInterface;

trait ConfigurableTrait
{
    /**
     * @param      $field
     * @param null $storeId
     *
     * @return mixed
     */
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * @param      $field
     * @param null $storeId
     *
     * @return mixed
     */
    public function getDefaultConfigValue($field)
    {
        return $this->scopeConfig->getValue(
            $field, \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * @param      $code
     * @param null $storeId
     *
     * @return mixed
     */
    public function getGeneralConfig($path, $storeId = null)
    {
        return $this->getConfigValue($path, $storeId);
    }

    /**
     * Will provide the list of emails added in config for path
     *
     * @return array
     */
    public function getEmailFromConfig($path)
    {
        static $processed;
        if (isset($processed[$path])) {
            return $processed[$path];
        }
        // The config values
        $emailsAsString = $this->getConfigValue(self::XML_PATH . $path);
        // Formating text
        // Get all the emails as list
        $emailList = array_map('trim', explode(",", $emailsAsString));
        // Constructing the list of emails which will be used
        $validEmailList = [];
        foreach ($emailList as $email) {
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validEmailList[] = $email;
            }
        }
        $processed[$path] = $validEmailList;
        return $processed[$path];
    }
}


<?php

namespace Epoint\SwisspostApi\Helper;

class Transformer extends Data
{
    /**
     * Default data for customer.
     *
     * @const ACCOUNT_DEFAULT_MALE_ACCOUNT_TITLE
     */
    CONST ACCOUNT_DEFAULT_MALE_ACCOUNT_TITLE = 'MR';

    /**
     * Default data for customer.
     *
     * @const ACCOUNT_DEFAULT_FEMALE_ACCOUNT_TITLE
     */
    CONST ACCOUNT_DEFAULT_FEMALE_ACCOUNT_TITLE = 'MS';

    /**
     * Allowed titles.
     *
     * @const ACCOUNT_ALLOWED_TITLES
     */
    CONST ACCOUNT_ALLOWED_TITLES = 'MRS,MS,MR,COMPANY,PRACTICE,INSTITUTE,STUDIO,DR,PROF,PROFDR,PROFDR,BA,MBA,PHD';

    /**
     * Gender const male.
     *
     * @const CUSTOMER_GENDER_MALE
     */
    CONST CUSTOMER_GENDER_MALE = 1;

    /**
     * Gender const female.
     *
     * @const CUSTOMER_GENDER_FEMALE
     */
    CONST CUSTOMER_GENDER_FEMALE = 2;

    /**
     * Gender const undefined.
     *
     * @const CUSTOMER_GENDER_UNDEFINED
     */
    CONST CUSTOMER_GENDER_UNDEFINED = 3;

    /**
     * Gender const undefined.
     *
     * @const CUSTOMER_DEFAULT_LANGUAGE_CODE
     */
    CONST CUSTOMER_DEFAULT_LANGUAGE_CODE = 'de';

    /**
     * Gender const allowed.
     *
     * @const CUSTOMER_ALLOWED_LANGUAGE_CODE
     */
    CONST CUSTOMER_ALLOWED_LANGUAGE_CODE = ['de', 'en', 'it', 'fr'];

    /**
     * Gender const male.
     *
     * @const API_GENDER_MALE
     */
    CONST API_GENDER_MALE = 'male';

    /**
     * Gender const female.
     *
     * @const API_GENDER_FEMALE
     */
    CONST API_GENDER_FEMALE = 'female';

    /**
     * Gender const undefined.
     *
     * @const API_GENDER_UNDEFINED
     */
    CONST API_GENDER_UNDEFINED = 'other';

    /**
     * Default account address type
     *
     * @const API_DEFAULT_ACCOUNT_ADDRESS_TYPE
     */
    CONST API_DEFAULT_ACCOUNT_ADDRESS_TYPE = 'default';

    /**
     * Invoice account address type
     *
     * @const API_BILLING_ACCOUNT_ADDRESS_TYPE
     */
    CONST API_BILLING_ACCOUNT_ADDRESS_TYPE = 'invoice';

    /**
     * Shipping account address type
     *
     * @const API_SHIPPING_ACCOUNT_ADDRESS_TYPE
     */
    CONST API_SHIPPING_ACCOUNT_ADDRESS_TYPE = 'shipping';

    /**
     * Default account main tag
     *
     * @const API_DEFAULT_ACCOUNT_MAIN_TAG
     */
    CONST API_DEFAULT_ACCOUNT_MAIN_TAG = 'b2c';

    /**
     * Path for dynamic mapping
     *
     * @const PATH_TO_DYNAMIC_CUSTOMER_GROUPS_MAPPING
     */
    const PATH_TO_DYNAMIC_CUSTOMER_GROUPS_MAPPING = 'customer_groups_mapping/dynamic_customer_groups_mapping';

    /**
     * Convert customer title to API allowed titles.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     *
     * @return string
     */
    public function toAccountTitle($customer)
    {
        static $titles;
        // Getting the default account title depending on gender
        $addressTitle = $this->toDefaultCustomerTitleByGender($customer->getGender());
        // If the customer has set the name prefix -> trigger prefix validation and the new account title value
        if ($customer->getPrefix() && !empty($this->toAccountTitleByPrefix($customer->getPrefix()))) {
            $addressTitle = $this->toAccountTitleByPrefix($customer->getPrefix());
        }
        return $addressTitle;
    }

    /**
     * Will return the default account title by customer gender
     *
     * @param $gender
     *
     * @return string
     */
    public function toDefaultCustomerTitleByGender($gender)
    {
        if ($gender == self::CUSTOMER_GENDER_FEMALE) {
            return self::ACCOUNT_DEFAULT_FEMALE_ACCOUNT_TITLE;
        }
        if ($gender == self::CUSTOMER_GENDER_MALE) {
            return self::ACCOUNT_DEFAULT_MALE_ACCOUNT_TITLE;
        }
        return '';
    }

    /**
     * Will validate the name prefix provided.
     * If is valid we return the value as account title
     *
     * @param $prefix
     *
     * @return string
     */
    public function toAccountTitleByPrefix($prefix)
    {
        static $titles;
        // Default value
        $addressTitle = '';
        // Checking the prefix and updating the title value
        if (!empty($prefix)) {
            $addressTitle = strtoupper(
                preg_replace(
                    '/[^A-Za-z0-9\-]/', '',
                    $prefix
                )
            );
            if ($addressTitle) {
                if (!isset($titles)) {
                    $titles = array_map(
                        'strtoupper',
                        explode(",", self::ACCOUNT_ALLOWED_TITLES)
                    );
                }
                // Validate
                if (!in_array($addressTitle, $titles)) {
                    $addressTitle = '';
                }
            }
        }
        return $addressTitle;
    }

    /**
     * Store language, based on store id, lowercase, 2 letter format.
     *
     * @param int $storeId
     *
     * @return string
     */
    public function getStoreLanguage($storeId)
    {
        $lang = self::CUSTOMER_DEFAULT_LANGUAGE_CODE;
        if ($storeId) {
            $customerLang = $this->getStoresLanguageCode($storeId);
            if ($customerLang
                && in_array($customerLang, self::CUSTOMER_ALLOWED_LANGUAGE_CODE)
            ) {
                $lang = $customerLang;
            }
        }
        return $lang;
    }

    /**
     * Convert customer gender to API allowed genders
     *
     * @param  $customerGender
     *
     * @return  string
     */
    public function toGender($customerGender)
    {
        return $customerGender == self::CUSTOMER_GENDER_MALE
            ? self::API_GENDER_MALE
            :
            ($customerGender == self::CUSTOMER_GENDER_FEMALE
                ? self::API_GENDER_FEMALE : self::API_GENDER_UNDEFINED);
    }

    /**
     * Get default API account address type
     *
     * @return  string
     */
    public function toAccountAddressType()
    {
        return self::API_DEFAULT_ACCOUNT_ADDRESS_TYPE;
    }

    /**
     * Get billing API account address type
     *
     * @return  string
     */
    public function toBillingAccountAddressType()
    {
        return self::API_BILLING_ACCOUNT_ADDRESS_TYPE;
    }

    /**
     * Get shipping API account address type
     *
     * @return  string
     */
    public function toShippingAccountAddressType()
    {
        return self::API_SHIPPING_ACCOUNT_ADDRESS_TYPE;
    }

    /**
     * Get the odoo mapped value for selected customer group id
     *
     * @param $customerGroupId
     *
     * @return string
     */
    public function toAccountOdooCategory($customerGroupId)
    {
        // For processing data
        $keyToCheck = 'local_customer_group_code';
        $valueToCheck = $customerGroupId;
        $keyToReturn = 'odoo_customer_group_code';

        // Get the mapping from db
        $mappingFields = json_decode($this->getConfigValue('swisspostsales/' . self::PATH_TO_DYNAMIC_CUSTOMER_GROUPS_MAPPING), true);
        // Process data
        if (!empty($mappingFields) && is_array($mappingFields)) {
            foreach ($mappingFields as $mappingKey => $mappingValues) {
                if (isset($mappingValues[$keyToCheck]) && ($mappingValues[$keyToCheck] == $valueToCheck)) {
                    return $mappingValues[$keyToReturn];
                }
            }
        }
        return '';
    }

    /**
     * Get default API account main tag
     *
     * @return  string
     */
    public function toAccountMainTag()
    {
        return self::API_DEFAULT_ACCOUNT_MAIN_TAG;
    }
}

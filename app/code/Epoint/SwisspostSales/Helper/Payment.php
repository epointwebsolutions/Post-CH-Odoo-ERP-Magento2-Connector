<?php

namespace Epoint\SwisspostSales\Helper;

use Magento\Sales\Model\Order as LocalOrder;

class Payment extends Data
{
    /**
     * Path for dynamic mapping
     * @const PATH_TO_DYNAMIC_PAYMENT_MAPPING
     */
    const PATH_TO_DYNAMIC_PAYMENT_MAPPING = 'payments_mapping/dynamic_payments_mapping';

    /**
     * Path for default mapping
     * @const PATH_TO_DEFAULT_PAYMENT_MAPPING
     */
    const PATH_TO_DEFAULT_PAYMENT_MAPPING = 'payments_mapping/default_payments_mapping';

    /**
     * Key for identifying local code mapping
     * @const KEY_FOR_LOCAL_CODE
     */
    const KEY_FOR_LOCAL_CODE = 'local_payment_code';

    /**
     * Key for identifying default code mapping
     * @const KEY_FOR_ODOO_CODE
     */
    const KEY_FOR_ODOO_CODE = 'odoo_payment_code';

    /**
     * @param $external
     *
     * @return mixed|null
     */
    public function getLocalConfigCode($external)
    {
        return $this->getMappingConfiguration(self::KEY_FOR_ODOO_CODE, $external, self::KEY_FOR_LOCAL_CODE);
    }

    /**
     * @param $local
     *
     * @return mixed|null
     */
    public function getExternalPaymentCode($local)
    {
        // Getting the mapping value
        if (!empty($externalCode = $this->getMappingConfiguration(self::KEY_FOR_LOCAL_CODE, $local, self::KEY_FOR_ODOO_CODE))){
            return $externalCode;
        }
        // If we got here means no mapping has been found
        // So we return the define default method
        return $this->getConfigValue(self::XML_PATH . self::PATH_TO_DEFAULT_PAYMENT_MAPPING);
    }

    /**
     * @param $keyToCheck
     * @param $valueToCheck
     * @param $keyToReturn
     *
     * @return mixed|null
     */
    public function getMappingConfiguration($keyToCheck, $valueToCheck, $keyToReturn)
    {
        // Reading the mapping from db
        $mappingFields = json_decode($this->getConfigValue(self::XML_PATH . self::PATH_TO_DYNAMIC_PAYMENT_MAPPING), true);

        if (!empty($mappingFields) && is_array($mappingFields)){
            foreach ($mappingFields as $mappingKey => $mappingValues){
                if (!empty($mappingValues[$keyToCheck]) && ($mappingValues[$keyToCheck] == $valueToCheck)){
                    return $mappingValues[$keyToReturn];
                }
            }
        }
        return null;
    }

    /**
     * Check if the cron for import order payment status is enabled
     *
     * @return bool
     */
    public function isImportOrderPaymentStatusCronEnabled()
    {
        return $this->getConfigValue(
            self::XML_PATH . 'import_payment/import_payment_cron'
        ) ? true : false;
    }

    /**
     * Will return the defined order status used for payment import
     *
     * @return mixed
     */
    public function getConfigOrderStatusForImportOrderPayment()
    {
        $status = $this->getDefaultStatusForState(LocalOrder::STATE_NEW);
        if ($newStatus = $this->getConfigValue(self::XML_PATH . 'import_payment/import_payment_order_status')) {
            $status = $newStatus;
        }
        return strtolower($status);
    }
}

<?php

namespace Epoint\SwisspostSales\Helper;

use Magento\Sales\Model\Order as LocalOrder;

class Shipping extends Data
{
    /**
     * Path for dynamic mapping
     *
     * @const PATH_TO_DYNAMIC_SHIPPING_MAPPING
     */
    const PATH_TO_DYNAMIC_SHIPPING_MAPPING = 'shipping_mapping/dynamic_shipping_mapping';

    /**
     * Path for default mapping
     *
     * @const PATH_TO_DEFAULT_SHIPPING_MAPPING
     */
    const PATH_TO_DEFAULT_SHIPPING_MAPPING = 'shipping_mapping/default_shipping_mapping';

    /**
     * Key for identifying local code mapping
     *
     * @const KEY_FOR_LOCAL_CODE
     */
    const KEY_FOR_LOCAL_CODE = 'local_shipping_code';

    /**
     * Key for identifying default code mapping
     *
     * @const KEY_FOR_ODOO_CODE
     */
    const KEY_FOR_ODOO_CODE = 'odoo_shipping_code';

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
    public function getExternalShippingCode($local)
    {
        // Getting the mapping value
        if (!empty($externalCode = $this->getMappingConfiguration(self::KEY_FOR_LOCAL_CODE, $local, self::KEY_FOR_ODOO_CODE))) {
            return $externalCode;
        }
        // If we got here means no mapping has been found
        // So we return the define default method
        return $this->getConfigValue(self::XML_PATH . self::PATH_TO_DEFAULT_SHIPPING_MAPPING);
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
        // Get the mapping from db
        $mappingFields = json_decode($this->getConfigValue(self::XML_PATH . self::PATH_TO_DYNAMIC_SHIPPING_MAPPING), true);

        // Process data
        if (!empty($mappingFields) && is_array($mappingFields)) {
            foreach ($mappingFields as $mappingKey => $mappingValues) {
                if (!empty($mappingValues[$keyToCheck]) && ($mappingValues[$keyToCheck] == $valueToCheck)) {
                    return $mappingValues[$keyToReturn];
                }
            }
        }
        return null;
    }

    /**
     * Check if the cron for import order transfer status is enabled
     *
     * @return bool
     */
    public function isImportOrderTransferStatusCronEnabled()
    {
        return $this->getConfigValue(
            self::XML_PATH . 'import_transfer/import_transfer_cron'
        ) ? true : false;
    }

    /**
     * Will return the defined order status used for transfer import
     *
     * @return mixed
     */
    public function getConfigOrderStatusForImportOrderTransfer()
    {
        $status = $this->getDefaultStatusForState(LocalOrder::STATE_NEW);
        if ($newStatus = $this->getConfigValue(self::XML_PATH . 'import_transfer/import_transfer_order_status')) {
            $status = $newStatus;
        }
        return strtolower($status);
    }

    /**
     * Checking config settings for printing delivery report(s)
     *
     * @return bool
     */
    public function isPrintPdfEnabled()
    {
        return $this->getConfigValue(
            self::XML_PATH . 'order_shipping_docs/odoo_shipping_docs'
        ) ? true : false;
    }
}

<?php

namespace Epoint\SwisspostSales\Helper;

class ProductTax extends Data
{
    /**
     * Path for dynamic mapping
     * @const PATH_TO_DYNAMIC_TAX_CLASS_MAPPING
     */
    const PATH_TO_DYNAMIC_TAX_CLASS_MAPPING = 'product_tax_mapping/dynamic_product_tax_mapping';

    /**
     * Path for default mapping
     * @const PATH_TO_DEFAULT_TAX_CLASS_MAPPING
     */
    const PATH_TO_DEFAULT_TAX_CLASS_MAPPING = 'product_tax_mapping/default_product_tax_mapping';

    /**
     * Key for identifying local code mapping
     * @const KEY_FOR_LOCAL_CODE
     */
    const KEY_FOR_LOCAL_CODE = 'local_tax_class_code';

    /**
     * Key for identifying default code mapping
     * @const KEY_FOR_ODOO_CODE
     */
    const KEY_FOR_ODOO_CODE = 'odoo_tax_class_code';

    /**
     * @param $external
     *
     * @return mixed|null
     */
    public function getLocalConfigCode($external)
    {
        // Getting the mapping value
        if (!empty($localCode = $this->getMappingConfiguration(self::KEY_FOR_ODOO_CODE, $external, self::KEY_FOR_LOCAL_CODE))){
            return $localCode;
        }

        // If we got here means no mapping has been found
        // So we return the none class tax with id 0
        return 0;
    }

    /**
     * @param $local
     *
     * @return mixed|null
     */
    public function getExternalTaxClassCode($local)
    {
        // Getting the mapping value
        if (!empty($externalCode = $this->getMappingConfiguration(self::KEY_FOR_LOCAL_CODE, $local, self::KEY_FOR_ODOO_CODE))){
            return $externalCode;
        }
        // If we got here means no mapping has been found
        // So we return the define default method
        return $this->getConfigValue(self::XML_PATH . self::PATH_TO_DEFAULT_TAX_CLASS_MAPPING);
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
        $mappingFields = json_decode($this->getConfigValue(self::XML_PATH . self::PATH_TO_DYNAMIC_TAX_CLASS_MAPPING), true);

        // Process data
        if (!empty($mappingFields) && is_array($mappingFields)){
            foreach ($mappingFields as $mappingKey => $mappingValues){
                if (!empty($mappingValues[$keyToCheck]) && ($mappingValues[$keyToCheck] == $valueToCheck)){
                    return $mappingValues[$keyToReturn];
                }
            }
        }
        return null;
    }
}

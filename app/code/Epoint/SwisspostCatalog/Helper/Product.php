<?php

namespace Epoint\SwisspostCatalog\Helper;

use Epoint\SwisspostApi\Model\Api\Data\Translatable;

class Product extends Data
{
    /**
     * Product of type to be disabled
     *
     * @const DISABLED_PRODUCTS_BY_TYPE
     */
    const DISABLED_PRODUCTS_BY_TYPE = 'service,special_products';

    /**
     * Entity type
     *
     * @const PRODUCT_ENTITY_TYPE
     */
    const PRODUCT_ENTITY_TYPE = 'product';

    /**
     * Check if the cron is enabled or not.
     *
     * @return bool
     */
    public function isCronEnabled()
    {
        return $this->getConfigValue(self::XML_PATH . 'product/enable_import')
            ? true : false;
    }

    /**
     * Check if the cron is enabled or not.
     *
     * @return bool
     */
    public function isImageImportEnabled()
    {
        return $this->getConfigValue(
            self::XML_PATH . 'product/enable_import_images'
        ) ? true : false;
    }

    /**
     * Check if a product type is in the disabled list
     *
     * @param $productType
     *
     * @return bool
     */
    public function isProductTypeDisabled($productType)
    {
        // Getting the list of product types
        static $types;
        if (!isset($types)) {
            $types = array_map(
                'strtoupper',
                explode(",", self::DISABLED_PRODUCTS_BY_TYPE)
            );
        }

        if (!in_array(strtoupper($productType), $types)) {
            return false;
        }
        return true;
    }

    /**
     * Get product title from translation.
     *
     * @param Translatable $product
     * @param string       $languageCode
     * @param null         $storeId
     *
     * @return string
     */
    public function getTitle(Translatable $product, $languageCode = '',
        $storeId = null
    ) {
        if (!$languageCode) {
            $languageCode = $this->getConfigLanguageCode($storeId);
        }
        return $product->translate('title', $languageCode);
    }

    /**
     * Get product description_long from translation
     *
     * @param Translatable $product
     * @param string       $languageCode
     * @param null         $storeId
     *
     * @return string
     */
    public function getLongDescription(Translatable $product,
        $languageCode = '', $storeId = null
    ) {
        if (!$languageCode) {
            $languageCode = $this->getConfigLanguageCode($storeId);
        }
        return $product->translate('description_long', $languageCode);
    }

    /**
     * Get product description_short from translation
     *
     * @param Translatable $product
     * @param string       $languageCode
     * @param null         $storeId
     *
     * @return string
     */
    public function getShortDescription(Translatable $product,
        $languageCode = '', $storeId = null
    ) {
        if (!$languageCode) {
            $languageCode = $this->getConfigLanguageCode($storeId);
        }
        return $product->translate('description_short', $languageCode);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getConfigLanguageCode($storeId = null)
    {
        if (!$storeId) {
            return ($this->getDefaultConfigValue(
                self::XML_PATH . 'product/language'
            ));
        }
        return ($this->getConfigValue(
            self::XML_PATH . 'product/language', $storeId
        ));
    }

    /**
     * Mapping the externalAttributeCode with localAttributeCode
     *
     * @param $externalAttributeCode
     *
     * @return string
     */
    public function getLocalAttributeCode($externalAttributeCode)
    {
        if ($externalAttributeCode === 'title') {
            return 'name';
        }
        if ($externalAttributeCode === 'description_long') {
            return 'description';
        }
        if ($externalAttributeCode === 'description_short') {
            return 'short_description';
        }
        return '';
    }

    /**
     * Get product import limiter
     *
     * @return int|mixed
     */
    public function getProductImportLimit()
    {
        // Return the limit value if the value has been set
        if (!empty($importLimit = $this->getConfigValue(self::XML_PATH . 'product/import_limit'))){
            return $importLimit;
        }
        // Returning the default value
        return 0;
    }
}

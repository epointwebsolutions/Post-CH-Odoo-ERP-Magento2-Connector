<?php

namespace Epoint\SwisspostCatalog\Helper;

use Epoint\SwisspostApi\Model\Api\Category As ApiCategory;
use Epoint\SwisspostApi\Model\Api\Data\Translatable;

class Category extends Data
{
    /**
     * Entity type
     * @const CATEGORY_ENTITY_TYPE
     */
    const CATEGORY_ENTITY_TYPE = 'category';

    /**
     * Get a category parent
     *
     * @param ApiCategory $category
     *
     * @return \Magento\Catalog\Model\Category|null
     */
    public function getParent(ApiCategory $category)
    {
        $localCategory = $category->toLocal();
        if ($localCategory && $localCategory->getId()) {
            return $localCategory->getParentCategory();
        }

        return null;
    }

    /**
     * Get a category parent.
     *
     * @return null
     */
    public function getRootLocalCategory()
    {
        $rootCategoryId = $this->storeManager->getStore()->getRootCategoryId();
        if ($this->getConfigValue(self::XML_PATH . 'category/local_root')) {
            $rootCategoryId = $this->getConfigValue(self::XML_PATH . 'category/local_root');
        }
        if ($rootCategoryId) {
            $rootCategory = $this->objectManager->get(\Magento\Catalog\Model\Category::class)->load(
                $rootCategoryId
            );
            if (!$rootCategory || $rootCategory->getEntityId()) {
                $rootCategory = $this->objectManager->get(\Magento\Catalog\Model\Category::class)->load(
                    $this->storeManager->getStore()->getRootCategoryId()
                );
            }
            return $rootCategory;
        }
        return null;
    }

    /**
     * Build options list for select
     *
     * @param $categoryList
     * @param $externalId
     *
     * @return array
     */
    public function getFrom($categoryList, $externalId)
    {
        foreach ($categoryList as $category) {
            if ($category->getExternalId() == $externalId) {
                return $this->getChidren($categoryList, $category);
            }
        }
        return [];
    }

    /**
     * Build options list for select.
     *
     * @param             $categoryList
     * @param ApiCategory $category
     *
     * @return array
     */
    public function getChidren($categoryList, ApiCategory $category)
    {
        $children = [$category];
        $path = [$category->getExternalId()];
        foreach ($categoryList as $possibleChild) {
            if ($possibleChild->getExternalId() != $category->getExternalId() && !in_array($possibleChild->getExternalId(),
                    $path)) {
                $children[] = $possibleChild;
                $path[] = $possibleChild->getExternalId();
            }
        }
        return $children;
    }

    /**
     * Get a category parent.
     * @param Category $category
     *
     * @return mixed
     */
    public function getRootExternalCategory($categoryList)
    {
        if ($this->getConfigValue(self::XML_PATH . 'category/external_root')) {
            $list = $this->getFrom($categoryList,
                $this->getConfigValue(self::XML_PATH . 'category/external_root')
            );
            if ($list && isset($list[0])) {
                return $list[0];
            }
        }
        return null;
    }

    /**
     * Get category title from translation.
     *
     * @param Translatable $category
     * @param string       $languageCode
     * @param null         $storeId
     *
     * @return string
     */
    public function getTitle(Translatable $category, $languageCode = '',
        $storeId = null
    ) {
        if (!$languageCode) {
            $languageCode = $this->getConfigLanguageCode($storeId);
        }
        return $category->translate('title', $languageCode);
    }

    /**
     * Get category description from translation
     *
     * @param Translatable $category
     * @param string       $languageCode
     * @param null         $storeId
     *
     * @return string
     */
    public function getDescription(Translatable $category,
        $languageCode = '', $storeId = null
    ) {
        if (!$languageCode) {
            $languageCode = $this->getConfigLanguageCode($storeId);
        }
        return $category->translate('description', $languageCode);
    }

    /**
     * Check fi the cron is enabled or not.
     *
     * @return bool
     */
    public function isCronEnabled()
    {
        return $this->getConfigValue(self::XML_PATH . 'category/enable_import') ?  true:false;
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
                self::XML_PATH . 'category/language'
            ));
        }
        return ($this->getConfigValue(
            self::XML_PATH . 'category/language', $storeId
        ));
    }
}

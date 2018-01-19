<?php

namespace Epoint\SwisspostCatalog\Model\Config\Source\Category;


use Epoint\SwisspostCatalog\Helper\Data;

class Tree extends Data implements \Magento\Framework\Option\ArrayInterface
{

    /*
     * Return categories helper
     */

    public function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
        return $this->objectManager->get('\Magento\Catalog\Helper\Category')
            ->getStoreCategories($sorted , $asCollection, $toLoad);
    }

    /*
     * Option getter
     * @return array
     */
    public function toOptionArray()
    {
        $arr = $this->toArray();
        $ret = [];

        foreach ($arr as $key => $value)
        {
            $ret[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $ret;
    }

    /*
     * Get options in "key-value" format
     * @return array
     */
    public function toArray()
    {
        $catagoryList = [];
        $rootCategory = $this->objectManager->create('\Magento\Catalog\Model\Category')
            ->load($this->storeManager->getStore()->getRootCategoryId());
        $catagoryList[$rootCategory->getId()] = $rootCategory->getName();
        $categories = $this->getStoreCategories(true,false,true);
        foreach ($categories as $category){
            $prefix = str_repeat('--', $category->getLevel());
            $catagoryList[$category->getEntityId()] = $prefix . $category->getName();
        }

        return $catagoryList;
    }

}

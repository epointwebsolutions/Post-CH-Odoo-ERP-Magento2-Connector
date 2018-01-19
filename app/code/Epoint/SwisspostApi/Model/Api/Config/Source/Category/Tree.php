<?php

namespace Epoint\SwisspostApi\Model\Api\Config\Source\Category;

use Epoint\SwisspostApi\Model\Api\Category;
use Magento\Framework\ObjectManagerInterface;

class Tree implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Object manager.
     * @var \Magento\Framework\App\ObjectManager::getInstance()
     */
    protected $objectManager;

    /** Category list
     * @var list Epoint\SwisspostApi\Model\Api\Category as ApiCategory
     */
    static protected $_ApiCategoryList;

    /**
     * Account constructor.
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
        if(!isset(self::$_ApiCategoryList)){
            $categoryList = $this->objectManager->get('Epoint\SwisspostApi\Model\Api\Lists\Category');
            self::$_ApiCategoryList = $categoryList->search();
        }
    }

    /**
     * Build options list for select.
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach (self::$_ApiCategoryList as $category){
            $prefix = str_repeat('--', $category->getLevel());
            $options[] = [
                'value' => $category->getExternalId(),
                'label' => $prefix . $category->get('path'),
            ];
        }
        return $options;
    }


}

<?php

namespace Epoint\SwisspostApi\Model\Api\Lists;

use Epoint\SwisspostApi\Helper\Resource;
use Magento\Framework\ObjectManagerInterface;

abstract class ApiDataListObject
{
    /**
     * Class map.
     * @var string
     */
    protected $classMapp = '';
    /**
     * Object manager.
     * @var \Magento\Framework\App\ObjectManager::getInstance()
     */
    protected $objectManager;

    /**
     * The Api Resource
     * @var \Epoint\SwisspostApi\Helper\Resource
     */
    protected $apiResource;

    /**
     * Account constructor.
     */
    public function __construct(ObjectManagerInterface $objectManager, Resource $resource)
    {
        $this->objectManager = $objectManager;
        $this->apiResource = $resource;
    }

    /**
     * Convert api result item into object from \Epoint\SwisspostApi\Model\Api\*
     *
     * @param $result
     *   The api result
     * @return $items
     */
    protected function _loadFromResult($result){
        $items = [];
        if (is_object($result) && $result->isOk() && $result->get('values') ){
            foreach ($result->get('values') as $item){
                /** $var  \Epoint\SwisspostApi\Model\Api\* */
                $apiObject = $this->objectManager->create($this->classMapp);
                $apiObject->loadFromResultItem($item);
                $items[] = $apiObject;
            }
        }
        return $items;
    }

    /**
     * Search method.
     * @return  mixed
     */
    abstract protected function _search($filter = array());

    /**
     * The filter array.
     * @param $filter array
     *   The filter array.
     *
     * @return
     *   The categories lists.
     */
    public function search($filter = [])
    {
        $result =  $this->_search($filter);
        return $this->_loadFromResult($result);
    }
}

<?php

namespace Epoint\SwisspostApi\Model\Api\Lists;

class Product extends ApiDataListObject
{
    /**
     * List class map.
     * @var string
     */
    protected $classMapp = 'Epoint\SwisspostApi\Model\Api\Product';

    /**
     * @var $loaded
     * Will store the last requested list.
     */
    protected static $loaded;

    /**
     * The filter array.
     * @param $filter array
     *   The filter array.
     *
     * @return
     *   The products lists.
     */
    protected function _search($filter = [])
    {
        // Will return the list if no filter has provided
        if(!$filter && self::$loaded){
            return self::$loaded;
        }
        $result =  $this->apiResource->getProducts($filter);
        if(!$filter){
            self::$loaded = $result;
        }
        return $result;
    }
}

<?php

namespace Epoint\SwisspostApi\Model\Api\Lists;

class Category extends ApiDataListObject
{
    /**
     * List class map.
     * @var string
     */
    protected $classMapp = 'Epoint\SwisspostApi\Model\Api\Category';

    /**
     * Loaded list.
     * @var
     */
    protected static $loaded;

    /**
     * @inheritdoc
     */
    public function _search($filter = [])
    {
        // Cache full list.
        if(!$filter && self::$loaded){
            return self::$loaded;
        }
        $result = $this->apiResource->getProductCategories($filter);
        if(!$filter){
            self::$loaded = $result;
        }
        return $result;
    }
}

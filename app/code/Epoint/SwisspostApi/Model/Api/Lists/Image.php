<?php

namespace Epoint\SwisspostApi\Model\Api\Lists;

class Image extends ApiDataListObject
{
    /**
     * List class map.
     * @var string
     */
    protected $classMapp = 'Epoint\SwisspostApi\Model\Api\Image';

    /**
     * @inheritdoc
     */
    protected function _search($filter = [])
    {
        return $this->apiResource->getInventory($filter['product_ref']);
    }
}

<?php

namespace Epoint\SwisspostApi\Model\Api\Lists;

use Epoint\SwisspostApi\Helper\Resource;
use Magento\Framework\ObjectManagerInterface;
use Epoint\SwisspostApi\Model\Api\Lists\Product as ApiListProduct;

class Inventory extends ApiDataListObject
{
    /**
     * List class map.
     * @var string
     */
    protected $classMapp = 'Epoint\SwisspostApi\Model\Api\Inventory';

    /**
     * @var \Epoint\SwisspostApi\Model\Api\Lists\Product
     */
    protected $apiListProduct;

    public function __construct(
        ObjectManagerInterface $objectManager,
        Resource $resource,
        ApiListProduct $apiListProduct
    ) {
        parent::__construct($objectManager, $resource);
        $this->apiListProduct = $apiListProduct;
    }

    /**
     * @inheritdoc
     */
    protected function _search($filter = [])
    {
        // Checking to see if something comes along with filter
        if (count($filter) == 0 || $filter['product_codes'] === null || count($filter['product_codes']) == 0) {
            // Build a list with all products.
            $filterProducts = ['fields' => ['product_code']];
            $items = $this->apiListProduct->search($filterProducts);
            foreach ($items as $item){
                $filter['product_codes'][] = $item->get('product_code');
            }
        }

        return $this->apiResource->getInventory($filter);
    }
}

<?php

namespace Epoint\SwisspostApi\Model\Api;

use Epoint\SwisspostApi\Model\Api\Product as ApiModelProduct;
use Magento\Framework\Event\Manager;
use Magento\Framework\ObjectManagerInterface;
use Epoint\SwisspostApi\Helper\Resource;
use \Psr\Log\LoggerInterface;

class Inventory extends ApiDataObject
{
    /**
     * @var ApiModelProduct
     */
    protected $apiModelProduct;

    /**
     * Inventory constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param Resource               $resource
     * @param Manager                $eventManager
     * @param LoggerInterface        $logger
     * @param Product                $apiModelProduct
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Resource $resource,
        Manager $eventManager,
        LoggerInterface $logger,
        ApiModelProduct $apiModelProduct
    ) {
        parent::__construct($objectManager, $resource, $eventManager, $logger);
        $this->apiModelProduct = $apiModelProduct;
    }

    /**
     * @inheritdoc
     */
    public function getReferenceId($objectId = '')
    {
        if (!empty($objectId)) {
            return $objectId;
        }
    }

    /**
     * @inheritdoc
     */
    public function getInstance($product)
    {
        $apiObject = $this->objectManager->get(
            \Epoint\SwisspostApi\Model\Api\Inventory::class
        );
        $apiObject->set('product_code', $this->apiModelProduct->getReferenceId($product->getSKU()));

        return $apiObject;
    }

    /**
     * Get data for one product with sku
     *
     * @param $sku
     *
     * @return $this
     */
    public function load($sku)
    {
        $this->reset();
        // Constructing the filter
        $filter = ['product_codes' => []];
        if ($sku) {
            $filter = ['product_codes' => [$sku]];
        }
        $filter['limit'] = 1;

        // Getting data
        $result = $this->apiResource->getInventory($filter);

        // Store values
        if ($result->isOk() && $result->get('values')) {
            $item = current($result->get('values'));
            if ($item) {
                return $this->loadFromResultItem($item);
            }
        }
        return $this;
    }
}

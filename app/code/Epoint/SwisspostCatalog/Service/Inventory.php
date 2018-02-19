<?php

namespace Epoint\SwisspostCatalog\Service;

use Epoint\SwisspostApi\Helper\ConfigurableTrait;
use \Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Epoint\SwisspostApi\Service\BaseExchange;
use Psr\Log\LoggerInterface;
use Epoint\SwisspostCatalog\Helper\Inventory as InventoryHelper;
use Epoint\SwisspostApi\Model\Api\Lists\Inventory as InventoryApiList;
use Epoint\SwisspostCatalog\Model\Inventory as InventoryModel;

class Inventory extends BaseExchange
{
    /**
     * Configurable behavior.
     */
    use ConfigurableTrait;

    /**
     * @var \Epoint\SwisspostCatalog\Helper\Inventory
     */
    protected $inventoryHelper;

    /**
     * @var \Epoint\SwisspostApi\Model\Api\Lists\Inventory
     */
    protected $inventoryApiList;

    /**
     * @var \Epoint\SwisspostCatalog\Model\Inventory
     */
    protected $inventoryModel;

    /**
     * Inventory constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface        $logger
     * @param ScopeConfigInterface   $scopeConfig
     * @param InventoryHelper        $inventoryHelper
     * @param InventoryApiList       $inventoryApiList
     * @param InventoryModel         $inventoryModel
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        InventoryHelper $inventoryHelper,
        InventoryApiList $inventoryApiList,
        InventoryModel $inventoryModel
    ) {
        parent::__construct($objectManager, $logger, $scopeConfig);
        $this->inventoryHelper = $inventoryHelper;
        $this->inventoryApiList = $inventoryApiList;
        $this->inventoryModel = $inventoryModel;
    }

    /**
     * @inheritdoc
     */
    public function run($items = array())
    {
        /** @var \Epoint\SwisspostApi\Model\Api\Inventory $inventory */
        foreach ($items as $inventory) {
            /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
            $product = $this->inventoryHelper->getProduct($inventory);
            if (!$product || !$product->getId()) {
                throw new \Exception(
                    sprintf(
                        __('Missing product.'),
                        $inventory->get('product_code')
                    )
                );
            }

            // Updating product stock
            $this->inventoryModel->createUpdate($product, $inventory->get('qty_on_sale'), $inventory->get('sale_ok'));

            try {
                $product->save();
            } catch (\Exception $e) {
                $this->logException($e);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function listFactory()
    {
        return $this->inventoryApiList;
    }

    /**
     * @inheritdoc
     */
    public function helperFactory()
    {
        return $this->inventoryHelper;
    }
}

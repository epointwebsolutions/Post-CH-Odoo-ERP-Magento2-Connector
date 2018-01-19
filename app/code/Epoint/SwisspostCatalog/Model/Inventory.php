<?php

namespace Epoint\SwisspostCatalog\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use \Psr\Log\LoggerInterface;
use Epoint\SwisspostApi\Helper\LoggerTrait;

class Inventory
{
    /**
     * Logger
     */
    use LoggerTrait;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * Inventory constructor.
     *
     * @param StockRegistryInterface $stockRegistry
     * @param LoggerInterface        $logger
     */
    public function __construct(
        StockRegistryInterface $stockRegistry,
        LoggerInterface $logger
    ){
        $this->stockRegistry = $stockRegistry;
        $this->logger = $logger;
    }

    /**
     * Preparing stock data
     *
     * @param $qtyOnSale
     * @param $saleOK
     *
     * @return array
     */
    private function prepare($qtyOnSale, $saleOK)
    {
        return [
            'is_in_stock' => $qtyOnSale > 0 && $saleOK ? 1 : 0,
            'qty'                         => MAX(
                0, $qtyOnSale
            ),
            'manage_stock'                => 1,
            'use_config_notify_stock_qty' => 1,
            'min_sale_qty'                => 1,
            'max_sale_qty'                => 1,
        ];
    }

    /**
     * Create/Update product stock
     *
     * @param ProductInterface $product
     * @param                  $qtyOnSale
     * @param                  $saleOK
     *
     * @return ProductInterface
     */
    public function createUpdate(ProductInterface $product, $qtyOnSale, $saleOK)
    {
        // Preparing data
        $data = $this->prepare($qtyOnSale, $saleOK);
        // Get the product stock object
        /** @var \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem */
        $stockItem = $this->stockRegistry->getStockItem($product->getId());
        try {
            foreach ($data as $property=>$value){
                $stockItem->setData($property, $value);
            }
            $product->setQuantityAndStockStatus(['qty' => $data['qty'], 'is_in_stock' => $data['is_in_stock']]);
            $stockItem->save();
        } catch (\Exception $e) {
            $this->logException($e);
        }
        return $product;
    }
}

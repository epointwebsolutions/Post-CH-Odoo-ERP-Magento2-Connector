<?php

namespace Epoint\SwisspostCatalog\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Epoint\SwisspostApi\Model\Api\Inventory As ApiInventory;

class Inventory extends Data
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Inventory constructor.
     *
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context, $objectManager, $storeManager);
        $this->productRepository = $productRepository;
    }

    /**
     * Check if the cron is enabled or not.
     *
     * @return bool
     */
    public function isCronEnabled()
    {
        return $this->getConfigValue(self::XML_PATH . 'inventory/enable_import') ?  true:false;
    }

    /**
     * Get product  by sku
     *
     * @param ApiInventory $inventory
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function getProduct(ApiInventory $inventory)
    {
        $product = $this->productRepository->get(
            $inventory->get('product_code')
        );
        return $product;
    }
}

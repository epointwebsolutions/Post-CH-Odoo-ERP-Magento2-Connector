<?php

namespace Epoint\SwisspostCatalog\Service;

use Epoint\SwisspostApi\Helper\ConfigurableTrait;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Epoint\SwisspostApi\Model\Api\Product as ApiProduct;
use Epoint\SwisspostApi\Service\CronRunner;
use Epoint\SwisspostCatalog\Service\Media as MediaService;
use Epoint\SwisspostApi\Service\BaseExchange;
use Magento\Catalog\Api\Data\ProductInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product\Action;
use Epoint\SwisspostCatalog\Helper\Product as ProductHelper;
use Epoint\SwisspostApi\Model\Api\Lists\Product as ApiListProduct;
use Epoint\SwisspostCatalog\Model\Inventory as InventoryModel;
use Epoint\SwisspostSales\Helper\ProductTax as ProductTaxHelper;

class Product extends BaseExchange
{
    /**
     * Configurable behavior.
     */
    use ConfigurableTrait;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * Product Factory
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product $productResource
     */
    protected $productResource;

    /**
     * Media import service.
     *
     * @var \Epoint\SwisspostCatalog\Service\Media $mediaService
     */
    protected $mediaService;

    /**
     * @var \Magento\Catalog\Model\Product\Action $action
     */
    protected $action;

    /**
     * @var \Epoint\SwisspostCatalog\Helper\Product $productHelper
     */
    protected $productHelper;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface
     */
    protected $productRepositoryInterface;

    /**
     * @var \Epoint\SwisspostApi\Model\Api\Lists\Product $apiListProduct
     */
    protected $apiListProduct;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var \Epoint\SwisspostCatalog\Model\Inventory
     */
    protected  $inventoryModel;

    /**
     * @var \Epoint\SwisspostSales\Helper\ProductTax
     */
    protected $productTaxHelper;

    /**
     * Product enabled and visible on catalog and search
     *
     * @const VISIBILITY_TYPE_ON_SALE
     */
    const VISIBILITY_TYPE_ON_SALE = 'on_sale';

    /**
     * Product enabled and visible on catalog and search
     *
     * @const VISIBILITY_TYPE_VISIBLE
     */
    const VISIBILITY_TYPE_VISIBLE = 'visible';

    /**
     * Product disabled and not visible
     *
     * @const VISIBILITY_TYPE_NOT_VISIBLE
     */
    const VISIBILITY_TYPE_NOT_VISIBLE = 'not_visible';

    /**
     * Product disabled and not visible
     *
     * @const VISIBILITY_TYPE_NOT_VISIBLE_CONDITIONAL
     */
    const VISIBILITY_TYPE_NOT_VISIBLE_CONDITIONAL = 'not_visible_conditional';

    /**
     * Define the default product attribute set id
     *
     * @const DEFAULT_ATTRIBUTE_SET_ID
     */
    const DEFAULT_ATTRIBUTE_SET_ID = 4;

    /**
     * Define the default product attribute set name
     *
     * @const DEFAULT_ATTRIBUTE_SET_NAME
     */
    const DEFAULT_ATTRIBUTE_SET_NAME = 'Default';

    /**
     * Product constructor.
     *
     * @param ObjectManagerInterface       $objectManager
     * @param StoreManagerInterface        $storeManager
     * @param ProductResource              $productResource
     * @param Media                        $mediaService
     * @param \Magento\Framework\App\State $state
     * @param LoggerInterface              $logger
     * @param Action                       $action
     * @param ProductHelper                $productHelper
     * @param ProductRepositoryInterface   $productRepositoryInterface
     * @param ScopeConfigInterface         $scopeConfig
     * @param ApiListProduct               $apiListProduct
     * @param InventoryModel               $inventoryModel
     * @param ProductTaxHelper             $productTaxHelper
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        ProductResource $productResource,
        MediaService $mediaService,
        \Magento\Framework\App\State $state,
        LoggerInterface $logger,
        Action $action,
        ProductHelper $productHelper,
        ProductRepositoryInterface $productRepositoryInterface,
        ScopeConfigInterface $scopeConfig,
        ApiListProduct $apiListProduct,
        InventoryModel $inventoryModel,
        ProductTaxHelper $productTaxHelper
    ) {
        parent::__construct($objectManager, $logger, $scopeConfig);
        $this->storeManager = $storeManager;
        $this->productResource = $productResource;
        $this->mediaService = $mediaService;
        $this->action = $action;
        $this->productHelper = $productHelper;
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->apiListProduct = $apiListProduct;
        $this->inventoryModel = $inventoryModel;
        $this->productTaxHelper = $productTaxHelper;
    }

    /**
     * @param ApiProduct $apiProduct
     *
     * @return array
     * @throws \Exception
     */
    public function prepare(ApiProduct $apiProduct)
    {
        if (!$apiProduct->get('product_code')) {
            throw new \Exception(__('Missing product SKU on import.'));
        }
        if (!$apiProduct->get('title')) {
            throw new \Exception(__('Missing product name on import.'));
        }
        // Setting up the product categories
        $categoryIdsList = [];
        if ($apiProduct->get('category_ids')) {
            $categoryIdsList = $apiProduct->get('category_ids');
        }
        if ($apiProduct->get('category_id')
            && !in_array($apiProduct->get('category_id'), $categoryIdsList)
        ) {
            $categoryIdsList[] = $apiProduct->get('category_id');
        }

        // Getting the product category local_id
        $localCategoryList = [];
        foreach ($categoryIdsList as $categoryID) {
            $apiCategory
                = $this->objectManager->get(
                \Epoint\SwisspostApi\Model\Api\Category::class
            );
            $category = $apiCategory->toLocalByExternalId($categoryID);
            if ($category && $category->getId()) {
                $localCategoryList[] = $category->getId();
            }
        }

        // Define visibility on store and product status
        list($visibility, $status) = $this->getVisibilityAndStatus($apiProduct);

        // Constructing the options container
        $productOptions = [
            "sku"                  => $apiProduct->get('product_code'),
            "name"                 => $this->productHelper->getTitle($apiProduct),
            "description"          => $this->productHelper->getLongDescription(
                $apiProduct
            ),
            "short_description"    => $this->productHelper->getShortDescription(
                $apiProduct
            ),
            "status"               => $status,
            "type_id"              => 'simple',
            "price"                => $apiProduct->get('list_price'),
            "weight"               => $apiProduct->get('weight'),
            "visibility"           => $visibility,
            "diameter"             => $apiProduct->get('diameter'),
            "length"               => $apiProduct->get('length'),
            "ean13"                => $apiProduct->get('ean13'),
            "width"                => $apiProduct->get('width'),
            "height"               => $apiProduct->get('height'),
            "uom_name"             => $apiProduct->get('uom_name'),
            "volume"               => $apiProduct->get('volume'),
            "weight_net"           => $apiProduct->get('weight_net'),
            "manufacturer_website" => $apiProduct->get('manufacturer_website'),
            "sale_delay"           => $apiProduct->get('sale_delay'),
            "odoo_id"              => $apiProduct->get('odoo_id'),
            'category_ids'         => $localCategoryList,
            'api_entity'           => $apiProduct
        ];
        return $productOptions;
    }

    /**
     * * State that should be used by the webshop. Can be one of:
     * 1. on_sale: product can be seen on the webshop and is sellable
     * 2. visible: product should be seen, but not added to the basket or sold
     * 3. not_visible: product should not be visible
     * 4. not_visible_conditional: product can be seen in some conditions.
     * this can be used for example for phase-out, in which case it is
     * visible until there is stock, then it disappears
     *
     * @param ApiProduct $product
     *
     * @return array
     */
    public function getVisibilityAndStatus(ApiProduct $product)
    {
        $visibility = Visibility::VISIBILITY_BOTH;
        $status = Status::STATUS_ENABLED;
        if ($product) {
            // Check if the product is not active
            if ((int)$product->get('active') == 0) {
                $visibility = Visibility::VISIBILITY_NOT_VISIBLE;
                $status = Status::STATUS_DISABLED;
            }

            // Check webshop state.
            switch ($product->get('webshop_state')) {
                case self::VISIBILITY_TYPE_ON_SALE:
                    break;
                case self::VISIBILITY_TYPE_VISIBLE:
                    break;
                case self::VISIBILITY_TYPE_NOT_VISIBLE:
                    $status = Status::STATUS_DISABLED;
                    $visibility = Visibility::VISIBILITY_NOT_VISIBLE;
                    break;
                case self::VISIBILITY_TYPE_NOT_VISIBLE_CONDITIONAL:
                    $status = Status::STATUS_DISABLED;
                    $visibility = Visibility::VISIBILITY_NOT_VISIBLE;
                    break;
                default:
                    $status = Status::STATUS_DISABLED;
                    $visibility = Visibility::VISIBILITY_NOT_VISIBLE;
                    break;
            }

            // Checking to see if the product type is present into the disable types
            if ($this->productHelper->isProductTypeDisabled($product->get('type'))) {
                $visibility = Visibility::VISIBILITY_NOT_VISIBLE;
                $status = Status::STATUS_DISABLED;
            }
        }
        return [$visibility, $status];
    }

    /**
     * Updating product stock
     * @param ProductInterface $product
     * @param                  $apiProducts
     */
    public function setupInventory(ProductInterface $product, $apiProducts)
    {
        // Updating product stock
        $this->inventoryModel->createUpdate($product, $apiProducts->get('qty_on_sale'), $apiProducts->get('sale_ok'));
    }

    /**
     * @param ApiProduct $product
     * @param array      $data
     *
     * @return bool|\Magento\Catalog\Model\AbstractModel|mixed
     */
    public function createUpdate($apiProduct, $data = [])
    {
        /** @var  \Epoint\SwisspostApi\Model\Api\Product $entity */
        $entity = null;
        if (isset($data['api_entity'])) {
            $entity = $data['api_entity'];
            unset($data['api_entity']);
        }

        $media = null;
        if (isset($data['media']) && is_array($data['media'])) {
            $media = $data['media'];
            unset($data['media']);
        }

        $product
            = $this->objectManager->create(
            \Magento\Catalog\Model\Product::class
        );
        if ($product->getIdBySku($data['sku'])) {
            $product = $this->productRepositoryInterface->get($data['sku']);
        }

        // Default values.
        $catalogConfig
            = $this->objectManager->create(
            \Magento\Catalog\Model\Config::class
        );

        $attributeSetId = $catalogConfig->getAttributeSetId(
            self::DEFAULT_ATTRIBUTE_SET_ID,
            self::DEFAULT_ATTRIBUTE_SET_NAME
        );

        // Set websites
        static $websitesIds;
        if (!isset($websitesIds)) {
            foreach($this->storeManager->getWebsites() as $website) {
                $websitesIds[] = $website->getId();
            }
        }
        $product->setWebsiteIds($websitesIds);

        // Set attribute set id
        $product->setAttributeSetId($attributeSetId);

        // Getting the local class id depending on tax_api_codes and mapping configuration
        $odooTaxCodes = $apiProduct->get('tax_api_codes');
        $localClassTaxId = $this->productTaxHelper->getLocalConfigCode(reset($odooTaxCodes));
        $product->setTaxClassId($localClassTaxId);

        // Adding the product option
        $product->addData($data);
        $product->save();

        // Updating product stock
        $this->setupInventory($product, $apiProduct);

        try {
            $this->productRepositoryInterface->save($product);
            /** @var \Epoint\SwisspostCatalog\Helper\Product */
            if ($entity && $this->productHelper->isImageImportEnabled()) {
                $this->mediaService->execute($entity, $product);
            }

            // Update product name/description/short description for each store (language dependency)
            $storeList = $this->storeManager->getStores();
            // Updating product attributes for each store
            foreach ($storeList as $store) {
                $this->setProductAttributesForStore(
                    $apiProduct, $product, $store->getId()
                );
            }
            // Delete images.
            if ($entity && $product && $product->getId()) {
                $entity->connect($product->getId());
            }
        } catch (\Exception $e) {
            $this->logException($e);
        }
        return $product;
    }

    /**
     * Update product translatable attributes for store
     *
     * @param $apiProduct
     * @param $product
     * @param $storeID
     */
    public function setProductAttributesForStore(
        $apiProduct, $product, $storeID
    ) {
        // Getting the currentStoreProduct
        $currentStoreProduct = $this->productRepositoryInterface->getById($product->getId(), false, $storeID);

        // Getting the list of the translatable attributes sort by language
        $externalAttributesList = $apiProduct->get('languages');
        // To extract the needed data we need to know what language is set on store
        $fromConfigLanguageCode = $this->productHelper->getConfigLanguageCode(
            $storeID
        );
        // Getting the product attribute list mapped for current store configuration value
        $productExternalAttributeList = [];
        foreach ($externalAttributesList as $languageCode => $attributeList) {
            if (strtolower($languageCode) == strtolower(
                    $fromConfigLanguageCode
                )
            ) {
                $productExternalAttributeList = $attributeList;
            }
        }
        // Constructing the product local attribute list for current store
        $productLocalAttributeList = [];
        foreach (
            $productExternalAttributeList as $externalAttributeCode =>
            $externalAttributeValue
        ) {
            $localAttributeCode = $this->productHelper->getLocalAttributeCode(
                $externalAttributeCode
            );
            if ($localAttributeCode) {
                $value = $apiProduct->translate(
                    $externalAttributeCode, $fromConfigLanguageCode
                );
                // Setting product on store with id
                // Compare and update if needed
                if ($currentStoreProduct->getData($localAttributeCode) != $value) {
                    $productLocalAttributeList[$localAttributeCode] = $value;
                }
            }
        }
        // Update product for storeId
        if ($productLocalAttributeList) {
            $this->action->updateAttributes(
                [$currentStoreProduct->getId()], $productLocalAttributeList, $storeID
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function run($items = array())
    {
        $this->storeManager->setCurrentStore('admin');
        $products = [];
        foreach ($items as $_item) {
            $data = $this->prepare($_item);
            $products[] = $this->createUpdate($_item, $data);
        }
        return $products;
    }

    /**
     * @inheritdoc
     */
    public function listFactory()
    {
        return $this->apiListProduct;
    }

    /**
     * @inheritdoc
     */
    public function helperFactory()
    {
        return $this->productHelper;
    }

    /**
     * Execute export.
     */
    public function execute()
    {
        /** @var \Epoint\SwisspostCatalog\Helper\Data $helper */
        if($this->isEnabled()) {
            /** @var \Epoint\SwisspostApi\Model\Api\Lists $itemList */
            $listFactory = $this->listFactory();
            // Before we start trigger the import action,
            // we must check if an import limit value has been setup
            $limitImport = $this->productHelper->getProductImportLimit();
            $filter = [];
            // If the limiter has any other value beside the default one (0)
            // we add it to the filter
            if ($limitImport > 0){
                $filter['limit'] = (int)$limitImport;
            }
            /** @var \Epoint\SwisspostApi\Model\Api\ApiDataObject $items */
            $items = $listFactory->search($filter);
            // Run import.
            $this->run($items);
        }
    }
}

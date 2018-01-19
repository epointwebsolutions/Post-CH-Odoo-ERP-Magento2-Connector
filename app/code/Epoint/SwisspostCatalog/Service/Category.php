<?php

namespace Epoint\SwisspostCatalog\Service;

use Epoint\SwisspostApi\Helper\ConfigurableTrait;
use Epoint\SwisspostApi\Model\Api\Category as ApiCategory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\ObjectManagerInterface;
use \Magento\Catalog\Api\CategoryRepositoryInterface;
use Epoint\SwisspostApi\Model\ResourceModel\Entity;
use Epoint\SwisspostApi\Service\BaseExchange;
use Psr\Log\LoggerInterface;
use \Magento\Catalog\Model\CategoryFactory;
use Epoint\SwisspostCatalog\Helper\Category as CategoryHelper;
use Epoint\SwisspostApi\Model\Api\Lists\Category as ApiListCategory;

class Category extends BaseExchange
{
    /**
     * Configurable behavior.
     */
    use ConfigurableTrait;

    /**
     * The Api Resource
     *
     * @var Resource
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepositoryInterface
     */
    protected $categoryRepositoryInterface;

    /**
     * @var \Magento\Catalog\Model\CategoryRepository $categoryRepository
     */
    protected $categoryRepository;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory $categoryModelFactory
     */
    protected $categoryModelFactory;

    /**
     * @var \Epoint\SwisspostCatalog\Helper\Category $categoryHelper
     */
    protected $categoryHelper;

    /**
     * @var ApiListCategory
     */
    protected $apiListCategory;

    /**
     * Category constructor.
     *
     * @param StoreManagerInterface       $storeManager
     * @param ObjectManagerInterface      $objectManager
     * @param LoggerInterface             $logger
     * @param CategoryRepositoryInterface $categoryRepositoryInterface
     * @param CategoryFactory             $categoryModelFactory
     * @param CategoryHelper              $categoryHelper
     * @param ApiListCategory             $apiListCategory
     * @param ScopeConfigInterface        $scopeConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
        CategoryRepositoryInterface $categoryRepositoryInterface,
        CategoryFactory $categoryModelFactory,
        CategoryHelper $categoryHelper,
        ApiListCategory $apiListCategory,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($objectManager, $logger, $scopeConfig);
        $this->storeManager = $storeManager;
        $this->categoryRepositoryInterface = $categoryRepositoryInterface;
        $this->categoryModelFactory = $categoryModelFactory;
        $this->categoryHelper = $categoryHelper;
        $this->apiListCategory = $apiListCategory;
    }

    /**
     * Preparing category data
     *
     * @param ApiCategory                          $category
     * @param \Magento\Catalog\Model\Category|null $localCategory
     * @param \Magento\Catalog\Model\Category|null $parenCategory
     *
     * @return array
     */
    public function prepare(
        ApiCategory $category,
        \Magento\Catalog\Model\Category $localCategory = null,
        \Magento\Catalog\Model\Category $parenCategory = null
    ) {
        $categoryTmp = $this->categoryModelFactory->create();
        $isNew = true;
        if ($localCategory === null) {
            $localCategory = $category->toLocal();
        }
        if ($localCategory && $localCategory->getId()) {
            $isNew = false;
            $categoryTmp = $localCategory;
        }
        if ($parenCategory === null && !$isNew) {
            $parenCategory = $localCategory->getParentCategory();
        }
        // Load import language,
        $name = ucfirst($this->categoryHelper->getTitle($category));
        $url = strtolower($name);
        $cleanurl = trim(preg_replace('/ +/', '',
                    preg_replace('/[^A-Za-z0-9 ]/',
                        '',
                        urldecode(html_entity_decode(strip_tags($url)))))
            ) . '-' . $category->getExternalId() . '-' . rand(1, 100000);
        $categoryTmp->setName($name);
        // This will not be saved.
        $categoryTmp->setExternalId($category->getExternalId());

        $data = [
            'data'              => [
                "parent_id"       => $parenCategory->getId(),
                'name'            => $name,
                "is_active"       => true,
                "position"        => 1,
                "url_key"         => $cleanurl,
                "include_in_menu" => true,
                "store_id"        => $parenCategory->getStoreId(),
            ]
            ,
            'custom_attributes' => [
                "display_mode"            => "PRODUCTS",
                "is_anchor"               => "1",
                "description"             =>  $this->categoryHelper->getDescription($category),
                Entity::OBSERVER_VARIABLE => $category,
            ]
        ];
        if ($localCategory && $localCategory->getId()) {
            $data['data']['id'] = $localCategory->getId();
            unset($data['data']['url_key']);
        }
        return $data;
    }

    /**
     * Create update category
     *
     * @param array $data
     *
     * @return mixed
     */
    public function createUpdate($data = array())
    {
        /** @var  \Epoint\SwisspostApi\Model\Api\Entity $entity */
        $entity = null;
        if (isset($data['custom_attributes'][Entity::OBSERVER_VARIABLE])) {
            $entity = $data['custom_attributes'][Entity::OBSERVER_VARIABLE];
            unset($data['custom_attributes'][Entity::OBSERVER_VARIABLE]);
        }
        $category = $this->objectManager
            ->create(\Magento\Catalog\Model\Category::class, $data)
            ->setCustomAttributes($data['custom_attributes']);
        try {
            $this->categoryRepositoryInterface->save($category);
            if ($entity && $category && $category->getId()) {
                $entity->connect($category->getId());
            }
            // Updating the translatable attributes for existing stores
            $this->setCategoryAttributesForStore($entity, $category);
        } catch (\Exception $e) {
            $this->logException($e);
        }
        return $category;
    }

    /**
     * Update category translatable attributes for configured store store
     *
     * @param $apiCategory
     * @param \Magento\Catalog\Model\Category $category
     */
    public function setCategoryAttributesForStore(
        $apiCategory, $category
    ) {
        // Getting the store list
        $stores = $this->storeManager->getStores();
        // Updating each store
        foreach ($stores as $store) {
            // Get the category for selected store
            $selectedStoreCategory = $category->setStoreId($store->getId())->load($category->getCategoryId());
            // Get language code for selected store
            $languageCode = $this->categoryHelper->getConfigLanguageCode($store->getId());
            // Get category title for selected store language
            $categoryStoreTitle = $this->categoryHelper->getTitle($apiCategory, $languageCode, $store->getId());
            // Updating attributes for selected store
            $selectedStoreCategory->setData('name', $categoryStoreTitle);
            $selectedStoreCategory->setData('description', $this->categoryHelper->getDescription($apiCategory, $languageCode, $store->getId()));
            $selectedStoreCategory->setData('store_id', $store->getId());
            $selectedStoreCategory->save();
        }
    }

    /**
     * @inheritdoc
     */
    public function run($items = array())
    {
        $externRootCategory = $this->categoryHelper->getRootExternalCategory($items);
        if (!$externRootCategory || !$externRootCategory->getExternalId()) {
            throw new \Exception(__('Missing import extern root category, please configure it.'));
        }
        $internRootCategory = $this->categoryHelper->getRootLocalCategory();
        if (!$internRootCategory || !$internRootCategory->getId()) {
            throw new \Exception(__('Missing import local root category, please configure it.'));
        }

        $this->storeManager->setCurrentStore('admin');

        // Save top import category.
        $imported = [];
        $importCategories = $this->categoryHelper->getChidren($items,
            $externRootCategory);
        foreach ($importCategories as $item) {
            if ($item->getExternalId()
                == $externRootCategory->getExternalId()
            ) {
                $data = $this->prepare($item, $internRootCategory);
                $category = $this->createUpdate($data);
                $imported[(string)$item->getExternalId()] = $category;
                print 'Imported:' . $item->getExternalId() . ' - '
                    . $category->getId();
                break;
            }
        }
        $doImport = true;
        $rounds = 1;
        $entriesCount = count($importCategories);
        // Do import.
        while ($doImport) {
            foreach ($importCategories as $item) {
                // If the parent has been imported.
                $parentId = (string)$item->get('parent_id');
                $externalId = (string)$item->getExternalId();
                if (array_key_exists($parentId, $imported)
                    && !array_key_exists($externalId, $imported)
                ) {
                    try {
                        //It was not imported at all.
                        $parent = $imported[$parentId];
                        $localCategory = $item->toLocal();
                        // Prepare item.
                        $data = $this->prepare($item, $localCategory, $parent);
                        $category = $this->createUpdate($data);
                    } catch (\Exception $e) {
                        $this->logException($e);
                    }
                    // Add entity on saved items.
                    $imported[$externalId] = $category;
                }
            }
            // max deep is count of items
            if (count($imported) >= $entriesCount) {
                $doImport = false;
            }
            $rounds++;
            if ($doImport && $rounds >= $entriesCount) {
                $doImport = false;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function listFactory()
    {
        return $this->apiListCategory;
    }

    /**
     * @inheritdoc
     */
    public function helperFactory()
    {
        return $this->categoryHelper;
    }
}

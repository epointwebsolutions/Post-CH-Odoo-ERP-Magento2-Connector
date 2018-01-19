<?php

namespace Epoint\SwisspostApi\Model\Api;

use Epoint\SwisspostApi\Model\Api\Image as ApiImageModel;
use Magento\Framework\Event\Manager;
use Magento\Framework\ObjectManagerInterface;
use \Psr\Log\LoggerInterface;
use Epoint\SwisspostApi\Helper\Resource;

class Product extends ApiDataObject implements Data\Translatable, Data\Entity
{
    /**
     * Product external id key.
     * @const EXTERNAL_ID_CODE
     */
    const EXTERNAL_ID_CODE = 'product_code';

    /**
     * Config. entity type
     *
     * @const ENTITY_TYPE
     */
    const ENTITY_TYPE = 'product';

    /**
     * Entity.
     *
     * @var $entity
     */
    protected $entity;

    /**
     * @var ApiImageModel
     */
    protected $apiImageModel;

    /**
     * Product constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param Resource               $resource
     * @param Manager                $eventManager
     * @param LoggerInterface        $logger
     * @param Image                  $apiImageModel
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Resource $resource,
        Manager $eventManager,
        LoggerInterface $logger,
        ApiImageModel $apiImageModel
    ) {
        parent::__construct($objectManager, $resource, $eventManager, $logger);
        $this->apiImageModel = $apiImageModel;
    }

    /**
     * @inheritdoc
     */
    public function getReferenceId($objectId = '')
    {
        if (!empty($objectId)) {
            return $objectId;
        }
        return $this->get('product_code');
    }

    /**
     * @inheritdoc
     */
    public function getInstance($product)
    {
        $apiObject = $this->objectManager->get(
            \Epoint\SwisspostApi\Model\Api\Product::class
        );
        $apiObject->set('product_code', $this->getReferenceId($product->getSKU()));
        return $apiObject;
    }

    /**
     * @param $sku
     *
     * @return $this|null
     */
    public function load($sku)
    {
        $this->reset();
        $filter = ['filters' => ''];
        if ($sku) {
            $filter = [
                'filters' => [
                    'product_code = ' . $sku . ''
                ]
            ];
        }
        $filter['limit'] = 1;

        $result = $this->apiResource->getProducts($filter);

        if ($result->isOk() && $result->get('values')) {
            $item = current($result->get('values'));
            if ($item) {
                $this->loadFromResultItem($item);
                return $this;
            }
        }
        return null;
    }

    /**
     * Get product images.
     *
     * @return array
     */
    public function getImages()
    {
        $images = [];
        $result = $this->apiResource->getImages($this->get('product_code'));

        if ($result->isOk() && $result->get('values')) {
            // Getting the items list
            $items = $result->get('values');
            // Validate data and if pass will be added to the list
            foreach ($items as $item) {
                /** @var ApiImageModel $image */
                $image = $this->objectManager->create(
                    ApiImageModel::class
                );
                $image->loadFromResultItem($item);
                if ($image->validateImageData()){
                    $images[] = $image;
                }
            }
        }

        return $images;
    }

    /**
     * @return \Magento\Catalog\Model\Product|mixed
     */
    public function toLocal()
    {
        $product
            = $this->objectManager->create(
            \Magento\Catalog\Model\Product::class
        );
        if ($this->isLocalSaved()) {
            // Load internal id.
            $product = $product->load($this->getLocalId());
        }
        return $product;
    }

    /**
     * @return mixed
     */
    public function isLocalSaved()
    {
        return $this->getExternalId() && $this->getLocalId();
    }

    /**
     * Return external code
     *
     * @return mixed
     */
    public function getExternalId()
    {
        return $this->get(self::EXTERNAL_ID_CODE);
    }

    /**
     * Return local id.
     *
     * @return mixed
     */
    public function getLocalId()
    {
        if ($this->getExternalId()) {
            $this->entity = $this->objectManager->create(
                \Epoint\SwisspostApi\Model\Entity::class
            )->loadByTypeAndExternalId(
                self::ENTITY_TYPE,
                $this->getExternalId()
            );
            if ($this->entity) {
                return $this->entity->getLocalId();
            }
        }
        return null;
    }

    /**
     * @param $externalId
     *
     * @return mixed
     */
    public function toLocalByExternalId($externalId)
    {
        $product
            = $this->objectManager->create(
            \Magento\Catalog\Model\Product::class
        );
        if ($externalId) {
            $this->entity = $this->objectManager->create(
                \Epoint\SwisspostApi\Model\Entity::class
            )->loadByTypeAndExternalId(
                self::ENTITY_TYPE,
                $externalId
            );
            if ($this->entity) {
                $product = $product->load($this->entity->getLocalId());
            }
        }
        return $product;
    }

    /**
     * Save local entity
     * @param $localId
     *
     * @throws \Exception
     */
    public function connect($localId)
    {
        if (!$this->getExternalId()) {
            throw new \Exception(__t('Missing external id.'));
        }
        if (!$localId) {
            throw new \Exception(__t('Missing local id.'));
        }
        $savedLocalId = $this->getLocalId();
        if (!$this->entity) {
            if ($savedLocalId && $savedLocalId != $localId) {
                throw new \Exception(__('Entity conflict on save.'));
            }
        }
        $this->entity->setType(self::ENTITY_TYPE);
        $this->entity->setExternalId($this->getExternalId());
        $this->entity->setLocalId($localId);
        $this->entity->save();
    }

    /**
     * @inheritdoc
     */
    public function translate($property, $code = '')
    {
        $labels = $this->get('languages');
        if (isset($labels[$code]) && isset($labels[$code][$property])) {
            return $labels[$code][$property];
        }
        return '';
    }
}

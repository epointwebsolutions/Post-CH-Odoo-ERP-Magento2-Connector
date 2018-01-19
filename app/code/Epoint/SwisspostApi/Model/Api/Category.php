<?php

namespace Epoint\SwisspostApi\Model\Api;

use Epoint\SwisspostApi\Helper\Result;
use Epoint\SwisspostApi\Model\Api\Data\Translatable;
use Epoint\SwisspostApi\Model\Api\Data\Entity;

class Category extends ApiDataObject implements Entity, Translatable
{
    /**
     * Level path separator.
     * @const LEVEL_PATH_SEPARATOR
     */
    const LEVEL_PATH_SEPARATOR = ' / ';

    /**
     * Category external id key.
     * @const EXTERNAL_ID_CODE
     */
    const EXTERNAL_ID_CODE = 'odoo_id';

    /**
     * Entity.
     * @var $_entity
     */
    protected $_entity;

    /**
     * Entity type
     * @const ENTITY_TYPE
     */
    const ENTITY_TYPE = 'category';

    /**
     * @inheritdoc
     */
    public function getReferenceId($objectId = '')
    {
    }

    /**
     * @param $category
     * @return Category
     */
    public function getInstance($category)
    {
        $apiObject = new Category();
        $apiObject->set('title', $category->getName());
        return $apiObject;
    }

    /**
     * @param $categoryName
     *
     * @return $this|null
     */
    public function load($categoryName)
    {
        $this->reset();
        $filter = ['filters' => ''];
        if ($categoryName) {
            $filter = [
                'filters' => [
                    'name = ' . $categoryName . ''
                ]
            ];
        }
        $filter['limit'] = 1;
        $result = $this->apiResource->getProductCategories($filter);
        if($result->isOk() && $result->get('values')){
            $item = current($result->get('values'));
            if($item){
                $this->loadFromResultItem($item);
                return $this;
            }
        }
        return null;
    }

    /**
     * @return mixed
     */
    public function isLocalSaved()
    {
        return $this->getExternalId() && $this->getLocalId();
    }

    /**
     * @return mixed
     */
    public function getExternalId()
    {
        return $this->get(self::EXTERNAL_ID_CODE);
    }

    /**
     * Return local id.
     * @return mixed
     */
    public function getLocalId()
    {
        if ($this->getExternalId()) {
            $this->_entity =
                $this->objectManager->create(
                    \Epoint\SwisspostApi\Model\Entity::class
                )->loadByTypeAndExternalId(self::ENTITY_TYPE,
                    $this->getExternalId());
            if ($this->_entity) {
                return $this->_entity->getLocalId();
            }
        }
        return null;
    }

    /**
     * @return \Magento\Catalog\Model\Category|mixed
     */
    public function toLocal()
    {
        $category = $this->objectManager->create(\Magento\Catalog\Model\Category::class);
        if ($this->isLocalSaved()) {
            // Load internal id.
            $category = $category->load($this->getLocalId());
        }
        return $category;
    }

    /**
     * Get category level.
     *
     * @return int
     *   The category level
     */
    public function getLevel()
    {
        return MAX(0, count(explode(self::LEVEL_PATH_SEPARATOR, $this->get('path'))) - 1);
    }

    /**
     * @param $externalId
     *
     * @return mixed
     */
    public function toLocalByExternalId($externalId){
        $category = $this->objectManager->create(\Magento\Catalog\Model\Category::class);
        if ($externalId) {
            $this->_entity =
                $this->objectManager->create(
                    \Epoint\SwisspostApi\Model\Entity::class
                )->loadByTypeAndExternalId(self::ENTITY_TYPE,
                    $externalId);
            if ($this->_entity) {
                $category = $category->load($this->_entity->getLocalId());
            }
        }
        return $category;
    }

    /**
     *
     * Save local entity
     * @param $localId
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
        if (!$this->_entity) {
            $savedLocalId = $this->getLocalId();
            if ($savedLocalId && $savedLocalId != $localId) {
                throw new \Exception(__('Entity conflict on save.'));
            }
        }
        $this->_entity->setType(self::ENTITY_TYPE);
        $this->_entity->setExternalId($this->getExternalId());
        $this->_entity->setLocalId($localId);
        $this->_entity->save();
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

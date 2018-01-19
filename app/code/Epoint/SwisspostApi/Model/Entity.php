<?php

namespace Epoint\SwisspostApi\Model;

use Magento\Framework\Model\AbstractModel;

class Entity extends AbstractModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Epoint\SwisspostApi\Model\ResourceModel\Entity::class
        );
    }

    /**
     * Load collection first item;
     * @param $type
     * @param $localId
     * @return \Magento\Framework\DataObject
     */
    public function loadByTypeAndLocalId($type, $localId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('type', $type);
        $collection->addFieldToFilter('local_id', $localId);
        return $collection->getFirstItem();
    }
    /**
     * Load collection first item;
     * @param $type
     * @param $localId
     * @return \Magento\Framework\DataObject
     */
    public function loadByTypeAndExternalId($type, $externalId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('type', $type);
        $collection->addFieldToFilter('external_id', $externalId);
        return $collection->getFirstItem();
    }
}

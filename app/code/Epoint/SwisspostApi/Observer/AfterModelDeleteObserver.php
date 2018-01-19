<?php
/**
 * Copyright © 2013-2017 Epoint, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Epoint\SwisspostApi\Observer;

use Epoint\SwisspostApi\Model\Api\Category as ApiCategory;

/**
 * Model save observer.
 */
class AfterModelDeleteObserver extends BaseObserver
{
    /**
     * Handler for 'model_delete_after' event.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // Remove local connection, entity.
        if ($object = $observer->getObject()) {
            $type = null;
            $id = null;
            if (is_subclass_of($object, 'Magento\Catalog\Model\Category')
                || is_subclass_of($object, 'Magento\Catalog\Model\Product')) {
                $id = $object->getId();
                $type = ApiCategory::ENTITY_TYPE;
                if ($type && $id) {
                    /** @var  \Epoint\SwisspostApi\Model\Entity $connection */
                    $connection = $this->objectManager->get(
                        \Epoint\SwisspostApi\Model\Entity::class
                    )->loadByTypeAndExternalId($type,
                        $id);
                    if ($connection && $connection->getId()) {
                        $connection->delete();
                    }
                }
            }
        }
    }
}

<?php

namespace Epoint\SwisspostApi\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Contact Resource Model
 *
 * @author      Pierre FAY
 */
class Entity extends AbstractDb
{
    const OBSERVER_VARIABLE = 'epoint_swisspost_entity';
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('epoint_swisspost_entities', 'id');
    }
}

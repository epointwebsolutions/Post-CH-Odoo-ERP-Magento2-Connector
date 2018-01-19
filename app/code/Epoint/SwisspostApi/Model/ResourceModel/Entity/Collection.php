<?php


namespace Epoint\SwisspostApi\Model\ResourceModel\Entity;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Contact Resource Model Collection
 *
 * @author      Pierre FAY
 */
class Collection extends AbstractCollection
{
    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Epoint\SwisspostApi\Model\Entity',
               'Epoint\SwisspostApi\Model\ResourceModel\Entity'
        );
    }
}

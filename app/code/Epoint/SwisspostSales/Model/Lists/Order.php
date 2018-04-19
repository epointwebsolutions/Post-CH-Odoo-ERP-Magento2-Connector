<?php

namespace Epoint\SwisspostSales\Model\Lists;

use \Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Epoint\SwisspostSales\Helper\Order as HelperOrder;
use Epoint\SwisspostApi\Model\Api\SaleOrder;

class Order
{
    /**
     * @const STATUS_FILTER_ATTRIBUTE_CODE;
     */
    const STATUS_FILTER_ATTRIBUTE_CODE = 'status';
    /**
     * The order helper
     *
     * @var HelperOrder
     */
    protected $helperOrder;

    /**
     * The Api Resource
     *
     * @var Resource
     */
    protected $collectionFactory;

    /**
     * Order constructor.
     *
     * @param CollectionFactory $collectionFactory
     * @param HelperOrder       $helperOrder
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        HelperOrder $helperOrder
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->helperOrder = $helperOrder;
    }

    /**
     * @inheritdoc
     */
    public function search()
    {
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->getSelect()->where(
            ' CONVERT(main_table.increment_id, CHAR(32)) IN 
        (SELECT local_id FROM epoint_swisspost_entities WHERE `type`="' . SaleOrder::ENTITY_TYPE . '" AND automatic_export=1)'
        );

        // Add status filter.
        if ($acceptedStatus = $this->helperOrder->getCronExportOrderConfigStatus()) {
            $collection->addFieldToFilter(
                self::STATUS_FILTER_ATTRIBUTE_CODE,
                ['in' => $acceptedStatus]
            );
        }
        $collection->load();
        return $collection;
    }

    /**
     * Return the list of orders with status
     *
     * @param $orderStatus
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getSentOrders($orderStatus)
    {
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->getSelect()->where(
            ' CONVERT(main_table.increment_id, CHAR(32)) IN 
        (SELECT local_id FROM epoint_swisspost_entities WHERE `type`="' . SaleOrder::ENTITY_TYPE . '")'
        );

        // Add status filter.
        if (!empty($orderStatus)) {
            $collection->addFieldToFilter(
                self::STATUS_FILTER_ATTRIBUTE_CODE,
                ['in' => $orderStatus]
            );
        }
        $collection->load();
        return $collection;
    }
}

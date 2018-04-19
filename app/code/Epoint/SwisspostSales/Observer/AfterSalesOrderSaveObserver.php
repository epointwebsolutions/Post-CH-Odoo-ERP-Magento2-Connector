<?php
/**
 * Copyright © 2013-2017 Epoint, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Epoint\SwisspostSales\Observer;

use Epoint\SwisspostApi\Observer\BaseObserver;
use \Magento\Framework\ObjectManagerInterface;
use \Psr\Log\LoggerInterface;
use Epoint\SwisspostSales\Service\Order as OrderService;
use Epoint\SwisspostSales\Helper\Order as OrderHelper;
use Epoint\SwisspostApi\Model\Api\SaleOrder as SaleOrderApiModel;

/**
 * Sales Order save observer.
 */
class AfterSalesOrderSaveObserver extends BaseObserver
{
    /**
     * @var OrderService
     */
    protected $orderService;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var \Epoint\SwisspostApi\Model\Api\SaleOrder
     */
    protected $saleOrderApiModel;

    /**
     * AfterSalesOrderSaveObserver constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Epoint\SwisspostSales\Service\Order $orderService
     * @param \Epoint\SwisspostSales\Helper\Order $orderHelper
     * @param \Epoint\SwisspostApi\Model\Api\SaleOrder $saleOrderApiModel
     */
    public function __construct(
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        OrderService $orderService,
        OrderHelper $orderHelper,
        SaleOrderApiModel $saleOrderApiModel
    ) {
        parent::__construct($logger, $objectManager);
        $this->orderService = $orderService;
        $this->orderHelper = $orderHelper;
        $this->saleOrderApiModel = $saleOrderApiModel;
    }

    /**
     * Handler for 'sales_order_save_after' event.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $observer->getEvent()->getOrder();
        // Stop execution.
        if ($order->getSentOdoo()) {
            return;
        }

        try {
            // If the order has been placed by a guest, the order status will be set to export failure status
            // and the order will be sent by the cron
            // If not we trigger the export order action
            if ($order->getCustomerIsGuest()) {
                // Add message and set new status.
                $order->setStatus($this->orderHelper->getExportFailureNewStatus());
                $message = __('Order status has been set to be On Hold.');
                $order->addStatusToHistory($order->getStatus(), $message);
                $order->setSentOdoo(true);
                $order->save();

                // Save entity
                /** @var \Epoint\SwisspostApi\Model\Api\SaleOrder $apiOrder */
                $apiOrder = $this->saleOrderApiModel->getInstance($order);
                $apiOrder->set(
                    SaleOrderApiModel::ENTITY_AUTOMATIC_EXPORT,
                    '1'
                );
                $apiOrder->connect($apiOrder->get('order_ref'));
            } else {
                $this->orderService->run([$order]);
            }
        } catch (\Exception $e) {
            $this->logException($e);
        }
    }
}

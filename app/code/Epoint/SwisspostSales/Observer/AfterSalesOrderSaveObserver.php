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
     * AfterSalesOrderSaveObserver constructor.
     *
     * @param LoggerInterface $logger
     * @param ObjectManagerInterface   $objectManager
     * @param OrderService    $orderService
     */
    public function __construct(
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        OrderService $orderService,
        OrderHelper $orderHelper
    ) {
        parent::__construct($logger, $objectManager);
        $this->orderService = $orderService;
        $this->orderHelper = $orderHelper;
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
        if($order->getSentOdoo()){
            return ;
        }

        try {
            // If the order has been placed by a guest, the order status will be set to export failure status
            // and the order will be sent by the cron
            // If not we trigger the export order action
            if ($order->getCustomerIsGuest()){
                // Add message and set new status.
                $order->setStatus($this->orderHelper->getExportFailureNewStatus());
                $message = __('Order status has been set to be On Hold.');
                $order->addStatusToHistory($order->getStatus(), $message);
                $order->setSentOdoo(true);
                $order->save();
            } else {
                $this->orderService->run([$order]);
            }
        } catch (\Exception $e) {
            $this->logException($e);
        }
    }
}

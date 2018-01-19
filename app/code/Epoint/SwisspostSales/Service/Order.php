<?php

namespace Epoint\SwisspostSales\Service;

use Epoint\SwisspostApi\Service\BaseExchange;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\ObjectManagerInterface;
use Epoint\SwisspostApi\Model\Api\SaleOrder as SaleOrderApiModel;
use Epoint\SwisspostSales\Service\Coupon as CouponService;
use Epoint\SwisspostSales\Helper\Order as OrderHelper;
use Psr\Log\LoggerInterface;
use Epoint\SwisspostSales\Model\Lists\Order as ListOrderModel;

class Order extends BaseExchange
{
    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var CouponService
     */
    protected $couponService;

    /**
     * @var SaleOrderApiModel
     */
    protected $saleOrderApiModel;

    /**
     * @var ListOrderModel
     */
    protected $listOrderModel;

    /**
     * Order constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface        $logger
     * @param OrderHelper            $orderHelper
     * @param Coupon                 $couponService
     * @param SaleOrderApiModel      $saleOrderApiModel
     * @param ListOrderModel         $listOrderModel
     * @param ScopeConfigInterface   $scopeConfig
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
        OrderHelper $orderHelper,
        CouponService $couponService,
        SaleOrderApiModel $saleOrderApiModel,
        ListOrderModel $listOrderModel,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($objectManager, $logger, $scopeConfig);
        $this->orderHelper = $orderHelper;
        $this->couponService  = $couponService;
        $this->saleOrderApiModel = $saleOrderApiModel;
        $this->listOrderModel = $listOrderModel;;
    }

    /**
     * @inheritdoc
     */
    public function run($items = array())
    {
        $processed = [];
        foreach ($items as $order) {
            try {
                /** @var \Epoint\SwisspostApi\Model\Api\SaleOrder $apiOrder */
                $apiOrder = $this->saleOrderApiModel->getInstance($order);

                // Only one time can be sent.
                if (!$apiOrder->isLocalSaved()) {
                    // Export order
                    $result = $apiOrder->save();
                    if ($result !== null && $result && $result->isOK()) {
                        // Set connection.
                        $apiOrder->set(
                            SaleOrderApiModel::EXTERNAL_ID_CODE,
                            $result->get(SaleOrderApiModel::EXTERNAL_ID_CODE)
                        );
                        $apiOrder->connect($apiOrder->get('order_ref'));

                        // Status in message error.
                        $newStatus = $this->orderHelper->getExportSuccessNewStatus();
                        $message = sprintf(
                            __(
                                'Order export to SwissPost successful with reference: %s'
                            ),
                            $result->get(SaleOrderApiModel::EXTERNAL_ID_CODE)
                        );
                        // Setting the response status for the selected order
                        $order->setIsOdooResponseError(false);

                        // The order was exported to Odoo.
                        // If the export is enabled in config we trigger the action
                        if ($this->orderHelper->isExportOrderCouponAsGiftCardEnabled()){
                            $this->couponService->run([$order]);
                        }
                    } else {
                        $newStatus = $this->orderHelper->getExportFailureNewStatus();
                        $message = sprintf(
                            __('Order export to SwissPost fails: %s'),
                            ($result
                                ? $result->getDebugMessage()
                                : __(
                                    'Unknown error.'
                                ))
                        );
                        $order->setIsOdooResponseError(true);
                    }
                    // Adding the message to be displayed on order object
                    $order->setResponseMessage($message);
                    // Add message and set new status.
                    $order->setStatus($newStatus);
                    $order->setSentOdoo(true);
                    $order->addStatusToHistory($order->getStatus(), $message);
                    $order->save();

                    $processed[] = $order;
                    return $processed;
                }

                // Throwing exception if the order has already been saved locally (happens when the order was exported successfully before)
                throw new \Exception(
                    sprintf(
                        __(
                            'Trying to resend Swisspost order: %d'),
                        $order->getId()
                    )
                );
            } catch (\Exception $e) {
                $this->logException($e);
            }
        }
        return $processed;
    }

    /**
     * @inheritdoc
     */
    public function helperFactory()
    {
        return $this->orderHelper;
    }

    /**
     * @inheritdoc
     */
    public function listFactory()
    {
        return $this->listOrderModel;
    }
}

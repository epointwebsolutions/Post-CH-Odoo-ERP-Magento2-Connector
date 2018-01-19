<?php

namespace Epoint\SwisspostSales\Service;

use Epoint\SwisspostApi\Service\BaseExchange;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Epoint\SwisspostApi\Model\Api\Coupon as CouponApiModel;

class Coupon extends BaseExchange
{
    /**
     * @var CouponApiModel $couponApiModel
     */
    protected $couponApiModel;

    /**
     * Coupon constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface        $logger
     * @param CouponApiModel         $couponApiModel
     * @param ScopeConfigInterface   $scopeConfig
     */
    public function __construct(ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
        CouponApiModel $couponApiModel,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($objectManager, $logger, $scopeConfig);
        $this->couponApiModel = $couponApiModel;
    }

    /**
     * @inheritdoc
     */
    public function run($items)
    {
        $processed = [];
        foreach ($items as $order) {
            try {
                // Api Model
                $apiCoupon = $this->couponApiModel->getInstance($order);
                // Checking to see if a coupon exist
                if ($apiCoupon->get('is_present')) {
                    // Trigger action
                    $result = $apiCoupon->export();

                    if ($result && $result->isOK()) {
                        // Setup message
                        $message = sprintf(
                            __(
                                'Coupon %s was exported to SwissPost successfully.'
                            ), $apiCoupon->get('name')
                        );
                    } else {
                        // Setup message
                        $message = sprintf(
                            __('%s coupon export to SwissPost fails: %s'),
                            $apiCoupon->get('name'),
                            ($result
                                ? $result->getDebugMessage()
                                : __(
                                    'Unknown error.'
                                ))
                        );
                    }
                } else {
                    $message = sprintf(__('Operation stoped. Reason: NO coupon found!'));
                }
                $order->addStatusHistoryComment($message);
                $processed[] = $order;
            } catch (\Exception $e) {
                $this->logException($e);
            }
        }
        return $processed;
    }

    /**
     * @inheritdoc
     */
    public function listFactory()
    {
        return $this->objectManager->get(
            \Epoint\SwisspostSales\Model\Lists\Order::class
        );
    }

    /**
     * @inheritdoc
     */
    public function helperFactory()
    {

    }
}
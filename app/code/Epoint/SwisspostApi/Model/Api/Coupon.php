<?php

namespace Epoint\SwisspostApi\Model\Api;

use Magento\Framework\Event\Manager;
use Magento\Framework\ObjectManagerInterface;
use Epoint\SwisspostApi\Helper\Resource as SwisspostResources;
use Epoint\SwisspostApi\Model\Api\SaleOrder as ApiModelSaleOrder;
use \Psr\Log\LoggerInterface;

class Coupon extends ApiDataObject
{
    /**
     * @var ApiModelSaleOrder
     */
    protected $apiModelSaleOrder;

    public function __construct(
        ObjectManagerInterface $objectManager,
        SwisspostResources $resource,
        Manager $eventManager,
        LoggerInterface $logger,
        ApiModelSaleOrder $apiModelSaleOrder
    ) {
        parent::__construct($objectManager, $resource, $eventManager, $logger);
        $this->apiModelSaleOrder = $apiModelSaleOrder;
    }

    /**
     * @inheritdoc
     */
    public function getReferenceId($objectId = '')
    {
        if (!empty($objectId)) {
            return $objectId;
        }
    }

    /**
     * @inheritdoc
     */
    public function getInstance($order)
    {
        $apiObject = $this->objectManager->get(
            \Epoint\SwisspostApi\Model\Api\Coupon::class
        );
        // Default flag value which will tell us if we found any coupon attached to the order
        $apiObject->set('is_present', '0');
        // Add increment id as order_ref
        $apiObject->set('order_ref', $this->apiModelSaleOrder->getReferenceId($order->getIncrementId()));
        // Getting the coupons attached to the order
        if ($orderCouponCode = $order->getCouponCode()) {
            // Coupon code
            $apiObject->set('name', $orderCouponCode);
            // Order created date
            $orderCreatedDate = date('Y-m-d', strtotime($order->getCreatedAt()));
            $apiObject->set('date', $orderCreatedDate);
            // Coupon discount amount applied to the order
            $apiObject->set('amount', $order->getDiscountAmount());
            // Updating the flag
            $apiObject->set('is_present', '1');
        }

        return $apiObject;
    }

    /**
     * Export order coupons
     *
     * @return mixed
     * @throws \Exception
     */
    public function export()
    {

        // Getting order incrementId as order ref
        $orderIncrementId = $this->get('order_ref');
        // Checking if $orderIncrementId is valid
        if (!$orderIncrementId) {
            throw new \Exception(__('Missing order incrementedId.'));
        }

        // Constructing the coupon array
        $couponData = [
                [
                'name' => $this->get('name'),
                'date' => $this->get('date'),
                'amount' => abs($this->get('amount'))
                ]
        ];

        // Getting data
        $result = $this->apiResource->addSaleOrderCoupon($orderIncrementId, $couponData);

        // Store values
        if ($result->isOk() && $result->get('values')) {
            $item = current($result->get('values'));
            if ($item) {
                // Before we store values the data container must be cleared
                $this->reset();
                $this->loadFromResultItem($item);
            }
        }
        return $result;
    }
}

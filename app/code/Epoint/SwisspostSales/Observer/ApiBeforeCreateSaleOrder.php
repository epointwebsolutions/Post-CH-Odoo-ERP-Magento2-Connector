<?php
/**
 * Copyright © 2013-2017 Epoint, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Epoint\SwisspostSales\Observer;

use Epoint\SwisspostApi\Observer\BaseObserver;
use \Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Epoint\SwisspostSales\Helper\Shipping as ShippingHelper;
use Epoint\SwisspostSales\Helper\Payment as PaymentHelper;
use Epoint\SwisspostSales\Helper\ProductTax as ProductTaxHelper;

/**
 * Sales Order setup payment observer.
 */
class ApiBeforeCreateSaleOrder extends BaseObserver
{
    /**
     * @var ShippingHelper
     */
    protected $shippingHelper;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var ProductTaxHelper
     */
    protected $productTaxHelper;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * ApiBeforeCreateSaleOrder constructor.
     *
     * @param LoggerInterface                                 $logger
     * @param ObjectManagerInterface                          $objectManager
     * @param ShippingHelper                                  $shippingHelper
     * @param PaymentHelper                                   $paymentHelper
     * @param ProductTaxHelper                                $productTaxHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        ShippingHelper $shippingHelper,
        PaymentHelper $paymentHelper,
        ProductTaxHelper $productTaxHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($logger, $objectManager);
        $this->shippingHelper = $shippingHelper;
        $this->paymentHelper = $paymentHelper;
        $this->productTaxHelper = $productTaxHelper;
        $this->productRepository = $productRepository;
    }

    /**
     * Handler for 'swisspostapi_order_before_api_create_sale_order' event.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getData('order');
        /** @var \Epoint\SwisspostApi\Model\Api\SaleOrder $apiObject */
        $apiObject = $observer->getData('apiObject');

        // Setup shipping
        // Getting the order shipping method and shipping method code
        $shippingMethod = $order->getShippingMethod(true);
        $shippingMethodCode = $shippingMethod->getData('carrier_code');

        // Getting the external code
        $externalCode = $this->shippingHelper->getExternalShippingCode($shippingMethodCode);
        $apiObject->set('delivery_method', $externalCode);

        // Setup payment
        // Getting the order payment method and payment method code
        $paymentMethod = $order->getPayment()->getMethodInstance();
        $paymentMethodCode = $paymentMethod->getCode();

        // Getting the external code
        $externalCode = $this->paymentHelper->getExternalPaymentCode($paymentMethodCode);
        $apiObject->set('payment_method', $externalCode);

        // Add products lines.
        $orderLines = [];
        foreach ($order->getAllVisibleItems() as $orderItem) {
            if ($orderLine = $this->toOrderLineSalesOrder($orderItem)
            ) {
                $orderLines[] = $orderLine;
            }
        }
        $apiObject->set('order_lines', $orderLines);
    }

    /**
     * Convert product sales order.
     *
     * @param $orderItem `
     *                   The order item.
     *
     * @return array
     *  The structured API order item.
     */
    public function toOrderLineSalesOrder(\Magento\Sales\Model\Order\Item $orderItem)
    {
        $item = [];

        // Product sku
        $item['product'] = $orderItem->getSKU();
        // Product name
        $item['name'] = $orderItem->getName();
        // Quantity
        $item['quantity'] = $orderItem->getQtyOrdered();
        // Price for 1 unit
        $amount = $orderItem->getPrice();
        $item['price_unit'] = number_format($amount, 2, '.', '');
        // Calculate discount amount.
        $orderItemDiscount = $orderItem->getOriginalPrice() - $orderItem->getPrice();
        $item['discount'] = $orderItemDiscount > 0 && $item['quantity'] > 0 ? $orderItemDiscount : 0;
        // Convert discount to percent
        if ($item['discount'] > 0) {
            $percent = ($item['discount'] * 100) / $orderItem->getOriginalPrice();
            $item['discount'] = number_format(round($percent), 1);
        }
        // Product description
        if ($orderItem->getDescription()) {
            $item['name'] = $orderItem->getDescription();
        }
        // Setting up the product tax class
        $item['tax_id'] = [$this->productTaxHelper->getExternalTaxClassCode(
            $orderItem->getProduct()->getTaxClassId()
        )];
        return $item;
    }
}

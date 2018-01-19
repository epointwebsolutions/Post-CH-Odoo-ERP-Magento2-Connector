<?php

namespace Epoint\SwisspostSales\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order as LocalOrder;
use Magento\Sales\Model\Order\Shipment;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use \Magento\Sales\Model\Order\Shipment\Track as ShipmentTrack;
use Epoint\SwisspostApi\Model\Api\SaleOrder as ApiModelSaleOrder;
use Epoint\SwisspostApi\Model\Api\Invoice as ApiModelInvoice;

class Order extends Data
{
    /**
     * @var \Magento\Sales\Model\Service\InvoiceService $invoiceService
     */
    protected $invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction $transaction
     */
    protected $transaction;

    /**
     * @var \Magento\Sales\Model\Convert\Order $convertOrder
     */
    protected $convertOrder;

    /**
     * @var \Magento\Sales\Model\Order\Shipment\Track $shipmentTrack
     */
    protected $shipmentTrack;

    /**
     * @var ApiModelSaleOrder
     */
    protected $apiModelSaleOrder;

    /**
     * @var ApiModelInvoice
     */
    protected $apiModelInvoice;

    /**
     * Order constructor.
     *
     * @param Context                $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface  $storeManager
     * @param InvoiceService         $invoiceService
     * @param Transaction            $transaction
     * @param LoggerInterface        $logger
     * @param ConvertOrder           $convertOrder
     * @param ShipmentTrack          $shipmentTrack
     * @param ApiModelSaleOrder      $apiModelSaleOrder
     * @param ApiModelInvoice        $apiModelInvoice
     */
    public function __construct(Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        InvoiceService $invoiceService,
        Transaction $transaction,
        LoggerInterface $logger,
        ConvertOrder $convertOrder,
        ShipmentTrack $shipmentTrack,
        ApiModelSaleOrder $apiModelSaleOrder,
        ApiModelInvoice $apiModelInvoice
    ) {
        parent::__construct($context, $objectManager, $storeManager, $logger);
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->convertOrder = $convertOrder;
        $this->shipmentTrack = $shipmentTrack;
        $this->apiModelSaleOrder = $apiModelSaleOrder;
        $this->apiModelInvoice = $apiModelInvoice;
    }

    /**
     * Check if cron is enabled from config
     * @return bool
     */
    public function isCronEnabled()
    {
        return $this->getConfigValue(self::XML_PATH . 'order/enable_export') ?  true:false;
    }

    /**
     * The order new status for successful export
     * @return mixed|string
     */
    public function getExportSuccessNewStatus()
    {
        $status = $this->getDefaultStatusForState(LocalOrder::STATE_NEW);
        if ($this->getConfigValue(self::XML_PATH . 'order/successful_export_status')) {
            $status = $this->getConfigValue(self::XML_PATH . 'order/successful_export_status');
        }
        return strtolower($status);
    }

    /**
     * The order new status for export failure
     *
     * @return mixed|string
     */
    public function getExportFailureNewStatus()
    {
        $status = $this->getDefaultStatusForState(LocalOrder::STATE_HOLDED);
        if ($this->getConfigValue(self::XML_PATH . 'order/failure_export_status')) {
            $status = $this->getConfigValue(self::XML_PATH . 'order/failure_export_status');
        }
        return strtolower($status);
    }

    /**
     * Will return the accepted status for cron order export
     * Default order state is LocalOrder::STATE_HOLDED
     * @return mixed
     */
    public function getCronExportOrderConfigStatus()
    {
        $status = [$this->getDefaultStatusForState(LocalOrder::STATE_HOLDED)];
        if ($this->getConfigValue(self::XML_PATH . 'order/filter_status')) {
            $unprocessedData = $this->getConfigValue(self::XML_PATH . 'order/filter_status');
            // Processing data
            $status = explode(',', $unprocessedData);
        }
        return $status;
    }

    /**
     * Will try to create an invoice
     *
     * @param LocalOrder $order
     *
     * @return LocalOrder\Invoice|null
     */
    public function createInvoice(LocalOrder $order)
    {
        $invoice = null;
        try {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setState($invoice::STATE_OPEN);
            $invoice->register();
            $invoice->save();
            $transactionSave = $this->transaction
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transactionSave->save();
            //send notification code
            $order->addStatusHistoryComment(
                __('The invoice has been create from'.' Magento-Odoo Integration module')
            )
                ->save();
            return $invoice;
        } catch (\Exception $e) {
            $this->logException($e);
        }
        return $invoice;
    }

    /**
     * Will try to create an order shipment
     * @param LocalOrder $order
     *
     * @return LocalOrder\Shipment
     */
    public function createShipment(LocalOrder $order)
    {
        // Initialize the order shipment object
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $this->convertOrder->toShipment($order);

        // Loop through order items
        foreach ($order->getAllItems() as $orderItem) {
            // Check if order item has qty to ship or is virtual
            if (! $orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }
            // Read the item qty for shipping
            $qtyShipped = $orderItem->getQtyToShip();
            // Create shipment item with qty
            $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem);
            $shipmentItem->setQty($qtyShipped);

            // Add shipment item to shipment
            $shipment->addItem($shipmentItem);
        }

        // Register shipment
        $shipment->register();

        $shipment->getOrder()->setIsInProcess(true);

        try {
            // Save created shipment and order
            $shipment->save();
            $shipment->getOrder()->save();
            //send notification code
            $order->addStatusHistoryComment(
                __('The shipment has been create from'.' Magento-Odoo Integration module')
            )
                ->save();
        } catch (\Exception $e) {
            $this->logException($e);
        }
        return $shipment;
    }

    /**
     * Set the new tracking number
     *
     * @param LocalOrder $order
     * @param Shipment   $shipment
     * @param array      $params
     */
    public function setTrackingNumber(LocalOrder $order, Shipment $shipment, $params = [])
    {
        try {
            foreach ($params['tracking_number'] as $number) {
                // If Number is not attached on this shipment, we can add it to shipment
                if(!$this->existsTrackingNumber($shipment, $number)) {
                    $trackingDetail = array(
                        'carrier_code' => $order->getShippingDescription(),
                        'title'        => $order->getShippingDescription(),
                        'number'       => $number,
                        'order_id'     => $order->getId()
                    );

                    $this->shipmentTrack
                        ->setShipment($shipment)
                        ->addData($trackingDetail)
                        ->save();

                    //send notification code
                    $order->addStatusHistoryComment(
                        sprintf(__('The shipment tracking number %s has been added'), $number)
                    )
                        ->save();
                }
            }
        } catch (\Exception $e) {
            $this->logException($e);
        }
    }

    /**
     * Check if exists a number attached to a shipment
     * @param Shipment $shipment
     * @param          $number
     *
     * @return bool
     */
    public function existsTrackingNumber(Shipment $shipment, $number)
    {
        $trackNumbers = [];
        foreach($shipment->getAllTracks() as $trackNumber) {
            $trackNumbers[] = $trackNumber->getNumber();
        }
        // Validating
        if(in_array($number, $trackNumbers)){
            return true;
        }
        return false;
    }

    /**
     * Check if an invoice can be created
     * @param LocalOrder $order
     *
     * @return bool
     */
    public function canInvoice(LocalOrder $order)
    {
        if (!$order->hasInvoices() && $order->canInvoice()) {
            return true;
        }
        return false;
    }

    /**
     * Check if a shipment can be created
     * @param LocalOrder $order
     *
     * @return bool
     */
    public function canShipment(LocalOrder $order)
    {
        if (!$order->hasShipments() && $order->canShip()) {
            return true;
        }
        return false;
    }

    /**
     * Convert external order ref to internal order ref (Order incrementId)
     *
     * @param $externalOrderRef
     *
     * @return bool|string
     */
    public function toLocalOrderRef($externalOrderRef)
    {
        $localOrderRef = '';
        // Extract local ref from external ref
        if (($pos = strpos($externalOrderRef, "-")) !== FALSE) {
            $localOrderRef = substr($externalOrderRef, $pos+1);
        }
        return $localOrderRef;
    }

    /**
     * Check if export order as gift card is enabled in config
     * @return bool
     */
    public function isExportOrderCouponAsGiftCardEnabled()
    {
        return $this->getConfigValue(self::XML_PATH . 'order_gift_cards/coupon_as_gift_card') ?  true:false;
    }

    /**
     * Will create a list of orders Ids only
     * @param array $orderList
     *
     * @return array
     */
    public function extractOrderIdsFromOrderList($orderList)
    {
        $idsList = [];
        if (!empty($orderList)) {
            /** @var LocalOrder $order */
            foreach ($orderList as $order) {
                $idsList[] = $this->apiModelSaleOrder->getReferenceId($order->getIncrementId());
            }
        }
        return $idsList;
    }

    /**
     * Will create a list of invoices ids from provided orders list
     * @param array $orderList
     *
     * @return array
     */
    public function getOrdersInvoiceFromList($orderList)
    {
        $invoiceIdsList = [];
        if (!empty($orderList)) {
            /** @var LocalOrder $order */
            foreach ($orderList as $order) {
                foreach ($order->getInvoiceCollection() as $invoice) {
                    $invoiceIdsList [] = $this->apiModelInvoice->getReferenceId($invoice->getIncrementId());
                }
            }
        }
        return $invoiceIdsList;
    }
}

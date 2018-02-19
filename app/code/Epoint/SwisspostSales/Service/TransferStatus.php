<?php

namespace Epoint\SwisspostSales\Service;

use Epoint\SwisspostApi\Service\BaseExchange;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order as LocalOrder;
use \Magento\Framework\ObjectManagerInterface;
use \Magento\Framework\Message\ManagerInterface;
use Epoint\SwisspostSales\Helper\Order as OrderHelper;
use Epoint\SwisspostSales\Helper\Shipping as ShippingHelper;
use \Psr\Log\LoggerInterface;
use Epoint\SwisspostApi\Helper\Resource as SwisspostResources;
use Epoint\SwisspostSales\Model\Lists\Order as OrderModelList;

class TransferStatus extends BaseExchange
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface $messageManager
     */
    protected $messageManager;

    /**
     * @var OrderHelper $orderHelper
     */
    protected $orderHelper;

    /**
     * @var ShippingHelper $shippingHelper
     */
    protected $shippingHelper;

    /**
     * The Api Resource
     *
     * @var SwisspostResources
     */
    protected $apiResource;

    /**
     * @var OrderModelList
     */
    protected $orderModelList;

    /**
     * TransferStatus constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface        $logger
     * @param ManagerInterface       $messageManager
     * @param OrderHelper            $orderHelper
     * @param ShippingHelper         $shippingHelper
     * @param ScopeConfigInterface   $scopeConfig
     * @param SwisspostResources     $apiResource
     * @param OrderModelList         $orderModelList
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
        ManagerInterface $messageManager,
        OrderHelper $orderHelper,
        ShippingHelper $shippingHelper,
        ScopeConfigInterface $scopeConfig,
        SwisspostResources $apiResource,
        OrderModelList $orderModelList
    ) {
        parent::__construct($objectManager, $logger, $scopeConfig);
        $this->messageManager = $messageManager;
        $this->orderHelper = $orderHelper;
        $this->shippingHelper = $shippingHelper;
        $this->apiResource = $apiResource;
        $this->orderModelList = $orderModelList;
    }

    /**
     * @param array $transferData
     *
     * @return array
     */
    public function prepare($transferData = [])
    {
        if ($transferData['order_ref'] = $this->orderHelper->toLocalOrderRef($transferData['order_ref'])) {
            // Retrive order ref
            $orderIncrementId = $transferData['order_ref'];
            // Load local order
            if ($order = $this->objectManager->create(
                LocalOrder::class
            )->loadByIncrementId($orderIncrementId)
            ) {
                return [$transferData, $order];
            }
        }
        return [[], null];
    }

    /**
     * @param            $transferData
     * @param LocalOrder $order
     *
     * @return LocalOrder
     */
    public function updateOrder($transferData, LocalOrder $order)
    {
        // Output message container
        $outputMessage = '';

        // Checking payment status
        if ($transferData['state']) {
            switch (strtolower($transferData['state'])) {
                case 'done':
                    // Managing shipment
                    $shipment = null;
                    if ($this->orderHelper->canShipment($order)) {
                        $shipment = $this->orderHelper->createShipment($order);
                        // Updating output message
                        $outputMessage = $outputMessage . sprintf(__(' A new shipment has been created.'));
                    }

                    // Checking to see if a new shipping was created.
                    // If not we load the last shipment if is present
                    if ($shipment === null || !$shipment->getId()) {
                        // Load last shipment
                        $shipment = $order->getShipmentsCollection()
                            ->addAttributeToSort('created_at', 'DSC')
                            ->setPage(1, 1)
                            ->getFirstItem();
                    }

                    // attach tracking info
                    if ($shipment && $shipment->getId()) {
                        $this->orderHelper->setTrackingNumber($order, $shipment, $transferData);
                    }
                    // Updating output message
                    $outputMessage = $outputMessage . sprintf(__(' The shipment status is DONE.'));

                    break;

                default:
                    // Updating output message
                    $outputMessage = $outputMessage . sprintf(__(' No shipment found. The shipment status is OPEN.'));
                    break;
            }
        }

        // Output message
        $this->messageManager->addSuccessMessage($outputMessage);

        return $order;
    }

    /**
     * @param $items
     *
     * @return array
     */
    public function run($items)
    {
        $orders = [];
        foreach ($items as $item) {
            // Preparing data
            list($transferData, $order) = $this->prepare($item);
            // Check if order exist in the system
            if (!empty($order->getId()) && $order !== null) {
                // Update order
                $orders[] = $this->updateOrder($transferData, $order);
            } else {
                // Output error message
                $this->messageManager->addErrorMessage(sprintf(__('Order with id %s not found!'), $transferData['order_ref']));
            }
        }
        return $orders;
    }

    /**
     * @inheritdoc
     */
    public function helperFactory()
    {
        return $this->shippingHelper;
    }

    /**
     * @inheritdoc
     */
    public function listFactory()
    {
        return $this->orderModelList;
    }

    /**
     * Execute import order transfer status.
     */
    public function execute()
    {
        if ($this->shippingHelper->isImportOrderTransferStatusCronEnabled()) {
            // If an order status is define -> trigger import action
            if ($orderStatus = $this->shippingHelper->getConfigOrderStatusForImportOrderTransfer()) {
                /** @var \Epoint\SwisspostSales\Model\Lists\Order $itemList */
                $listFactory = $this->listFactory();
                /** @var array \Magento\Sales\Model\Order $selectedOrders */
                $selectedOrders = $listFactory->getSentOrders($orderStatus);

                // Getting the ids list for selected orders
                $orderIdsList = $this->orderHelper->extractOrderIdsFromOrderList($selectedOrders);

                // Check orders transfer status
                $result = $this->apiResource->checkOrdersTransferStatus($orderIdsList);

                // Processing the result
                if ($result->isOK()) {
                    // Run import.
                    $this->run($result->get('values'));
                }
            }
        }
    }
}
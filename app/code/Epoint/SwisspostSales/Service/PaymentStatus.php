<?php

namespace Epoint\SwisspostSales\Service;

use Epoint\SwisspostApi\Service\BaseExchange;
use Magento\Sales\Model\Order as LocalOrder;
use \Magento\Framework\ObjectManagerInterface;
use \Magento\Framework\Message\ManagerInterface;
use Epoint\SwisspostSales\Helper\Order as OrderHelper;
use Epoint\SwisspostSales\Helper\Payment as PaymentHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Psr\Log\LoggerInterface;
use Epoint\SwisspostApi\Helper\Resource as SwisspostResources;
use Epoint\SwisspostSales\Model\Lists\Order as OrderModelList;

class PaymentStatus extends BaseExchange
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
     * @var PaymentHelper $paymentHelper
     */
    protected $paymentHelper;

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
     * PaymentStatus constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param LoggerInterface        $logger
     * @param ManagerInterface       $messageManager
     * @param OrderHelper            $orderHelper
     * @param PaymentHelper          $paymentHelper
     * @param ScopeConfigInterface   $scopeConfig
     * @param SwisspostResources     $apiResource
     * @param OrderModelList         $orderModelList
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
        ManagerInterface $messageManager,
        OrderHelper $orderHelper,
        PaymentHelper $paymentHelper,
        ScopeConfigInterface $scopeConfig,
        SwisspostResources $apiResource,
        OrderModelList $orderModelList
    ) {
        parent::__construct($objectManager, $logger, $scopeConfig);
        $this->messageManager = $messageManager;
        $this->orderHelper = $orderHelper;
        $this->paymentHelper = $paymentHelper;
        $this->apiResource = $apiResource;
        $this->orderModelList = $orderModelList;
    }

    /**
     * @param array $paymentData
     *
     * @return array
     */
    public function prepare($paymentData = [])
    {
        if ($paymentData['order_ref'] = $this->orderHelper->toLocalOrderRef(
            $paymentData['order_ref']
        )
        ) {
            // Load local order
            $orderIncrementId = $paymentData['order_ref'];
            if ($order = $this->objectManager->create(
                LocalOrder::class
            )->loadByIncrementId($orderIncrementId)
            ) {
                return [$paymentData, $order];
            }
        }
        return [[], null];
    }

    /**
     * @param            $paymentData
     * @param LocalOrder $order
     *
     * @return LocalOrder
     */
    public function updateOrder($paymentData, LocalOrder $order)
    {
        // Output message container
        $outputMessage = '';
        // Invoice
        $invoice = null;
        // If there is no invoice present... we create one
        if ($this->orderHelper->canInvoice($order)) {
            // Create invoice if none is present
            $invoice = $this->orderHelper->createInvoice($order);
            $outputMessage = sprintf(__('An invoice has been created.'));
        } else {
            /** @var \Magento\Sales\Model\Order\Invoice $invoice */
            $invoice = $order->getInvoiceCollection()
                ->addAttributeToSort('created_at', 'DSC')
                ->setPage(1, 1)
                ->getFirstItem();
        }

        // Checking payment status
        if ($paymentData['state']) {
            switch (strtolower($paymentData['state'])) {
                case 'paid':
                    // Updating invoice state if is open
                    if ($invoice !== null
                        && $invoice->getState() == $invoice::STATE_OPEN
                    ) {
                        $invoice->setState($invoice::STATE_PAID);
                        $invoice->save();
                    }
                    // Updating outputMessage text
                    $outputMessage = $outputMessage . sprintf(__(' The invoice has been paid. The invoice status is PAID.'));
                    break;

                default:
                    $invoice->setState($invoice::STATE_OPEN);
                    $invoice->save();
                    $outputMessage = $outputMessage . sprintf(__(' The invoice has not been paid. The invoice status is PENDING.'));
                    break;
            }
        }

        // Output message
        $this->messageManager->addSuccessMessage($outputMessage);

        return $order;
    }

    /**
     * @param $externalOrderRef
     *
     * @return bool|string
     */
    public function toLocalOrderRef($externalOrderRef)
    {
        $localOrderRef = '';
        // Extract local ref from external ref
        if (($pos = strpos($externalOrderRef, "-")) !== false) {
            $localOrderRef = substr($externalOrderRef, $pos + 1);
        }
        return $localOrderRef;
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
            list($paymentData, $order) = $this->prepare($item);
            // Check if order exist in the system
            if (!empty($order->getId()) && $order !== null) {
                // Update order
                $orders[] = $this->updateOrder($paymentData, $order);
            } else {
                // Output error message
                $this->messageManager->addErrorMessage(
                    sprintf(
                        __('Order with id %s not found!'),
                        $paymentData['order_ref']
                    )
                );
            }
        }
        return $orders;
    }

    /**
     * @inheritdoc
     */
    public function listFactory()
    {
        return $this->orderModelList;
    }

    /**
     * @inheritdoc
     */
    public function helperFactory()
    {
        return $this->paymentHelper;
    }

    /**
     * Execute import order payment status.
     */
    public function execute()
    {
        if ($this->paymentHelper->isImportOrderPaymentStatusCronEnabled()) {
            // If an order status is define -> trigger import action
            if ($orderStatus = $this->paymentHelper->getConfigOrderStatusForImportOrderPayment()) {
                /** @var \Epoint\SwisspostSales\Model\Lists\Order $itemList */
                $listFactory = $this->listFactory();
                /** @var array \Magento\Sales\Model\Order $selectedOrders */
                $selectedOrders = $listFactory->getSentOrders($orderStatus);

                // Getting the ids list for selected orders
                $orderIdsList = $this->orderHelper->extractOrderIdsFromOrderList($selectedOrders);
                // Getting the all the invoice ids of the selected orders
                $orderInvoiceIdsList = $this->orderHelper->getOrdersInvoiceFromList($selectedOrders);

                // Check orders payment status
                $result = $this->apiResource->checkOrdersPaymentStatus($orderIdsList, []);

                // Processing the result
                if ($result->isOK()) {
                    // Run import.
                    $this->run($result->get('values'));
                }
            }
        }
    }
}
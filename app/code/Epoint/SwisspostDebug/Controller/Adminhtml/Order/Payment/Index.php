<?php

namespace Epoint\SwisspostDebug\Controller\Adminhtml\Order\Payment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use \Magento\Sales\Api\OrderRepositoryInterface;
use Epoint\SwisspostSales\Service\PaymentStatus;
use Epoint\SwisspostApi\Helper\Resource as SwisspostResources;
use Epoint\SwisspostSales\Helper\Order as OrderHelper;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var Http
     */
    protected $_request;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $_orderRepository;

    /**
     * @var \Epoint\SwisspostSales\Service\PaymentStatus
     */
    protected $_orderPaymentStatusService;

    /**
     * @var \Epoint\SwisspostApi\Helper\Resource
     */
    protected $_apiResource;

    /**
     * @var \Epoint\SwisspostSales\Helper\Order
     */
    protected $_orderHelper;

    /**
     * Index constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Epoint\SwisspostSales\Service\PaymentStatus $orderPaymentStatusService
     * @param \Epoint\SwisspostApi\Helper\Resource $apiResource
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Epoint\SwisspostSales\Helper\Order $orderHelper
     */
    public function __construct(
        Context $context,
        PaymentStatus $orderPaymentStatusService,
        SwisspostResources $apiResource,
        OrderRepositoryInterface $orderRepository,
        OrderHelper $orderHelper
    )
    {
        parent::__construct($context);
        $this->_orderPaymentStatusService = $orderPaymentStatusService;
        $this->_apiResource = $apiResource;
        $this->_orderRepository = $orderRepository;
        $this->_orderHelper = $orderHelper;
    }

    /**
     * Execute command
     *
     * @throws \Exception
     */
    public function execute()
    {
        $this->_request = $this->getRequest();

        // Getting the order Id
        $orderId = $this->_request->getParam('order_id');

        if (!$orderId) {
            throw new \Exception(__('Missing order id.'));
        }

        /** @var \Magento\Sales\Model\Order $localOrder */
        $localOrder = $this->_orderRepository->get($orderId);

        if (!$localOrder || !$localOrder->getId()) {
            throw new \Exception(__('Missing order.'));
        }

        // Before triggering the check action the system needs to be sure an odoo_id(external_id) is present
        $selectedOrders = $this->_orderHelper->extractExportedOrders([$localOrder]);

        if (count($selectedOrders) > 0) {
            // Make request
            $result = $this->_apiResource->checkOrdersPaymentStatus([$localOrder->getIncrementId()]);
            // Processing the result
            if ($result->isOK()) {
                // Start updating orders payment status
                $imported = $this->_orderPaymentStatusService->run($result->get('values'));
                /** @var \Magento\Sales\Model\Order $order */
                foreach ($imported as $order) {
                    $outputMessage = sprintf(
                        __('Order with ID -> %s has received payment status data!'), $order->getIncrementId()
                    );
                    $this->messageManager->addSuccessMessage($outputMessage);

                    // Adding comment
                    $order->addStatusHistoryComment(sprintf(__('Order has received payment status!')));
                    $order->save();
                }
            } else {
                $outputMessage = sprintf(
                    __("%s: %s"), $result->get('status'), $result->get('comment')
                );
                $this->messageManager->addErrorMessage($outputMessage);
                $outputMessage = $result->getDebugMessage();
                $this->messageManager->addErrorMessage($outputMessage);

                // Adding comment
                $orderComment = sprintf(
                    __("Getting order payment status from SwissPost failed! %s: %s"),
                    $result->get('status'),
                    $result->get('comment')
                );
                $localOrder->addStatusHistoryComment($orderComment);
                $localOrder->save();
            }
        } else {
            $outputMessage = sprintf(
                __('Getting order payment status stopped. To be able to get the payment status, 
                the order must be exported to the SwissPost.')
            );
            $this->messageManager->addSuccessMessage($outputMessage);

            // Adding comment
            $localOrder->addStatusHistoryComment($outputMessage);
            $localOrder->save();
        }

        // Get referer url
        $url = $this->_redirect->getRefererUrl();
        $this->_redirect($url);
    }
}

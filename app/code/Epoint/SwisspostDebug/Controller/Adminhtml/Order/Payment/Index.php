<?php

namespace Epoint\SwisspostDebug\Controller\Adminhtml\Order\Payment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Epoint\SwisspostSales\Service\PaymentStatus;
use Epoint\SwisspostApi\Helper\Resource as SwisspostResources;
use Epoint\SwisspostSales\Helper\Payment as PaymentHelper;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var Http $_request
     */
    protected $_request;

    /**
     * @var PaymentStatus $orderPaymentStatusService
     */
    protected $orderPaymentStatusService;

    /**
     * The Api Resource
     *
     * @var SwisspostResources
     */
    protected $apiResource;

    /**
     * @var PaymentHelper $paymentHelper
     */
    protected $paymentHelper;

    /**
     * Index constructor.
     *
     * @param Context            $context
     * @param PaymentStatus      $orderPaymentStatusService
     * @param SwisspostResources $apiResource
     * @param PaymentHelper      $paymentHelper
     */
    public function __construct(
        Context $context,
        PaymentStatus $orderPaymentStatusService,
        SwisspostResources $apiResource,
        PaymentHelper $paymentHelper
    ) {
        parent::__construct($context);
        $this->orderPaymentStatusService = $orderPaymentStatusService;
        $this->apiResource = $apiResource;
        $this->paymentHelper = $paymentHelper;
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

        // Creating a local order
        /** @var \Magento\Sales\Model\Order $localOrder */
        $localOrder = $this->_objectManager->create(
            \Magento\Sales\Model\Order::class
        )->load($orderId);

        if (!$localOrder || !$localOrder->getId()) {
            throw new \Exception(__('Missing order.'));
        }

        // Make request
        $result = $this->apiResource->checkOrdersPaymentStatus([$localOrder->getIncrementId()]);
        // Processing the result
        if ($result->isOK()) {
            // Start updating orders payment status
            $imported = $this->orderPaymentStatusService->run($result->get('values'));
            foreach ($imported as $order) {
                $outputMessage = sprintf(
                    __('Order with ID -> %s has received payment status data!'), $order->getIncrementId()
                );
                $this->messageManager->addSuccessMessage($outputMessage);
            }
        } else {
            $outputMessage = sprintf(
                __("%s: %s"), $result->get('status'), $result->get('comment')
            );
            $this->messageManager->addErrorMessage($outputMessage);
            $outputMessage = $result->getDebugMessage();
            $this->messageManager->addErrorMessage($outputMessage);
        }

        // Get referer url
        $url = $this->_redirect->getRefererUrl();
        $this->_redirect($url);
    }
}

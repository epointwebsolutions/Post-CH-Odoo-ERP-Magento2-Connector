<?php

namespace Epoint\SwisspostDebug\Controller\Adminhtml\Order\Transfer;

use Magento\Framework\App\Request\Http;
use Epoint\SwisspostApi\Helper\Resource as SwisspostResources;
use Epoint\SwisspostSales\Helper\Shipping as ShippingHelper;
use \Magento\Sales\Api\OrderRepositoryInterface;
use Epoint\SwisspostSales\Helper\Order as OrderHelper;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var Http
     */
    protected $_request;

    /**
     * @var \Epoint\SwisspostSales\Service\TransferStatus
     */
    protected $_orderTransferStatusService;

    /**
     * @var \Epoint\SwisspostApi\Helper\Resource
     */
    protected $_apiResource;

    /**
     * @var \Epoint\SwisspostSales\Helper\Shipping
     */
    protected $_shippingHelper;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $_orderRepository;

    /**
     * @var \Epoint\SwisspostSales\Helper\Order
     */
    protected $_orderHelper;


    /**
     * Index constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Epoint\SwisspostSales\Service\TransferStatus $orderTransferStatusService
     * @param \Epoint\SwisspostApi\Helper\Resource $apiResource
     * @param \Epoint\SwisspostSales\Helper\Shipping $shippingHelper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Epoint\SwisspostSales\Helper\Order $orderHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Epoint\SwisspostSales\Service\TransferStatus $orderTransferStatusService,
        SwisspostResources $apiResource,
        ShippingHelper $shippingHelper,
        OrderRepositoryInterface $orderRepository,
        OrderHelper $orderHelper
    )
    {
        parent::__construct($context);
        $this->_orderTransferStatusService = $orderTransferStatusService;
        $this->_apiResource = $apiResource;
        $this->_shippingHelper = $shippingHelper;
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
            $result = $this->_apiResource->checkOrdersTransferStatus([$localOrder->getIncrementId()]);

            // Processing the result
            if ($result->isOK()) {
                // Start updating orders payment status
                $imported = $this->_orderTransferStatusService->run($result->get('values'));
                /** @var \Magento\Sales\Model\Order $order */
                foreach ($imported as $order) {
                    $outputMessage = sprintf(
                        __('Order with ID -> %s has received transport status data!'), $order->getIncrementId()
                    );
                    $this->messageManager->addSuccessMessage($outputMessage);
                    // Adding comment
                    $order->addStatusHistoryComment(sprintf(__('Order has received transport status!')));
                    $order->save();
                }
            } else {
                if (is_string($result->get('status')) && is_string($result->get('comment'))) {
                    $outputMessage = sprintf(
                        __("%s: %s"), $result->get('status'), $result->get('comment')
                    );
                    $orderComment = sprintf(
                        __('Getting order transport status from SwissPost failed! %s: %s'),
                        $result->get('status'),
                        $result->get('comment')
                    );
                } else {
                    if (is_string($result->get('error_no'))) {
                        $outputMessage = sprintf(__('Error on request, with error number %s'), $result->get('error_no'));
                        $orderComment = sprintf(
                            __('Getting order transport status from SwissPost failed! Error on request, with error number %s. %s'),
                            $result->get('error_no'),
                            $result->getDebugMessage()
                        );
                    } else {
                        $outputMessage = sprintf(__('Request error!'));
                        $orderComment = sprintf(
                            __('Getting order transport status from SwissPost failed! Request error. %s'),
                            $result->getDebugMessage()
                        );
                    }
                }
                $this->messageManager->addErrorMessage($outputMessage);
                $outputMessage = $result->getDebugMessage();
                $this->messageManager->addErrorMessage($outputMessage);

                // Adding comment
                $localOrder->addStatusHistoryComment($orderComment);
                $localOrder->save();
            }
        } else {
            $outputMessage = sprintf(
                __('Getting order transport status stopped. To be able to get the transport status, 
                please export the order to the SwissPost first.')
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

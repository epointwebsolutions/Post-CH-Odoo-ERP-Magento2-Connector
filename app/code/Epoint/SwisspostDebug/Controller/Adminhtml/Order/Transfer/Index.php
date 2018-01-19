<?php

namespace Epoint\SwisspostDebug\Controller\Adminhtml\Order\Transfer;

use Magento\Framework\App\Request\Http;
use Epoint\SwisspostSales\Service\TransferStatus;
use Epoint\SwisspostApi\Helper\Resource as SwisspostResources;
use Epoint\SwisspostSales\Helper\Shipping as ShippingHelper;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var Http $_request
     */
    protected $_request;

    /**
     * @var \Magento\Framework\App\ObjectManager $objectManager
     */
    protected $objectManager;

    /**
     * @var TransferStatus $orderTransferStatusService
     */
    protected $orderTransferStatusService;

    /**
     * The Api Resource
     *
     * @var SwisspostResources
     */
    protected $apiResource;

    /**
     * @var ShippingHelper $shippingHelper
     */
    protected $shippingHelper;

    /**
     * Execute command
     * @throws \Exception
     */
    public function execute()
    {
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->orderTransferStatusService = $this->objectManager->get(\Epoint\SwisspostSales\Service\TransferStatus::class);
        $this->apiResource = $this->objectManager->get(\Epoint\SwisspostApi\Helper\Resource::class);
        $this->shippingHelper = $this->objectManager->get(ShippingHelper::class);
        $this->_request = $this->getRequest();

        // Getting the order Id
        $orderId = $this->_request->getParam('order_id');

        if (!$orderId) {
            throw new \Exception(__('Missing order id.'));
        }

        // Creating a local order
        /** @var \Magento\Sales\Model\Order $localOrder */
        $localOrder = $this->objectManager->create(
            \Magento\Sales\Model\Order::class
        )->load($orderId);

        if (!$localOrder || !$localOrder->getId()) {
            throw new \Exception(__('Missing order.'));
        }

        // Make request
        $result = $this->apiResource->checkOrdersTransferStatus([$localOrder->getIncrementId()]);

        // Processing the result
        if ($result->isOK()) {
            // Start updating orders payment status
            $imported = $this->orderTransferStatusService->run($result->get('values'));
            foreach ($imported as $order) {
                $outputMessage = sprintf(
                    __('Order with ID -> %s has received transport status data!'), $order->getIncrementId()
                );
                $this->messageManager->addSuccessMessage($outputMessage);
            }
        } else {
            $outputMessage = '';
            if (is_string($result->get('status')) && is_string($result->get('comment'))) {
                $outputMessage = sprintf(
                    __("%s: %s"), $result->get('status'), $result->get('comment')
                );
            } else if (is_string($result->get('error_no'))) {
                $outputMessage = sprintf(__('Error on request, with error number %s'), $result->get('error_no'));
            } else {
                $outputMessage = __('Request error!');
            }
            $this->messageManager->addErrorMessage($outputMessage);
            $outputMessage = $result->getDebugMessage();
            $this->messageManager->addErrorMessage($outputMessage);
        }

        // Get referer url
        $url = $this->_redirect->getRefererUrl();
        $this->_redirect($url);
    }
}

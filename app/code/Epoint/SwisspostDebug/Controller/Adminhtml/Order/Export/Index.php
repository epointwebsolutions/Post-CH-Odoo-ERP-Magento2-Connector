<?php

namespace Epoint\SwisspostDebug\Controller\Adminhtml\Order\Export;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use \Epoint\SwisspostSales\Service\Order as OrderService;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var Http $_request
     */
    protected $_request;

    /**
     * @var \Epoint\SwisspostSales\Service\Order $orderService
     */
    protected $orderService;

    /**
     * Index constructor.
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
        $this->_request = $this->getRequest();
        $this->orderService = $this->_objectManager->get(OrderService::class);
    }

    /**
     * Execute command
     *
     * @throws \Exception
     */
    public function execute()
    {
        // Getting the order Id
        $orderId = $this->_request->getParam('order_id');

        if (!$orderId) {
            throw new \Exception(__('Missing order id.'));
        }

        // Creating a local order
        $localOrder = $this->_objectManager->create(
            \Magento\Sales\Model\Order::class
        )->load($orderId);

        if (!$localOrder || !$localOrder->getId()) {
            throw new \Exception(__('Missing order.'));
        }

        try {
            $processed = $this->orderService->run([$localOrder]);
            foreach ($processed as $processedOrder) {
                // Checking if on selected order export the response has failed or not
                if ($processedOrder->getIsOdooResponseError()) {
                    // Displaying the saved message after the export took place
                    $this->messageManager->addErrorMessage($processedOrder->getResponseMessage());
                } else {
                    // Displaying the saved message after the export took place
                    $this->messageManager->addSuccessMessage($processedOrder->getResponseMessage());
                }
            }
        } catch (\Exception $e) {
            $this->logException($e);
        }

        // Get referer url
        $url = $this->_redirect->getRefererUrl();
        $this->_redirect($url);
    }
}

<?php

namespace Epoint\SwisspostDebug\Controller\Adminhtml\Order\Export;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use \Epoint\SwisspostSales\Service\Order as OrderService;
use Psr\Log\LoggerInterface;
use Epoint\SwisspostApi\Helper\LoggerTrait;
use \Magento\Sales\Api\OrderRepositoryInterface;

class Index extends \Magento\Backend\App\Action
{
    /**
     * Epoint\SwisspostApi\Helper\LoggerTrait
     */
    use LoggerTrait;

    /**
     * @var Http $_request
     */
    protected $_request;

    /**
     * @var \Epoint\SwisspostSales\Service\Order $orderService
     */
    protected $_orderService;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * Index constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Epoint\SwisspostSales\Service\Order $orderService
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        OrderService $orderService,
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($context);
        $this->_request = $this->getRequest();
        $this->_orderService = $orderService;
        $this->logger = $logger;
        $this->_orderRepository = $orderRepository;
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

        /** @var \Magento\Sales\Model\Order $localOrder */
        $localOrder = $this->_orderRepository->get($orderId);

        if (!$localOrder || !$localOrder->getId()) {
            throw new \Exception(__('Missing order.'));
        }

        try {
            $processed = $this->_orderService->run([$localOrder]);
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

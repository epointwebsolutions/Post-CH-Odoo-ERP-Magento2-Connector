<?php

namespace Epoint\SwisspostApi\Model\Api;

use Epoint\SwisspostApi\Model\Api\SaleOrder as ApiModelSaleOrder;
use Magento\Framework\Event\Manager;
use Magento\Framework\ObjectManagerInterface;
use \Psr\Log\LoggerInterface;
use Epoint\SwisspostApi\Helper\Resource;

class Invoice extends ApiDataObject
{
    /**
     * @var ApiModelSaleOrder
     */
    protected $apiModelSaleOrder;

    /**
     * Invoice constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param Resource               $resource
     * @param Manager                $eventManager
     * @param LoggerInterface        $logger
     * @param SaleOrder              $apiModelSaleOrder
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Resource $resource,
        Manager $eventManager,
        LoggerInterface $logger,
        ApiModelSaleOrder $apiModelSaleOrder
    ) {
        parent::__construct($objectManager, $resource, $eventManager, $logger);
        $this->apiModelSaleOrder = $apiModelSaleOrder;
    }

    /**
     * @inheritdoc
     */
    public function getReferenceId($objectId = '')
    {
        if (!empty($objectId)) {
            return $objectId;
        }
    }

    /**
     * @inheritdoc
     */
    public function getInstance($order)
    {
        $apiObject = $this->objectManager->get(
            \Epoint\SwisspostApi\Model\Api\Invoice::class
        );
        $apiObject->set('order_ref', $this->apiModelSaleOrder->getReferenceId($order->getIncrementId()));

        return $apiObject;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function load()
    {
        $orderIncrementId = $this->get('order_ref');

        // Checking if orderId is valid
        if (!$orderIncrementId) {
            throw new \Exception(__('Missing order id.'));
        }

        // Getting data
        return $this->apiResource->getInvoice($orderIncrementId);
    }
}

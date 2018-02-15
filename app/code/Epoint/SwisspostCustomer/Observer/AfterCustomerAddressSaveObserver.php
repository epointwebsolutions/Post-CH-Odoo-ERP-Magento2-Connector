<?php
/**
 * Copyright © 2013-2017 Epoint, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Epoint\SwisspostCustomer\Observer;

use \Magento\Framework\ObjectManagerInterface;
use \Psr\Log\LoggerInterface;
use Epoint\SwisspostApi\Observer\BaseObserver;
use Epoint\SwisspostApi\Model\Api\Address as ApiModelAddress;

/**
 * Customer address log observer.
 */
class AfterCustomerAddressSaveObserver extends BaseObserver
{
    /**
     * @var ApiModelAddress
     */
    protected $apiModelAddress;

    /**
     * AfterCustomerAddressSaveObserver constructor.
     *
     * @param LoggerInterface        $logger
     * @param ObjectManagerInterface $objectManager
     * @param ApiModelAddress        $apiModelAddress
     */
    public function __construct(
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        ApiModelAddress $apiModelAddress
    ) {
        parent::__construct($logger, $objectManager);
        $this->apiModelAddress = $apiModelAddress;
    }

    /**
     * Handler for 'customer_address_save_after' event.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Customer\Model\Address $address */
        $address = $observer->getEvent()->getCustomerAddress();
        $addressModel = $this->apiModelAddress->getInstance($address);
        try {
            $result = $addressModel->save();
        } catch (\Exception $e) {
            $this->logException($e);
        }
    }
}

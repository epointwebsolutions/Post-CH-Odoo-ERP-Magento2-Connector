<?php
/**
 * Copyright © 2013-2017 Epoint, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Epoint\SwisspostCustomer\Observer;

use Epoint\SwisspostApi\Observer\BaseObserver;
use \Psr\Log\LoggerInterface;
use Epoint\SwisspostApi\Model\Api\Account as ApiModelAccount;
use \Magento\Framework\ObjectManagerInterface;

/**
 * Customer log observer.
 */
class AfterCustomerSaveObserver extends BaseObserver
{
    /**
     * @var ApiModelAccount
     */
    protected $apiModelAccount;

    /**
     * because the observer is called twice we use the flag to save customer to Odoo only once
     *
     * @var bool
     */
    private $canCreateUpdateAccount = true;

    /**
     * AfterCustomerSaveObserver constructor.
     *
     * @param LoggerInterface        $logger
     * @param ObjectManagerInterface $objectManager
     * @param ApiModelAccount        $apiModelAccount
     */
    public function __construct(
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        ApiModelAccount $apiModelAccount
    ) {
        parent::__construct($logger, $objectManager);
        $this->apiModelAccount = $apiModelAccount;
    }

    /**
     * Handler for 'customer_save_after' event.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // Because the system fire twice the observer, we need to check/update the flag depending on state
        if ($this->canCreateUpdateAccount) {
            /** @var \Magento\Customer\Model\Customer $customer */
            $customer = $observer->getEvent()->getCustomer();
            if ($customer && $customer->getId()) {
                $account = $this->apiModelAccount->getInstance($customer);
                try {
                    $result = $account->save();
                } catch (\Exception $e) {
                    $this->logException($e);
                }
            } else {
                throw new \Exception(__('Missing customer.'));
            }
            // Updating flag
            $this->canCreateUpdateAccount = false;
        } else {
            // Updating flag
            $this->canCreateUpdateAccount = true;
        }

    }
}

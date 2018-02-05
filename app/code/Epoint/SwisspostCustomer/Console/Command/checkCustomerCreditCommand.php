<?php

namespace Epoint\SwisspostCustomer\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Epoint\SwisspostApi\Model\Api\Account as AccountModelApi;

class checkCustomerCreditCommand extends Command
{
    /**
     * Account ref argument
     *
     * @const ACCOUNT_REF_ARGUMENT
     */
    const ACCOUNT_REF_ARGUMENT = 'account_ref';

    /**
     * Amount value argument
     *
     * @const ACCOUNT_VALUE_ARGUMENT
     */
    const ACCOUNT_VALUE_ARGUMENT = 'credit_amount';

    /**
     * @var \Magento\Framework\App\ObjectManager $_objectManager
     */
    private $objectManager;

    /**
     * @var AccountModelApi
     */
    private $accountModelApi;

    /**
     * Implement configure method.
     */
    protected function configure()
    {
        $this->setName('epoint-swisspostapi:checkCustomerCredit')
            ->setDescription(__('Run checkCustomerCredit'))
            ->setDefinition([
                    new InputArgument(
                        self::ACCOUNT_REF_ARGUMENT,
                        InputArgument::REQUIRED,
                        'CheckCustomerCredit_Account_ref'
                    ),
                    new InputArgument(
                        self::ACCOUNT_VALUE_ARGUMENT,
                        InputArgument::REQUIRED,
                        'CheckCustomerCredit_Credit_amount'
                    )
                ]
            );
        $this->objectManager
            = \Magento\Framework\App\ObjectManager::getInstance();
        $this->accountModelApi = $this->objectManager->get(\Epoint\SwisspostApi\Model\Api\Account::class);
    }

    /**
     * Execute command method.
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set area code.
        $this->objectManager->get(\Magento\Framework\App\State::class)
            ->setAreaCode('adminhtml');

        $customerID = $input->getArgument(self::ACCOUNT_REF_ARGUMENT);
        $creditAmount = $input->getArgument(self::ACCOUNT_VALUE_ARGUMENT);

        if (!$customerID) {
            throw new \Exception(__('Missing customer ID.'));
        }

        if (!$creditAmount) {
            throw new \Exception(__('Missing customer credit value.'));
        }

        // Perform credit check operation
        $result = $this->accountModelApi->checkCustomerCredit($customerID, $creditAmount);
        if ($result->isOk() && $result->get('check_ok') === true) {
            $output->writeln(
                sprintf(
                    __('Swisspost API check customer credit successful, result: %s'),
                    $result->get('comment')
                )
            );
        } else {
            $output->writeln(
                sprintf(
                    __('Swisspost API check customer credit unsuccessful, result: %s'),
                    $result->get('comment')
                )
            );
        }
    }
}

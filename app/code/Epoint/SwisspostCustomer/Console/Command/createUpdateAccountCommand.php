<?php

namespace Epoint\SwisspostCustomer\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use \Magento\Framework\ObjectManagerInterface;
use \Epoint\SwisspostApi\Model\Api\Account as AccountApiModel;
use \Magento\Customer\Model\Customer as CustomerModel;
use \Magento\Framework\App\State as AppState;

class createUpdateAccountCommand extends Command
{
    /**
     * Customer ID argument
     *
     * @param CUSTOMER_ID_ARGUMENT
     */
    const CUSTOMER_ID_ARGUMENT = 'customer';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Epoint\SwisspostApi\Model\Api\Account
     */
    private $accountApiModel;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    private $customerModel;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * createUpdateAccountCommand constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param AccountApiModel        $accountApiModel
     * @param CustomerModel          $customerModel
     * @param AppState               $appState
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        AccountApiModel $accountApiModel,
        CustomerModel $customerModel,
        AppState $appState
    ) {
        $this->objectManager = $objectManager;
        $this->accountApiModel = $accountApiModel;
        $this->customerModel = $customerModel;
        $this->appState = $appState;
        parent::__construct();
    }

    /**
     * Implement configure method.
     */
    protected function configure()
    {
        $this->setName('epoint-swisspostapi:createUpdateAccount')
            ->setDescription(__('Run createUpdateAccount for a customer'))
            ->setDefinition(
                [
                    new InputArgument(
                        self::CUSTOMER_ID_ARGUMENT,
                        InputArgument::REQUIRED,
                        'Customer'
                    )
                ]
            );
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
        $this->appState->setAreaCode('adminhtml');

        // Getting the customer id
        $customer_id = $input->getArgument(self::CUSTOMER_ID_ARGUMENT);
        if (!$customer_id) {
            throw new \Exception(__('Missing customer id.'));
        }

        // Load local customer
        $customer = $this->customerModel->load($customer_id);

        if (!$customer || !$customer->getId()) {
            throw new \Exception(__('Missing customer.'));
        }

        $account = $this->accountApiModel->getInstance($customer);

        // Export customer
        $result = $account->save();

        // Processing response
        if ($result->isOK()) {
            $output->writeln(
                sprintf(
                    __('Swisspost API create/update account successful, odoo id: %s'),
                    $result->get('odoo_id')
                )
            );
        } else {
            $output->writeln(
                sprintf(
                    __('Swisspost API create/update account fails, debug message: %s'),
                    implode(
                        "\n", $result->getDebug()
                    )
                )
            );
        }
    }
}

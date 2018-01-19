<?php

namespace Epoint\SwisspostCustomer\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class createUpdateAccountCommand extends Command
{
    /**
     * Customer ID argument
     *
     * @param CUSTOMER_ID_ARGUMENT
     */
    const CUSTOMER_ID_ARGUMENT = 'customer';

    /**
     * @var \Magento\Framework\App\ObjectManager $objectManager
     */
    private $objectManager;

    /**
     * Implement configure method.
     */
    protected function configure()
    {
        $this->setName('epoint-swisspostapi:createUpdateAccount')
            ->setDescription(__('Run createUpdateAccount for a customer'))
            ->setDefinition([
                    new InputArgument(
                        self::CUSTOMER_ID_ARGUMENT,
                        InputArgument::REQUIRED,
                        'Customer'
                    )
                ]
            );
        $this->objectManager
            = \Magento\Framework\App\ObjectManager::getInstance();
    }

    /**
     * Execute command method.
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Getting the customer id
        $customer_id = $input->getArgument(self::CUSTOMER_ID_ARGUMENT);
        if (!$customer_id) {
            throw new \Exception(__('Missing customer id.'));
        }

        // Load local customer
        $customer
            = $this->objectManager->get(\Magento\Customer\Model\Customer::class)
            ->load($customer_id);

        if (!$customer || !$customer->getId()) {
            throw new \Exception(__('Missing customer.'));
        }

        $account
            = $this->objectManager->get(\Epoint\SwisspostApi\Model\Api\Account::class)
            ->getInstance($customer);

        // Export customer
        $result = $account->save();

        // Processing respose
        if ($result->isOK()) {
            $output->writeln(sprintf(__('Swisspost API create/update account successful, odoo id: %s'),
                $result->get('odoo_id')));
        } else {
            $output->writeln(sprintf(__('Swisspost API create/update account fails, debug message: %s'),
                    implode(
                        "\n", $result->getDebug()))
            );
        }
    }
}

<?php

namespace Epoint\SwisspostCustomer\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class createUpdateAddressCommand extends Command
{

    /**
     * Name argument
     *
     * @const ADDRESS_ID_ARGUMENT
     */
    const ADDRESS_ID_ARGUMENT = 'address';

    /**
     * @var \Magento\Framework\App\ObjectManager $_objectManager
     */
    private $objectManager;

    /**
     * Implement configure method.
     */
    protected function configure()
    {
        $this->setName('epoint-swisspostapi:createUpdateAddress')
            ->setDescription(__('Run createUpdateAddress for a customer'))
            ->setDefinition([
                    new InputArgument(
                        self::ADDRESS_ID_ARGUMENT,
                        InputArgument::REQUIRED,
                        'Address'
                    )
                ]
            );
        $this->objectManager
            = \Magento\Framework\App\ObjectManager::getInstance();
    }

    /**
     * Execute command method.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Getting the address id
        $address_id = $input->getArgument(self::ADDRESS_ID_ARGUMENT);
        if (!$address_id) {
            throw new \Exception(__('Missing address id.'));
        }

        $localAddress = $this->objectManager->create(
            \Magento\Customer\Model\Address::class
        )->load($address_id);

        if (!$localAddress && !$localAddress->getId()) {
            throw new \Exception(__('Missing address.'));
        }
        $address
            = $this->objectManager->get(\Epoint\SwisspostApi\Model\Api\Address::class)
            ->getInstance($localAddress);

        // Export address
        $result = $address->save();

        if ($result->isOK()) {
            $output->writeln(sprintf(__('Swisspost API create/update address successful, odoo id: %s'),
                $result->get('odoo_id')));
        } else {
            $output->writeln(sprintf(__('Swisspost API create/update account fails, debug message: %s'),
                    implode(
                        "\n", $result->getDebug()))
            );
        }
    }
}

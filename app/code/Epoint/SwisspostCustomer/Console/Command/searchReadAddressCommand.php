<?php

namespace Epoint\SwisspostCustomer\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use \Magento\Customer\Model\Address\Proxy as AddressModel;
use Epoint\SwisspostApi\Model\Api\Address\Proxy as AddressApiModel;
use \Magento\Framework\App\State as AppState;

class searchReadAddressCommand extends Command
{

    /**
     * Name argument
     */
    const ADDRESS_ID_ARGUMENT = 'address';

    /**
     * @var \Magento\Customer\Model\Address
     */
    private $addressModel;

    /**
     * @var \Epoint\SwisspostApi\Model\Api\Address
     */
    private $addressApiModel;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * searchReadAddressCommand constructor.
     *
     * @param \Magento\Customer\Model\Address\Proxy        $addressModel
     * @param \Epoint\SwisspostApi\Model\Api\Address\Proxy $addressApiModel
     * @param \Magento\Framework\App\State                 $appState
     */
    public function __construct(
        AddressModel $addressModel,
        AddressApiModel $addressApiModel,
        AppState $appState
    ) {
        $this->addressModel = $addressModel;
        $this->addressApiModel = $addressApiModel;
        $this->appState = $appState;
        parent::__construct();
    }

    /**
     * Implement configure method.
     */
    protected function configure()
    {
        $this->setName('epoint-swisspostapi:searchReadAddress')
            ->setDescription(__('Run searchReadAddressAddress for a customer'))
            ->setDefinition(
                [
                    new InputArgument(
                        self::ADDRESS_ID_ARGUMENT,
                        InputArgument::REQUIRED,
                        'Address'
                    )]
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

        $address_id = $input->getArgument(self::ADDRESS_ID_ARGUMENT);
        if (!$address_id) {
            throw new \Exception(__('Missing address id.'));
        }

        $localAddress = $this->addressModel->load($address_id);

        if (!$localAddress && !$localAddress->getId()) {
            throw new \Exception(__('Missing address.'));
        }
        $address = $this->addressApiModel->load($localAddress);
        if ($address && $address->get('odoo_id')) {
            $output->writeln(sprintf(__('Swisspost API search read address successful, odoo id: %s'), $address->get('odoo_id')));
        } else {
            $output->writeln(sprintf(__('Swisspost API search read address return null')));
        }
    }
}

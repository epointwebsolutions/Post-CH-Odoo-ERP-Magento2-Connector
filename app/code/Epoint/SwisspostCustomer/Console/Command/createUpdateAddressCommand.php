<?php

namespace Epoint\SwisspostCustomer\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use \Magento\Framework\ObjectManagerInterface;
use \Magento\Customer\Model\Address as CustomerAddressModel;
use \Epoint\SwisspostApi\Model\Api\Address as AddressApiModel;
use \Magento\Framework\App\State as AppState;

class createUpdateAddressCommand extends Command
{
    /**
     * Name argument
     *
     * @const ADDRESS_ID_ARGUMENT
     */
    const ADDRESS_ID_ARGUMENT = 'address';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\Customer\Model\Address
     */
    private $customerAddressModel;

    /**
     * @var \Epoint\SwisspostApi\Model\Api\Address
     */
    private $addressApiModel;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * createUpdateAddressCommand constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param CustomerAddressModel   $customerAddressModel
     * @param AddressApiModel        $addressApiModel
     * @param AppState               $appState
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        CustomerAddressModel $customerAddressModel,
        AddressApiModel $addressApiModel,
        AppState $appState
    ) {
        $this->objectManager = $objectManager;
        $this->customerAddressModel = $customerAddressModel;
        $this->addressApiModel = $addressApiModel;
        $this->appState = $appState;
        parent::__construct();
    }

    /**
     * Implement configure method.
     */
    protected function configure()
    {
        $this->setName('epoint-swisspostapi:createUpdateAddress')
            ->setDescription(__('Run createUpdateAddress for a customer'))
            ->setDefinition(
                [
                    new InputArgument(
                        self::ADDRESS_ID_ARGUMENT,
                        InputArgument::REQUIRED,
                        'Address'
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

        // Getting the address id
        $address_id = $input->getArgument(self::ADDRESS_ID_ARGUMENT);

        $output->writeln(
            sprintf(
                __('Address ID input: %s'),
                $address_id
            )
        );

        if (!isset($address_id) || $address_id == null) {
            throw new \Exception(__('Missing address id.'));
        }

        $localAddress = $this->customerAddressModel->load($address_id);

        if (!$localAddress && !$localAddress->getId()) {
            throw new \Exception(__('Missing address.'));
        }
        $address = $this->addressApiModel->getInstance($localAddress);

        // Export address
        $result = $address->save();

        if ($result->isOK()) {
            $output->writeln(
                sprintf(
                    __('Swisspost API create/update address successful, odoo id: %s'),
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

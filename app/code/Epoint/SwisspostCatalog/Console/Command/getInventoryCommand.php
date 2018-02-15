<?php

namespace Epoint\SwisspostCatalog\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use \Magento\Framework\ObjectManagerInterface;
use Epoint\SwisspostApi\Model\Api\Lists\Inventory as ApiListInventory;
use Epoint\SwisspostCatalog\Service\Inventory as ServiceInventory;
use \Magento\Framework\App\State as AppState;

class getInventoryCommand extends Command
{

    /**
     * Product argument
     */
    const PRODUCT_ARGUMENT = 'sku';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Epoint\SwisspostApi\Model\Api\Lists\Inventory
     */
    private $apiListInventory;

    /**
     * @var \Epoint\SwisspostCatalog\Service\Inventory
     */
    private $serviceInventory;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * getInventoryCommand constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param ApiListInventory       $apiListInventory
     * @param ServiceInventory       $serviceInventory
     * @param AppState               $appState
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ApiListInventory $apiListInventory,
        ServiceInventory $serviceInventory,
        AppState $appState
    ) {
        $this->objectManager = $objectManager;
        $this->apiListInventory = $apiListInventory;
        $this->serviceInventory = $serviceInventory;
        $this->appState = $appState;
        parent::__construct();
    }

    /**
     * Implement configure method.
     */
    protected function configure()
    {
        $this->setName('epoint-swisspostapi:getInventory')
            ->setDescription(__('Run getInventory'))
            ->setDefinition(
                [
                    new InputArgument(
                        self::PRODUCT_ARGUMENT,
                        InputArgument::OPTIONAL,
                        'Product'
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

        $product_sku = $input->getArgument(self::PRODUCT_ARGUMENT);
        $filter['product_codes'] = [];
        if ($product_sku) {
            $filter['product_codes'] = [$product_sku];
        }

        $values = $this->apiListInventory->search($filter);
        foreach ($values as $value) {
            print_r($value->getData());
        }

        if ($values) {
            $this->serviceInventory->run($values);
            $output->writeln(sprintf(__('Swisspost API load inventory request successful, stock items: %s'), count($values)));
        } else {
            $output->writeln(sprintf(__('Swisspost API load inventory result no values')));
        }
    }
}

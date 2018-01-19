<?php

namespace Epoint\SwisspostCatalog\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class getInventoryCommand extends Command {

    /**
     * Product argument
     */
    const PRODUCT_ARGUMENT = 'sku';

    /**
     * @var \Magento\Framework\App\ObjectManager $_objectManager
     */
    private $_objectManager;

    /**
     * Implement configure method.
     */
    protected function configure() {
        $this->setName('epoint-swisspostapi:getInventory')
            ->setDescription(__('Run getInventory'))
            ->setDefinition([
                    new InputArgument(
                        self::PRODUCT_ARGUMENT,
                        InputArgument::OPTIONAL,
                        'Product'
                    )]
            );
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    /**
     * Execute command method.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $product_sku = $input->getArgument(self::PRODUCT_ARGUMENT);
        $inventory = $this->_objectManager->get(\Epoint\SwisspostApi\Model\Api\Lists\Inventory::class);
        $filter['product_codes'] = [];
        if($product_sku){
            $filter['product_codes'] = [$product_sku];
        }

        $values = $inventory->search($filter);
        foreach ($values as $value){
            print_r($value->getData());
        }

        if($values){
            $importer = $this->_objectManager->get(\Epoint\SwisspostCatalog\Service\Inventory::class);
            $importer->run($values);
            $output->writeln(sprintf(__('Swisspost API load inventory request successful, stock items: %s'), count($values)));
        }else{
            $output->writeln(sprintf(__('Swisspost API load inventory result no values')));
        }
    }
}

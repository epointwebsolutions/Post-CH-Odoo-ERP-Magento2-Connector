<?php

namespace Epoint\SwisspostCatalog\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class getProductsCommand extends Command {

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
        $this->setName('epoint-swisspostapi:getProducts')
            ->setDescription(__('Run getProducts'))
            ->setDefinition([
                    new InputArgument(
                        self::PRODUCT_ARGUMENT,
                        InputArgument::OPTIONAL,
                        'Products'
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
    protected function execute(InputInterface $input, OutputInterface $output) {
        $productSKU = $input->getArgument(self::PRODUCT_ARGUMENT);
        $product = $this->_objectManager->get('Epoint\SwisspostApi\Model\Api\Lists\Product');
        $filter = [];
        if($productSKU){
            $filter = ['filters' => ['product_code = '. $productSKU]];
        }
        $items = $product->search($filter);
        if($items){
            foreach ($items as $item){
                foreach ($item->getImages() as $image){
                    $media = $this->_objectManager->get('Epoint\SwisspostCatalog\Model\MediaFactory')->create($image);
                }
            }
            $output->writeln(sprintf(__('Swisspost API load products request successful, result count: %s'), count($items)));
        }else{
            $output->writeln(sprintf(__('Swisspost API load products result no values')));
        }
    }
}

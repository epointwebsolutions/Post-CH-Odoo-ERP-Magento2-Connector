<?php

namespace Epoint\SwisspostCatalog\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class getProductCategoriesCommand extends Command {

    /**
     * Product category argument
     */
    const PRODUCT_CATEGORY_ARGUMENT = 'category';

    /**
     * @var \Magento\Framework\App\ObjectManager $_objectManager
     */
    private $_objectManager;

    /**
     * Implement configure method.
     */
    protected function configure() {
        $this->setName('epoint-swisspostapi:getProductCategories')
            ->setDescription(__('Run getProductsCategories'))
            ->setDefinition([
                    new InputArgument(
                        self::PRODUCT_CATEGORY_ARGUMENT,
                        InputArgument::OPTIONAL,
                        'ProductCategories'
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
        $categoryName = $input->getArgument(self::PRODUCT_CATEGORY_ARGUMENT);

        $category = $this->_objectManager->get('Epoint\SwisspostApi\Model\Api\Category');

        if($categoryName){
            $values = $category->load($categoryName);
        }else{
            $values = $category->search();
        }

        if($values){
            $output->writeln(sprintf(__('Swisspost API load product categories request successful, result: %s'), json_encode($values)));
        }else{
            $output->writeln(sprintf(__('Swisspost API load product categories result no values')));
        }
    }
}

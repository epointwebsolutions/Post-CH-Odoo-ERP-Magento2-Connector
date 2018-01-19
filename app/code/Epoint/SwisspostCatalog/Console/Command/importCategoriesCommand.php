<?php

namespace Epoint\SwisspostCatalog\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class importCategoriesCommand extends Command
{
    /**
     * Category argument
     *
     * @const CATEGORY_NAME_ARGUMENT
     */
    const CATEGORY_NAME_ARGUMENT = 'category';

    /**
     * @var \Magento\Framework\App\ObjectManager $objectManager
     */
    private $objectManager;

    /**
     * Implement configure method.
     */
    protected function configure()
    {
        $this->setName('epoint-swisspostapi:importCategories')
            ->setDescription(__('Run getProductsCategories'))
            ->setDefinition([
                    new InputArgument(
                        self::CATEGORY_NAME_ARGUMENT,
                        InputArgument::OPTIONAL,
                        'Category'
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
        // Set area code.
        $this->objectManager->get(\Magento\Framework\App\State::class)
            ->setAreaCode('adminhtml');

        // Reading the category name
        $categoryName = $input->getArgument(self::CATEGORY_NAME_ARGUMENT);
        if ($categoryName) {
            $category
                = $this->objectManager->get(\Epoint\SwisspostApi\Model\Api\Category::class);
            $categories[] = $category->load($categoryName);
        } else {
            $categoryList
                = $this->objectManager->get(\Epoint\SwisspostApi\Model\Api\Lists\Category::class);
            $categories = $categoryList->search();
        }
        $importer
            = $this->objectManager->get(\Epoint\SwisspostCatalog\Service\Category::class);
        if ($categories) {
            $importer->run($categories);
            $output->writeln(sprintf(__('Swisspost API load product categories request successful, count: %s'),
                count($categories)));
        } else {
            $output->writeln(sprintf(__('Swisspost API load product categories result no values')));
        }
    }
}

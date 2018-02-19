<?php

namespace Epoint\SwisspostCatalog\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use \Magento\Framework\App\State as AppState;
use \Epoint\SwisspostApi\Model\Api\Category as CategoryApiModel;
use \Epoint\SwisspostApi\Model\Api\Lists\Category as CategoryApiList;
use \Epoint\SwisspostCatalog\Service\Category as CategoryService;

class getProductCategoriesCommand extends Command
{

    /**
     * Category argument
     */
    const CATEGORY_NAME_ARGUMENT = 'category';

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var \Epoint\SwisspostApi\Model\Api\Category
     */
    private $categoryApiModel;

    /**
     * @var \Epoint\SwisspostApi\Model\Api\Lists\Category
     */
    private $categoryApiList;

    /**
     * @var \Epoint\SwisspostCatalog\Service\Category
     */
    private $categoryService;

    /**
     * getProductCategoriesCommand constructor.
     *
     * @param \Magento\Framework\App\State                  $appState
     * @param \Epoint\SwisspostApi\Model\Api\Category       $categoryApiModel
     * @param \Epoint\SwisspostApi\Model\Api\Lists\Category $categoryApiList
     * @param \Epoint\SwisspostCatalog\Service\Category     $categoryService
     */
    public function __construct(
        AppState $appState,
        CategoryApiModel $categoryApiModel,
        CategoryApiList $categoryApiList,
        CategoryService $categoryService
    ) {
        $this->appState = $appState;
        $this->categoryApiModel = $categoryApiModel;
        $this->categoryApiList = $categoryApiList;
        $this->categoryService = $categoryService;
        parent::__construct();
    }

    /**
     * Implement configure method.
     */
    protected function configure()
    {
        $this->setName('epoint-swisspostapi:getProductCategories')
            ->setDescription(__('Run getProductsCategories'))
            ->setDefinition(
                [
                    new InputArgument(
                        self::CATEGORY_NAME_ARGUMENT,
                        InputArgument::OPTIONAL,
                        'Category'
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

        // Reading the category name
        $categoryName = $input->getArgument(self::CATEGORY_NAME_ARGUMENT);
        if ($categoryName) {
            $categories[] = $this->categoryApiModel->load($categoryName);
        } else {
            $categories = $this->categoryApiList->search();
        }
        if ($categories) {
            $this->categoryService->run($categories);
            $output->writeln(
                sprintf(
                    __('Swisspost API load product categories request successful, count: %s'),
                    count($categories)
                )
            );
        } else {
            $output->writeln(sprintf(__('Swisspost API load product categories result no values')));
        }
    }
}

<?php

namespace Epoint\SwisspostCatalog\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Epoint\SwisspostApi\Model\Api\Lists\Product as ProductApiList;
use Epoint\SwisspostCatalog\Model\MediaFactory;
use \Magento\Framework\App\State as AppState;

class getProductsCommand extends Command
{
    /**
     * Product argument
     */
    const PRODUCT_ARGUMENT = 'sku';

    /**
     * @var \Epoint\SwisspostApi\Model\Api\Lists\Product
     */
    private $productApiList;

    /**
     * @var \Epoint\SwisspostCatalog\Model\MediaFactory
     */
    private $mediaFactory;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * getProductsCommand constructor.
     *
     * @param \Epoint\SwisspostApi\Model\Api\Lists\Product $productApiList
     * @param \Epoint\SwisspostCatalog\Model\MediaFactory  $mediaFactory
     * @param \Magento\Framework\App\State                 $appState
     */
    public function __construct(
        ProductApiList $productApiList,
        MediaFactory $mediaFactory,
        AppState $appState
    ) {
        $this->productApiList = $productApiList;
        $this->mediaFactory = $mediaFactory;
        $this->appState = $appState;
        parent::__construct();
    }

    /**
     * Implement configure method.
     */
    protected function configure()
    {
        $this->setName('epoint-swisspostapi:getProducts')
            ->setDescription(__('Run getProducts'))
            ->setDefinition(
                [
                    new InputArgument(
                        self::PRODUCT_ARGUMENT,
                        InputArgument::OPTIONAL,
                        'Products'
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

        $productSKU = $input->getArgument(self::PRODUCT_ARGUMENT);
        $filter = [];
        if ($productSKU) {
            $filter = ['filters' => ['product_code = ' . $productSKU]];
        }
        $items = $this->productApiList->search($filter);
        if ($items) {
            foreach ($items as $item) {
                foreach ($item->getImages() as $image) {
                    $media = $this->mediaFactory->create($image);
                }
            }
            $output->writeln(sprintf(__('Swisspost API load products request successful, result count: %s'), count($items)));
        } else {
            $output->writeln(sprintf(__('Swisspost API load products result no values')));
        }
    }
}

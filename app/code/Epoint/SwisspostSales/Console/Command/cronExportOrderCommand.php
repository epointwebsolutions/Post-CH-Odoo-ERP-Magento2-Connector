<?php

namespace Epoint\SwisspostSales\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class cronExportOrderCommand extends Command {

    /**
     * Name argument
     */
    const ORDER_ID_ARGUMENT = 'order';

    /**
     * @var \Magento\Framework\App\ObjectManager $_objectManager
     */
    private $_objectManager;

    /**
     * Implement configure method.
     */
    protected function configure() {
        $this->setName('epoint-swisspostapi:cronExportOrder')
            ->setDescription(__('Run cronExportOrderCommand for all orders'))
            ->setDefinition([
                 ]
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
        // Set area code.
        $this->_objectManager->get('Magento\Framework\App\State')->setAreaCode('backend');

        $crontab = $this->_objectManager->create(
            'Epoint\SwisspostSales\Model\Cron\Order'
        );
        /** @var Epoint\SwisspostApi\Model\Api\Lists $itemList */
        $listFactory = $crontab->listFactory();
        /** @var list Epoint\SwisspostApi\Model\Api\ApiDataObject $items */
        $items = $listFactory->search();
        // Run import.
        $crontab->run($items);

        $output->writeln(__('Swisspost export crontab done.'));
    }
}

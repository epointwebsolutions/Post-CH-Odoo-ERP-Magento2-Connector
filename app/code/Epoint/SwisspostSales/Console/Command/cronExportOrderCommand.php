<?php

namespace Epoint\SwisspostSales\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use \Magento\Framework\App\State as AppState;
use \Magento\Framework\ObjectManagerInterface;
use Epoint\SwisspostSales\Model\Cron\OrderFactory as OrderCron;

class cronExportOrderCommand extends Command
{

    /**
     * Name argument
     */
    const ORDER_ID_ARGUMENT = 'order';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var \Epoint\SwisspostSales\Model\Cron\Order
     */
    private $orderCron;

    /**
     * cronExportOrderCommand constructor.
     *
     * @param AppState               $appState
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        AppState $appState,
        ObjectManagerInterface $objectManager,
        OrderCron $orderCron
    ) {
        $this->appState = $appState;
        $this->objectManager = $objectManager;
        $this->orderCron = $orderCron;
        parent::__construct();
    }

    /**
     * Implement configure method.
     */
    protected function configure()
    {
        $this->setName('epoint-swisspostapi:cronExportOrder')
            ->setDescription(__('Run cronExportOrderCommand for all orders'))
            ->setDefinition(
                [
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

        $listFactory = $this->orderCron->listFactory();
        /** @var \Epoint\SwisspostApi\Model\Api\ApiDataObject $items */
        $items = $listFactory->search();
        // Run import.
        $this->orderCron->run($items);

        $output->writeln(__('Swisspost export crontab done.'));
    }
}

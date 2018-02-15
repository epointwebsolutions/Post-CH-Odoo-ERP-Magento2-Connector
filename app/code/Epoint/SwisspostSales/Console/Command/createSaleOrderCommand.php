<?php

namespace Epoint\SwisspostSales\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use \Magento\Framework\ObjectManagerInterface;
use \Magento\Framework\App\State as AppState;
use \Magento\Sales\Model\OrderFactory as SalesOrderModel;
use \Epoint\SwisspostApi\Model\Api\SaleOrder as SaleOrderApiModel;

class createSaleOrderCommand extends Command
{
    /**
     * Name argument
     *
     * @const ORDER_ID_ARGUMENT
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
     * @var \Magento\Sales\Model\Order
     */
    private $salesOrderModel;

    /**
     * @var \Epoint\SwisspostApi\Model\Api\SaleOrder
     */
    private $saleOrderApiModel;

    public function __construct(
        ObjectManagerInterface $objectManager,
        AppState $appState,
        SalesOrderModel $salesOrderModel,
        SaleOrderApiModel $saleOrderApiModel
    ) {
        $this->objectManager = $objectManager;
        $this->appState = $appState;
        $this->salesOrderModel = $salesOrderModel;
        $this->saleOrderApiModel = $saleOrderApiModel;
        parent::__construct();
    }

    /**
     * Implement configure method.
     */
    protected function configure()
    {
        $this->setName('epoint-swisspostapi:createSaleOrder')
            ->setDescription(__('Run createSalesOrder for an order'))
            ->setDefinition(
                [
                    new InputArgument(
                        self::ORDER_ID_ARGUMENT,
                        InputArgument::REQUIRED,
                        'Order'
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
        $this->appState->setAreaCode('backend');

        $orderId = $input->getArgument(self::ORDER_ID_ARGUMENT);
        if (!$orderId) {
            throw new \Exception(__('Missing order id.'));
        }
        $localOrder = $this->salesOrderModel->load($orderId);

        if (!$localOrder || !$localOrder->getId()) {
            throw new \Exception(__('Missing order.'));
        }
        $apiOrder = $this->saleOrderApiModel->getInstance($localOrder);

        // Export
        $result = $apiOrder->save();

        if ($result !== null && $result->isOK()) {
            $apiOrder->set('odoo_id', $result->get('odoo_id'));
            $apiOrder->connect($apiOrder->get('order_ref'));
            $output->writeln(
                sprintf(
                    __(
                        'Swisspost API create sales order successful, odoo id: %s'
                    ), $result->get('odoo_id')
                )
            );
        } else {
            if ($result !== null) {
                $output->writeln(
                    sprintf(
                        __(
                            'Swisspost API create sales order fails, debug message: %s'
                        ),
                        $result->getDebugMessage()
                    )
                );
            } else {
                $output->writeln(
                    sprintf(
                        __(
                            'Swisspost API create sales order fails because the order has been sent.'
                        )
                    )
                );
            }
        }
    }
}

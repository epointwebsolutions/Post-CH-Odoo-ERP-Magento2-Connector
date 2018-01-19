<?php

namespace Epoint\SwisspostCustomer\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Magento\Customer\Api\CustomerRepositoryInterface;
use \Magento\Framework\ObjectManagerInterface;

class searchReadAccountCommand extends Command
{
  /**
   * Name argument
   */
  const CUSTOMER_ID_ARGUMENT = 'customer';

  /**
   * @var ObjectManagerInterface $objectManager
   */
  private $objectManager;

    /**
     * @var CustomerRepositoryInterface $customerRepositoryInterface
     */
  protected $customerRepositoryInterface;

    /**
   * Implement configure method.
   */
  protected function configure()
  {
    $this->setName('epoint-swisspostapi:searchReadAccount')
      ->setDescription(__('Run searchReadAccount for a customer'))
      ->setDefinition([
        new InputArgument(
          self::CUSTOMER_ID_ARGUMENT,
          InputArgument::REQUIRED,
          'Customer'
        )]
      );

    $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    $this->customerRepositoryInterface = $this->objectManager->get(\Magento\Customer\Api\CustomerRepositoryInterface::class);
  }

  /**
   * Execute command method.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
      $customer_id = $input->getArgument(self::CUSTOMER_ID_ARGUMENT);
      if (!$customer_id) {
          throw new \Exception(__('Missing customer id.'));
      }
      $customer = $this->customerRepositoryInterface->getById($customer_id);
      if (!$customer || !$customer->getId()) {
          throw new \Exception(__('Missing customer.'));
      }
      $account = $this->objectManager->get('Epoint\SwisspostApi\Model\Api\Account')->load($customer);
      print_r($account->getData());
  }
}

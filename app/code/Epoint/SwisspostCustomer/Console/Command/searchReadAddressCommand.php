<?php

namespace Epoint\SwisspostCustomer\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class searchReadAddressCommand extends Command {

  /**
   * Name argument
   */
  const ADDRESS_ID_ARGUMENT = 'address';

  /**
   * @var \Magento\Framework\App\ObjectManager $_objectManager
   */
  private $_objectManager;

  /**
   * Implement configure method.
   */
  protected function configure() {
    $this->setName('epoint-swisspostapi:searchReadAddress')
      ->setDescription(__('Run searchReadAddressAddress for a customer'))
      ->setDefinition([
        new InputArgument(
          self::ADDRESS_ID_ARGUMENT,
          InputArgument::REQUIRED,
          'Address'
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
    $address_id = $input->getArgument(self::ADDRESS_ID_ARGUMENT);
    if(!$address_id){
      throw new \Exception(__('Missing address id.'));
    }

    $localAddress = $this->_objectManager->create(
      'Magento\Customer\Model\Address'
    )->load($address_id);

    if(!$localAddress && !$localAddress->getId()){
      throw new \Exception(__('Missing address.'));
    }
    $address = $this->_objectManager->get('Epoint\SwisspostApi\Model\Api\Address')->load($localAddress);
    if($address && $address->get('odoo_id')){
      $output->writeln(sprintf(__('Swisspost API search read address successful, odoo id: %s'), $address->get('odoo_id')));
    }else{
      $output->writeln(sprintf(__('Swisspost API search read address return null')));
    }
  }
}

<?php

namespace Epoint\SwisspostApi\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectCommand extends Command
{
    /**
     * @var \Magento\Framework\App\ObjectManager $_objectManager
     */
    private $_objectManager;

    /**
     * Implement configure method.
     */
    protected function configure()
    {
        $this->setName('epoint-swisspostapi:connect')->setDescription(
            'Check API authentication parameters'
        );
        $this->_objectManager
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
        if ($session_id = $this->_objectManager->get(
            \Epoint\SwisspostApi\Helper\Resource::class
        )->sessionAuthenticate()
            ->get('session_id')
        ) {
            $output->writeln(
                sprintf(
                    __('Swisspost API auth is working, session id: %s'),
                    $session_id
                )
            );
        } else {
            $output->writeln(__('Swisspost API auth is not working.'));
        }
    }
}

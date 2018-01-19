<?php
/**
 * Copyright © 2013-2017 Epoint, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Epoint\SwisspostApi\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\ObjectManagerInterface;
use Epoint\SwisspostApi\Helper\LoggerTrait;
use \Psr\Log\LoggerInterface;

/**
 * Customer log observer.
 */
abstract class BaseObserver implements ObserverInterface
{
    /**
     * Trait logger
     */
    use LoggerTrait;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * BaseObserver constructor.
     *
     * @param LoggerInterface $logger
     * @param ObjectManagerInterface   $objectManager
     */
    public function __construct(
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager
    )
    {
        $this->logger = $logger;
        $this->objectManager = $objectManager;
    }
}

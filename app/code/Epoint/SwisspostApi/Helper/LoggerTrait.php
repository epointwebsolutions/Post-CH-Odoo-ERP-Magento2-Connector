<?php

namespace Epoint\SwisspostApi\Helper;

trait LoggerTrait
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Logging exception.
     *
     * @param \Exception $e
     */
    protected function logException(\Exception $e)
    {

        if (isset($this->logger)) {
            $this->logger->critical(
                sprintf(
                    __('Exception: %s, file %s, line: %s, debug: %s'),
                    $e->getMessage(), $e->getFile(), $e->getLine(),
                    $e->getTraceAsString()
                )
            );
        }
    }

    /**
     * Detailed debug information.
     * @param $message
     */
    protected function debug($message)
    {
        if (isset($this->logger)) {
            $this->logger->debug($message);
        }
    }

    /**
     * Exceptional occurrences that are not errors.
     * @param $message
     */
    protected function warning($message)
    {
        if (isset($this->logger)) {
            $this->logger->warning($message);
        }
    }
}


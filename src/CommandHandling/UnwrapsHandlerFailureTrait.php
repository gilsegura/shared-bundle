<?php

declare(strict_types=1);

namespace SharedBundle\CommandHandling;

use Symfony\Component\Messenger\Exception\HandlerFailedException;

trait UnwrapsHandlerFailureTrait
{
    /**
     * @throws \Throwable
     */
    private function unwrap(HandlerFailedException $exception): never
    {
        $previous = $exception;

        while ($previous instanceof HandlerFailedException) {
            $previous = $previous->getPrevious();
        }

        if (!$previous instanceof \Throwable) {
            throw new MessengerBusException();
        }

        throw $previous;
    }
}

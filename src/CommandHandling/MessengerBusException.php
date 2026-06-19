<?php

declare(strict_types=1);

namespace SharedBundle\CommandHandling;

use Shared\Exception\InfrastructureException;

/**
 * Raised when a Messenger bus dispatch fails for a reason other than a
 * handler's own exception.
 */
final class MessengerBusException extends InfrastructureException
{
    public function __construct(
        string $message = 'An error occurred while processing your message.',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    public static function throwable(\Throwable $e): self
    {
        return new self($e->getMessage(), $e);
    }
}

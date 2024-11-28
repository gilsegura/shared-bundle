<?php

declare(strict_types=1);

namespace SharedBundle\CommandHandling;

final class MessengerBusException extends \Exception
{
    public function __construct(
        string $message = 'An error occurred while processing your message.',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function throwable(\Throwable $e): self
    {
        return new self($e->getMessage(), $e->getCode(), $e);
    }
}

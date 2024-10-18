<?php

declare(strict_types=1);

namespace SharedBundle\CommandHandling;

use Shared\CommandHandling\CommandBusInterface;
use Shared\CommandHandling\CommandInterface;
use SharedBundle\Exception\MessageBusExceptionTrait;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class MessengerCommandBus implements CommandBusInterface
{
    use MessageBusExceptionTrait;

    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @throws \Throwable
     */
    #[\Override]
    public function handle(CommandInterface $command): void
    {
        try {
            $this->messageBus->dispatch($command);
        } catch (HandlerFailedException $handlerFailedException) {
            $this->throwException($handlerFailedException);
        }
    }
}

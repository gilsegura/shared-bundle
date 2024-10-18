<?php

declare(strict_types=1);

namespace SharedBundle\EventHandling;

use Shared\Domain\DomainMessage;
use SharedBundle\Exception\MessageBusExceptionTrait;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class MessengerAsyncEventBus
{
    use MessageBusExceptionTrait;

    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function handle(DomainMessage $message): void
    {
        try {
            $this->messageBus->dispatch($message, [
                new AmqpStamp($message->type),
            ]);
        } catch (HandlerFailedException $handlerFailedException) {
            $this->throwException($handlerFailedException);
        }
    }
}

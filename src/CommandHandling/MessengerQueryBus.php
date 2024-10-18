<?php

declare(strict_types=1);

namespace SharedBundle\CommandHandling;

use Shared\CommandHandling\Collection;
use Shared\CommandHandling\Item;
use Shared\CommandHandling\QueryBusInterface;
use Shared\CommandHandling\QueryInterface;
use SharedBundle\Exception\MessageBusExceptionTrait;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final readonly class MessengerQueryBus implements QueryBusInterface
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
    public function ask(QueryInterface $query): Item|Collection
    {
        try {
            $envelope = $this->messageBus->dispatch($query);

            /** @var HandledStamp $stamp */
            $stamp = $envelope->last(HandledStamp::class);

            return $stamp->getResult();
        } catch (HandlerFailedException $handlerFailedException) {
            $this->throwException($handlerFailedException);
        }
    }
}

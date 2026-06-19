<?php

declare(strict_types=1);

namespace SharedBundle\CommandHandling;

use Shared\CommandHandling\QueryBusInterface;
use Shared\CommandHandling\QueryInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Query bus backed by Symfony Messenger. Dispatches a query, returns the
 * handler's result and unwraps HandlerFailedException to the real domain
 * exception.
 */
final readonly class MessengerQueryBus implements QueryBusInterface
{
    use UnwrapsHandlerFailureTrait;

    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @template TResult
     *
     * @param QueryInterface<TResult> $query
     *
     * @return TResult
     */
    #[\Override]
    public function __invoke(QueryInterface $query): mixed
    {
        try {
            $envelope = $this->messageBus->dispatch($query);

            $stamp = $envelope->last(HandledStamp::class);

            if (!$stamp instanceof HandledStamp) {
                throw new MessengerBusException();
            }

            /* @var TResult */
            return $stamp->getResult();
        } catch (HandlerFailedException $e) {
            $this->unwrap($e);
        } catch (\Throwable $e) {
            throw MessengerBusException::throwable($e);
        }
    }
}

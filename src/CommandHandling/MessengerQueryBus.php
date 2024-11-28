<?php

declare(strict_types=1);

namespace SharedBundle\CommandHandling;

use ProxyAssert\Assertion;
use Serializer\SerializableInterface;
use Shared\CommandHandling\QueryBusInterface;
use Shared\CommandHandling\QueryInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final readonly class MessengerQueryBus implements QueryBusInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    #[\Override]
    public function __invoke(QueryInterface $query): SerializableInterface
    {
        try {
            $envelope = $this->messageBus->dispatch($query);

            /** @var HandledStamp $stamp */
            $stamp = $envelope->last(HandledStamp::class);

            $result = $stamp->getResult();

            Assertion::isInstanceOf($result, SerializableInterface::class);

            return $result;
        } catch (HandlerFailedException $e) {
            while ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious();
            }

            if (!$e instanceof \Throwable) {
                throw new MessengerBusException();
            }

            throw $e;
        } catch (\Throwable $e) {
            throw MessengerBusException::throwable($e);
        }
    }
}

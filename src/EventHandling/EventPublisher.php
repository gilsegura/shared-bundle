<?php

declare(strict_types=1);

namespace SharedBundle\EventHandling;

use Shared\Domain\DomainMessage;
use Shared\EventHandling\EventListenerInterface;
use SharedBundle\CommandHandling\MessengerBusException;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

final class EventPublisher implements EventSubscriberInterface, EventListenerInterface
{
    /** @var DomainMessage[] */
    private array $messages = [];

    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'publish',
            ConsoleEvents::TERMINATE => 'publish',
        ];
    }

    public function publish(): void
    {
        foreach ($this->messages as $message) {
            try {
                $this->messageBus->dispatch($message, [
                    new AmqpStamp($message->type),
                ]);
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

    #[\Override]
    public function __invoke(DomainMessage $message): void
    {
        $this->messages[] = $message;
    }
}

<?php

declare(strict_types=1);

namespace SharedBundle\EventHandling;

use Shared\Domain\DomainMessage;
use Shared\EventHandling\EventListenerInterface;
use SharedBundle\CommandHandling\MessengerBusException;
use SharedBundle\CommandHandling\UnwrapsHandlerFailureTrait;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBusInterface;

final class EventPublisher implements EventSubscriberInterface, EventListenerInterface
{
    use UnwrapsHandlerFailureTrait;

    /** @var DomainMessage[] */
    private array $messages = [];

    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'publish',
            ConsoleEvents::TERMINATE => 'publish',
            WorkerStoppedEvent::class => 'publish',
        ];
    }

    public function publish(): void
    {
        while ([] !== $this->messages) {
            $message = array_shift($this->messages);

            try {
                $this->messageBus->dispatch($message);
            } catch (NoHandlerForMessageException) {
                // An asynchronous event with no subscribers is not an error.
            } catch (HandlerFailedException $e) {
                $this->unwrap($e);
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

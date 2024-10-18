<?php

declare(strict_types=1);

namespace SharedBundle\EventHandling;

use Shared\Domain\DomainMessage;
use Shared\EventHandling\EventListenerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class AsyncEventPublisher implements EventSubscriberInterface, EventListenerInterface
{
    /** @var DomainMessage[] */
    private array $messages = [];

    public function __construct(
        private readonly MessengerAsyncEventBus $bus,
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

    /**
     * @throws \Throwable
     */
    public function publish(): void
    {
        if ([] === $this->messages) {
            return;
        }

        foreach ($this->messages as $message) {
            $this->bus->handle($message);
        }
    }

    #[\Override]
    public function handle(DomainMessage $message): void
    {
        $this->messages[] = $message;
    }
}

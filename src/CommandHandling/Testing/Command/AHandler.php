<?php

declare(strict_types=1);

namespace SharedBundle\CommandHandling\Testing\Command;

use Shared\CommandHandling\CommandHandlerInterface;
use Shared\Domain\DomainEventInterface;
use Shared\Domain\DomainEventStream;
use Shared\Domain\DomainMessage;
use Shared\Domain\Metadata;
use Shared\Domain\Uuid;
use Shared\EventHandling\EventBusInterface;

final readonly class AHandler implements CommandHandlerInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(ACommand $command): void
    {
        $this->eventBus->publish(new DomainEventStream(DomainMessage::record(
            new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac'),
            0,
            Metadata::empty(),
            new EventWasOccurred()
        )));
    }
}

final readonly class EventWasOccurred implements DomainEventInterface
{
    #[\Override]
    public static function deserialize(array $data): self
    {
        return new self();
    }

    #[\Override]
    public function serialize(): array
    {
        return [];
    }
}

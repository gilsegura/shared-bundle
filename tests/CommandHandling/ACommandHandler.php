<?php

declare(strict_types=1);

namespace SharedBundle\Tests\CommandHandling;

use Shared\CommandHandling\CommandHandlerInterface;
use Shared\Domain\DomainEventStream;
use Shared\Domain\DomainMessage;
use Shared\Domain\Metadata;
use Shared\Domain\Uuid;
use Shared\EventHandling\EventBusInterface;

/**
 * @implements CommandHandlerInterface<ACommand>
 */
final readonly class ACommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(ACommand $command): void
    {
        $this->eventBus->__invoke(new DomainEventStream(DomainMessage::record(
            new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac'),
            0,
            Metadata::empty(),
            new EventWasOccurred()
        )));
    }
}

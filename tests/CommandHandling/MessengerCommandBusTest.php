<?php

declare(strict_types=1);

namespace SharedBundle\Tests\CommandHandling;

use Shared\CommandHandling\CommandHandlerInterface;
use Shared\CommandHandling\CommandInterface;
use Shared\Domain\DomainEventInterface;
use Shared\Domain\DomainEventStream;
use Shared\Domain\DomainMessage;
use Shared\Domain\Metadata;
use Shared\Domain\Uuid;
use Shared\EventHandling\EventBusInterface;

final class MessengerCommandBusTest extends AbstractApplicationTestCase
{
    public function test_must_throw_exception_when_handling_command(): void
    {
        self::expectException(\Exception::class);

        $this->handle(new ThrowableCommand());

        $this->fireTerminateEvents();
    }

    public function test_must_handle_command(): void
    {
        $this->handle(new ACommand());

        $this->fireTerminateEvents();

        self::assertTrue(true);
    }
}

final readonly class ThrowableCommand implements CommandInterface
{
}

final readonly class ThrowableCommandHandler implements CommandHandlerInterface
{
    public function __invoke(ThrowableCommand $command): void
    {
        throw new \Exception();
    }
}

final readonly class ACommand implements CommandInterface
{
}

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

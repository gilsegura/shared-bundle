<?php

declare(strict_types=1);

namespace SharedBundle\Tests\EventHandling;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shared\Domain\DomainEventInterface;
use Shared\Domain\DomainMessage;
use Shared\Domain\Metadata;
use Shared\Domain\Uuid;
use SharedBundle\EventHandling\MessengerAsyncEventBus;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerAsyncEventBusTest extends TestCase
{
    public function test_must_throw_exception_when_handling_message(): void
    {
        self::expectException(\Exception::class);

        /** @var MessageBusInterface|MockObject $messageBus */
        $messageBus = self::createMock(MessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatch')
            ->willThrowException(new HandlerFailedException(new Envelope(new \stdClass()), [new \Exception()]));

        $bus = new MessengerAsyncEventBus($messageBus);

        $bus->handle(DomainMessage::record(
            new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac'),
            0,
            Metadata::empty(),
            new AsyncEventWasOccurred()
        ));
    }
}

final readonly class AsyncEventWasOccurred implements DomainEventInterface
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

<?php

declare(strict_types=1);

namespace SharedBundle\Tests\CommandHandling;

use PHPUnit\Framework\TestCase;
use Serializer\SerializableInterface;
use Shared\CommandHandling\QueryInterface;
use SharedBundle\CommandHandling\MessengerBusException;
use SharedBundle\CommandHandling\MessengerQueryBus;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class MessengerQueryBusUnitTest extends TestCase
{
    public function test_must_return_handler_result(): void
    {
        $query = self::createStub(QueryInterface::class);
        $result = self::createStub(SerializableInterface::class);

        $envelope = new Envelope($query, [new HandledStamp($result, 'handler')]);

        $messageBus = self::createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturn($envelope);

        $returned = new MessengerQueryBus($messageBus)->__invoke($query);

        self::assertSame($result, $returned);
    }

    public function test_must_return_null_when_handler_returns_null(): void
    {
        $query = self::createStub(QueryInterface::class);

        $envelope = new Envelope($query, [new HandledStamp(null, 'handler')]);

        $messageBus = self::createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturn($envelope);

        $returned = new MessengerQueryBus($messageBus)->__invoke($query);

        self::assertNull($returned);
    }

    public function test_must_throw_when_no_handled_stamp(): void
    {
        $query = self::createStub(QueryInterface::class);

        $messageBus = self::createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')->willReturn(new Envelope($query));

        $this->expectException(MessengerBusException::class);

        new MessengerQueryBus($messageBus)->__invoke($query);
    }

    public function test_must_unwrap_handler_failure_to_root_cause(): void
    {
        $query = self::createStub(QueryInterface::class);

        $root = new \RuntimeException('root cause');

        $messageBus = self::createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willThrowException(new HandlerFailedException(new Envelope($query), [$root]));

        $this->expectExceptionObject($root);

        new MessengerQueryBus($messageBus)->__invoke($query);
    }

    public function test_must_wrap_unexpected_throwable(): void
    {
        $query = self::createStub(QueryInterface::class);

        $messageBus = self::createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willThrowException(new \RuntimeException('boom'));

        $this->expectException(MessengerBusException::class);

        new MessengerQueryBus($messageBus)->__invoke($query);
    }
}

<?php

declare(strict_types=1);

namespace SharedBundle\Tests\CommandHandling;

use PHPUnit\Framework\TestCase;
use Shared\CommandHandling\CommandInterface;
use SharedBundle\CommandHandling\MessengerBusException;
use SharedBundle\CommandHandling\MessengerCommandBus;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerCommandBusUnitTest extends TestCase
{
    public function test_must_dispatch_command(): void
    {
        $command = self::createStub(CommandInterface::class);

        $messageBus = self::createMock(MessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatch')
            ->with($command)
            ->willReturn(new Envelope($command));

        new MessengerCommandBus($messageBus)->__invoke($command);
    }

    public function test_must_unwrap_handler_failure_to_root_cause(): void
    {
        $command = self::createStub(CommandInterface::class);

        $root = new \RuntimeException('root cause');

        $messageBus = self::createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willThrowException(new HandlerFailedException(new Envelope($command), [$root]));

        $this->expectExceptionObject($root);

        new MessengerCommandBus($messageBus)->__invoke($command);
    }

    public function test_must_wrap_unexpected_throwable(): void
    {
        $command = self::createStub(CommandInterface::class);

        $messageBus = self::createStub(MessageBusInterface::class);
        $messageBus->method('dispatch')
            ->willThrowException(new \RuntimeException('boom'));

        $this->expectException(MessengerBusException::class);

        new MessengerCommandBus($messageBus)->__invoke($command);
    }
}

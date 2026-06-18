<?php

declare(strict_types=1);

namespace SharedBundle\Tests\EventHandling;

use PHPUnit\Framework\TestCase;
use Shared\Domain\DomainMessage;
use Shared\Domain\Metadata;
use Shared\Domain\Uuid;
use SharedBundle\EventHandling\UnwrapDomainMessageMiddleware;
use SharedBundle\SharedBundle;
use SharedBundle\Tests\CommandHandling\EventWasOccurred;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;

final class UnwrapDomainMessageMiddlewareTest extends TestCase
{
    public function test_must_unwrap_payload_when_received_from_transport(): void
    {
        $payload = new EventWasOccurred();

        $envelope = new Envelope(
            $this->domainMessage($payload),
            [new ReceivedStamp('async')]
        );

        $handled = $this->handleThrough($envelope);

        self::assertSame($payload, $handled->getMessage());
    }

    public function test_must_not_unwrap_when_dispatched_to_transport(): void
    {
        $domainMessage = $this->domainMessage(new EventWasOccurred());

        $envelope = new Envelope($domainMessage);

        $handled = $this->handleThrough($envelope);

        self::assertSame($domainMessage, $handled->getMessage());
    }

    public function test_must_preserve_stamps_when_unwrapping(): void
    {
        $envelope = new Envelope(
            $this->domainMessage(new EventWasOccurred()),
            [
                new ReceivedStamp('async'),
                new BusNameStamp(SharedBundle::EVENT_BUS),
                new TransportMessageIdStamp('message-id'),
            ]
        );

        $handled = $this->handleThrough($envelope);

        self::assertNotNull($handled->last(ReceivedStamp::class));
        self::assertNotNull($handled->last(BusNameStamp::class));
        self::assertNotNull($handled->last(TransportMessageIdStamp::class));
    }

    private function domainMessage(EventWasOccurred $payload): DomainMessage
    {
        return DomainMessage::record(
            new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac'),
            0,
            Metadata::empty(),
            $payload
        );
    }

    private function handleThrough(Envelope $envelope): Envelope
    {
        $next = self::createStub(MiddlewareInterface::class);
        $next->method('handle')->willReturnArgument(0);

        $stack = self::createStub(StackInterface::class);
        $stack->method('next')->willReturn($next);

        return new UnwrapDomainMessageMiddleware()->handle($envelope, $stack);
    }
}

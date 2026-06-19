<?php

declare(strict_types=1);

namespace SharedBundle\EventHandling;

use Shared\Domain\DomainMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Messenger middleware on the async event bus that unwraps a DomainMessage
 * and forwards its payload to the regular message handlers.
 */
final readonly class UnwrapDomainMessageMiddleware implements MiddlewareInterface
{
    #[\Override]
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        if (
            $message instanceof DomainMessage
            && $envelope->last(ReceivedStamp::class) instanceof StampInterface
        ) {
            $stamps = [];

            foreach ($envelope->all() as $stampsByType) {
                foreach ($stampsByType as $stamp) {
                    $stamps[] = $stamp;
                }
            }

            $envelope = new Envelope($message->payload, $stamps);
        }

        return $stack->next()->handle($envelope, $stack);
    }
}

<?php

declare(strict_types=1);

namespace SharedBundle\Tests\EventSourcing;

use Shared\Domain\DomainMessage;
use Shared\Upcasting\UpcasterInterface;

/**
 * No-op upcaster fixture: returns the message unchanged. Used only to assert the
 * pass wires the upcaster sequence into the chain.
 */
final readonly class AThingUpcaster implements UpcasterInterface
{
    #[\Override]
    public function __invoke(DomainMessage $message): DomainMessage
    {
        return $message;
    }
}

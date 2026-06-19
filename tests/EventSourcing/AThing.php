<?php

declare(strict_types=1);

namespace SharedBundle\Tests\EventSourcing;

use Shared\Domain\Uuid;
use Shared\EventSourcing\AbstractEventSourcedAggregateRoot;

/**
 * Minimal aggregate root used only as a reflection target for the
 * AggregateRootPass test; it is never instantiated or replayed.
 */
final class AThing extends AbstractEventSourcedAggregateRoot
{
    #[\Override]
    public function id(): Uuid
    {
        return new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac');
    }
}

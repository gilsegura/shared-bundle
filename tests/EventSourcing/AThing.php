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
        return Uuid::uuid4();
    }
}

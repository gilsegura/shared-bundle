<?php

declare(strict_types=1);

namespace SharedBundle\Tests\EventSourcing;

use Shared\EventSourcing\AbstractEventSourcingRepository;
use SharedBundle\EventSourcing\Attribute\AggregateRoot;
use SharedBundle\EventSourcing\Attribute\SnapshotConfig;

/**
 * @template-extends AbstractEventSourcingRepository<AThing>
 */
#[AggregateRoot(AThing::class, snapshot: new SnapshotConfig(every: 100))]
final readonly class AThingWithSnapshotRepository extends AbstractEventSourcingRepository
{
}

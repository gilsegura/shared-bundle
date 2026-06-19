<?php

declare(strict_types=1);

namespace SharedBundle\Tests\EventSourcing;

use Shared\EventSourcing\AbstractEventSourcingRepository;
use SharedBundle\EventSourcing\Attribute\AggregateRoot;

/**
 * @template-extends AbstractEventSourcingRepository<AThing>
 */
#[AggregateRoot(AThing::class, upcasters: [AThingUpcaster::class])]
final readonly class AThingWithUpcastersRepository extends AbstractEventSourcingRepository
{
}

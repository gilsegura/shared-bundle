<?php

declare(strict_types=1);

namespace SharedBundle\Tests\EventSourcing;

use Shared\EventSourcing\AbstractEventSourcingRepository;
use SharedBundle\EventSourcing\Attribute\AggregateRoot;

/**
 * @template-extends AbstractEventSourcingRepository<AThing>
 */
#[AggregateRoot(AThing::class)]
final readonly class AThingRepository extends AbstractEventSourcingRepository
{
}

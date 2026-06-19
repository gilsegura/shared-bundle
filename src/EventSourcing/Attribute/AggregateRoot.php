<?php

declare(strict_types=1);

namespace SharedBundle\EventSourcing\Attribute;

use Shared\Upcasting\UpcasterInterface;

/**
 * Marks a repository extending AbstractEventSourcingRepository and declares the
 * aggregate root it manages, plus the optional sequence of upcasters its event
 * history needs. A compiler pass injects the event store (always wrapped in an
 * UpcastingEventStore with these upcasters, in order), the event bus, the stream
 * decorator and an aggregate factory — so the repository needs no constructor.
 *
 * With no upcasters the chain is empty and events pass through unchanged:
 *
 *     #[AggregateRoot(User::class)]
 *
 * With upcasters, the array order is the order of the sequence:
 *
 *     #[AggregateRoot(User::class, upcasters: [UserV1ToV2::class, UserV2ToV3::class])]
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AggregateRoot
{
    /**
     * @param class-string                           $aggregateRoot
     * @param array<class-string<UpcasterInterface>> $upcasters     ordered upcaster sequence
     */
    public function __construct(
        public string $aggregateRoot,
        public array $upcasters = [],
    ) {
    }
}

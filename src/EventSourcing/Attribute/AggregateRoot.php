<?php

declare(strict_types=1);

namespace SharedBundle\EventSourcing\Attribute;

use Shared\Upcasting\UpcasterInterface;

/**
 * Marks a repository extending AbstractEventSourcingRepository and declares the
 * aggregate root it manages, plus the optional sequence of upcasters its event
 * history needs and an optional snapshot policy. A compiler pass injects the
 * aggregate loader (the event-store loader, decorated with snapshotting when a
 * snapshot config is given), the event store, the event bus, the stream
 * decorator and — when snapshotting — a snapshotter, so the repository needs no
 * constructor.
 *
 * With no upcasters the chain is empty and events pass through unchanged:
 *
 *     #[AggregateRoot(User::class)]
 *
 * With upcasters, the array order is the order of the sequence:
 *
 *     #[AggregateRoot(User::class, upcasters: [UserV1ToV2::class, UserV2ToV3::class])]
 *
 * With snapshotting, loads resume from the latest snapshot and saves capture one
 * per the policy:
 *
 *     #[AggregateRoot(User::class, snapshot: new SnapshotConfig(every: 100))]
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
        public ?SnapshotConfig $snapshot = null,
    ) {
    }
}

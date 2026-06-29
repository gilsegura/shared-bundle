<?php

declare(strict_types=1);

namespace SharedBundle\EventSourcing\Attribute;

use Shared\Snapshotting\SnapshotStrategyInterface;

/**
 * Declares the snapshot policy for an aggregate inside #[AggregateRoot]. By
 * default it builds an event-count strategy that snapshots every $every events;
 * a custom strategy service can be named instead, in which case $every is
 * ignored and the referenced service is used as-is.
 *
 *     #[AggregateRoot(Thing::class, snapshot: new SnapshotConfig(every: 100))]
 *
 *     #[AggregateRoot(Thing::class, snapshot: new SnapshotConfig(
 *         strategy: TimeBasedSnapshotStrategy::class,
 *     ))]
 */
final readonly class SnapshotConfig
{
    /**
     * @param class-string<SnapshotStrategyInterface>|null $strategy a custom
     *                                                               strategy service id; when null the event-count strategy is used
     */
    public function __construct(
        public int $every = 0,
        public ?string $strategy = null,
    ) {
    }
}

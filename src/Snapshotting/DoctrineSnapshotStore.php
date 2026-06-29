<?php

declare(strict_types=1);

namespace SharedBundle\Snapshotting;

use Shared\Criteria;
use Shared\Domain\Uuid;
use Shared\EventSourcing\AggregateRootInterface;
use Shared\Snapshotting\CorruptSnapshotException;
use Shared\Snapshotting\Snapshot;
use Shared\Snapshotting\SnapshotStoreInterface;
use SharedBundle\Persistence\Doctrine\AbstractObjectManager;
use SharedBundle\Persistence\Doctrine\Attribute\ObjectManager;
use SharedBundle\Persistence\Doctrine\ObjectManagerException;

/**
 * Doctrine-backed snapshot store. Persists the Snapshot value object directly —
 * its id, playhead and the aggregate stored through the aggregate_root DBAL type
 * (native serialize) — in a single global table, keeping the latest snapshot per
 * aggregate id. A stored snapshot whose aggregate cannot be restored surfaces as
 * a corrupt snapshot, and the caller falls back to a full replay.
 *
 * Its constructor arguments come from #[ObjectManager(Snapshot::class)].
 *
 * @template TAggregate of AggregateRootInterface
 *
 * @template-extends AbstractObjectManager<string, Snapshot<TAggregate>>
 *
 * @implements SnapshotStoreInterface<TAggregate>
 */
#[ObjectManager(Snapshot::class)]
final readonly class DoctrineSnapshotStore extends AbstractObjectManager implements SnapshotStoreInterface
{
    #[\Override]
    public function load(Uuid $id): ?Snapshot
    {
        try {
            $snapshots = $this->search(new Criteria\AndX(new Criteria\EqId($id)));
        } catch (ObjectManagerException $e) {
            throw CorruptSnapshotException::fromThrowable($e);
        }

        return $snapshots[\array_key_first($snapshots)] ?? null;
    }

    #[\Override]
    public function save(Snapshot $snapshot): void
    {
        try {
            // One row per aggregate: a readonly Snapshot cannot be mutated in
            // place, so the previous one is removed before the new one is stored,
            // keeping only the latest.
            $existing = $this->load($snapshot->id);

            if ($existing instanceof Snapshot) {
                $this->unregister($existing);
            }

            $this->register($snapshot);
        } catch (ObjectManagerException $e) {
            throw CorruptSnapshotException::fromThrowable($e);
        }
    }
}

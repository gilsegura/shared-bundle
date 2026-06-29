<?php

declare(strict_types=1);

namespace SharedBundle\Tests\Snapshotting;

use Shared\Domain\Uuid;
use Shared\Snapshotting\Snapshot;
use SharedBundle\Snapshotting\DoctrineSnapshotStore;
use SharedBundle\Tests\EventSourcing\AThing;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

final class DoctrineSnapshotStoreTest extends KernelTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $application = new Application($kernel);

        $application->get('doctrine:schema:drop')
            ->run(new ArrayInput(['--force' => true, '--full-database' => true]), new NullOutput());

        $application->get('doctrine:schema:create')
            ->run(new ArrayInput([]), new NullOutput());
    }

    public function test_must_return_null_when_no_snapshot_exists(): void
    {
        /** @var DoctrineSnapshotStore<AThing> $store */
        $store = self::getContainer()->get(DoctrineSnapshotStore::class);

        self::assertNull($store->load(new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac')));
    }

    public function test_must_round_trip_a_snapshot(): void
    {
        /** @var DoctrineSnapshotStore<AThing> $store */
        $store = self::getContainer()->get(DoctrineSnapshotStore::class);

        $id = new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac');

        $store->save(new Snapshot($id, 41, new AThing()));

        $snapshot = $store->load($id);

        self::assertInstanceOf(Snapshot::class, $snapshot);
        self::assertTrue($id->equals($snapshot->id));
        self::assertSame(41, $snapshot->playhead);
        self::assertInstanceOf(AThing::class, $snapshot->aggregateRoot);
    }

    public function test_must_keep_only_the_latest_snapshot_for_an_aggregate(): void
    {
        /** @var DoctrineSnapshotStore<AThing> $store */
        $store = self::getContainer()->get(DoctrineSnapshotStore::class);

        $id = new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac');

        $store->save(new Snapshot($id, 41, new AThing()));
        $store->save(new Snapshot($id, 99, new AThing()));

        $snapshot = $store->load($id);

        self::assertInstanceOf(Snapshot::class, $snapshot);
        self::assertSame(99, $snapshot->playhead);
    }
}

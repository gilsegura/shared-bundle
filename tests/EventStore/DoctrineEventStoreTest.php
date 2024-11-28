<?php

declare(strict_types=1);

namespace SharedBundle\Tests\EventStore;

use Shared\Criteria;
use Shared\Domain\DomainEventInterface;
use Shared\Domain\DomainEventStream;
use Shared\Domain\DomainMessage;
use Shared\Domain\Metadata;
use Shared\Domain\Uuid;
use Shared\EventStore\CallableEventVisitor;
use Shared\EventStore\StreamAlreadyExistsException;
use Shared\EventStore\StreamNotFoundException;
use SharedBundle\EventStore\DoctrineEventStore;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

final class DoctrineEventStoreTest extends KernelTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $application->get('doctrine:schema:create')
            ->run(new ArrayInput([]), new NullOutput());
    }

    public function test_must_throw_stream_not_found_exception_when_load_stream_from_id(): void
    {
        self::expectException(StreamNotFoundException::class);

        /** @var DoctrineEventStore $eventStore */
        $eventStore = self::getContainer()->get(DoctrineEventStore::class);
        $eventStore->load(new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac'));
    }

    public function test_must_throw_stream_not_found_exception_when_load_stream_from_playhead(): void
    {
        self::expectException(StreamNotFoundException::class);

        /** @var DoctrineEventStore $eventStore */
        $eventStore = self::getContainer()->get(DoctrineEventStore::class);
        $eventStore->load(new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac'), 0);
    }

    public function test_must_throw_playhead_already_exists_exception_when_append_stream(): void
    {
        self::expectException(StreamAlreadyExistsException::class);

        /** @var DoctrineEventStore $eventStore */
        $eventStore = self::getContainer()->get(DoctrineEventStore::class);
        $eventStore->append(new DomainEventStream(DomainMessage::record(
            new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac'),
            0,
            Metadata::empty(),
            new EventStoredWasOccurred()
        )));

        $eventStore->append(new DomainEventStream(DomainMessage::record(
            new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac'),
            0,
            Metadata::empty(),
            new EventStoredWasOccurred()
        )));
    }

    public function test_must_load_stream_from_id(): void
    {
        /** @var DoctrineEventStore $eventStore */
        $eventStore = self::getContainer()->get(DoctrineEventStore::class);
        $eventStore->append(new DomainEventStream(DomainMessage::record(
            new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac'),
            0,
            Metadata::empty(),
            new EventStoredWasOccurred()
        )));

        $stream = $eventStore->load(new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac'));

        self::assertInstanceOf(DomainEventStream::class, $stream);
    }

    public function test_must_load_stream_from_playhead(): void
    {
        /** @var DoctrineEventStore $eventStore */
        $eventStore = self::getContainer()->get(DoctrineEventStore::class);
        $eventStore->append(new DomainEventStream(DomainMessage::record(
            new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac'),
            0,
            Metadata::empty(),
            new EventStoredWasOccurred()
        )));

        $stream = $eventStore->load(new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac'), 0);

        self::assertInstanceOf(DomainEventStream::class, $stream);
    }

    public function test_must_visit_events(): void
    {
        /** @var DoctrineEventStore $eventStore */
        $eventStore = self::getContainer()->get(DoctrineEventStore::class);
        $eventStore->append(new DomainEventStream(
            DomainMessage::record(
                new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac'),
                0,
                Metadata::empty(),
                new EventStoredWasOccurred()
            ),
            DomainMessage::record(
                new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac'),
                1,
                Metadata::empty(),
                new EventStoredWasOccurred()
            ),
            DomainMessage::record(
                new Uuid('e305ff9e-a4ca-4a95-8e12-fb1f83ba40ef'),
                0,
                Metadata::empty(),
                new EventStoredWasOccurred()
            ),
        ));

        $playhead = 0;

        $eventVisitor = new CallableEventVisitor(
            static function (DomainMessage $message) use (&$playhead) {
                self::assertSame($playhead, $message->playhead);
                ++$playhead;
            }
        );

        $eventStore->visitEvents(new Criteria\AndX(new Criteria\EqId(new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac'))), $eventVisitor);
    }
}

final readonly class EventStoredWasOccurred implements DomainEventInterface
{
    #[\Override]
    public static function deserialize(array $data): self
    {
        return new self();
    }

    #[\Override]
    public function serialize(): array
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace SharedBundle\Tests;

use Shared\CommandHandling\CommandBusInterface;
use Shared\CommandHandling\QueryBusInterface;
use Shared\Domain\DomainEventStream;
use Shared\Domain\DomainMessage;
use Shared\Domain\Metadata;
use Shared\Domain\Uuid;
use Shared\EventHandling\EventBusInterface;
use Shared\EventHandling\SimpleEventBus;
use Shared\EventSourcing\MetadataEnricher\MetadataEnrichingEventStreamDecorator;
use Shared\EventStore\EventStoreInterface;
use Shared\EventStore\EventStoreManagerInterface;
use SharedBundle\CommandHandling\MessengerCommandBus;
use SharedBundle\CommandHandling\MessengerQueryBus;
use SharedBundle\DBAL\DBALHealthyConnection;
use SharedBundle\EventHandling\EventPublisher;
use SharedBundle\EventStore\DoctrineEventStore;
use SharedBundle\Tests\CommandHandling\EventWasOccurred;
use SharedBundle\Tests\EventSourcing\AMetadataEnricher;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SharedBundleTest extends KernelTestCase
{
    public function test_must_boot_kernel(): void
    {
        self::bootKernel();

        self::assertTrue(self::getContainer()->has('kernel'));
    }

    public function test_must_register_bus_services(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        self::assertInstanceOf(MessengerCommandBus::class, $container->get(CommandBusInterface::class));
        self::assertInstanceOf(MessengerQueryBus::class, $container->get(QueryBusInterface::class));
        self::assertInstanceOf(SimpleEventBus::class, $container->get(EventBusInterface::class));
    }

    public function test_must_register_event_store_services(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        self::assertInstanceOf(DoctrineEventStore::class, $container->get(EventStoreInterface::class));
        self::assertInstanceOf(DoctrineEventStore::class, $container->get(EventStoreManagerInterface::class));
    }

    public function test_must_register_support_services(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        self::assertInstanceOf(EventPublisher::class, $container->get(EventPublisher::class));
        self::assertInstanceOf(DBALHealthyConnection::class, $container->get(DBALHealthyConnection::class));
    }

    public function test_must_collect_metadata_enrichers_into_the_stream_decorator(): void
    {
        self::bootKernel();

        /** @var MetadataEnrichingEventStreamDecorator $decorator */
        $decorator = self::getContainer()->get(MetadataEnrichingEventStreamDecorator::class);

        // Run a stream through the decorator: if the bundle collected the
        // autoconfigured enricher, the message comes out carrying its key.
        $stream = $decorator(new DomainEventStream(DomainMessage::record(
            new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac'),
            0,
            Metadata::empty(),
            new EventWasOccurred(),
        )));

        $metadata = $stream->messages[0]->metadata->serialize();

        self::assertArrayHasKey('enriched_by', $metadata);
        self::assertSame(AMetadataEnricher::class, $metadata['enriched_by']);
    }
}

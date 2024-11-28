<?php

declare(strict_types=1);

namespace SharedBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Shared\CommandHandling\CommandBusInterface;
use Shared\CommandHandling\QueryBusInterface;
use Shared\EventHandling\EventBusInterface;
use Shared\EventHandling\SimpleEventBus;
use Shared\EventSourcing\EventStreamDecoratorInterface;
use Shared\EventSourcing\MetadataEnricher\MetadataEnrichingEventStreamDecorator;
use Shared\EventStore\EventStoreInterface;
use Shared\EventStore\EventStoreManagerInterface;
use SharedBundle\AMQP\AMQPHealthyConnection;
use SharedBundle\CommandHandling\MessengerCommandBus;
use SharedBundle\CommandHandling\MessengerQueryBus;
use SharedBundle\DBAL\DBALHealthyConnection;
use SharedBundle\DependencyInjection\SharedExtension;
use SharedBundle\EventHandling\EventPublisher;
use SharedBundle\EventStore\DoctrineEventStore;

final class PackagesSharedExtensionTest extends AbstractExtensionTestCase
{
    #[\Override]
    protected function getContainerExtensions(): array
    {
        return [
            new SharedExtension(),
        ];
    }

    public function test_must_contains_services(): void
    {
        $this->load([]);

        self::assertContainerBuilderHasService(MessengerCommandBus::class);
        self::assertContainerBuilderHasAlias(CommandBusInterface::class, MessengerCommandBus::class);
        self::assertContainerBuilderHasService(MessengerQueryBus::class);
        self::assertContainerBuilderHasAlias(QueryBusInterface::class, MessengerQueryBus::class);
        self::assertContainerBuilderHasService(EventPublisher::class);
        self::assertContainerBuilderHasService(DBALHealthyConnection::class);
        self::assertContainerBuilderHasService(AMQPHealthyConnection::class);
        self::assertContainerBuilderHasService(SimpleEventBus::class);
        self::assertContainerBuilderHasAlias(EventBusInterface::class, SimpleEventBus::class);
        self::assertContainerBuilderHasService(MetadataEnrichingEventStreamDecorator::class);
        self::assertContainerBuilderHasAlias(EventStreamDecoratorInterface::class, MetadataEnrichingEventStreamDecorator::class);
        self::assertContainerBuilderHasService(DoctrineEventStore::class);
        self::assertContainerBuilderHasAlias(EventStoreInterface::class, DoctrineEventStore::class);
        self::assertContainerBuilderHasAlias(EventStoreManagerInterface::class, DoctrineEventStore::class);
    }
}

<?php

declare(strict_types=1);

namespace SharedBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Shared\CommandHandling\CommandBusInterface;
use Shared\CommandHandling\QueryBusInterface;
use Shared\EventHandling\EventBusInterface;
use Shared\EventHandling\SimpleEventBus;
use Shared\EventSourcing\EventStreamDecoratorInterface;
use Shared\EventStore\EventStoreInterface;
use Shared\EventStore\EventStoreManagerInterface;
use SharedBundle\DependencyInjection\SharedExtension;
use SharedBundle\EventHandling\AsyncEventPublisher;
use SharedBundle\EventHandling\MessengerAsyncEventBus;
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

        self::assertContainerBuilderHasService(MessengerAsyncEventBus::class);
        self::assertContainerBuilderHasService(CommandBusInterface::class);
        self::assertContainerBuilderHasService(QueryBusInterface::class);
        self::assertContainerBuilderHasService(AsyncEventPublisher::class);
        self::assertContainerBuilderHasService(EventBusInterface::class, SimpleEventBus::class);
        self::assertContainerBuilderHasService(EventStreamDecoratorInterface::class);
        self::assertContainerBuilderHasService(DoctrineEventStore::class);
        self::assertContainerBuilderHasService(EventStoreInterface::class);
        self::assertContainerBuilderHasService(EventStoreManagerInterface::class);
    }
}

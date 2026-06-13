<?php

declare(strict_types=1);

namespace SharedBundle\Tests;

use Shared\CommandHandling\CommandBusInterface;
use Shared\CommandHandling\QueryBusInterface;
use Shared\EventHandling\EventBusInterface;
use Shared\EventHandling\SimpleEventBus;
use Shared\EventStore\EventStoreInterface;
use Shared\EventStore\EventStoreManagerInterface;
use SharedBundle\CommandHandling\MessengerCommandBus;
use SharedBundle\CommandHandling\MessengerQueryBus;
use SharedBundle\DBAL\DBALHealthyConnection;
use SharedBundle\EventHandling\EventPublisher;
use SharedBundle\EventStore\DoctrineEventStore;
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
}

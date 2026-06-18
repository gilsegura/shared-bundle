<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Shared\CommandHandling\CommandBusInterface;
use Shared\CommandHandling\QueryBusInterface;
use Shared\EventHandling\EventBusInterface;
use Shared\EventHandling\SimpleEventBus;
use Shared\EventSourcing\EventStreamDecoratorInterface;
use Shared\EventSourcing\MetadataEnricher\MetadataEnrichingEventStreamDecorator;
use Shared\EventStore\EventStoreInterface;
use Shared\EventStore\EventStoreManagerInterface;
use SharedBundle\CommandHandling\MessengerCommandBus;
use SharedBundle\CommandHandling\MessengerQueryBus;
use SharedBundle\DBAL\DBALHealthyConnection;
use SharedBundle\EventHandling\EventPublisher;
use SharedBundle\EventHandling\UnwrapDomainMessageMiddleware;
use SharedBundle\EventStore\DoctrineEventStore;
use SharedBundle\SharedBundle;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public(false);

    // COMMAND BUS
    $services->set(MessengerCommandBus::class)
        ->args([service(SharedBundle::COMMAND_BUS)]);
    $services->alias(CommandBusInterface::class, MessengerCommandBus::class);

    // QUERY BUS
    $services->set(MessengerQueryBus::class)
        ->args([service(SharedBundle::QUERY_BUS)]);
    $services->alias(QueryBusInterface::class, MessengerQueryBus::class);

    // EVENT PUBLISHER
    $services->set(EventPublisher::class)
        ->args([service(SharedBundle::EVENT_BUS)])
        ->tag('kernel.event_subscriber');

    $services->set(UnwrapDomainMessageMiddleware::class);

    // HEALTH CHECKS
    $services->set(DBALHealthyConnection::class)
        ->args([service('doctrine.dbal.default_connection')]);

    // DOMAIN
    $services->set(SimpleEventBus::class);
    $services->alias(EventBusInterface::class, SimpleEventBus::class);

    $services->set(MetadataEnrichingEventStreamDecorator::class);
    $services->alias(EventStreamDecoratorInterface::class, MetadataEnrichingEventStreamDecorator::class);

    // EVENT STORE
    // Constructor arguments are injected by ObjectManagerPass from the
    // #[ObjectManager(DomainMessage::class)] attribute on the class.
    $services->set(DoctrineEventStore::class);
    $services->alias(EventStoreInterface::class, DoctrineEventStore::class);
    $services->alias(EventStoreManagerInterface::class, DoctrineEventStore::class);
};

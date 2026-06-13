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

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public(false);

    // COMMAND BUS
    $services->set(MessengerCommandBus::class)
        ->args([service('messenger.bus.command')]);
    $services->alias(CommandBusInterface::class, MessengerCommandBus::class);

    // QUERY BUS
    $services->set(MessengerQueryBus::class)
        ->args([service('messenger.bus.query')]);
    $services->alias(QueryBusInterface::class, MessengerQueryBus::class);

    // EVENT PUBLISHER
    $services->set(EventPublisher::class)
        ->args([service('messenger.bus.event.async')])
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
    $services->set(DoctrineEventStore::class)
        ->args([service('doctrine.orm.entity_manager')]);
    $services->alias(EventStoreInterface::class, DoctrineEventStore::class);
    $services->alias(EventStoreManagerInterface::class, DoctrineEventStore::class);
};

<?php

declare(strict_types=1);

namespace SharedBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Shared\EventHandling\SimpleEventBus;
use Shared\EventSourcing\MetadataEnricher\MetadataEnrichingEventStreamDecorator;
use SharedBundle\AMQP\AMQPHealthyConnection;
use SharedBundle\CommandHandling\MessengerCommandBus;
use SharedBundle\CommandHandling\MessengerQueryBus;
use SharedBundle\DBAL\DBALHealthyConnection;
use SharedBundle\EventStore\DoctrineEventStore;
use SharedBundle\SharedBundle;
use SharedBundle\Tests\CommandHandling\ACommandHandler;
use SharedBundle\Tests\CommandHandling\AQueryHandler;
use SharedBundle\Tests\CommandHandling\ThrowableCommandHandler;
use SharedBundle\Tests\CommandHandling\ThrowableQueryHandler;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new DoctrineBundle(),
            new FrameworkBundle(),
            new SharedBundle(),
        ];
    }

    public function process(ContainerBuilder $container): void
    {
        $container->findDefinition(MessengerCommandBus::class)->setPublic(true);
        $container->findDefinition(MessengerQueryBus::class)->setPublic(true);

        $container->findDefinition(SimpleEventBus::class)->setPublic(true);
        $container->findDefinition(MetadataEnrichingEventStreamDecorator::class)->setPublic(true);

        $container->findDefinition(DoctrineEventStore::class)->setPublic(true);
        $container->findDefinition(DBALHealthyConnection::class)->setPublic(true);
        $container->findDefinition(AMQPHealthyConnection::class)->setPublic(true);
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('doctrine', [
                'dbal' => [
                    'driver' => 'pdo_sqlite',
                    'charset' => 'utf8mb4',
                    'url' => 'sqlite:///:memory:',
                ],
                'orm' => [
                    'auto_mapping' => true,
                    'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                ],
            ]);

            $container->loadFromExtension('framework', [
                'secret' => 'nope',
                'test' => true,
                'http_method_override' => true,
                'php_errors' => [
                    'log' => true,
                ],
            ]);

            $container
                ->register(ThrowableCommandHandler::class, ThrowableCommandHandler::class)
                ->setAutoconfigured(true)
                ->setAutowired(true)
                ->addTag('messenger.message_handler', ['bus' => 'messenger.bus.command']);

            $container
                ->register(ACommandHandler::class, ACommandHandler::class)
                ->setAutoconfigured(true)
                ->setAutowired(true)
                ->addTag('messenger.message_handler', ['bus' => 'messenger.bus.command']);

            $container
                ->register(ThrowableQueryHandler::class, ThrowableQueryHandler::class)
                ->setAutoconfigured(true)
                ->setAutowired(true)
                ->addTag('messenger.message_handler', ['bus' => 'messenger.bus.query']);

            $container
                ->register(AQueryHandler::class, AQueryHandler::class)
                ->setAutoconfigured(true)
                ->setAutowired(true)
                ->addTag('messenger.message_handler', ['bus' => 'messenger.bus.query']);
        });
    }
}

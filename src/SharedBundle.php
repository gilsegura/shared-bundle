<?php

declare(strict_types=1);

namespace SharedBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Shared\CommandHandling\CommandHandlerInterface;
use Shared\CommandHandling\QueryHandlerInterface;
use Shared\EventHandling\EventListenerInterface;
use Shared\EventSourcing\MetadataEnricher\MetadataEnricherInterface;
use SharedBundle\DBAL\Types\DateTimeImmutableType;
use SharedBundle\DBAL\Types\EmailType;
use SharedBundle\DBAL\Types\HashedPasswordType;
use SharedBundle\DBAL\Types\NotEmptyStringType;
use SharedBundle\DBAL\Types\SerializableType;
use SharedBundle\DBAL\Types\UuidType;
use SharedBundle\DependencyInjection\AggregateRootPass;
use SharedBundle\DependencyInjection\EventBusSubscriberPass;
use SharedBundle\DependencyInjection\MetadataEnricherPass;
use SharedBundle\DependencyInjection\ObjectManagerPass;
use SharedBundle\EventHandling\UnwrapDomainMessageMiddleware;
use SharedBundle\EventSourcing\Attribute\AggregateRoot;
use SharedBundle\Persistence\Doctrine\Attribute\ObjectManager;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Symfony integration for the gilsegura/shared package: registers the
 * command, query and async event buses, the Doctrine DBAL types, the event
 * store mapping, and the attribute-driven wiring for repositories and
 * object managers.
 */
final class SharedBundle extends AbstractBundle
{
    /**
     * Tag carried by every EventListener collected by the EventBusSubscriberPass.
     */
    public const string EVENT_LISTENER_TAG = 'packages.shared.event_handling.event_listener';

    /**
     * Tag carried by every object manager wired from its #[ObjectManager]
     * attribute by the ObjectManagerPass.
     */
    public const string OBJECT_MANAGER_TAG = 'packages.shared.persistence.object_manager';

    /**
     * Tag carried by every repository wired from its #[AggregateRoot] attribute
     * by the AggregateRootPass.
     */
    public const string AGGREGATE_ROOT_TAG = 'packages.shared.event_sourcing.aggregate_root';

    /**
     * Tag carried by every metadata enricher collected into the stream
     * decorator that enriches outgoing events.
     */
    public const string METADATA_ENRICHER_TAG = 'packages.shared.event_sourcing.metadata_enricher';

    /**
     * Messenger bus ids the bundle defines and wires its services against.
     */
    public const string COMMAND_BUS = 'messenger.bus.command';

    public const string QUERY_BUS = 'messenger.bus.query';

    public const string EVENT_BUS = 'messenger.bus.event.async';

    /**
     * Declares the hard dependency on DoctrineBundle in a way that works on both
     * Symfony 7.4 and 8.1 (the #[RequiredBundle] attribute only exists in 8.1+).
     *
     * @return array<class-string, array<string, bool>>
     */
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
        ];
    }

    #[\Override]
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new EventBusSubscriberPass(), priority: 10);

        $container->addCompilerPass(
            new ObjectManagerPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            priority: 100,
        );

        $container->addCompilerPass(
            new AggregateRootPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            priority: 100,
        );

        $container->addCompilerPass(new MetadataEnricherPass());

        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createXmlMappingDriver([
                $this->getPath().'/config/packages/doctrine/mapping' => 'Shared\Domain',
            ])
        );

        // Autoconfiguration lives in build() so it applies to every service in
        // the container. Implementing a handler/listener interface is enough:
        // the bundle routes it to the right Messenger bus or tags it as an event
        // listener, so an application never wires this by hand.
        $container->registerForAutoconfiguration(CommandHandlerInterface::class)
            ->addTag('messenger.message_handler', ['bus' => self::COMMAND_BUS]);

        $container->registerForAutoconfiguration(QueryHandlerInterface::class)
            ->addTag('messenger.message_handler', ['bus' => self::QUERY_BUS]);

        $container->registerForAutoconfiguration(EventListenerInterface::class)
            ->addTag(self::EVENT_LISTENER_TAG);

        // Metadata enrichers are collected by interface into the stream
        // decorator, so an application enriches every event's metadata just by
        // implementing MetadataEnricherInterface.
        $container->registerForAutoconfiguration(MetadataEnricherInterface::class)
            ->addTag(self::METADATA_ENRICHER_TAG);

        // Object managers carrying #[ObjectManager] get tagged so the pass can
        // inject the entity manager and the entity class-string from the
        // attribute, letting them skip the constructor entirely.
        $container->registerAttributeForAutoconfiguration(
            ObjectManager::class,
            static function (ChildDefinition $definition): void {
                $definition->addTag(self::OBJECT_MANAGER_TAG);
            },
        );

        // Repositories carrying #[AggregateRoot] get tagged so the pass can
        // inject the event store, buses and the aggregate factory built from the
        // attribute, letting them skip the constructor entirely.
        $container->registerAttributeForAutoconfiguration(
            AggregateRoot::class,
            static function (ChildDefinition $definition): void {
                $definition->addTag(self::AGGREGATE_ROOT_TAG);
            },
        );
    }

    /**
     * @param array<array-key, mixed> $config
     */
    #[\Override]
    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder,
    ): void {
        $container->import($this->getPath().'/config/services.php');
    }

    #[\Override]
    public function prependExtension(
        ContainerConfigurator $container,
        ContainerBuilder $builder,
    ): void {
        $builder->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => [
                    DateTimeImmutableType::NAME => DateTimeImmutableType::class,
                    EmailType::NAME => EmailType::class,
                    HashedPasswordType::NAME => HashedPasswordType::class,
                    NotEmptyStringType::NAME => NotEmptyStringType::class,
                    SerializableType::NAME => SerializableType::class,
                    UuidType::NAME => UuidType::class,
                ],
            ],
        ]);

        $builder->prependExtensionConfig('framework', [
            'messenger' => [
                'default_bus' => self::COMMAND_BUS,
                'stop_worker_on_signals' => [
                    'SIGTERM',
                    'SIGINT',
                ],
                'buses' => [
                    self::COMMAND_BUS => [
                        'default_middleware' => false,
                        'middleware' => [
                            'add_bus_name_stamp_middleware',
                            'dispatch_after_current_bus',
                            'doctrine_transaction',
                            'handle_message',
                        ],
                    ],
                    self::QUERY_BUS => [
                        'default_middleware' => false,
                        'middleware' => [
                            'add_bus_name_stamp_middleware',
                            'handle_message',
                        ],
                    ],
                    self::EVENT_BUS => [
                        'default_middleware' => [
                            'enabled' => true,
                            'allow_no_handlers' => true,
                        ],
                        'middleware' => [
                            UnwrapDomainMessageMiddleware::class,
                        ],
                    ],
                ],
            ],
        ]);
    }
}

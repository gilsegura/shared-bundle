<?php

declare(strict_types=1);

namespace SharedBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Shared\EventHandling\EventListenerInterface;
use SharedBundle\DBAL\Types\DateTimeImmutableType;
use SharedBundle\DBAL\Types\EmailType;
use SharedBundle\DBAL\Types\HashedPasswordType;
use SharedBundle\DBAL\Types\NotEmptyStringType;
use SharedBundle\DBAL\Types\SerializableType;
use SharedBundle\DBAL\Types\UuidType;
use SharedBundle\DependencyInjection\EventBusSubscriberPass;
use SharedBundle\EventHandling\UnwrapDomainMessageMiddleware;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class SharedBundle extends AbstractBundle
{
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
            DoctrineOrmMappingsPass::createXmlMappingDriver([
                $this->getPath().'/config/packages/doctrine/mapping' => 'Shared\Domain',
            ])
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
        $builder->registerForAutoconfiguration(EventListenerInterface::class)
            ->addTag('packages.shared.event_handling.event_listener');

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
                'default_bus' => 'messenger.bus.command',
                'stop_worker_on_signals' => [
                    'SIGTERM',
                    'SIGINT',
                ],
                'buses' => [
                    'messenger.bus.command' => [
                        'default_middleware' => false,
                        'middleware' => [
                            'add_bus_name_stamp_middleware',
                            'dispatch_after_current_bus',
                            'doctrine_transaction',
                            'handle_message',
                        ],
                    ],
                    'messenger.bus.query' => [
                        'default_middleware' => false,
                        'middleware' => [
                            'add_bus_name_stamp_middleware',
                            'handle_message',
                        ],
                    ],
                    'messenger.bus.event.async' => [
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

<?php

declare(strict_types=1);

namespace SharedBundle\DependencyInjection;

use SharedBundle\DBAL\Types\DateTimeImmutableType;
use SharedBundle\DBAL\Types\EmailType;
use SharedBundle\DBAL\Types\HashedPasswordType;
use SharedBundle\DBAL\Types\HighResolutionTimeImmutableType;
use SharedBundle\DBAL\Types\NotEmptyStringType;
use SharedBundle\DBAL\Types\SerializableType;
use SharedBundle\DBAL\Types\UuidType;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SharedExtension extends Extension implements PrependExtensionInterface
{
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../../config'));

        $loader->load('services.xml');
    }

    #[\Override]
    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineConfig($container);
        $this->prependFrameworkConfig($container);
    }

    private function prependDoctrineConfig(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => [
                    DateTimeImmutableType::NAME => DateTimeImmutableType::class,
                    EmailType::NAME => EmailType::class,
                    HashedPasswordType::NAME => HashedPasswordType::class,
                    HighResolutionTimeImmutableType::NAME => HighResolutionTimeImmutableType::class,
                    NotEmptyStringType::NAME => NotEmptyStringType::class,
                    SerializableType::NAME => SerializableType::class,
                    UuidType::NAME => UuidType::class,
                ],
            ],
        ]);
    }

    private function prependFrameworkConfig(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('framework', [
            'messenger' => [
                'default-bus' => 'messenger.bus.command',
                'buses' => [
                    'messenger.bus.command' => [
                        'default-middleware' => false,
                        'middleware' => [
                            'doctrine_transaction',
                            'handle_message',
                        ],
                    ],
                    'messenger.bus.query' => [
                        'default-middleware' => false,
                        'middleware' => [
                            'handle_message',
                        ],
                    ],
                    'messenger.bus.event.async' => [
                        'default-middleware' => 'allow_no_handlers',
                    ],
                ],
            ],
        ]);
    }
}

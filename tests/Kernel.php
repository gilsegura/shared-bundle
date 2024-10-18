<?php

declare(strict_types=1);

namespace SharedBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use SharedBundle\SharedBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    #[\Override]
    public function registerBundles(): iterable
    {
        return [
            new DoctrineBundle(),
            new FrameworkBundle(),
            new SharedBundle(),
        ];
    }

    #[\Override]
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(static function (ContainerBuilder $container) {
            $container->loadFromExtension('doctrine', [
                'dbal' => [
                    'driver' => 'pdo_sqlite',
                    'charset' => 'utf8mb4',
                    'url' => 'sqlite:///:memory:',
                ],
                'orm' => [
                    'auto_generate_proxy_classes' => true,
                    'auto_mapping' => true,
                    'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                ],
            ]);

            $container->loadFromExtension('framework', [
                'secret' => 'nope',
                'test' => null,
                'http_method_override' => true,
                'php_errors' => ['log' => true],
            ]);

            if (!$container->hasDefinition('kernel')) {
                $container
                    ->register('kernel', self::class)
                    ->setSynthetic(true)
                    ->setPublic(true);
            }
        });
    }
}

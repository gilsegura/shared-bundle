<?php

declare(strict_types=1);

namespace SharedBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use SharedBundle\DependencyInjection\EventBusSubscriberPass;
use SharedBundle\DependencyInjection\SharedExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class SharedBundle extends AbstractBundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new EventBusSubscriberPass());

        $this->addRegisterMappingsPass($container);
    }

    private function addRegisterMappingsPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(DoctrineOrmMappingsPass::createXmlMappingDriver([
            realpath(__DIR__.'/../config/packages/doctrine/mapping') => 'Shared\Domain',
        ]));
    }

    #[\Override]
    public function getContainerExtension(): ExtensionInterface
    {
        return new SharedExtension();
    }
}

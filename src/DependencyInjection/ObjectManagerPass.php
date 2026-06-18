<?php

declare(strict_types=1);

namespace SharedBundle\DependencyInjection;

use Doctrine\ORM\EntityManagerInterface;
use SharedBundle\Persistence\Doctrine\AbstractObjectManager;
use SharedBundle\Persistence\Doctrine\Attribute\ObjectManager;
use SharedBundle\SharedBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Wires every object manager carrying #[ObjectManager]. The attribute names the
 * managed entity; this pass supplies the two constructor arguments of
 * AbstractObjectManager — the entity manager as a service reference, plus the
 * entity class-string from the attribute — so the object manager needs no
 * constructor.
 */
final class ObjectManagerPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        foreach (array_keys($container->findTaggedServiceIds(SharedBundle::OBJECT_MANAGER_TAG)) as $id) {
            $definition = $container->getDefinition($id);

            /** @var class-string $class */
            $class = $container->getParameterBag()->resolveValue($definition->getClass());

            $reflection = new \ReflectionClass($class);

            if (!$reflection->isSubclassOf(AbstractObjectManager::class)) {
                throw new \InvalidArgumentException(\sprintf('Service "%s" must extend "%s" to use #[ObjectManager].', $id, AbstractObjectManager::class));
            }

            $attributes = $reflection->getAttributes(ObjectManager::class);

            if ([] === $attributes) {
                throw new \InvalidArgumentException(\sprintf('Service "%s" is tagged "%s" but has no #[ObjectManager] attribute.', $id, SharedBundle::OBJECT_MANAGER_TAG));
            }

            $entity = $attributes[0]->newInstance()->entity;

            // Fix both constructor arguments and turn autowiring off: the entity
            // manager is referenced explicitly and the entity class-string is not
            // autowireable, so autowiring must not try to resolve them again.
            $definition
                ->setAutowired(false)
                ->setArguments([
                    new Reference(EntityManagerInterface::class),
                    $entity,
                ]);
        }
    }
}

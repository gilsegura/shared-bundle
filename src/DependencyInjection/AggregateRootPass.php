<?php

declare(strict_types=1);

namespace SharedBundle\DependencyInjection;

use Shared\EventHandling\EventBusInterface;
use Shared\EventSourcing\AbstractEventSourcingRepository;
use Shared\EventSourcing\EventStreamDecoratorInterface;
use Shared\EventSourcing\Factory\PublicConstructorAggregateRootFactory;
use SharedBundle\EventSourcing\Attribute\AggregateRoot;
use SharedBundle\EventStore\DoctrineEventStore;
use SharedBundle\SharedBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Wires every repository carrying #[AggregateRoot]. The attribute names the
 * aggregate; this pass supplies the four constructor arguments of
 * AbstractEventSourcingRepository — the event store, event bus and stream
 * decorator as references, plus a PublicConstructorAggregateRootFactory built
 * inline for the aggregate — so the repository needs no constructor.
 */
final class AggregateRootPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        foreach (array_keys($container->findTaggedServiceIds(SharedBundle::AGGREGATE_ROOT_TAG)) as $id) {
            $definition = $container->getDefinition($id);

            /** @var class-string $class */
            $class = $container->getParameterBag()->resolveValue($definition->getClass());

            $reflection = new \ReflectionClass($class);

            if (!$reflection->isSubclassOf(AbstractEventSourcingRepository::class)) {
                throw new \InvalidArgumentException(\sprintf('Service "%s" must extend "%s" to use #[AggregateRoot].', $id, AbstractEventSourcingRepository::class));
            }

            $attributes = $reflection->getAttributes(AggregateRoot::class);

            if ([] === $attributes) {
                throw new \InvalidArgumentException(\sprintf('Service "%s" is tagged "%s" but has no #[AggregateRoot] attribute.', $id, SharedBundle::AGGREGATE_ROOT_TAG));
            }

            $aggregateRoot = $attributes[0]->newInstance()->aggregateRoot;

            // The factory is built inline from the aggregate class-string — no
            // per-aggregate factory service is registered.
            $factory = new Definition(PublicConstructorAggregateRootFactory::class)
                ->setArguments([$aggregateRoot]);

            // Fix the four constructor arguments and turn autowiring off: the
            // three dependencies are referenced explicitly and the factory is an
            // inline definition, so autowiring must not try to resolve them.
            $definition
                ->setAutowired(false)
                ->setArguments([
                    new Reference(DoctrineEventStore::class),
                    new Reference(EventBusInterface::class),
                    new Reference(EventStreamDecoratorInterface::class),
                    $factory,
                ]);
        }
    }
}

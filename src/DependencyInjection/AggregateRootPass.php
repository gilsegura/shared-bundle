<?php

declare(strict_types=1);

namespace SharedBundle\DependencyInjection;

use Shared\EventHandling\EventBusInterface;
use Shared\EventSourcing\AbstractEventSourcingRepository;
use Shared\EventSourcing\EventStreamDecoratorInterface;
use Shared\EventSourcing\Factory\PublicConstructorAggregateRootFactory;
use Shared\Upcasting\SequentialUpcasterChain;
use Shared\Upcasting\UpcastingEventStore;
use SharedBundle\EventSourcing\Attribute\AggregateRoot;
use SharedBundle\EventStore\DoctrineEventStore;
use SharedBundle\SharedBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Wires every repository carrying #[AggregateRoot]. The attribute names the
 * aggregate and the ordered upcaster sequence; this pass supplies the four
 * constructor arguments of AbstractEventSourcingRepository:
 *
 *  - the event store, always an UpcastingEventStore wrapping the DoctrineEventStore
 *    with a SequentialUpcasterChain built from the declared upcasters (an empty
 *    chain passes events through unchanged);
 *  - the event bus and the stream decorator, as service references;
 *  - a PublicConstructorAggregateRootFactory built inline for the aggregate.
 *
 * So the repository needs no constructor.
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

            $attribute = $attributes[0]->newInstance();

            // The upcaster sequence, in declared order, each referenced as a
            // service so it can carry its own dependencies.
            $upcasters = array_map(
                static fn (string $upcaster): Reference => new Reference($upcaster),
                $attribute->upcasters,
            );

            $chain = new Definition(SequentialUpcasterChain::class)
                ->setArguments($upcasters);

            // The event store is always an UpcastingEventStore wrapping the
            // Doctrine one; with an empty chain it is a no-op pass-through.
            $eventStore = new Definition(UpcastingEventStore::class)
                ->setArguments([
                    new Reference(DoctrineEventStore::class),
                    $chain,
                ]);

            // The aggregate factory is built inline from the aggregate class.
            $factory = new Definition(PublicConstructorAggregateRootFactory::class)
                ->setArguments([$attribute->aggregateRoot]);

            $definition
                ->setAutowired(false)
                ->setArguments([
                    $eventStore,
                    new Reference(EventBusInterface::class),
                    new Reference(EventStreamDecoratorInterface::class),
                    $factory,
                ]);
        }
    }
}

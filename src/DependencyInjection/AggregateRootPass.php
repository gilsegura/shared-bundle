<?php

declare(strict_types=1);

namespace SharedBundle\DependencyInjection;

use Shared\EventHandling\EventBusInterface;
use Shared\EventSourcing\AbstractEventSourcingRepository;
use Shared\EventSourcing\EventStreamDecoratorInterface;
use Shared\EventSourcing\Factory\PublicConstructorAggregateRootFactory;
use Shared\EventSourcing\Loader\EventStoreAggregateRootLoader;
use Shared\EventSourcing\Register\EventStoreAggregateRootRegister;
use Shared\Snapshotting\EventCountSnapshotStrategy;
use Shared\Snapshotting\SnapshotAggregateRootLoader;
use Shared\Snapshotting\SnapshotAggregateRootRegister;
use Shared\Upcasting\SequentialUpcasterChain;
use Shared\Upcasting\UpcastingEventStore;
use SharedBundle\EventSourcing\Attribute\AggregateRoot;
use SharedBundle\EventSourcing\Attribute\SnapshotConfig;
use SharedBundle\EventStore\DoctrineEventStore;
use SharedBundle\SharedBundle;
use SharedBundle\Snapshotting\DoctrineSnapshotStore;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Wires every repository carrying #[AggregateRoot]. The attribute names the
 * aggregate, the ordered upcaster sequence and an optional snapshot policy; this
 * pass supplies the two constructor arguments of AbstractEventSourcingRepository:
 *
 *  - the loader: an EventStoreAggregateRootLoader over the event store and an
 *    inline factory, decorated with a SnapshotAggregateRootLoader when snapshot
 *    is configured;
 *  - the register: an EventStoreAggregateRootRegister over the event store, event
 *    bus and stream decorator, decorated with a SnapshotAggregateRootRegister
 *    when snapshot is configured.
 *
 * The event store is always an UpcastingEventStore wrapping the DoctrineEventStore
 * with a SequentialUpcasterChain built from the declared upcasters (an empty
 * chain passes events through unchanged). So the repository needs no constructor.
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
            // Doctrine one; with an empty chain it is a no-op pass-through. It is
            // stateless, so the same inline definition is reused on both seams.
            $eventStore = new Definition(UpcastingEventStore::class)
                ->setArguments([
                    new Reference(DoctrineEventStore::class),
                    $chain,
                ]);

            // Read seam: the base loader over the event store and an inline
            // factory, decorated by snapshotting when configured.
            $loader = new Definition(EventStoreAggregateRootLoader::class)
                ->setArguments([
                    $eventStore,
                    new Definition(PublicConstructorAggregateRootFactory::class)
                        ->setArguments([$attribute->aggregateRoot]),
                ]);

            // Write seam: the base register over the event store, event bus and
            // stream decorator, decorated by snapshotting when configured.
            $register = new Definition(EventStoreAggregateRootRegister::class)
                ->setArguments([
                    $eventStore,
                    new Reference(EventBusInterface::class),
                    new Reference(EventStreamDecoratorInterface::class),
                ]);

            if ($attribute->snapshot instanceof SnapshotConfig) {
                $snapshotStore = new Reference(DoctrineSnapshotStore::class);
                $strategy = $this->strategy($attribute->snapshot);

                $loader = new Definition(SnapshotAggregateRootLoader::class)
                    ->setArguments([$loader, $eventStore, $snapshotStore]);

                $register = new Definition(SnapshotAggregateRootRegister::class)
                    ->setArguments([$register, $snapshotStore, $strategy]);
            }

            $definition
                ->setAutowired(false)
                ->setArguments([$loader, $register]);
        }
    }

    /**
     * Builds the snapshot strategy: a referenced custom strategy service when one
     * is named, otherwise an inline event-count strategy.
     */
    private function strategy(SnapshotConfig $config): Definition|Reference
    {
        if (null !== $config->strategy) {
            return new Reference($config->strategy);
        }

        return new Definition(EventCountSnapshotStrategy::class)
            ->setArguments([$config->every]);
    }
}

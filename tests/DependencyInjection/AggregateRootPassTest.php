<?php

declare(strict_types=1);

namespace SharedBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Shared\EventHandling\EventBusInterface;
use Shared\EventSourcing\EventStreamDecoratorInterface;
use Shared\EventSourcing\Factory\PublicConstructorAggregateRootFactory;
use Shared\EventSourcing\Loader\EventStoreAggregateRootLoader;
use Shared\EventSourcing\Register\EventStoreAggregateRootRegister;
use Shared\Snapshotting\EventCountSnapshotStrategy;
use Shared\Snapshotting\SnapshotAggregateRootLoader;
use Shared\Snapshotting\SnapshotAggregateRootRegister;
use Shared\Upcasting\SequentialUpcasterChain;
use Shared\Upcasting\UpcastingEventStore;
use SharedBundle\DependencyInjection\AggregateRootPass;
use SharedBundle\EventStore\DoctrineEventStore;
use SharedBundle\SharedBundle;
use SharedBundle\Snapshotting\DoctrineSnapshotStore;
use SharedBundle\Tests\EventSourcing\AThing;
use SharedBundle\Tests\EventSourcing\AThingRepository;
use SharedBundle\Tests\EventSourcing\AThingUpcaster;
use SharedBundle\Tests\EventSourcing\AThingWithSnapshotRepository;
use SharedBundle\Tests\EventSourcing\AThingWithUpcastersRepository;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class AggregateRootPassTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();

        foreach ([DoctrineEventStore::class, DoctrineSnapshotStore::class, EventBusInterface::class, EventStreamDecoratorInterface::class] as $service) {
            $this->container->setDefinition($service, new Definition($service)->setSynthetic(true));
        }

        $this->container->setDefinition(AThingUpcaster::class, new Definition(AThingUpcaster::class));
    }

    private function compile(): void
    {
        new AggregateRootPass()->process($this->container);
    }

    public function test_must_throw_when_service_does_not_extend_abstract_event_sourcing_repository(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $definition = new Definition(\stdClass::class);
        $definition->setPublic(true);
        $definition->addTag(SharedBundle::AGGREGATE_ROOT_TAG);

        $this->container->setDefinition('repository', $definition);

        $this->compile();
    }

    public function test_must_wire_a_loader_and_a_register_without_snapshot(): void
    {
        $this->register(AThingRepository::class);

        $this->compile();

        $arguments = $this->container->getDefinition(AThingRepository::class)->getArguments();

        // First argument: the base loader over the event store and an inline factory.
        $loader = $arguments[0];
        self::assertInstanceOf(Definition::class, $loader);
        self::assertSame(EventStoreAggregateRootLoader::class, $loader->getClass());

        $factory = $loader->getArgument(1);
        self::assertInstanceOf(Definition::class, $factory);
        self::assertSame(PublicConstructorAggregateRootFactory::class, $factory->getClass());
        self::assertSame(AThing::class, $factory->getArgument(0));

        // Second argument: the base register over the event store, bus and decorator.
        $register = $arguments[1];
        self::assertInstanceOf(Definition::class, $register);
        self::assertSame(EventStoreAggregateRootRegister::class, $register->getClass());
        self::assertEquals(new Reference(EventBusInterface::class), $register->getArgument(1));
        self::assertEquals(new Reference(EventStreamDecoratorInterface::class), $register->getArgument(2));

        // Both seams wrap the same kind of event store: an UpcastingEventStore.
        $loaderEventStore = $loader->getArgument(0);
        self::assertInstanceOf(Definition::class, $loaderEventStore);
        self::assertSame(UpcastingEventStore::class, $loaderEventStore->getClass());
        self::assertEquals(new Reference(DoctrineEventStore::class), $loaderEventStore->getArgument(0));

        $registerEventStore = $register->getArgument(0);
        self::assertInstanceOf(Definition::class, $registerEventStore);
        self::assertSame(UpcastingEventStore::class, $registerEventStore->getClass());
    }

    public function test_must_build_the_chain_with_the_declared_upcaster_sequence(): void
    {
        $this->register(AThingWithUpcastersRepository::class);

        $this->compile();

        $arguments = $this->container->getDefinition(AThingWithUpcastersRepository::class)->getArguments();

        $loader = $arguments[0];
        self::assertInstanceOf(Definition::class, $loader);

        $eventStore = $loader->getArgument(0);
        self::assertInstanceOf(Definition::class, $eventStore);

        $chain = $eventStore->getArgument(1);
        self::assertInstanceOf(Definition::class, $chain);
        self::assertSame(SequentialUpcasterChain::class, $chain->getClass());

        $upcasters = $chain->getArguments();
        self::assertCount(1, $upcasters);
        self::assertEquals(new Reference(AThingUpcaster::class), $upcasters[0]);
    }

    public function test_must_not_be_autowired(): void
    {
        $this->register(AThingRepository::class);

        $this->compile();

        self::assertFalse($this->container->getDefinition(AThingRepository::class)->isAutowired());
    }

    public function test_must_decorate_both_seams_with_snapshotting_when_configured(): void
    {
        $this->register(AThingWithSnapshotRepository::class);

        $this->compile();

        $arguments = $this->container->getDefinition(AThingWithSnapshotRepository::class)->getArguments();

        // Read seam decorated by the snapshot loader, wrapping the base loader.
        $loader = $arguments[0];
        self::assertInstanceOf(Definition::class, $loader);
        self::assertSame(SnapshotAggregateRootLoader::class, $loader->getClass());

        $innerLoader = $loader->getArgument(0);
        self::assertInstanceOf(Definition::class, $innerLoader);
        self::assertSame(EventStoreAggregateRootLoader::class, $innerLoader->getClass());

        self::assertEquals(new Reference(DoctrineSnapshotStore::class), $loader->getArgument(2));

        // Write seam decorated by the snapshot register, wrapping the base register.
        $register = $arguments[1];
        self::assertInstanceOf(Definition::class, $register);
        self::assertSame(SnapshotAggregateRootRegister::class, $register->getClass());

        $innerRegister = $register->getArgument(0);
        self::assertInstanceOf(Definition::class, $innerRegister);
        self::assertSame(EventStoreAggregateRootRegister::class, $innerRegister->getClass());

        self::assertEquals(new Reference(DoctrineSnapshotStore::class), $register->getArgument(1));

        // The strategy is an inline event-count strategy with the configured count.
        $strategy = $register->getArgument(2);
        self::assertInstanceOf(Definition::class, $strategy);
        self::assertSame(EventCountSnapshotStrategy::class, $strategy->getClass());
        self::assertSame(100, $strategy->getArgument(0));
    }

    /**
     * @param class-string $repository
     */
    private function register(string $repository): void
    {
        $definition = new Definition($repository);
        $definition->setPublic(true);
        $definition->setAutowired(true);
        $definition->addTag(SharedBundle::AGGREGATE_ROOT_TAG);

        $this->container->setDefinition($repository, $definition);
    }
}

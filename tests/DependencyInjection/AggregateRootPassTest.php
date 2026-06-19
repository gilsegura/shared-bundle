<?php

declare(strict_types=1);

namespace SharedBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Shared\EventHandling\EventBusInterface;
use Shared\EventSourcing\EventStreamDecoratorInterface;
use Shared\EventSourcing\Factory\PublicConstructorAggregateRootFactory;
use Shared\Upcasting\SequentialUpcasterChain;
use Shared\Upcasting\UpcastingEventStore;
use SharedBundle\DependencyInjection\AggregateRootPass;
use SharedBundle\EventStore\DoctrineEventStore;
use SharedBundle\SharedBundle;
use SharedBundle\Tests\EventSourcing\AThing;
use SharedBundle\Tests\EventSourcing\AThingRepository;
use SharedBundle\Tests\EventSourcing\AThingUpcaster;
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

        // The pass references services by id; register synthetic stubs so the
        // container resolves them when it compiles (this is a unit test on the
        // pass, not the full integration).
        foreach ([DoctrineEventStore::class, EventBusInterface::class, EventStreamDecoratorInterface::class] as $service) {
            $this->container->setDefinition($service, new Definition($service)->setSynthetic(true));
        }

        // The upcaster is referenced by the chain, so it must exist as a service.
        $this->container->setDefinition(AThingUpcaster::class, new Definition(AThingUpcaster::class));
    }

    private function compile(): void
    {
        $this->container->addCompilerPass(new AggregateRootPass());
        $this->container->compile();
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

    public function test_must_wrap_the_event_store_in_an_upcasting_store_with_an_empty_chain(): void
    {
        $this->register(AThingRepository::class);

        $this->compile();

        $arguments = $this->container->getDefinition(AThingRepository::class)->getArguments();

        // First argument: an UpcastingEventStore wrapping the Doctrine store and
        // an empty SequentialUpcasterChain.
        $eventStore = $arguments[0];
        self::assertInstanceOf(Definition::class, $eventStore);
        self::assertSame(UpcastingEventStore::class, $eventStore->getClass());
        self::assertEquals(new Reference(DoctrineEventStore::class), $eventStore->getArgument(0));

        $chain = $eventStore->getArgument(1);
        self::assertInstanceOf(Definition::class, $chain);
        self::assertSame(SequentialUpcasterChain::class, $chain->getClass());
        self::assertSame([], $chain->getArguments());
    }

    public function test_must_build_the_chain_with_the_declared_upcaster_sequence(): void
    {
        $this->register(AThingWithUpcastersRepository::class);

        $this->compile();

        $arguments = $this->container->getDefinition(AThingWithUpcastersRepository::class)->getArguments();

        $eventStore = $arguments[0];
        self::assertInstanceOf(Definition::class, $eventStore);

        $chain = $eventStore->getArgument(1);
        self::assertInstanceOf(Definition::class, $chain);

        // After compilation the container resolves the Reference into the
        // upcaster's Definition; assert the declared upcaster is the single
        // entry in the chain.
        $upcasters = $chain->getArguments();
        self::assertCount(1, $upcasters);
        self::assertInstanceOf(Definition::class, $upcasters[0]);
        self::assertSame(AThingUpcaster::class, $upcasters[0]->getClass());
    }

    public function test_must_inject_bus_decorator_and_factory(): void
    {
        $this->register(AThingRepository::class);

        $this->compile();

        $wired = $this->container->getDefinition(AThingRepository::class);
        $arguments = $wired->getArguments();

        self::assertFalse($wired->isAutowired());
        self::assertEquals(new Reference(EventBusInterface::class), $arguments[1]);
        self::assertEquals(new Reference(EventStreamDecoratorInterface::class), $arguments[2]);

        $factory = $arguments[3];
        self::assertInstanceOf(Definition::class, $factory);
        self::assertSame(PublicConstructorAggregateRootFactory::class, $factory->getClass());
        self::assertSame(AThing::class, $factory->getArgument(0));
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

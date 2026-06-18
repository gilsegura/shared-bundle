<?php

declare(strict_types=1);

namespace SharedBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Shared\EventHandling\EventBusInterface;
use Shared\EventSourcing\EventStreamDecoratorInterface;
use Shared\EventSourcing\Factory\PublicConstructorAggregateRootFactory;
use SharedBundle\DependencyInjection\AggregateRootPass;
use SharedBundle\EventStore\DoctrineEventStore;
use SharedBundle\SharedBundle;
use SharedBundle\Tests\EventSourcing\AThing;
use SharedBundle\Tests\EventSourcing\AThingRepository;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class AggregateRootPassTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
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

    public function test_must_inject_dependencies_and_factory_from_the_attribute(): void
    {
        // The pass references services by id; register synthetic stubs so the
        // container resolves them when it compiles (this is a unit test on the
        // pass, not the full integration).
        foreach ([DoctrineEventStore::class, EventBusInterface::class, EventStreamDecoratorInterface::class] as $service) {
            $this->container->setDefinition($service, new Definition($service)->setSynthetic(true));
        }

        $definition = new Definition(AThingRepository::class);
        $definition->setPublic(true);
        $definition->setAutowired(true);
        $definition->addTag(SharedBundle::AGGREGATE_ROOT_TAG);

        $this->container->setDefinition(AThingRepository::class, $definition);

        $this->compile();

        $wired = $this->container->getDefinition(AThingRepository::class);

        $arguments = $wired->getArguments();

        self::assertFalse($wired->isAutowired());
        self::assertEquals(new Reference(DoctrineEventStore::class), $arguments[0]);
        self::assertEquals(new Reference(EventBusInterface::class), $arguments[1]);
        self::assertEquals(new Reference(EventStreamDecoratorInterface::class), $arguments[2]);

        // The fourth argument is an inline factory built for the aggregate.
        $factory = $arguments[3];
        self::assertInstanceOf(Definition::class, $factory);
        self::assertSame(PublicConstructorAggregateRootFactory::class, $factory->getClass());
        self::assertSame(AThing::class, $factory->getArgument(0));
    }
}

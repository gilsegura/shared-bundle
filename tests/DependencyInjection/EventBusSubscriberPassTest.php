<?php

declare(strict_types=1);

namespace SharedBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Shared\EventHandling\EventListenerInterface;
use Shared\EventHandling\SimpleEventBus;
use SharedBundle\DependencyInjection\EventBusSubscriberPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class EventBusSubscriberPassTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
    }

    private function compile(): void
    {
        $this->container->addCompilerPass(new EventBusSubscriberPass());
        $this->container->compile();
    }

    public function test_must_throw_invalid_argument_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $eventBus = new Definition(SimpleEventBus::class);
        $eventBus->setPublic(true);

        $this->container->setDefinition(SimpleEventBus::class, $eventBus);

        $eventListener = new Definition(\stdClass::class);
        $eventListener->setPublic(true);
        $eventListener->addTag('packages.shared.event_handling.event_listener');

        $this->container->setDefinition('event_listener', $eventListener);

        $this->compile();
    }

    public function test_must_register_event_bus_subscriber(): void
    {
        $eventBus = new Definition(SimpleEventBus::class);
        $eventBus->setPublic(true);

        $this->container->setDefinition(SimpleEventBus::class, $eventBus);

        $eventListener = new Definition(EventListenerInterface::class);
        $eventListener->setPublic(true);
        $eventListener->addTag('packages.shared.event_handling.event_listener');

        $this->container->setDefinition('event_listener', $eventListener);

        $this->compile();

        $definition = $this->container->getDefinition(SimpleEventBus::class);

        $arguments = $definition->getArguments();

        self::assertArrayHasKey(0, $arguments);

        self::assertEquals(
            new Reference('event_listener'),
            $arguments[0]
        );
    }
}

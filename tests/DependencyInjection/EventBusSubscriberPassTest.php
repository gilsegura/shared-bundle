<?php

declare(strict_types=1);

namespace SharedBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Shared\EventHandling\EventListenerInterface;
use Shared\EventHandling\SimpleEventBus;
use SharedBundle\DependencyInjection\EventBusSubscriberPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class EventBusSubscriberPassTest extends AbstractCompilerPassTestCase
{
    #[\Override]
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new EventBusSubscriberPass());
    }

    public function test_must_throw_invalid_argument_exception(): void
    {
        self::expectException(\InvalidArgumentException::class);

        $this->setDefinition(SimpleEventBus::class, new Definition());

        $eventListener = new Definition(\stdClass::class);
        $eventListener->addTag('packages.shared.event_handling.event_listener');
        $this->setDefinition('event_listener', $eventListener);

        $this->compile();
    }

    public function test_must_register_event_bus_subscriber(): void
    {
        $this->setDefinition(SimpleEventBus::class, new Definition());

        $eventListener = new Definition(EventListenerInterface::class);
        $eventListener->addTag('packages.shared.event_handling.event_listener');
        $this->setDefinition('event_listener', $eventListener);

        $this->compile();

        self::assertContainerBuilderHasServiceDefinitionWithArgument(
            SimpleEventBus::class,
            0,
            new Reference('event_listener')
        );
    }
}

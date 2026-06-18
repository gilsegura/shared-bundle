<?php

declare(strict_types=1);

namespace SharedBundle\DependencyInjection;

use Shared\EventHandling\EventListenerInterface;
use Shared\EventHandling\SimpleEventBus;
use SharedBundle\SharedBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects every tagged event listener and registers it on the SimpleEventBus.
 * Each listener is validated to implement EventListenerInterface so a misconfig
 * fails at compile time rather than at dispatch.
 */
final class EventBusSubscriberPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        try {
            $eventBus = $container->getDefinition(SimpleEventBus::class);
        } catch (ServiceNotFoundException) {
            return;
        }

        foreach (array_keys($container->findTaggedServiceIds(SharedBundle::EVENT_LISTENER_TAG)) as $id) {
            $definition = $container->getDefinition($id);

            /** @var class-string $class */
            $class = $container->getParameterBag()->resolveValue($definition->getClass());

            $reflection = new \ReflectionClass($class);

            if (!$reflection->implementsInterface(EventListenerInterface::class)) {
                throw new \InvalidArgumentException(\sprintf('Service "%s" must implement interface "%s".', $id, EventListenerInterface::class));
            }

            $eventBus->addArgument(new Reference($id));
        }
    }
}

<?php

declare(strict_types=1);

namespace SharedBundle\DependencyInjection;

use Shared\EventSourcing\MetadataEnricher\MetadataEnrichingEventStreamDecorator;
use SharedBundle\SharedBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects every service tagged as a metadata enricher and injects them into the
 * MetadataEnrichingEventStreamDecorator. The decorator takes a variadic of
 * enrichers, so the tagged services are unpacked as positional arguments rather
 * than passed as a single iterator — keeping the domain decorator's natural
 * constructor untouched.
 */
final class MetadataEnricherPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(MetadataEnrichingEventStreamDecorator::class)) {
            return;
        }

        $enrichers = array_map(
            static fn (string $id): Reference => new Reference($id),
            array_keys($container->findTaggedServiceIds(SharedBundle::METADATA_ENRICHER_TAG)),
        );

        $container->getDefinition(MetadataEnrichingEventStreamDecorator::class)
            ->setArguments($enrichers);
    }
}

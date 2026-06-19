<?php

declare(strict_types=1);

namespace SharedBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Shared\EventSourcing\MetadataEnricher\MetadataEnrichingEventStreamDecorator;
use SharedBundle\DependencyInjection\MetadataEnricherPass;
use SharedBundle\SharedBundle;
use SharedBundle\Tests\EventSourcing\AMetadataEnricher;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class MetadataEnricherPassTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->container->setDefinition(
            MetadataEnrichingEventStreamDecorator::class,
            new Definition(MetadataEnrichingEventStreamDecorator::class)->setPublic(true),
        );
    }

    private function compile(): void
    {
        $this->container->addCompilerPass(new MetadataEnricherPass());
        $this->container->compile();
    }

    public function test_must_leave_the_decorator_without_arguments_when_there_are_no_enrichers(): void
    {
        $this->compile();

        $arguments = $this->container->getDefinition(MetadataEnrichingEventStreamDecorator::class)->getArguments();

        self::assertSame([], $arguments);
    }

    public function test_must_inject_the_tagged_enrichers_as_positional_arguments(): void
    {
        // Synthetic so the container keeps the reference instead of inlining or
        // removing it when it compiles.
        $this->container->setDefinition(
            AMetadataEnricher::class,
            new Definition(AMetadataEnricher::class)
                ->setSynthetic(true)
                ->addTag(SharedBundle::METADATA_ENRICHER_TAG),
        );

        $this->compile();

        $arguments = $this->container->getDefinition(MetadataEnrichingEventStreamDecorator::class)->getArguments();

        // Unpacked as positional args (to match the decorator's variadic), not
        // wrapped in an iterator.
        self::assertCount(1, $arguments);
        self::assertEquals(new Reference(AMetadataEnricher::class), $arguments[0]);
    }
}

<?php

declare(strict_types=1);

namespace SharedBundle\Tests\EventSourcing;

use Shared\Domain\Metadata;
use Shared\EventSourcing\MetadataEnricher\MetadataEnricherInterface;

/**
 * Metadata enricher fixture: adds a fixed key so a test can assert the bundle
 * collected it into the stream decorator by interface.
 */
final readonly class AMetadataEnricher implements MetadataEnricherInterface
{
    #[\Override]
    public function __invoke(Metadata $metadata): Metadata
    {
        return $metadata->merge(Metadata::kv('enriched_by', self::class));
    }
}

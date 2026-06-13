<?php

declare(strict_types=1);

namespace SharedBundle\Tests\CommandHandling;

use Serializer\SerializableInterface;
use Shared\Domain\DomainEventInterface;

/**
 * @implements SerializableInterface<array{}>
 */
final readonly class EventWasOccurred implements DomainEventInterface, SerializableInterface
{
    #[\Override]
    public static function deserialize(array $attributes): static
    {
        return new self();
    }

    #[\Override]
    public function serialize(): array
    {
        return [];
    }
}

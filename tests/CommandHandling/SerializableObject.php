<?php

declare(strict_types=1);

namespace SharedBundle\Tests\CommandHandling;

use Serializer\SerializableInterface;

/**
 * @implements SerializableInterface<array{}>
 */
final readonly class SerializableObject implements SerializableInterface
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

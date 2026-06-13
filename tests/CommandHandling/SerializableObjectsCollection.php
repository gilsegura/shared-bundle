<?php

declare(strict_types=1);

namespace SharedBundle\Tests\CommandHandling;

use Serializer\SerializableInterface;

/**
 * @implements SerializableInterface<array<array-key, mixed>>
 */
final readonly class SerializableObjectsCollection implements SerializableInterface
{
    /** @var SerializableObject[] */
    private array $objects;

    public function __construct(
        SerializableObject ...$objects,
    ) {
        $this->objects = $objects;
    }

    #[\Override]
    public static function deserialize(array $attributes): static
    {
        return new self(...array_map(
            static fn (mixed $object): SerializableObject => SerializableObject::deserialize(
                is_array($object) ? $object : [],
            ),
            array_values($attributes),
        ));
    }

    #[\Override]
    public function serialize(): array
    {
        return array_map(
            static fn (SerializableObject $object): array => $object->serialize(),
            $this->objects,
        );
    }
}

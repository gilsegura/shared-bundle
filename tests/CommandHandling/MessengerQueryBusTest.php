<?php

declare(strict_types=1);

namespace SharedBundle\Tests\CommandHandling;

use Serializer\SerializableInterface;
use Shared\CommandHandling\QueryHandlerInterface;
use Shared\CommandHandling\QueryInterface;

final class MessengerQueryBusTest extends AbstractApplicationTestCase
{
    public function test_must_throw_exception_when_handling_query(): void
    {
        self::expectException(\Exception::class);

        $this->ask(new ThrowableQuery());

        $this->fireTerminateEvents();
    }

    public function test_must_handle_query(): void
    {
        /** @var SerializableObjectsCollection $collection */
        $collection = $this->ask(new AQuery());

        $this->fireTerminateEvents();

        self::assertInstanceOf(SerializableObjectsCollection::class, $collection);
    }
}

final readonly class ThrowableQuery implements QueryInterface
{
}

final readonly class ThrowableQueryHandler implements QueryHandlerInterface
{
    public function __invoke(ThrowableQuery $query): SerializableInterface
    {
        throw new \Exception();
    }
}

final readonly class AQuery implements QueryInterface
{
}

final readonly class AQueryHandler implements QueryHandlerInterface
{
    public function __invoke(AQuery $query): SerializableObjectsCollection
    {
        return new SerializableObjectsCollection(
            new SerializableObject()
        );
    }
}

final readonly class SerializableObject implements SerializableInterface
{
    #[\Override]
    public static function deserialize(array $data): self
    {
        return new self();
    }

    #[\Override]
    public function serialize(): array
    {
        return [];
    }
}

final readonly class SerializableObjectsCollection implements SerializableInterface
{
    /** @var SerializableObject[] */
    private array $objects;

    public function __construct(
        SerializableObject ...$objects,
    ) {
        $this->objects = $objects;
    }

    /**
     * @param array<array<string, mixed>> $data
     */
    #[\Override]
    public static function deserialize(array $data): self
    {
        return new self(...array_map(static fn (array $object): SerializableObject => SerializableObject::deserialize($object), $data));
    }

    /**
     * @return array<array<string, mixed>>
     */
    #[\Override]
    public function serialize(): array
    {
        return array_map(static fn (SerializableObject $object): array => $object->serialize(), $this->objects);
    }
}

<?php

declare(strict_types=1);

namespace SharedBundle\CommandHandling\Testing\Query;

use Assert\Assertion;
use Shared\CommandHandling\Item;
use Shared\CommandHandling\QueryHandlerInterface;
use Shared\Domain\Uuid;
use Shared\ReadModel\SerializableReadModelInterface;

final readonly class AnotherHandler implements QueryHandlerInterface
{
    public function __invoke(AnotherQuery $query): Item
    {
        return Item::fromSerializable(ASerializable::deserialize([
            'id' => '9db0db88-3e44-4d2b-b46f-9ca547de06ac',
        ]));
    }
}

final readonly class ASerializable implements SerializableReadModelInterface
{
    private function __construct(
        public Uuid $id,
    ) {
    }

    #[\Override]
    public static function deserialize(array $data): self
    {
        Assertion::keyExists($data, 'id');

        return new self(
            new Uuid($data['id']),
        );
    }

    #[\Override]
    public function serialize(): array
    {
        return [
            'id' => $this->id->uuid,
        ];
    }

    #[\Override]
    public function id(): Uuid
    {
        return $this->id;
    }
}

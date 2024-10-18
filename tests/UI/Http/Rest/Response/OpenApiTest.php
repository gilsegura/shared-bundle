<?php

declare(strict_types=1);

namespace SharedBundle\Tests\UI\Http\Rest\Response;

use Assert\Assertion;
use PHPUnit\Framework\TestCase;
use Shared\CommandHandling\Collection;
use Shared\CommandHandling\Item;
use Shared\Domain\Uuid;
use Shared\ReadModel\SerializableReadModelInterface;
use SharedBundle\UI\Http\Rest\Response\OpenApi;

final class OpenApiTest extends TestCase
{
    public function test_must_format_response(): void
    {
        $empty = OpenApi::empty();

        self::assertInstanceOf(OpenApi::class, $empty);
        self::assertSame('{}', $empty->getContent());
        self::assertSame(OpenApi::HTTP_OK, $empty->getStatusCode());

        $created = OpenApi::created();

        self::assertInstanceOf(OpenApi::class, $created);
        self::assertSame('{}', $created->getContent());
        self::assertSame(OpenApi::HTTP_CREATED, $created->getStatusCode());

        $fromPayload = OpenApi::fromPayload(['data' => 'OK'], OpenApi::HTTP_OK);

        self::assertInstanceOf(OpenApi::class, $fromPayload);
        self::assertSame('{"data":"OK"}', $fromPayload->getContent());
        self::assertSame(OpenApi::HTTP_OK, $fromPayload->getStatusCode());

        $item = Item::fromSerializable(Serializable::deserialize([
            'id' => '9db0db88-3e44-4d2b-b46f-9ca547de06ac',
        ]));

        $one = OpenApi::one($item, OpenApi::HTTP_OK);

        self::assertInstanceOf(OpenApi::class, $one);
        self::assertSame('{"data":{"id":"9db0db88-3e44-4d2b-b46f-9ca547de06ac","type":"Serializable","attributes":{"id":"9db0db88-3e44-4d2b-b46f-9ca547de06ac"}}}', $one->getContent());
        self::assertSame(OpenApi::HTTP_OK, $one->getStatusCode());

        $collection = OpenApi::collection(new Collection(1, 1, 1, [$item]), OpenApi::HTTP_OK);

        self::assertInstanceOf(OpenApi::class, $collection);
        self::assertSame('{"meta":{"size":1,"page":1,"total":1},"data":[{"id":"9db0db88-3e44-4d2b-b46f-9ca547de06ac","type":"Serializable","attributes":{"id":"9db0db88-3e44-4d2b-b46f-9ca547de06ac"}}]}', $collection->getContent());
        self::assertSame(OpenApi::HTTP_OK, $collection->getStatusCode());
    }
}

final readonly class Serializable implements SerializableReadModelInterface
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

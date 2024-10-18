<?php

declare(strict_types=1);

namespace SharedBundle\UI\Http\Rest\Response;

use Shared\CommandHandling\Collection;
use Shared\CommandHandling\Item;
use Symfony\Component\HttpFoundation\JsonResponse;

final class OpenApi extends JsonResponse
{
    private function __construct(mixed $data = null, int $status = self::HTTP_OK, array $headers = [], bool $json = false)
    {
        parent::__construct($data, $status, $headers, $json);
    }

    public static function empty(int $status = self::HTTP_OK): self
    {
        return new self(null, $status);
    }

    public static function created(): self
    {
        return new self(null, self::HTTP_CREATED);
    }

    public static function fromPayload(array $payload, int $status): self
    {
        return new self($payload, $status);
    }

    public static function one(Item $item, int $status = self::HTTP_OK): self
    {
        return new self(
            [
                'data' => self::resource($item),
            ],
            $status
        );
    }

    public static function collection(Collection $collection, int $status = self::HTTP_OK): self
    {
        $resources = array_map(static fn (Item $data): array => self::resource($data), $collection->data);

        return new self(
            [
                'meta' => [
                    'size' => $collection->limit,
                    'page' => $collection->page,
                    'total' => $collection->total,
                ],
                'data' => $resources,
            ],
            $status
        );
    }

    private static function resource(Item $item): array
    {
        return [
            'id' => $item->id,
            'type' => $item->type,
            'attributes' => $item->payload,
        ];
    }
}

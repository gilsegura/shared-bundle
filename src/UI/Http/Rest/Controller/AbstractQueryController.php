<?php

declare(strict_types=1);

namespace SharedBundle\UI\Http\Rest\Controller;

use Shared\CommandHandling\Collection;
use Shared\CommandHandling\Item;
use Shared\CommandHandling\QueryBusInterface;
use Shared\CommandHandling\QueryInterface;
use SharedBundle\UI\Http\Rest\Exception\ExceptionHttpStatusCodeMapping;
use SharedBundle\UI\Http\Rest\Response\OpenApi;

abstract readonly class AbstractQueryController
{
    private const int CACHE_MAX_AGE = 31536000; // Year.

    public function __construct(
        private QueryBusInterface $queryBus,
        ExceptionHttpStatusCodeMapping $exceptionHttpStatusCodeMapping,
    ) {
        foreach ($this->exceptions() as $exception => $statusCode) {
            $exceptionHttpStatusCodeMapping->register($exception, $statusCode);
        }
    }

    abstract protected function exceptions(): array;

    final protected function ask(QueryInterface $query): Item|Collection
    {
        return $this->queryBus->ask($query);
    }

    final protected function jsonCollection(Collection $collection, int $status = OpenApi::HTTP_OK, bool $isImmutable = false): OpenApi
    {
        $response = OpenApi::collection($collection, $status);

        $this->decorateWithCache($response, $collection, $isImmutable);

        return $response;
    }

    final protected function json(Item $item, int $status = OpenApi::HTTP_OK): OpenApi
    {
        return OpenApi::one($item, $status);
    }

    private function decorateWithCache(OpenApi $response, Collection $collection, bool $isImmutable): void
    {
        if (!$isImmutable) {
            return;
        }

        if ($collection->limit !== \count($collection->data)) {
            return;
        }

        $response
            ->setMaxAge(self::CACHE_MAX_AGE)
            ->setSharedMaxAge(self::CACHE_MAX_AGE);
    }
}

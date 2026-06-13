<?php

declare(strict_types=1);

namespace SharedBundle\Tests\CommandHandling;

use Shared\CommandHandling\QueryHandlerInterface;

/**
 * @implements QueryHandlerInterface<SerializableObjectsCollection, ThrowableQuery>
 */
final readonly class ThrowableQueryHandler implements QueryHandlerInterface
{
    public function __invoke(ThrowableQuery $query): SerializableObjectsCollection
    {
        throw new \Exception();
    }
}

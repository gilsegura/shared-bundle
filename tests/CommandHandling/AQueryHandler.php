<?php

declare(strict_types=1);

namespace SharedBundle\Tests\CommandHandling;

use Shared\CommandHandling\QueryHandlerInterface;

/**
 * @implements QueryHandlerInterface<SerializableObjectsCollection, AQuery>
 */
final readonly class AQueryHandler implements QueryHandlerInterface
{
    public function __invoke(AQuery $query): SerializableObjectsCollection
    {
        return new SerializableObjectsCollection(
            new SerializableObject()
        );
    }
}

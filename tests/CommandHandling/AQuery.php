<?php

declare(strict_types=1);

namespace SharedBundle\Tests\CommandHandling;

use Shared\CommandHandling\QueryInterface;

/**
 * @implements QueryInterface<SerializableObjectsCollection>
 */
final readonly class AQuery implements QueryInterface
{
}

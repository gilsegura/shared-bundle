<?php

declare(strict_types=1);

namespace SharedBundle\CommandHandling\Testing\Query;

use Shared\CommandHandling\Collection;
use Shared\CommandHandling\QueryHandlerInterface;

final readonly class ThrowableHandler implements QueryHandlerInterface
{
    /**
     * @throws \Exception
     */
    public function __invoke(ThrowableQuery $query): Collection
    {
        throw new \Exception();
    }
}

<?php

declare(strict_types=1);

namespace SharedBundle\CommandHandling\Testing\Query;

use Shared\CommandHandling\Collection;
use Shared\CommandHandling\PageNotFoundException;
use Shared\CommandHandling\QueryHandlerInterface;

final readonly class AHandler implements QueryHandlerInterface
{
    /**
     * @throws PageNotFoundException
     */
    public function __invoke(AQuery $query): Collection
    {
        return new Collection(1, 10, 0, []);
    }
}

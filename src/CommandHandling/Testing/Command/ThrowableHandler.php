<?php

declare(strict_types=1);

namespace SharedBundle\CommandHandling\Testing\Command;

use Shared\CommandHandling\CommandHandlerInterface;

final readonly class ThrowableHandler implements CommandHandlerInterface
{
    /**
     * @throws \Exception
     */
    public function __invoke(ThrowableCommand $command): void
    {
        throw new \Exception();
    }
}

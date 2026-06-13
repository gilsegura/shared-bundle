<?php

declare(strict_types=1);

namespace SharedBundle\Tests\CommandHandling;

use Shared\CommandHandling\CommandHandlerInterface;

/**
 * @implements CommandHandlerInterface<ThrowableCommand>
 */
final readonly class ThrowableCommandHandler implements CommandHandlerInterface
{
    public function __invoke(ThrowableCommand $command): void
    {
        throw new \Exception();
    }
}

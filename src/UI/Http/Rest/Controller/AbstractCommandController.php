<?php

declare(strict_types=1);

namespace SharedBundle\UI\Http\Rest\Controller;

use Shared\CommandHandling\CommandBusInterface;
use Shared\CommandHandling\CommandInterface;
use SharedBundle\UI\Http\Rest\Exception\ExceptionHttpStatusCodeMapping;

abstract readonly class AbstractCommandController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        ExceptionHttpStatusCodeMapping $exceptionHttpStatusCodeMapping,
    ) {
        foreach ($this->exceptions() as $exception => $statusCode) {
            $exceptionHttpStatusCodeMapping->register($exception, $statusCode);
        }
    }

    abstract protected function exceptions(): array;

    final protected function handle(CommandInterface $command): void
    {
        $this->commandBus->handle($command);
    }
}

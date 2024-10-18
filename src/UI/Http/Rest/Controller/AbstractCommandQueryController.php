<?php

declare(strict_types=1);

namespace SharedBundle\UI\Http\Rest\Controller;

use Shared\CommandHandling\CommandBusInterface;
use Shared\CommandHandling\CommandInterface;
use Shared\CommandHandling\QueryBusInterface;
use SharedBundle\UI\Http\Rest\Exception\ExceptionHttpStatusCodeMapping;

abstract readonly class AbstractCommandQueryController extends AbstractQueryController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        QueryBusInterface $queryBus,
        ExceptionHttpStatusCodeMapping $exceptionHttpStatusCodeMapping,
    ) {
        parent::__construct($queryBus, $exceptionHttpStatusCodeMapping);
    }

    final protected function handle(CommandInterface $command): void
    {
        $this->commandBus->handle($command);
    }
}

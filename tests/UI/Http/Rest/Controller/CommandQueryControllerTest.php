<?php

declare(strict_types=1);

namespace SharedBundle\Tests\UI\Http\Rest\Controller;

use Shared\CommandHandling\CommandBusInterface;
use Shared\CommandHandling\Item;
use Shared\CommandHandling\QueryBusInterface;
use SharedBundle\CommandHandling\Testing\Command\ACommand;
use SharedBundle\CommandHandling\Testing\Query\AnotherQuery;
use SharedBundle\UI\Http\Rest\Controller\AbstractCommandQueryController;
use SharedBundle\UI\Http\Rest\Exception\ExceptionHttpStatusCodeMapping;
use SharedBundle\UI\Http\Rest\Response\OpenApi;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

final class CommandQueryControllerTest extends KernelTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function test_must_handle_command_query(): void
    {
        $commandBus = self::getContainer()->get(CommandBusInterface::class);
        $queryBus = self::getContainer()->get(QueryBusInterface::class);
        $exceptionHttpStatusCodeMapping = self::getContainer()->get(ExceptionHttpStatusCodeMapping::class);

        $controller = new CommandQueryController($commandBus, $queryBus, $exceptionHttpStatusCodeMapping);

        $response = $controller->__invoke();

        self::assertSame(OpenApi::HTTP_OK, $response->getStatusCode());
    }
}

final readonly class CommandQueryController extends AbstractCommandQueryController
{
    public function __invoke(): JsonResponse
    {
        $this->handle(new ACommand());

        /** @var Item $item */
        $item = $this->ask(new AnotherQuery());

        return $this->json($item);
    }

    #[\Override]
    protected function exceptions(): array
    {
        return [
            CommandQueryControllerException::class => 555,
        ];
    }
}

final class CommandQueryControllerException extends \Exception
{
}

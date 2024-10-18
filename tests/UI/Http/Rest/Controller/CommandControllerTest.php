<?php

declare(strict_types=1);

namespace SharedBundle\Tests\UI\Http\Rest\Controller;

use Shared\CommandHandling\CommandBusInterface;
use SharedBundle\CommandHandling\Testing\Command\ACommand;
use SharedBundle\UI\Http\Rest\Controller\AbstractCommandController;
use SharedBundle\UI\Http\Rest\Exception\ExceptionHttpStatusCodeMapping;
use SharedBundle\UI\Http\Rest\Response\OpenApi;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

final class CommandControllerTest extends KernelTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function test_must_handle_command(): void
    {
        $commandBus = self::getContainer()->get(CommandBusInterface::class);
        $exceptionHttpStatusCodeMapping = self::getContainer()->get(ExceptionHttpStatusCodeMapping::class);

        $controller = new CommandController($commandBus, $exceptionHttpStatusCodeMapping);

        $response = $controller->__invoke();

        self::assertSame(OpenApi::HTTP_CREATED, $response->getStatusCode());
    }
}

final readonly class CommandController extends AbstractCommandController
{
    public function __invoke(): JsonResponse
    {
        $this->handle(new ACommand());

        return OpenApi::created();
    }

    #[\Override]
    protected function exceptions(): array
    {
        return [
            CommandControllerException::class => 555,
        ];
    }
}

final class CommandControllerException extends \Exception
{
}

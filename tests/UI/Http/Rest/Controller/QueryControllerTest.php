<?php

declare(strict_types=1);

namespace SharedBundle\Tests\UI\Http\Rest\Controller;

use Shared\CommandHandling\Collection;
use Shared\CommandHandling\QueryBusInterface;
use SharedBundle\CommandHandling\Testing\Query\AQuery;
use SharedBundle\UI\Http\Rest\Controller\AbstractQueryController;
use SharedBundle\UI\Http\Rest\Exception\ExceptionHttpStatusCodeMapping;
use SharedBundle\UI\Http\Rest\Response\OpenApi;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

final class QueryControllerTest extends KernelTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function test_must_handle_query(): void
    {
        $queryBus = self::getContainer()->get(QueryBusInterface::class);
        $exceptionHttpStatusCodeMapping = self::getContainer()->get(ExceptionHttpStatusCodeMapping::class);

        $controller = new QueryController($queryBus, $exceptionHttpStatusCodeMapping);

        $response = $controller->__invoke();

        self::assertSame(OpenApi::HTTP_OK, $response->getStatusCode());
    }
}

final readonly class QueryController extends AbstractQueryController
{
    public function __invoke(): JsonResponse
    {
        /** @var Collection $collection */
        $collection = $this->ask(new AQuery());

        return $this->jsonCollection($collection);
    }

    #[\Override]
    protected function exceptions(): array
    {
        return [
            QueryControllerException::class => 555,
        ];
    }
}

final class QueryControllerException extends \Exception
{
}

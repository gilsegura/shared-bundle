<?php

declare(strict_types=1);

namespace SharedBundle\Tests\UI\Http\Rest\Exception;

use PHPUnit\Framework\TestCase;
use SharedBundle\UI\Http\Rest\Exception\ExceptionHttpStatusCodeMapping;
use SharedBundle\UI\Http\Rest\Response\OpenApi;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class ExceptionHttpStatusCodeMappingTest extends TestCase
{
    public function test_must_register_an_exception(): void
    {
        $expected = 555;

        $mapping = new ExceptionHttpStatusCodeMapping();
        $mapping->register(\RuntimeException::class, $expected);

        $statusCode = $mapping->handle(new \RuntimeException());

        self::assertSame($expected, $statusCode);
    }

    public function test_must_handle_unregistered_exception(): void
    {
        $mapping = new ExceptionHttpStatusCodeMapping();

        $statusCode = $mapping->handle(new \RuntimeException());

        self::assertSame(OpenApi::HTTP_INTERNAL_SERVER_ERROR, $statusCode);
    }

    public function test_must_handle_http_exception(): void
    {
        $mapping = new ExceptionHttpStatusCodeMapping();

        $statusCode = $mapping->handle(new ConflictHttpException());

        self::assertSame(OpenApi::HTTP_CONFLICT, $statusCode);
    }
}

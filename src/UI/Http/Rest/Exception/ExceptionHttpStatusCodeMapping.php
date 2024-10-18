<?php

declare(strict_types=1);

namespace SharedBundle\UI\Http\Rest\Exception;

use SharedBundle\UI\Http\Rest\Response\OpenApi;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class ExceptionHttpStatusCodeMapping
{
    private array $mapping = [
        \InvalidArgumentException::class => OpenApi::HTTP_BAD_REQUEST,
    ];

    public function register(string $exception, int $statusCode): void
    {
        $this->mapping[$exception] = $statusCode;
    }

    public function handle(\Throwable $exception): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if (!array_key_exists($exception::class, $this->mapping)) {
            return OpenApi::HTTP_INTERNAL_SERVER_ERROR;
        }

        return $this->mapping[$exception::class];
    }
}

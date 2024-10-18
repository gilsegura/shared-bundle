<?php

declare(strict_types=1);

namespace SharedBundle\UI\Http\Rest\Exception;

trait ExceptionMessageTrait
{
    public function error(\Throwable $exception): array
    {
        return [
            'error' => [
                'title' => \str_replace('\\', '.', $exception::class),
                'detail' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ],
        ];
    }

    public function metadata(\Throwable $exception): array
    {
        return [
            'meta' => [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'trace' => $exception->getTrace(),
                'traceString' => $exception->getTraceAsString(),
            ],
        ];
    }
}

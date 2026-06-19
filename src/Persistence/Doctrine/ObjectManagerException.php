<?php

declare(strict_types=1);

namespace SharedBundle\Persistence\Doctrine;

use Shared\Exception\InfrastructureException;

/**
 * Raised when a Doctrine-backed object manager operation fails.
 */
final class ObjectManagerException extends InfrastructureException
{
    public static function throwable(\Throwable $e): self
    {
        return new self($e->getMessage(), previous: $e);
    }
}

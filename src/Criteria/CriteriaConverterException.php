<?php

declare(strict_types=1);

namespace SharedBundle\Criteria;

use Shared\Exception\UnexpectedException;

/**
 * Raised when a Shared\Criteria cannot be translated into a Doctrine
 * criteria.
 */
final class CriteriaConverterException extends UnexpectedException
{
    public static function unsupportedExpression(string $className): self
    {
        return new self(\sprintf('The requested expression "%s" is not supported.', $className));
    }
}

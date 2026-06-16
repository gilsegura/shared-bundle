<?php

declare(strict_types=1);

namespace SharedBundle\Criteria;

use Shared\Exception\UnexpectedException;

final class CriteriaConverterException extends UnexpectedException
{
    public static function unsupportedExpression(string $className): self
    {
        return new self(\sprintf('The requested expression "%s" is not supported.', $className));
    }
}

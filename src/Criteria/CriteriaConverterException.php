<?php

declare(strict_types=1);

namespace SharedBundle\Criteria;

final class CriteriaConverterException extends \Exception
{
    public static function unsupportedExpression(string $className): self
    {
        return new self(sprintf('The requested expression "%s" does not supported.', $className), 400);
    }
}

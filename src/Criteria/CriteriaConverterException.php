<?php

declare(strict_types=1);

namespace SharedBundle\Criteria;

use Shared\Criteria\ExpressionInterface;

final class CriteriaConverterException extends \RuntimeException
{
    public static function new(ExpressionInterface $expr): self
    {
        return new self(sprintf('Expression "%s" not supported.', $expr::class));
    }
}

<?php

declare(strict_types=1);

namespace SharedBundle\Persistence\Doctrine;

use Doctrine\Common\Collections as Doctrine;
use ProxyAssert\Assertion;
use Shared\Criteria;
use SharedBundle\Criteria\CriteriaConverterException;

final readonly class DoctrineCriteriaConverter
{
    private function __construct()
    {
    }

    public static function convert(
        Criteria\AndX|Criteria\OrX|null $criteria = null,
        ?Criteria\OrderX $sort = null,
        ?int $offset = null,
        ?int $limit = null,
    ): Doctrine\Criteria {
        return new Doctrine\Criteria(
            null !== $criteria ? self::expression()($criteria->expr()) : null,
            /* @phpstan-ignore argument.type */
            [...self::orderings($sort)] ?: null,
            $offset,
            $limit
        );
    }

    private static function expression(): \Closure
    {
        return static function (Criteria\ExpressionInterface $expr): Doctrine\Expr\Expression {
            return match (true) {
                $expr instanceof Criteria\Expr\AndX => new Doctrine\Expr\CompositeExpression(
                    Doctrine\Expr\CompositeExpression::TYPE_AND,
                    array_map(self::expression(), $expr->expressions)
                ),
                $expr instanceof Criteria\Expr\OrX => new Doctrine\Expr\CompositeExpression(
                    Doctrine\Expr\CompositeExpression::TYPE_OR,
                    array_map(self::expression(), $expr->expressions)
                ),
                $expr instanceof Criteria\Expr\Comparison => new Doctrine\Expr\Comparison(
                    $expr->field,
                    $expr->operator->value,
                    $expr->value
                ),
                default => throw CriteriaConverterException::unsupportedExpression($expr::class),
            };
        };
    }

    private static function orderings(?Criteria\OrderX $sort = null): \Generator
    {
        if (!$sort instanceof Criteria\OrderX) {
            return [];
        }

        $orderX = $sort->expr();

        Assertion::isInstanceOf($orderX, Criteria\Expr\OrderX::class);

        /** @var Criteria\Expr\Sort[] $expressions */
        $expressions = $orderX->expressions;

        foreach ($expressions as $expression) {
            yield $expression->field => $expression->order->value;
        }
    }
}

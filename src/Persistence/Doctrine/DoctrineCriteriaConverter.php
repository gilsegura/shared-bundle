<?php

declare(strict_types=1);

namespace SharedBundle\Persistence\Doctrine;

use Doctrine\Common\Collections as Doctrine;
use Shared\Criteria;
use SharedBundle\Criteria\CriteriaConverterException;

/**
 * Translates the Shared\Criteria DSL into a Doctrine criteria so
 * repositories query with the domain's own filter and sort objects.
 */
final readonly class DoctrineCriteriaConverter
{
    public static function convert(
        Criteria\AndX|Criteria\OrX|null $criteria = null,
        ?Criteria\OrderX $sort = null,
        ?int $offset = null,
        ?int $limit = null,
    ): Doctrine\Criteria {
        return new Doctrine\Criteria(
            null !== $criteria ? self::expression($criteria->expr()) : null,
            self::orderings($sort),
            $offset,
            $limit,
        );
    }

    private static function expression(Criteria\ExpressionInterface $expr): Doctrine\Expr\Expression
    {
        return match (true) {
            $expr instanceof Criteria\Expr\AndX => new Doctrine\Expr\CompositeExpression(
                Doctrine\Expr\CompositeExpression::TYPE_AND,
                array_map(self::expression(...), $expr->expressions),
            ),
            $expr instanceof Criteria\Expr\OrX => new Doctrine\Expr\CompositeExpression(
                Doctrine\Expr\CompositeExpression::TYPE_OR,
                array_map(self::expression(...), $expr->expressions),
            ),
            $expr instanceof Criteria\Expr\Comparison => new Doctrine\Expr\Comparison(
                $expr->field,
                $expr->operator->value,
                $expr->value,
            ),
            default => throw CriteriaConverterException::unsupportedExpression($expr::class),
        };
    }

    /**
     * @return array<string, string>
     */
    private static function orderings(?Criteria\OrderX $sort = null): array
    {
        if (!$sort instanceof Criteria\OrderX) {
            return [];
        }

        $orderX = $sort->expr();

        if (!$orderX instanceof Criteria\Expr\OrderX) {
            throw CriteriaConverterException::unsupportedExpression($orderX::class);
        }

        $orderings = [];

        foreach ($orderX->expressions as $expression) {
            if (!$expression instanceof Criteria\Expr\Sort) {
                throw CriteriaConverterException::unsupportedExpression($expression::class);
            }

            $orderings[$expression->field] = $expression->order->value;
        }

        return $orderings;
    }
}

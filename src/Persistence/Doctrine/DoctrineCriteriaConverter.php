<?php

declare(strict_types=1);

namespace SharedBundle\Persistence\Doctrine;

use Doctrine\Common\Collections as Doctrine;
use Shared\Criteria;
use Shared\Criteria\CriteriaInterface;
use SharedBundle\Criteria\CriteriaConverterException;

final readonly class DoctrineCriteriaConverter
{
    /**
     * @throws CriteriaConverterException
     */
    public static function convert(
        Criteria\AndX|Criteria\OrX|null $criteria = null,
        ?Criteria\OrderX $sort = null,
        ?int $offset = null,
        ?int $limit = null,
    ): Doctrine\Criteria {
        $converter = new self();

        $expression = $criteria instanceof CriteriaInterface ? call_user_func($converter->expression(), $criteria->expr()) : null;
        $orderings = $sort instanceof CriteriaInterface ? iterator_to_array($converter->orderings($sort->expr())) : null;

        return new Doctrine\Criteria(
            $expression,
            $orderings,
            $offset,
            $limit
        );
    }

    private function expression(): \Closure
    {
        return function (Criteria\ExpressionInterface $expr): Doctrine\Expr\Expression {
            return match (true) {
                $expr instanceof Criteria\Expr\AndX => new Doctrine\Expr\CompositeExpression(
                    Doctrine\Expr\CompositeExpression::TYPE_AND,
                    array_map($this->expression(), $expr->expressions)
                ),
                $expr instanceof Criteria\Expr\OrX => new Doctrine\Expr\CompositeExpression(
                    Doctrine\Expr\CompositeExpression::TYPE_OR,
                    array_map($this->expression(), $expr->expressions)
                ),
                $expr instanceof Criteria\Expr\Comparison => new Doctrine\Expr\Comparison(
                    $expr->field,
                    $expr->operator->value,
                    $expr->value
                ),
                default => throw CriteriaConverterException::new($expr),
            };
        };
    }

    private function orderings(Criteria\ExpressionInterface $expr): \Generator
    {
        if (!$expr instanceof Criteria\Expr\OrderX) {
            throw CriteriaConverterException::new($expr);
        }

        /** @var Criteria\Expr\Sort $order */
        foreach ($expr->expressions as $order) {
            yield $order->field => $order->order->value;
        }
    }
}

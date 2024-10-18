<?php

declare(strict_types=1);

namespace SharedBundle\Persistence\Doctrine;

use Doctrine\Common\Collections\Selectable;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Shared\Criteria;
use SharedBundle\Criteria\CriteriaConverterException;

abstract readonly class AbstractObjectManager
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ObjectRepository&Selectable $repository,
    ) {
    }

    /**
     * @return object[]
     *
     * @throws CriteriaConverterException
     */
    final protected function search(
        Criteria\AndX|Criteria\OrX|null $criteria = null,
        ?Criteria\OrderX $sort = null,
        ?int $offset = null,
        ?int $limit = null,
    ): array {
        $criteria = DoctrineCriteriaConverter::convert($criteria, $sort, $offset, $limit);

        return $this->repository
            ->matching($criteria)
            ->toArray();
    }

    /**
     * @throws CriteriaConverterException
     */
    final protected function count(Criteria\AndX|Criteria\OrX|null $criteria = null): int
    {
        $criteria = DoctrineCriteriaConverter::convert($criteria);

        return $this->repository
            ->matching($criteria)
            ->count();
    }

    /**
     * @throws ConstraintViolationException
     * @throws ORMException
     */
    final protected function register(object $model): void
    {
        $this->manager->persist($model);
        $this->apply();
    }

    /**
     * @throws ORMException
     */
    final protected function unregister(object $model): void
    {
        $this->manager->remove($model);
        $this->apply();
    }

    /**
     * @throws ORMException
     */
    private function apply(): void
    {
        $this->manager->flush();
    }
}

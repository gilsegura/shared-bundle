<?php

declare(strict_types=1);

namespace SharedBundle\Persistence\Doctrine;

use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Shared\Criteria;

/**
 * @psalm-template TKey as array-key
 *
 * @template-covariant T of object
 */
abstract readonly class AbstractObjectManager
{
    public function __construct(
        private EntityManagerInterface $manager,
        /** @var ObjectRepository<T>&Selectable<TKey, T> */
        private ObjectRepository&Selectable $repository,
    ) {
    }

    /**
     * @throws ObjectManagerException
     */
    final protected function search(
        Criteria\AndX|Criteria\OrX|null $criteria = null,
        ?Criteria\OrderX $sort = null,
        ?int $offset = null,
        ?int $limit = null,
    ): array {
        try {
            $criteria = DoctrineCriteriaConverter::convert($criteria, $sort, $offset, $limit);

            return $this->repository
                ->matching($criteria)
                ->toArray();
        } catch (\Throwable $e) {
            throw ObjectManagerException::throwable($e);
        }
    }

    /**
     * @throws ObjectManagerException
     */
    final protected function count(Criteria\AndX|Criteria\OrX|null $criteria = null): int
    {
        try {
            $criteria = DoctrineCriteriaConverter::convert($criteria);

            return $this->repository
                ->matching($criteria)
                ->count();
        } catch (\Throwable $e) {
            throw ObjectManagerException::throwable($e);
        }
    }

    /**
     * @throws ObjectManagerException
     */
    final protected function register(object $model): void
    {
        try {
            $this->manager->persist($model);
            $this->apply();
        } catch (\Throwable $e) {
            throw ObjectManagerException::throwable($e);
        }
    }

    /**
     * @throws ObjectManagerException
     */
    final protected function unregister(object $model): void
    {
        try {
            $this->manager->remove($model);
            $this->apply();
        } catch (\Throwable $e) {
            throw ObjectManagerException::throwable($e);
        }
    }

    /**
     * @throws ObjectManagerException
     */
    private function apply(): void
    {
        $this->manager->flush();
    }
}

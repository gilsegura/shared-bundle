<?php

declare(strict_types=1);

namespace SharedBundle\Persistence\Doctrine;

use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Shared\Criteria;
use Shared\Query\Pagination;

/**
 * @template TKey of array-key
 *
 * @template-covariant T of object
 */
abstract readonly class AbstractObjectManager
{
    /** @var ObjectRepository<T>&Selectable<TKey, T> */
    private ObjectRepository&Selectable $repository;

    /**
     * Subclasses pass the entity class they manage; the Doctrine repository is
     * resolved here, so a concrete object manager never calls getRepository()
     * itself. Combined with #[ObjectManager], it needs no constructor at all.
     *
     * @param class-string<T> $entity
     */
    public function __construct(
        private EntityManagerInterface $manager,
        string $entity,
    ) {
        $this->repository = $manager->getRepository($entity);
    }

    /**
     * @return T[]
     *
     * @throws ObjectManagerException
     */
    final protected function search(
        Criteria\AndX|Criteria\OrX|null $criteria = null,
        ?Criteria\OrderX $sort = null,
        ?Pagination $pagination = null,
    ): array {
        try {
            $criteria = DoctrineCriteriaConverter::convert(
                $criteria,
                $sort,
                $pagination?->offset,
                $pagination?->limit,
            );

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

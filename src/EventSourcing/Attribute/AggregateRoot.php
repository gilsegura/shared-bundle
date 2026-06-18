<?php

declare(strict_types=1);

namespace SharedBundle\EventSourcing\Attribute;

/**
 * Marks a repository extending AbstractEventSourcingRepository and declares the
 * aggregate root it manages. A compiler pass reads this and injects the event
 * store, event bus, stream decorator and a factory built for the aggregate, so
 * the repository needs no constructor of its own.
 *
 *     #[AggregateRoot(User::class)]
 *     final readonly class UserRepository extends AbstractEventSourcingRepository
 *         implements UserRepositoryInterface { ... }
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AggregateRoot
{
    /**
     * @param class-string $aggregateRoot
     */
    public function __construct(
        public string $aggregateRoot,
    ) {
    }
}

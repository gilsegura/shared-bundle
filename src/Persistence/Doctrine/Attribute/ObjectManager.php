<?php

declare(strict_types=1);

namespace SharedBundle\Persistence\Doctrine\Attribute;

/**
 * Marks a class extending AbstractObjectManager and declares the entity it
 * manages. A compiler pass reads this and injects the entity manager plus the
 * entity class-string, so the object manager needs no constructor of its own.
 *
 *     #[ObjectManager(DomainMessage::class)]
 *     final readonly class DoctrineEventStore extends AbstractObjectManager { ... }
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class ObjectManager
{
    /**
     * @param class-string $entity
     */
    public function __construct(
        public string $entity,
    ) {
    }
}

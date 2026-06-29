<?php

declare(strict_types=1);

namespace SharedBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\SerializationFailed;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;
use Shared\EventSourcing\AggregateRootInterface;

/**
 * Doctrine DBAL type that stores an aggregate root as an opaque blob using PHP's
 * native serialize(), for snapshots. Unlike SerializableType it does not require
 * the value to implement SerializableInterface — the aggregate stays unaware of
 * snapshotting — so the whole object graph is captured and restored as-is.
 */
final class AggregateRootType extends Type
{
    public const string NAME = 'aggregate_root';

    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof AggregateRootInterface) {
            throw InvalidType::new($value, self::class, ['null', AggregateRootInterface::class]);
        }

        try {
            return \serialize($value);
        } catch (\Throwable $e) {
            throw SerializationFailed::new($value, self::NAME, $e->getMessage(), $e);
        }
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?AggregateRootInterface
    {
        if (null === $value) {
            return null;
        }

        if (\is_resource($value)) {
            $value = \stream_get_contents($value);
        }

        if (!\is_string($value)) {
            throw ValueNotConvertible::new(\get_debug_type($value), self::NAME);
        }

        $aggregateRoot = \unserialize($value);

        if (!$aggregateRoot instanceof AggregateRootInterface) {
            throw ValueNotConvertible::new(\get_debug_type($aggregateRoot), self::NAME);
        }

        return $aggregateRoot;
    }
}

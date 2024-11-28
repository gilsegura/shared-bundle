<?php

declare(strict_types=1);

namespace SharedBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Type;
use Shared\Domain\HighResolutionTimeImmutable;

final class HighResolutionTimeImmutableType extends Type
{
    public const string NAME = 'high_resolution_time_immutable';

    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getBigIntTypeDeclarationSQL($column);
    }

    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?int
    {
        if (
            null === $value
            || $value instanceof HighResolutionTimeImmutable
        ) {
            return $value?->time;
        }

        if (!\is_int($value)) {
            throw InvalidType::new($value, self::class, ['null', self::class]);
        }

        try {
            return (new HighResolutionTimeImmutable($value))->time;
        } catch (\Throwable $e) {
            throw InvalidType::new($value, self::class, ['null', self::class], $e);
        }
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?HighResolutionTimeImmutable
    {
        if (
            null === $value
            || $value instanceof HighResolutionTimeImmutable
        ) {
            return $value;
        }

        if (!\is_int($value)) {
            throw InvalidFormat::new(\get_debug_type($value), self::class, self::NAME);
        }

        try {
            return new HighResolutionTimeImmutable($value);
        } catch (\Throwable $e) {
            throw InvalidFormat::new(\get_debug_type($value), self::class, self::NAME, $e);
        }
    }
}

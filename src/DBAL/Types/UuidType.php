<?php

declare(strict_types=1);

namespace SharedBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Type;
use Shared\Domain\Uuid;

final class UuidType extends Type
{
    public const string NAME = 'uuid';

    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getGuidTypeDeclarationSQL($column);
    }

    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (
            null === $value
            || $value instanceof Uuid
        ) {
            return $value?->uuid;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::class, ['null', self::class]);
        }

        try {
            return (new Uuid($value))->uuid;
        } catch (\Throwable $e) {
            throw InvalidType::new($value, self::class, ['null', self::class], $e);
        }
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Uuid
    {
        if (
            null === $value
            || $value instanceof Uuid
        ) {
            return $value;
        }

        if (!\is_string($value)) {
            throw InvalidFormat::new(\get_debug_type($value), self::class, self::NAME);
        }

        try {
            return new Uuid($value);
        } catch (\Throwable $e) {
            throw InvalidFormat::new(\get_debug_type($value), self::class, self::NAME, $e);
        }
    }
}

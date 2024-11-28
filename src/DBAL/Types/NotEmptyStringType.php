<?php

declare(strict_types=1);

namespace SharedBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Type;
use Shared\Domain\NotEmptyString;

final class NotEmptyStringType extends Type
{
    public const string NAME = 'not_empty_string';

    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }

    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (
            null === $value
            || $value instanceof NotEmptyString
        ) {
            return $value?->string;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::class, ['null', self::class]);
        }

        try {
            return (new NotEmptyString($value))->string;
        } catch (\Throwable $e) {
            throw InvalidType::new($value, self::class, ['null', self::class], $e);
        }
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?NotEmptyString
    {
        if (
            null === $value
            || $value instanceof NotEmptyString
        ) {
            return $value;
        }

        if (!\is_string($value)) {
            throw InvalidFormat::new(\get_debug_type($value), self::class, self::NAME);
        }

        try {
            return new NotEmptyString($value);
        } catch (\Throwable $e) {
            throw InvalidFormat::new(\get_debug_type($value), self::class, self::NAME, $e);
        }
    }
}

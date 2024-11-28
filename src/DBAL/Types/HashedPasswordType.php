<?php

declare(strict_types=1);

namespace SharedBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Type;
use Shared\Domain\HashedPassword;

final class HashedPasswordType extends Type
{
    public const string NAME = 'hashed_password';

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
            || $value instanceof HashedPassword
        ) {
            return $value?->password;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::class, ['null', self::class]);
        }

        try {
            return (new HashedPassword($value))->password;
        } catch (\Throwable $e) {
            throw InvalidType::new($value, self::class, ['null', self::class], $e);
        }
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?HashedPassword
    {
        if (
            null === $value
            || $value instanceof HashedPassword
        ) {
            return $value;
        }

        if (!\is_string($value)) {
            throw InvalidFormat::new(\get_debug_type($value), self::class, self::NAME);
        }

        try {
            return new HashedPassword($value);
        } catch (\Throwable $e) {
            throw InvalidFormat::new(\get_debug_type($value), self::class, self::NAME, $e);
        }
    }
}

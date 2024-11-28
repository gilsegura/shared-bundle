<?php

declare(strict_types=1);

namespace SharedBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Type;
use Shared\Domain\Email;

final class EmailType extends Type
{
    public const string NAME = 'email';

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
            || $value instanceof Email
        ) {
            return $value?->email;
        }

        if (!\is_string($value)) {
            throw InvalidType::new($value, self::class, ['null', self::class]);
        }

        try {
            return (new Email($value))->email;
        } catch (\Throwable $e) {
            throw InvalidType::new($value, self::class, ['null', self::class], $e);
        }
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Email
    {
        if (
            null === $value
            || $value instanceof Email
        ) {
            return $value;
        }

        if (!\is_string($value)) {
            throw InvalidFormat::new(\get_debug_type($value), self::class, self::NAME);
        }

        try {
            return new Email($value);
        } catch (\Throwable $e) {
            throw InvalidFormat::new(\get_debug_type($value), self::class, self::NAME, $e);
        }
    }
}

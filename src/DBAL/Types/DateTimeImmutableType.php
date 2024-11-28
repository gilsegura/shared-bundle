<?php

declare(strict_types=1);

namespace SharedBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Type;
use Shared\Domain\DateTimeImmutable;

final class DateTimeImmutableType extends Type
{
    public const string NAME = 'datetime_immutable';

    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDateTimeTypeDeclarationSQL($column);
    }

    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            $value = \DateTimeImmutable::createFromFormat(DATE_ATOM, $value->dateTime);
        }

        if (!$value instanceof \DateTimeImmutable) {
            throw InvalidType::new($value, self::class, ['null', self::class]);
        }

        return $value->format($platform->getDateTimeFormatString());
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DateTimeImmutable
    {
        if (
            null === $value
            || $value instanceof DateTimeImmutable
        ) {
            return $value;
        }

        if (!\is_string($value)) {
            throw InvalidFormat::new(\get_debug_type($value), self::class, self::NAME);
        }

        $value = \DateTimeImmutable::createFromFormat($platform->getDateTimeFormatString(), $value);

        if (!$value instanceof \DateTimeImmutable) {
            throw InvalidFormat::new(\get_debug_type($value), self::class, self::NAME);
        }

        try {
            return new DateTimeImmutable($value->format(DATE_ATOM));
        } catch (\Throwable $e) {
            throw InvalidFormat::new(\get_debug_type($value), self::class, self::NAME, $e);
        }
    }
}

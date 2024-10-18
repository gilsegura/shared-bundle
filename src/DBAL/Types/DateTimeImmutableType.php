<?php

declare(strict_types=1);

namespace SharedBundle\DBAL\Types;

use DateTimeImmutable as NativeDateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeImmutableType as NativeDateTimeImmutableType;
use Shared\Domain\DateTimeImmutable;

final class DateTimeImmutableType extends NativeDateTimeImmutableType
{
    public const string NAME = 'datetime_immutable';

    #[\Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value || \is_string($value)) {
            return $value;
        }

        if ($value instanceof NativeDateTimeImmutable) {
            return $value->format($platform->getDateTimeFormatString());
        }

        if ($value instanceof DateTimeImmutable) {
            return $value->dateTime;
        }

        throw ConversionException::conversionFailedInvalidType($value, self::NAME, [$value, DateTimeImmutable::class]);
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?DateTimeImmutable
    {
        if (null === $value || $value instanceof DateTimeImmutable) {
            return $value;
        }

        try {
            return new DateTimeImmutable(date(DATE_ATOM, strtotime($value)));
        } catch (\Throwable) {
            throw ConversionException::conversionFailedFormat($value, self::NAME, $platform->getDateTimeFormatString());
        }
    }
}

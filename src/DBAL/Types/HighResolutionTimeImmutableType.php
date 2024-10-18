<?php

declare(strict_types=1);

namespace SharedBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\IntegerType;
use Shared\Domain\DateTimeImmutable;
use Shared\Domain\HighResolutionTimeImmutable;

final class HighResolutionTimeImmutableType extends IntegerType
{
    public const string NAME = 'high_resolution_time_immutable';

    #[\Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        if (null === $value || \is_int($value)) {
            return $value;
        }

        if ($value instanceof HighResolutionTimeImmutable) {
            return $value->time;
        }

        throw ConversionException::conversionFailedInvalidType($value, self::NAME, [$value, DateTimeImmutable::class]);
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?HighResolutionTimeImmutable
    {
        if (null === $value || $value instanceof HighResolutionTimeImmutable) {
            return $value;
        }

        try {
            return new HighResolutionTimeImmutable($value);
        } catch (\Throwable) {
            throw ConversionException::conversionFailed($value, self::NAME);
        }
    }
}

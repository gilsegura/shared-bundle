<?php

declare(strict_types=1);

namespace SharedBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\GuidType;
use Shared\Domain\Uuid;

final class UuidType extends GuidType
{
    public const string NAME = 'uuid';

    #[\Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value || \is_string($value)) {
            return $value;
        }

        if ($value instanceof Uuid) {
            return $value->uuid;
        }

        throw ConversionException::conversionFailed($value, self::NAME);
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?Uuid
    {
        if (null === $value || $value instanceof Uuid) {
            return $value;
        }

        try {
            return new Uuid($value);
        } catch (\Throwable) {
            throw ConversionException::conversionFailed($value, self::NAME);
        }
    }

    #[\Override]
    public function getName(): string
    {
        return self::NAME;
    }
}

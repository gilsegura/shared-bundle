<?php

declare(strict_types=1);

namespace SharedBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;
use Shared\Domain\NotEmptyString;

final class NotEmptyStringType extends StringType
{
    public const string NAME = 'not_empty_string';

    #[\Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value || \is_string($value)) {
            return $value;
        }

        if ($value instanceof NotEmptyString) {
            return $value->string;
        }

        throw ConversionException::conversionFailed($value, self::NAME);
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?NotEmptyString
    {
        if (null === $value || $value instanceof NotEmptyString) {
            return $value;
        }

        try {
            return new NotEmptyString($value);
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

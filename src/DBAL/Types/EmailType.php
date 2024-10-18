<?php

declare(strict_types=1);

namespace SharedBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;
use Shared\Domain\Email;

final class EmailType extends StringType
{
    public const string NAME = 'email';

    #[\Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value || \is_string($value)) {
            return $value;
        }

        if ($value instanceof Email) {
            return $value->email;
        }

        throw ConversionException::conversionFailed($value, self::NAME);
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?Email
    {
        if (null === $value || $value instanceof Email) {
            return $value;
        }

        try {
            return new Email($value);
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

<?php

declare(strict_types=1);

namespace SharedBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;
use Shared\Domain\HashedPassword;

final class HashedPasswordType extends StringType
{
    public const string NAME = 'hashed_password';

    #[\Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value || \is_string($value)) {
            return $value;
        }

        if ($value instanceof HashedPassword) {
            return $value->password;
        }

        throw ConversionException::conversionFailed($value, self::NAME);
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?HashedPassword
    {
        if (null === $value || $value instanceof HashedPassword) {
            return $value;
        }

        try {
            return new HashedPassword($value);
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

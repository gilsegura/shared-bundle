<?php

declare(strict_types=1);

namespace SharedBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;
use Shared\Serializer\Serializer;

final class SerializableType extends JsonType
{
    public const string NAME = 'serializable';

    #[\Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        try {
            return json_encode(Serializer::serialize($value), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        } catch (\Throwable $throwable) {
            throw ConversionException::conversionFailedSerialization($value, 'json', $throwable->getMessage(), $throwable);
        }
    }

    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?object
    {
        if (null === $value) {
            return null;
        }

        if (\is_resource($value)) {
            $value = stream_get_contents($value);
        }

        try {
            return Serializer::deserialize(json_decode($value, true, 512, JSON_THROW_ON_ERROR));
        } catch (\Throwable $throwable) {
            throw ConversionException::conversionFailed($value, self::NAME, $throwable);
        }
    }

    #[\Override]
    public function getName(): string
    {
        return self::NAME;
    }
}

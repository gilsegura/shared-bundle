<?php

declare(strict_types=1);

namespace SharedBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\SerializationFailed;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;
use Serializer\SerializableInterface;
use Serializer\Serializer;

final class SerializableType extends Type
{
    public const string NAME = 'serializable';

    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getJsonTypeDeclarationSQL($column);
    }

    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof SerializableInterface) {
            throw InvalidType::new($value, self::class, ['null', self::class]);
        }

        try {
            return json_encode(Serializer::serialize($value), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        } catch (\Throwable $e) {
            throw SerializationFailed::new($value, self::NAME, $e->getMessage(), $e);
        }
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?object
    {
        if (null === $value) {
            return null;
        }

        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        try {
            if (
                !\is_string($value)
                || !\is_array($serializedObject = json_decode($value, true, 512, JSON_THROW_ON_ERROR))
            ) {
                throw InvalidFormat::new(\get_debug_type($value), self::class, self::NAME);
            }

            return Serializer::deserialize($serializedObject);
        } catch (\Throwable $e) {
            throw ValueNotConvertible::new(\get_debug_type($value), self::NAME, $e->getMessage(), $e);
        }
    }
}

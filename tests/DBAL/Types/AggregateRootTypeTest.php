<?php

declare(strict_types=1);

namespace SharedBundle\Tests\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQL84Platform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SharedBundle\DBAL\Types\AggregateRootType;
use SharedBundle\Tests\EventSourcing\AThing;

final class AggregateRootTypeTest extends TestCase
{
    /**
     * @return array<int, array<int, AbstractPlatform>>
     */
    public static function platformProvider(): array
    {
        return [
            [new MySQL84Platform()],
            [new PostgreSQLPlatform()],
            [new SQLitePlatform()],
        ];
    }

    #[DataProvider('platformProvider')]
    public function test_must_throw_invalid_type_when_converting_a_non_aggregate(AbstractPlatform $platform): void
    {
        self::expectException(InvalidType::class);

        new AggregateRootType()->convertToDatabaseValue('not-an-aggregate', $platform);
    }

    #[DataProvider('platformProvider')]
    public function test_must_throw_when_converting_a_non_string_to_php(AbstractPlatform $platform): void
    {
        self::expectException(ValueNotConvertible::class);

        new AggregateRootType()->convertToPHPValue(1, $platform);
    }

    #[DataProvider('platformProvider')]
    public function test_must_round_trip_an_aggregate(AbstractPlatform $platform): void
    {
        $type = new AggregateRootType();

        $serialized = $type->convertToDatabaseValue(new AThing(), $platform);

        self::assertIsString($serialized);
        self::assertInstanceOf(AThing::class, $type->convertToPHPValue($serialized, $platform));
    }

    #[DataProvider('platformProvider')]
    public function test_must_pass_null_through(AbstractPlatform $platform): void
    {
        $type = new AggregateRootType();

        self::assertNull($type->convertToDatabaseValue(null, $platform));
        self::assertNull($type->convertToPHPValue(null, $platform));
    }
}

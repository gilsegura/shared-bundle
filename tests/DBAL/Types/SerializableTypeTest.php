<?php

declare(strict_types=1);

namespace SharedBundle\Tests\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\DB2Platform;
use Doctrine\DBAL\Platforms\MariaDB1010Platform;
use Doctrine\DBAL\Platforms\MariaDB1060Platform;
use Doctrine\DBAL\Platforms\MySQL84Platform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Serializer\SerializableInterface;
use SharedBundle\DBAL\Types\SerializableType;

final class SerializableTypeTest extends TestCase
{
    /**
     * @return array<int, array<int, AbstractPlatform>>
     */
    public static function platformProvider(): array
    {
        return [
            [new DB2Platform()],
            [new MariaDB1010Platform()],
            [new MariaDB1060Platform()],
            [new MySQL84Platform()],
            [new MySQLPlatform()],
            [new OraclePlatform()],
            [new PostgreSQLPlatform()],
            [new SQLitePlatform()],
            [new SQLServerPlatform()],
        ];
    }

    #[DataProvider('platformProvider')]
    public function test_must_throw_invalid_type_exception_when_convert_to_platform_invalid_type(AbstractPlatform $platform): void
    {
        self::expectException(InvalidType::class);

        (new SerializableType())->convertToDatabaseValue(1, $platform);
    }

    #[DataProvider('platformProvider')]
    public function test_must_throw_invalid_type_exception_when_convert_to_platform_invalid_content(AbstractPlatform $platform): void
    {
        self::expectException(InvalidType::class);

        (new SerializableType())->convertToDatabaseValue('1', $platform);
    }

    #[DataProvider('platformProvider')]
    public function test_must_convert_to_platform(AbstractPlatform $platform): void
    {
        $type = (new SerializableType())->convertToDatabaseValue(new Serializable(), $platform);

        self::assertIsString($type);
    }

    #[DataProvider('platformProvider')]
    public function test_must_convert_to_php(AbstractPlatform $platform): void
    {
        $type = (new SerializableType())->convertToPHPValue('{"class":"SharedBundle\\\\Tests\\\\DBAL\\\\Types\\\\Serializable","attributes":[]}', $platform);

        self::assertInstanceOf(SerializableInterface::class, $type);
    }
}

final readonly class Serializable implements SerializableInterface
{
    #[\Override]
    public static function deserialize(array $data): self
    {
        return new self();
    }

    #[\Override]
    public function serialize(): array
    {
        return [];
    }
}

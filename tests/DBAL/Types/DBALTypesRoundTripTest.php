<?php

declare(strict_types=1);

namespace SharedBundle\Tests\DBAL\Types;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Shared\Domain\DateTimeImmutable;
use Shared\Domain\Email;
use Shared\Domain\HashedPassword;
use Shared\Domain\NotEmptyString;
use Shared\Domain\Uuid;
use SharedBundle\DBAL\Types\DateTimeImmutableType;
use SharedBundle\DBAL\Types\EmailType;
use SharedBundle\DBAL\Types\HashedPasswordType;
use SharedBundle\DBAL\Types\NotEmptyStringType;
use SharedBundle\DBAL\Types\UuidType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DBALTypesRoundTripTest extends KernelTestCase
{
    private Connection $connection;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        /** @var Connection $connection */
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        $this->connection = $connection;

        $this->connection->executeStatement(
            'CREATE TEMPORARY TABLE type_probe (id INTEGER PRIMARY KEY, value TEXT)'
        );
    }

    public function test_must_round_trip_uuid(): void
    {
        $uuid = new Uuid('9db0db88-3e44-4d2b-b46f-9ca547de06ac');

        $recovered = $this->roundTrip($uuid, UuidType::NAME);

        self::assertInstanceOf(Uuid::class, $recovered);
        self::assertTrue($uuid->equals($recovered));
    }

    public function test_must_round_trip_email(): void
    {
        $email = new Email('user@example.com');

        $recovered = $this->roundTrip($email, EmailType::NAME);

        self::assertInstanceOf(Email::class, $recovered);
        self::assertTrue($email->equals($recovered));
    }

    public function test_must_round_trip_not_empty_string(): void
    {
        $string = new NotEmptyString('something');

        $recovered = $this->roundTrip($string, NotEmptyStringType::NAME);

        self::assertInstanceOf(NotEmptyString::class, $recovered);
        self::assertTrue($string->equals($recovered));
    }

    public function test_must_round_trip_hashed_password(): void
    {
        $password = new HashedPassword('$2y$10$abcdefghijklmnopqrstuv');

        $recovered = $this->roundTrip($password, HashedPasswordType::NAME);

        self::assertInstanceOf(HashedPassword::class, $recovered);
        self::assertTrue($password->equals($recovered));
    }

    public function test_must_round_trip_datetime_immutable(): void
    {
        $dateTime = new DateTimeImmutable('2026-01-15T10:30:00+00:00');

        $recovered = $this->roundTrip($dateTime, DateTimeImmutableType::NAME);

        self::assertInstanceOf(DateTimeImmutable::class, $recovered);
        self::assertTrue($dateTime->equals($recovered));
    }

    private function roundTrip(object $value, string $typeName): object
    {
        $platform = $this->connection->getDatabasePlatform();

        $type = Type::getType($typeName);

        $databaseValue = $type->convertToDatabaseValue($value, $platform);

        $this->connection->executeStatement(
            'INSERT INTO type_probe (id, value) VALUES (1, ?)',
            [$databaseValue]
        );

        $stored = $this->connection->fetchOne('SELECT value FROM type_probe WHERE id = 1');

        $recovered = $type->convertToPHPValue($stored, $platform);

        \assert(\is_object($recovered));

        return $recovered;
    }
}

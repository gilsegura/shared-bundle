<?php

declare(strict_types=1);

namespace SharedBundle\Tests\DBAL;

use SharedBundle\DBAL\DBALHealthyConnection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DBALHealthyConnectionTest extends KernelTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function test_must_is_healthy(): void
    {
        /** @var DBALHealthyConnection $healthy */
        $healthy = self::getContainer()->get(DBALHealthyConnection::class);

        self::assertTrue($healthy->__invoke());
    }
}

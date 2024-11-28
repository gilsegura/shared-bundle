<?php

declare(strict_types=1);

namespace SharedBundle\Tests\AMQP;

use SharedBundle\AMQP\AMQPHealthyConnection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AMQPHealthyConnectionTest extends KernelTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function test_must_is_healthy(): void
    {
        /** @var AMQPHealthyConnection $healthy */
        $healthy = self::getContainer()->get(AMQPHealthyConnection::class);

        self::assertFalse($healthy->__invoke());
    }
}

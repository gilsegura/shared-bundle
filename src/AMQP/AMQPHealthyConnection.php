<?php

declare(strict_types=1);

namespace SharedBundle\AMQP;

use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;

final readonly class AMQPHealthyConnection
{
    private Connection $connection;

    public function __construct(
        string $dsn,
    ) {
        $this->connection = Connection::fromDsn($dsn);
    }

    public function __invoke(): bool
    {
        try {
            $channel = $this->connection->channel();

            $channel->isConnected();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}

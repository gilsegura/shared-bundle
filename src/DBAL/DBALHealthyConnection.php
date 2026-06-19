<?php

declare(strict_types=1);

namespace SharedBundle\DBAL;

use Doctrine\DBAL\Connection;

/**
 * Invokable health check that reports whether the DBAL connection is
 * reachable.
 */
final readonly class DBALHealthyConnection
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function __invoke(): bool
    {
        try {
            $dummySelectSQL = $this->connection->getDatabasePlatform()->getDummySelectSQL();

            $this->connection->executeQuery($dummySelectSQL);

            return true;
        } catch (\Throwable) {
            $this->connection->close();

            return false;
        }
    }
}

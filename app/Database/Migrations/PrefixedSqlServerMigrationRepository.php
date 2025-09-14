<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;

class PrefixedSqlServerMigrationRepository extends DatabaseMigrationRepository {
    protected function connection(): Connection {
        /** @var ConnectionResolverInterface $resolver */
        $resolver = $this->resolver;

        $source = $this->connection ?: $resolver->getDefaultConnection();

        $connection = $resolver->connection($source);

        if (! $connection instanceof Connection) {
            throw new \RuntimeException('Expected Illuminate\Database\Connection, got ' . $connection::class);
        }

        return $connection;
    }

    public function repositoryExists(): bool {
        $connection = $this->connection();
        // Only do the OBJECT_ID trick on SQL Server
        if ($connection->getDriverName() === 'sqlsrv') {
            $table = $connection->getTablePrefix() . $this->table;        // e.g. "cobrabot.migrations"
            $wrapped = $connection->getQueryGrammar()->wrapTable($table);   // e.g. "[cobrabot].[migrations]"
            $param = str_replace(['[', ']'], '', $wrapped);         // "cobrabot.migrations"
            $sql = 'select 1 where object_id(?) is not null';

            return ! empty($connection->select($sql, [$param]));
        }

        // For sqlite/mysql/pgsql, use the normal schema check
        return $connection->getSchemaBuilder()->hasTable($this->table);
    }
}

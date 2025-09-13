<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;

class SqlServerSchemaAwareMigrationRepository extends DatabaseMigrationRepository {
    /** Ensure we can always get a Connection for the current source. */
    protected function connection(): Connection {
        /** @var ConnectionResolverInterface $resolver */
        $resolver = $this->resolver;

        $source = $this->connection ?: $resolver->getDefaultConnection();

        $connection = $resolver->connection($source);

        // Ensure correct type for static analysis
        if (! $connection instanceof Connection) {
            throw new \RuntimeException('Expected Illuminate\Database\Connection');
        }

        return $connection;
    }

    /** SQL Server: recognize schema-qualified table names like 'cobrabot.migrations'. */
    public function repositoryExists(): bool {
        $table = $this->table;

        // If repo table is schema-qualified, check via sys.schemas/sys.tables
        if (str_contains($table, '.')) {
            [$schema, $name] = explode('.', $table, 2);

            $sql = <<<'SQL'
                SELECT 1
                FROM sys.tables t
                JOIN sys.schemas s ON s.schema_id = t.schema_id
                WHERE s.name = ? AND t.name = ?
            SQL;

            return ! empty($this->connection()->select($sql, [$schema, $name]));
        }

        // Otherwise use the normal builder (works for MySQL/SQLite/etc.)
        return $this->connection()->getSchemaBuilder()->hasTable($table);
    }
}

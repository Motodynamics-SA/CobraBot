<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;

class PrefixedSqlServerMigrationRepository extends DatabaseMigrationRepository {
    protected function connection() {
        /** @var ConnectionResolverInterface $resolver */
        $resolver = $this->resolver;

        $source = $this->connection ?: $resolver->getDefaultConnection();

        return $resolver->connection($source);
    }

    public function repositoryExists(): bool {
        // Use the connection's table prefix (e.g., "cobrabot.")
        $table = $this->connection()->getTablePrefix() . $this->table;

        // OBJECT_ID('[schema].[table]') works best on SQL Server
        $wrapped = $this->connection()->getQueryGrammar()->wrapTable($table);
        $param = str_replace(['[', ']'], '', $wrapped);

        $sql = 'select 1 where object_id(?) is not null';

        return ! empty($this->connection()->select($sql, [$param]));
    }
}

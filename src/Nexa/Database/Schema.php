<?php

namespace Nexa\Database;

use PDO;

class Schema
{
    protected $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function create(Blueprint $blueprint)
    {
        $sql = $blueprint->toSql();
        $this->connection->exec($sql);
    }

    public function drop($table)
    {
        $this->connection->exec("DROP TABLE IF EXISTS {$table}");
    }

    public function table($table, \Closure $callback)
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        $this->alter($blueprint);
    }

    protected function alter(Blueprint $blueprint)
    {
        $sql = $blueprint->toAlterSql();
        if (!empty($sql)) {
            $this->connection->exec($sql);
        }
    }

    public function hasTable($table)
    {
        $stmt = $this->connection->query("SHOW TABLES LIKE '{$table}'");
        return $stmt->rowCount() > 0;
    }

    public function hasColumn($table, $column)
    {
        $stmt = $this->connection->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
        return $stmt->rowCount() > 0;
    }
}
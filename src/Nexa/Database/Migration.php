<?php

namespace Nexa\Database;

abstract class Migration
{
    protected $connection;
    protected $schema;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
        $this->schema = new Schema($this->connection);
    }

    abstract public function up();

    abstract public function down();

    public function run()
    {
        $this->up();
    }

    public function rollback()
    {
        $this->down();
    }

    protected function createTable($tableName, \Closure $callback)
    {
        $blueprint = new Blueprint($tableName);
        $callback($blueprint);
        $this->schema->create($blueprint);
    }

    protected function dropTable($tableName)
    {
        $this->schema->drop($tableName);
    }

    protected function addColumn($tableName, $columnName, $type, $options = [])
    {
        $this->schema->table($tableName, function(Blueprint $table) use ($columnName, $type, $options) {
            $table->addColumn($columnName, $type, $options);
        });
    }

    protected function dropColumn($tableName, $columnName)
{
    $this->schema->table($tableName, function(Blueprint $table) use ($columnName) {
        $table->dropColumn($columnName);
    });
}
}
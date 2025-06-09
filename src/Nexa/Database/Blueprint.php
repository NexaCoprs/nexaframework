<?php

namespace Nexa\Database;

class Blueprint
{
    protected $table;
    protected $columns = [];
    protected $commands = [];

    public function __construct($table)
    {
        $this->table = $table;
    }

    public function increments($column)
    {
        return $this->integer($column, ['unsigned' => true, 'auto_increment' => true, 'primary' => true]);
    }

    public function string($column, $length = 255)
    {
        return $this->addColumn($column, 'varchar', ['length' => $length]);
    }

    public function integer($column, $options = [])
    {
        return $this->addColumn($column, 'int', $options);
    }

    public function text($column)
    {
        return $this->addColumn($column, 'text');
    }

    public function timestamp($column)
    {
        return $this->addColumn($column, 'timestamp');
    }

    public function timestamps()
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    public function nullable()
    {
        if (!empty($this->columns)) {
            $lastColumn = end($this->columns);
            return $lastColumn->nullable();
        }
        return $this;
    }

    public function addColumn($name, $type, $options = [])
    {
        $column = new Column($name, $type, $options);
        $this->columns[] = $column;
        return $column;
    }

    protected function addOption($option, $value)
    {
        if (!empty($this->columns)) {
            $lastColumn = end($this->columns);
            $lastColumn->setOption($option, $value);
        }
        return $this;
    }

    public function toSql()
    {
        $columns = [];
        $primaryKeys = [];

        foreach ($this->columns as $column) {
            $columns[] = $column->toSql();
            if ($column->isPrimary()) {
                $primaryKeys[] = $column->getName();
            }
        }

        $sql = "CREATE TABLE {$this->table} (".implode(', ', $columns);

        if (!empty($primaryKeys)) {
            $sql .= ", PRIMARY KEY (".implode(', ', $primaryKeys).")";
        }

        $sql .= ")";

        return $sql;
    }

    public function dropColumn($column)
    {
        $this->commands[] = [
            'type' => 'drop',
            'columns' => is_array($column) ? $column : [$column]
        ];
        return $this;
    }

    public function toAlterSql()
    {
        $sql = [];

        foreach ($this->commands as $command) {
            switch ($command['type']) {
                case 'add':
                    foreach ($command['columns'] as $column) {
                        $sql[] = "ALTER TABLE {$this->table} ADD COLUMN ".$column->toSql();
                    }
                    break;
                case 'drop':
                    foreach ($command['columns'] as $column) {
                        $sql[] = "ALTER TABLE {$this->table} DROP COLUMN {$column}";
                    }
                    break;
            }
        }

        return implode('; ', $sql);
    }
}
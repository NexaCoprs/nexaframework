<?php

namespace Nexa\Database;

class Column
{
    protected $name;
    protected $type;
    protected $options = [];

    public function __construct($name, $type, $options = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->options = $options;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
        return $this;
    }

    public function nullable()
    {
        return $this->setOption('nullable', true);
    }

    public function unique()
    {
        return $this->setOption('unique', true);
    }

    public function isPrimary()
    {
        return isset($this->options['primary']) && $this->options['primary'] === true;
    }

    public function isNullable()
    {
        return isset($this->options['nullable']) && $this->options['nullable'] === true;
    }

    public function isUnique()
    {
        return isset($this->options['unique']) && $this->options['unique'] === true;
    }

    public function toSql()
    {
        $sql = "{$this->name} {$this->type}";

        if (isset($this->options['length'])) {
            $sql .= "({$this->options['length']})";
        }

        if (isset($this->options['unsigned']) && $this->options['unsigned'] === true) {
            $sql .= " UNSIGNED";
        }

        if (isset($this->options['auto_increment']) && $this->options['auto_increment'] === true) {
            $sql .= " AUTO_INCREMENT";
        }

        // Handle timestamp columns specially
        if (strtolower($this->type) === 'timestamp') {
            if ($this->isNullable()) {
                $sql .= " NULL DEFAULT NULL";
            } else {
                $sql .= " NOT NULL DEFAULT CURRENT_TIMESTAMP";
                if ($this->name === 'updated_at') {
                    $sql .= " ON UPDATE CURRENT_TIMESTAMP";
                }
            }
        } else {
            if (!$this->isNullable()) {
                $sql .= " NOT NULL";
            }

            if (isset($this->options['default'])) {
                $sql .= " DEFAULT '{$this->options['default']}'";
            }
        }

        if ($this->isUnique()) {
            $sql .= " UNIQUE";
        }

        return $sql;
    }
}
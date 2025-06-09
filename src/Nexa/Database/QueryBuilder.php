<?php

namespace Nexa\Database;

use PDO;

class QueryBuilder
{
    protected $model;
    protected $wheres = [];
    protected $orders = [];
    protected $limitValue;
    protected $offsetValue;
    protected $joins = [];
    protected $selects = ['*'];
    protected $bindings = [];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Ajoute une clause WHERE
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function where($column, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'and'
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Ajoute une clause WHERE OR
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'or'
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Ajoute une clause WHERE IN
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function whereIn($column, array $values)
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'and'
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    /**
     * Ajoute une clause WHERE NOT IN
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function whereNotIn($column, array $values)
    {
        $this->wheres[] = [
            'type' => 'not_in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'and'
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    /**
     * Ajoute une clause WHERE NULL
     *
     * @param string $column
     * @return $this
     */
    public function whereNull($column)
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'and'
        ];

        return $this;
    }

    /**
     * Ajoute une clause WHERE NOT NULL
     *
     * @param string $column
     * @return $this
     */
    public function whereNotNull($column)
    {
        $this->wheres[] = [
            'type' => 'not_null',
            'column' => $column,
            'boolean' => 'and'
        ];

        return $this;
    }

    /**
     * Ajoute une clause ORDER BY
     *
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtolower($direction) === 'desc' ? 'desc' : 'asc'
        ];

        return $this;
    }

    /**
     * Ajoute une clause LIMIT
     *
     * @param int $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->limitValue = $limit;
        return $this;
    }

    /**
     * Ajoute une clause OFFSET
     *
     * @param int $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->offsetValue = $offset;
        return $this;
    }

    /**
     * Ajoute une clause JOIN
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @param string $type
     * @return $this
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'inner')
    {
        if (func_num_args() === 3) {
            $second = $operator;
            $operator = '=';
        }

        $this->joins[] = [
            'type' => $type,
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];

        return $this;
    }

    /**
     * Ajoute une clause LEFT JOIN
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return $this
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    /**
     * Ajoute une clause RIGHT JOIN
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return $this
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    /**
     * Sélectionne des colonnes spécifiques
     *
     * @param array|string $columns
     * @return $this
     */
    public function select($columns = ['*'])
    {
        $this->selects = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Exécute la requête et retourne tous les résultats
     *
     * @return array
     */
    public function get()
    {
        $sql = $this->toSql();
        $connection = $this->model->getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->execute($this->bindings);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $model = new (get_class($this->model));
            $results[] = $model->fill($row);
        }

        return $results;
    }

    /**
     * Exécute la requête et retourne le premier résultat
     *
     * @return Model|null
     */
    public function first()
    {
        $this->limit(1);
        $results = $this->get();
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Compte le nombre de résultats
     *
     * @return int
     */
    public function count()
    {
        $originalSelects = $this->selects;
        $this->selects = ['COUNT(*) as count'];
        
        $sql = $this->toSql();
        $connection = $this->model->getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->execute($this->bindings);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->selects = $originalSelects;
        
        return (int) $result['count'];
    }

    /**
     * Génère la requête SQL
     *
     * @return string
     */
    public function toSql()
    {
        $sql = 'SELECT ' . implode(', ', $this->selects);
        $sql .= ' FROM ' . $this->model->getTableName();

        // Ajouter les JOINs
        foreach ($this->joins as $join) {
            $sql .= ' ' . strtoupper($join['type']) . ' JOIN ' . $join['table'];
            $sql .= ' ON ' . $join['first'] . ' ' . $join['operator'] . ' ' . $join['second'];
        }

        // Ajouter les WHEREs
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }

        // Ajouter les ORDER BYs
        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ';
            $orderClauses = [];
            foreach ($this->orders as $order) {
                $orderClauses[] = $order['column'] . ' ' . strtoupper($order['direction']);
            }
            $sql .= implode(', ', $orderClauses);
        }

        // Ajouter LIMIT et OFFSET
        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }

        if ($this->offsetValue !== null) {
            $sql .= ' OFFSET ' . $this->offsetValue;
        }

        return $sql;
    }

    /**
     * Compile les clauses WHERE
     *
     * @return string
     */
    protected function compileWheres()
    {
        $clauses = [];

        foreach ($this->wheres as $index => $where) {
            $boolean = $index === 0 ? '' : strtoupper($where['boolean']) . ' ';

            switch ($where['type']) {
                case 'basic':
                    $clauses[] = $boolean . $where['column'] . ' ' . $where['operator'] . ' ?';
                    break;
                case 'in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $clauses[] = $boolean . $where['column'] . ' IN (' . $placeholders . ')';
                    break;
                case 'not_in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $clauses[] = $boolean . $where['column'] . ' NOT IN (' . $placeholders . ')';
                    break;
                case 'null':
                    $clauses[] = $boolean . $where['column'] . ' IS NULL';
                    break;
                case 'not_null':
                    $clauses[] = $boolean . $where['column'] . ' IS NOT NULL';
                    break;
            }
        }

        return implode(' ', $clauses);
    }
}
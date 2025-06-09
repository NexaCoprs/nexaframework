<?php

namespace Nexa\Database;

use PDO;
use Nexa\Database\Relations\HasOneRelation;
use Nexa\Database\Relations\HasManyRelation;
use Nexa\Database\Relations\BelongsToRelation;
use Nexa\Database\Relations\BelongsToManyRelation;

abstract class Model
{
    protected static $connection;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $attributes = [];
    protected $relations = [];
    protected $hidden = [];
    protected $casts = [];

    public static function setConnection(PDO $connection)
    {
        static::$connection = $connection;
    }
    
    public function getConnection()
    {
        return static::$connection;
    }

    public function __construct(array $attributes = [])
    {
        if (!$this->table) {
            $this->table = $this->getTableName();
        }
        $this->fill($attributes);
    }
    
    public function getTableName()
    {
        $className = $this->getClassBasename(static::class);
        return strtolower($className) . 's';
    }
    
    protected function getClassBasename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }

    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    public function save()
    {
        if (isset($this->attributes[$this->primaryKey])) {
            return $this->update();
        }
        return $this->insert();
    }

    protected function insert()
    {
        $columns = implode(', ', array_keys($this->attributes));
        $placeholders = implode(', ', array_fill(0, count($this->attributes), '?'));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = static::$connection->prepare($sql);
        $stmt->execute(array_values($this->attributes));

        $this->attributes[$this->primaryKey] = static::$connection->lastInsertId();
        return true;
    }

    protected function update()
    {
        $set = [];
        foreach ($this->attributes as $key => $value) {
            if ($key !== $this->primaryKey) {
                $set[] = "$key = ?";
            }
        }
        $set = implode(', ', $set);

        $sql = "UPDATE {$this->table} SET $set WHERE {$this->primaryKey} = ?";
        $values = array_values(array_diff_key($this->attributes, [$this->primaryKey => null]));
        $values[] = $this->attributes[$this->primaryKey];

        $stmt = static::$connection->prepare($sql);
        return $stmt->execute($values);
    }

    public static function find($id)
    {
        $model = new static;
        $sql = "SELECT * FROM {$model->table} WHERE {$model->primaryKey} = ? LIMIT 1";
        $stmt = static::$connection->prepare($sql);
        $stmt->execute([$id]);

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return (new static)->fill($row);
        }
        return null;
    }

    public static function all()
    {
        $model = new static;
        $sql = "SELECT * FROM {$model->table}";
        $stmt = static::$connection->prepare($sql);
        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = (new static)->fill($row);
        }
        return $results;
    }

    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set($name, $value)
    {
        if (in_array($name, $this->fillable)) {
            $this->attributes[$name] = $value;
        }
    }

    // Relations
    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->primaryKey;
        
        return new HasOneRelation($this, new $related, $foreignKey, $localKey);
    }

    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->primaryKey;
        
        return new HasManyRelation($this, new $related, $foreignKey, $localKey);
    }

    public function belongsTo($related, $foreignKey = null, $ownerKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getRelatedForeignKey($related);
        $ownerKey = $ownerKey ?: (new $related)->primaryKey;
        
        return new BelongsToRelation($this, new $related, $foreignKey, $ownerKey);
    }

    public function belongsToMany($related, $table = null, $foreignPivotKey = null, $relatedPivotKey = null)
    {
        $table = $table ?: $this->getPivotTableName($related);
        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();
        $relatedPivotKey = $relatedPivotKey ?: (new $related)->getForeignKey();
        
        return new BelongsToManyRelation($this, new $related, $table, $foreignPivotKey, $relatedPivotKey);
    }

    protected function getForeignKey()
    {
        return strtolower($this->getClassBasename(static::class)) . '_id';
    }

    protected function getRelatedForeignKey($related)
    {
        return strtolower($this->getClassBasename($related)) . '_id';
    }

    protected function getPivotTableName($related)
    {
        $models = [
            strtolower($this->getClassBasename(static::class)),
            strtolower($this->getClassBasename($related))
        ];
        sort($models);
        return implode('_', $models);
    }

    // Query Builder amélioré
    public static function where($column, $operator = null, $value = null)
    {
        return (new QueryBuilder(new static))->where($column, $operator, $value);
    }

    public static function whereIn($column, array $values)
    {
        return (new QueryBuilder(new static))->whereIn($column, $values);
    }

    public static function orderBy($column, $direction = 'asc')
    {
        return (new QueryBuilder(new static))->orderBy($column, $direction);
    }

    public static function limit($limit)
    {
        return (new QueryBuilder(new static))->limit($limit);
    }

    public static function offset($offset)
    {
        return (new QueryBuilder(new static))->offset($offset);
    }

    public function toArray()
    {
        $array = $this->attributes;
        
        // Ajouter les relations chargées
        foreach ($this->relations as $key => $relation) {
            if (is_array($relation)) {
                $array[$key] = array_map(function($model) {
                    return $model->toArray();
                }, $relation);
            } else {
                $array[$key] = $relation ? $relation->toArray() : null;
            }
        }
        
        // Supprimer les champs cachés
        foreach ($this->hidden as $hidden) {
            unset($array[$hidden]);
        }
        
        return $array;
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }

    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->castAttribute($key, $this->attributes[$key]);
        }
        
        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }
        
        return null;
    }

    protected function castAttribute($key, $value)
    {
        if (!isset($this->casts[$key])) {
            return $value;
        }
        
        switch ($this->casts[$key]) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'array':
            case 'json':
                return json_decode($value, true);
            case 'date':
                return new \DateTime($value);
            default:
                return $value;
        }
    }

    /**
     * Set an attribute value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }
}
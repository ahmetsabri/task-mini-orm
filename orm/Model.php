<?php

namespace MiniORM;

use InvalidArgumentException;
use RuntimeException;

/**
 * Abstract Base Model Class
 * Provides CRUD operations and relationship functionality
 */
abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;
    protected static Database $db;

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->original = $attributes;
        $this->exists = !empty($attributes);

        if (empty($this->table)) {
            $this->table = $this->getDefaultTableName();
        }
    }

    /**
     * Set database instance
     */
    public static function setDatabase(Database $db): void
    {
        static::$db = $db;
    }

    /**
     * Get database instance
     */
    protected static function getDatabase(): Database
    {
        if (!isset(static::$db)) {
            static::$db = Database::getInstance();
        }
        return static::$db;
    }

    /**
     * Create new query builder instance
     */
    public static function query(): QueryBuilder
    {
        $instance = new static();
        return new QueryBuilder(static::getDatabase(), $instance->table);
    }

    /**
     * Find record by ID
     */
    public static function find(int $id): ?static
    {
        $data = static::query()->find($id);
        return $data ? new static($data) : null;
    }

    /**
     * Find record by ID or throw exception
     */
    public static function findOrFail(int $id): static
    {
        $model = static::find($id);
        if ($model === null) {
            throw new RuntimeException("No record found with ID: $id");
        }
        return $model;
    }

    /**
     * Get all records
     */
    public static function all(): array
    {
        $results = static::query()->get();
        return array_map(fn ($data) => new static($data), $results);
    }

    /**
     * Add WHERE condition
     */
    public static function where(string $column, string $operator, $value = null): QueryBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    /**
     * Create new record
     */
    public static function create(array $attributes): static
    {
        $instance = new static();
        $instance->fill($attributes);
        $instance->save();
        return $instance;
    }

    /**
     * Update record by ID
     */
    public static function updateById(int $id, array $attributes): int
    {
        return static::query()->where('id', $id)->update($attributes);
    }

    /**
     * Delete record by ID
     */
    public static function deleteById(int $id): int
    {
        return static::query()->where('id', $id)->delete();
    }

    /**
     * Count records
     */
    public static function count(): int
    {
        return static::query()->count();
    }

    /**
     * Check if records exist
     */
    public static function exists(): bool
    {
        return static::query()->exists();
    }

    /**
     * Get first record
     */
    public static function first(): ?static
    {
        $data = static::query()->first();
        return $data ? new static($data) : null;
    }

    /**
     * Eager load relationships
     */
    public static function with(array $relations): QueryBuilder
    {
        return static::query()->with($relations);
    }

    public static function load(string $relation): QueryBuilder
    {
        return static::query()->load($relation);
    }

    /**
     * Fill model with attributes
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * Save model to database
     */
    public function save(): bool
    {
        if ($this->exists) {
            return $this->performUpdate();
        } else {
            return $this->performInsert();
        }
    }

    /**
     * Delete model from database
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $id = $this->getAttribute($this->primaryKey);
        if ($id === null) {
            return false;
        }

        $deleted = static::query()->where($this->primaryKey, $id)->delete();

        if ($deleted > 0) {
            $this->exists = false;
            return true;
        }

        return false;
    }

    /**
     * Get attribute value
     */
    public function getAttribute(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Set attribute value
     */
    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Check if attribute is fillable
     */
    protected function isFillable(string $key): bool
    {
        return empty($this->fillable) || in_array($key, $this->fillable);
    }

    /**
     * Perform insert operation
     */
    protected function performInsert(): bool
    {
        $fillableAttributes = $this->getFillableAttributes();

        if (empty($fillableAttributes)) {
            throw new InvalidArgumentException('No fillable attributes to insert');
        }

        $id = static::query()->insert($fillableAttributes);
        $this->setAttribute($this->primaryKey, $id);
        $this->exists = true;
        $this->original = $this->attributes;

        return true;
    }

    /**
     * Perform update operation
     */
    protected function performUpdate(): bool
    {
        $id = $this->getAttribute($this->primaryKey);
        if ($id === null) {
            throw new RuntimeException('Cannot update model without primary key');
        }

        $dirty = $this->getDirty();
        if (empty($dirty)) {
            return true; // No changes to save
        }

        $updated = static::query()->where($this->primaryKey, $id)->update($dirty);

        if ($updated > 0) {
            $this->original = $this->attributes;
            return true;
        }

        return false;
    }

    /**
     * Get fillable attributes
     */
    protected function getFillableAttributes(): array
    {
        if (empty($this->fillable)) {
            return $this->attributes;
        }

        return array_intersect_key(
            $this->attributes,
            array_flip($this->fillable)
        );
    }

    /**
     * Get dirty attributes (changed since last save)
     */
    protected function getDirty(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                if ($this->isFillable($key)) {
                    $dirty[$key] = $value;
                }
            }
        }

        return $dirty;
    }

    /**
     * Get default table name based on class name
     */
    protected function getDefaultTableName(): string
    {
        $className = (new \ReflectionClass($this))->getShortName();
        return strtolower($className) . 's';
    }

    /**
     * Convert model to array
     */
    public function toArray(): array
    {
        $array = $this->attributes;

        // Remove hidden attributes
        foreach ($this->hidden as $key) {
            unset($array[$key]);
        }

        return $array;
    }

    /**
     * Convert model to JSON
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Define belongs to relationship
     */
    protected function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): ?Model
    {
        $foreignKey = $foreignKey ?? $this->getForeignKey($related);
        $ownerKey = $ownerKey ?? 'id';

        $foreignValue = $this->getAttribute($foreignKey);
        if ($foreignValue === null) {
            return null;
        }

        $relatedInstance = new $related();
        $data = $relatedInstance::query()->where($ownerKey, $foreignValue)->first();

        return $data ? new $related($data) : null;
    }

    /**
     * Define has many relationship
     */
    protected function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): array
    {
        $localKey = $localKey ?? $this->primaryKey;
        $foreignKey = $foreignKey ?? $this->getForeignKey(static::class);

        $localValue = $this->getAttribute($localKey);
        if ($localValue === null) {
            return [];
        }

        $relatedInstance = new $related();
        $results = $relatedInstance::query()->where($foreignKey, $localValue)->get();

        return array_map(fn ($data) => new $related($data), $results);
    }

    /**
     * Define has one relationship
     */
    protected function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): ?Model
    {
        $results = $this->hasMany($related, $foreignKey, $localKey);
        return $results[0] ?? null;
    }

    /**
     * Get foreign key name for relationship
     */
    protected function getForeignKey(string $class): string
    {
        $className = (new \ReflectionClass($class))->getShortName();
        return strtolower($className) . '_id';
    }

    /**
     * Magic getter for attributes
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter for attributes
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Magic isset for attributes
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Magic unset for attributes
     */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    /**
     * Magic toString method
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}

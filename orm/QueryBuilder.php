<?php

namespace MiniORM;

use InvalidArgumentException;
use RuntimeException;

/**
 * Fluent Query Builder
 * Builds SQL queries using method chaining
 */
class QueryBuilder
{
    private Database $db;
    private string $table;
    private array $select = ['*'];
    private array $where = [];
    private array $joins = [];
    private array $orderBy = [];
    private ?int $limitCount = null;
    private ?int $offsetCount = null;
    private array $bindings = [];
    private array $with = [];

    public function __construct(Database $db, string $table)
    {
        $this->db = $db;
        $this->table = $table;
    }

    /**
     * Set SELECT columns
     */
    public function select(array $columns): self
    {
        $this->select = $columns;
        return $this;
    }

    /**
     * Add WHERE condition
     */
    public function where(string $column, string $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];

        return $this;
    }

    /**
     * Add OR WHERE condition
     */
    public function orWhere(string $column, string $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = [
            'type' => 'OR',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];

        return $this;
    }

    /**
     * Add WHERE IN condition
     */
    public function whereIn(string $column, array $values): self
    {
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IN',
            'value' => $values
        ];

        return $this;
    }

    /**
     * Add JOIN clause
     */
    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'INNER',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];

        return $this;
    }

    /**
     * Add LEFT JOIN clause
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'LEFT',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];

        return $this;
    }

    /**
     * Add ORDER BY clause
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            throw new InvalidArgumentException('Order direction must be ASC or DESC');
        }

        $this->orderBy[] = ['column' => $column, 'direction' => $direction];
        return $this;
    }

    /**
     * Add LIMIT clause
     */
    public function limit(int $count): self
    {
        $this->limitCount = $count;
        return $this;
    }

    /**
     * Add OFFSET clause
     */
    public function offset(int $count): self
    {
        $this->offsetCount = $count;
        return $this;
    }

    /**
     * Set eager loading relationships
     */
    public function with(array $relations): self
    {
        $this->with = array_merge($this->with, $relations);
        return $this;
    }


    /**
     * Set eager loading relationships for one relationship using load method
     */

    public function load(string $relation): self
    {
        $this->with[] = $relation;
        return $this;
    }

    /**
     * Execute SELECT query and return all results
     */
    public function get(): array
    {
        $sql = $this->buildSelectSql();
        $stmt = $this->db->execute($sql, $this->bindings);
        return $stmt->fetchAll();
    }

    /**
     * Execute SELECT query and return first result
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Execute SELECT query and return count
     */
    public function count(): int
    {
        $originalSelect = $this->select;
        $this->select = ['COUNT(*) as count'];

        $sql = $this->buildSelectSql();
        $stmt = $this->db->execute($sql, $this->bindings);
        $result = $stmt->fetch();

        $this->select = $originalSelect;
        return (int) $result['count'];
    }

    /**
     * Check if any records exist
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Find record by ID
     */
    public function find(int $id): ?array
    {
        return $this->where('id', $id)->first();
    }

    /**
     * Insert new record
     */
    public function insert(array $data): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Insert data cannot be empty');
        }

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->db->execute($sql, array_values($data));
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update records
     */
    public function update(array $data): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Update data cannot be empty');
        }

        $sets = [];
        $bindings = [];

        foreach ($data as $column => $value) {
            $sets[] = "$column = ?";
            $bindings[] = $value;
        }

        $sql = sprintf('UPDATE %s SET %s', $this->table, implode(', ', $sets));

        if (!empty($this->where)) {
            $whereClause = $this->buildWhereClause();
            $sql .= ' WHERE ' . $whereClause['sql'];
            $bindings = array_merge($bindings, $whereClause['bindings']);
        }

        $stmt = $this->db->execute($sql, $bindings);
        return $stmt->rowCount();
    }

    /**
     * Delete records
     */
    public function delete(): int
    {
        $sql = sprintf('DELETE FROM %s', $this->table);

        $bindings = [];
        if (!empty($this->where)) {
            $whereClause = $this->buildWhereClause();
            $sql .= ' WHERE ' . $whereClause['sql'];
            $bindings = $whereClause['bindings'];
        }

        $stmt = $this->db->execute($sql, $bindings);
        return $stmt->rowCount();
    }

    /**
     * Build SELECT SQL query
     */
    private function buildSelectSql(): string
    {
        $sql = sprintf('SELECT %s FROM %s', implode(', ', $this->select), $this->table);

        // Add JOINs
        foreach ($this->joins as $join) {
            $sql .= sprintf(
                ' %s JOIN %s ON %s %s %s',
                $join['type'],
                $join['table'],
                $join['first'],
                $join['operator'],
                $join['second']
            );
        }

        // Add WHERE
        if (!empty($this->where)) {
            $whereClause = $this->buildWhereClause();
            $sql .= ' WHERE ' . $whereClause['sql'];
            $this->bindings = $whereClause['bindings'];
        }

        // Add ORDER BY
        if (!empty($this->orderBy)) {
            $orderClauses = [];
            foreach ($this->orderBy as $order) {
                $orderClauses[] = $order['column'] . ' ' . $order['direction'];
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderClauses);
        }

        // Add LIMIT
        if ($this->limitCount !== null) {
            $sql .= ' LIMIT ' . $this->limitCount;
        }

        // Add OFFSET
        if ($this->offsetCount !== null) {
            $sql .= ' OFFSET ' . $this->offsetCount;
        }

        return $sql;
    }

    /**
     * Build WHERE clause
     */
    private function buildWhereClause(): array
    {
        $conditions = [];
        $bindings = [];
        $isFirst = true;

        foreach ($this->where as $condition) {
            $clause = '';

            if (!$isFirst) {
                $clause .= ' ' . $condition['type'] . ' ';
            }

            if ($condition['operator'] === 'IN') {
                $placeholders = str_repeat('?,', count($condition['value']) - 1) . '?';
                $clause .= sprintf('%s IN (%s)', $condition['column'], $placeholders);
                $bindings = array_merge($bindings, $condition['value']);
            } else {
                $clause .= sprintf('%s %s ?', $condition['column'], $condition['operator']);
                $bindings[] = $condition['value'];
            }

            $conditions[] = $clause;
            $isFirst = false;
        }

        return [
            'sql' => implode('', $conditions),
            'bindings' => $bindings
        ];
    }

    /**
     * Get current table name
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get eager loading relationships
     */
    public function getWith(): array
    {
        return $this->with;
    }

    /**
     * Reset query builder state
     */
    public function reset(): self
    {
        $this->select = ['*'];
        $this->where = [];
        $this->joins = [];
        $this->orderBy = [];
        $this->limitCount = null;
        $this->offsetCount = null;
        $this->bindings = [];
        $this->with = [];
        return $this;
    }
}

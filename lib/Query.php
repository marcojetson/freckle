<?php

namespace Freckle;

use Doctrine\DBAL\Query\QueryBuilder;
use Freckle\Operator\Operator;

/**
 * @method $this eq(string $field, mixed $value)
 * @method $this equals(string $field, mixed $value)
 * @method $this not(string $field, mixed $value)
 * @method $this gt(string $field, mixed $value)
 * @method $this greaterThan(string $field, mixed $value)
 * @method $this gte(string $field, mixed $value)
 * @method $this greaterThanOrEquals(string $field, mixed $value)
 * @method $this lt(string $field, mixed $value)
 * @method $this lessThan(string $field, mixed $value)
 * @method $this lte(string $field, mixed $value)
 * @method $this lessThanOrEquals(string $field, mixed $value)
 * @method $this like(string $field, string $value)
 */
class Query implements \Countable, \IteratorAggregate
{
    /** @var Mapper */
    protected $mapper;

    /** @var QueryBuilder */
    protected $queryBuilder;

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
        $this->queryBuilder->select('*');
    }

    /**
     * @param Mapper $mapper
     * @return $this
     */
    public function mapper(Mapper $mapper)
    {
        $this->mapper = $mapper;
        return $this;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function from($table)
    {
        $this->queryBuilder->from($table);
        return $this;
    }

    /**
     * @return Entity|null
     */
    public function first()
    {
        $result = $this->limit(1)->run();
        return sizeof($result) > 0 ? $result[0] : null;
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        $queryBuilder = clone $this->queryBuilder;
        $count = $queryBuilder->select('COUNT(*)')->execute()->fetchColumn(0);
        return (int)$count;
    }

    /**
     * @param array $conditions
     * @return $this
     */
    public function where($conditions)
    {
        foreach ($conditions as $predicate => $value) {
            $parts = explode(' ', $predicate);
            $field = $parts[0];
            $operator = isset($parts[1]) ? $parts[1] : 'eq';
            $this->$operator($field, $value);
        }

        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->queryBuilder->setMaxResults($limit);
        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->queryBuilder->setFirstResult($offset);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return $this->run();
    }

    /**
     * @return Collection
     */
    public function run()
    {
        $statement = $this->queryBuilder->execute();
        $statement->setFetchMode(\PDO::FETCH_ASSOC);
        $results = $statement->fetchAll();
        $statement->closeCursor();

        return $this->mapper ? $this->mapper->collection($results) : new \ArrayIterator($results);
    }

    /**
     * @param mixed $value
     * @param int $type
     * @return string
     */
    public function parameter($value, $type = null)
    {
        return $this->queryBuilder->createNamedParameter($value, $type);
    }

    /**
     * @inheritdoc
     */
    public function __call($name, $arguments)
    {
        $operator = Operator::get($name);
        if (!$operator) {
            throw new \BadMethodCallException();
        }
        
        $condition = $operator($this, ...$arguments);
        $this->queryBuilder->andWhere($condition);

        return $this;
    }
}

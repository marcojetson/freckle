<?php

namespace Freckle;

use Doctrine\DBAL\Query\QueryBuilder;
use Freckle\Operator\Operator;

/**
 * @method $this eq(string $field, mixed $value)
 * @method $this equals(string $field, mixed $value)
 * @method $this orEq(string $field, mixed $value)
 * @method $this orEquals(string $field, mixed $value)
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
class Query implements \Countable, \IteratorAggregate, \ArrayAccess
{
    /** @var Mapper */
    protected $mapper;

    /** @var QueryBuilder */
    protected $queryBuilder;

    /** @var array */
    protected $result;

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
     * @param bool $limit
     * @return int
     */
    public function count($limit = true)
    {
        if ($limit) {
            return sizeof($this->run());
        }

        $queryBuilder = clone $this->queryBuilder;
        return (int)$queryBuilder->select('COUNT(*)')->execute()->fetchColumn(0);
    }

    /**
     * @param array $conditions
     * @return $this
     */
    public function where($conditions)
    {
        $this->queryBuilder->where($this->clause($conditions));
        return $this;
    }

    /**
     * @param array $conditions
     * @return $this
     */
    public function andWhere($conditions)
    {
        $this->queryBuilder->andWhere($this->clause($conditions));
        return $this;
    }

    /**
     * @param array $conditions
     * @return $this
     */
    public function orWhere($conditions)
    {
        $this->queryBuilder->orWhere($this->clause($conditions));
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
        return new \ArrayIterator($this->run());
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
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->run()[$offset]);
    }

    /**
     * @param int $offset
     * @return Entity|array
     */
    public function offsetGet($offset)
    {
        return $this->run()[$offset];
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException('Freckle\Query is read-only');
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        throw new \RuntimeException('Freckle\Query is read-only');
    }

    /**
     * @inheritdoc
     */
    public function __call($name, $arguments)
    {
        $operator = $this->operator($name);

        if (!$operator) {
            throw new \BadMethodCallException('Call to undefined method ' . get_class($this) . '::' . $name . '()');
        }

        $condition = $operator($this, ...$arguments);
        $this->queryBuilder->andWhere($condition);

        return $this;
    }

    /**
     * @param array $conditions
     * @param string $glue
     * @return string
     */
    protected function clause($conditions, $glue = 'and')
    {
        $restrictions = [];
        foreach ($conditions as $field => $value) {
            if ($field === 'and' || $field === 'or') {
                $restrictions[] = $this->clause($value, $field);
                continue;
            }

            $parts = explode(' ', $field);

            $field = $parts[0];
            $operatorName = isset($parts[1]) ? $parts[1] : 'eq';

            $operator = $this->operator($operatorName);
            if (!$operator) {
                throw new \InvalidArgumentException('Invalid operator ' . $operatorName);
            }

            $restrictions[] = $operator($this, $field, $value);
        }

        return join(' ' . $glue . ' ', $restrictions);
    }

    /**
     * @param string $name
     * @return Operator
     */
    protected function operator($name)
    {
        return Operator::get($name);
    }

    /**
     * @return array
     */
    protected function run()
    {
        if (!$this->result || $this->queryBuilder->getState() === QueryBuilder::STATE_DIRTY) {
            $statement = $this->queryBuilder->execute();
            $statement->setFetchMode(\PDO::FETCH_ASSOC);
            $rows = $statement->fetchAll();
            $statement->closeCursor();

            $this->result = $this->mapper ? $this->mapper->collection($rows) : $rows;
        }

        return $this->result;
    }
}

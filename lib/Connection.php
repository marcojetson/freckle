<?php

namespace Freckle;

class Connection extends \Doctrine\DBAL\Connection
{
    /** @var string */
    protected $mapperClass = Mapper::class;

    /** @var Mapper[] */
    protected $mappers;

    /** @var string */
    protected $queryClass = Query::class;

    /**
     * @param string $entityClass
     * @return Mapper
     */
    public function mapper($entityClass)
    {
        if (!isset($this->mappers[$entityClass])) {
            $definition = call_user_func([$entityClass, 'definition']);
            $mapperClass = isset($definition['mapper']) ? $definition['mapper'] : $this->mapperClass;
            $this->mappers[$entityClass] = new $mapperClass($this, $entityClass);
        }

        return $this->mappers[$entityClass];
    }

    /**
     * @param string $table
     * @param array $conditions
     * @return Query
     */
    public function select($table, array $conditions = [])
    {
        /** @var Query $query */
        $query = new $this->queryClass($this->createQueryBuilder()->from($table));

        if ($conditions) {
            $query->where($conditions);
        }

        return $query;
    }
}

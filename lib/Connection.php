<?php

namespace Freckle;

class Connection extends \Doctrine\DBAL\Connection
{
    /** @var string */
    protected $mappingClass = Mapping::class;

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
            /** @var Mapping $mapping */
            $mapping = call_user_func([$this->mappingClass, 'fromClass'], $entityClass);
            $mapperClass = $mapping->mapperClass();
            $this->mappers[$entityClass] = new $mapperClass($this, $mapping);
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

    /**
     * @param array $options
     * @return Mapping[]
     */
    public function generate(array $options = [])
    {
        $options = array_merge([
            'namespace' => null,
        ], $options);

        $mappings = [];

        $schemaManager = $this->getSchemaManager();
        
        foreach ($schemaManager->listTables() as $table) {
            $definition = [
                'namespace' => $options['namespace'],
                'table' => $table->getName(),
                'fields' => [],
            ];
            
            foreach ($table->getColumns() as $column) {
                $field = $column->getName();

                $definition['fields'][$field] = $column->getType()->getName();

                if ($column->getAutoincrement()) {
                    $definition['fields'][$field] = array_merge((array)$definition['fields'][$field], [
                        'sequence' => true,
                    ]);
                }
            }

            foreach ($schemaManager->listTableIndexes($definition['table']) as $index) {
                foreach ($index->getColumns() as $field) {
                    if (!isset($definition['fields'][$field])) {
                        continue;
                    }

                    if ($index->isPrimary()) {
                        $definition['fields'][$field] = array_merge((array)$definition['fields'][$field], [
                            'primary' => true,
                        ]);
                    }
                }
            }

            $mappings[] = new $this->mappingClass($definition);
        }

        return $mappings;
    }
}

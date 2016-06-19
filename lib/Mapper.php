<?php

namespace Freckle;

use Doctrine\DBAL\Types\Type;

class Mapper
{
    /** @var string */
    protected $collectionClass = Collection::class;

    /** @var Connection */
    protected $connection;

    /** @var array */
    protected $definition;

    /** @var string */
    protected $entityClass;

    /**
     * @param Connection $connection
     * @param string $entityClass
     */
    public function __construct(Connection $connection, $entityClass)
    {
        if (!is_subclass_of($entityClass, Entity::class)) {
            throw new \InvalidArgumentException('$entityClass must be a subclass of ' . Entity::class);
        }

        $this->connection = $connection;
        $this->entityClass = $entityClass;

        $definition = call_user_func([$this->entityClass, 'definition'], $this);
        $this->table = $definition['table'];
        $this->fields = array_map([$this, 'field'], $definition['fields']);
        $this->relations = isset($definition['relations']) ? $definition['relations'] : [];
    }

    /**
     * @return Connection
     */
    public function connection()
    {
        return $this->connection;
    }

    /**
     * @return string
     */
    public function table()
    {
        return $this->table;
    }

    /**
     * @param array $data
     * @return Entity
     */
    public function entity(array $data = [])
    {
        /** @var Entity $entity */
        $entity = new $this->entityClass();

        $values = [];
        foreach ($this->fields as $field => $definition) {
            $values[$field] = isset($data[$field]) ? $data[$field] : $definition['default'];
        }

        foreach ($this->relations as $relation => $callback) {
            $values[$relation] = function () use ($callback, $entity) {
                return $callback($this, $entity);
            };
        }

        $this->bind($entity, $values);

        return $entity;
    }

    /**
     * @param array $data
     * @return Entity
     */
    public function create(array $data = [])
    {
        $entity = $this->entity($data);
        $this->insert($entity);
        return $entity;
    }

    /**
     * @param Entity $entity
     */
    public function insert(Entity $entity)
    {
        $data = $this->flatten($entity);
        $sequence = null;
        foreach ($this->fields as $field => $definition) {
            if (!empty($definition['sequence']) && empty($data[$field])) {
                $sequence = $field;
                unset($data[$field]);
            }
        }

        $this->connection->insert($this->table(), $data);
        $entity->setNew(false);

        if ($sequence) {
            $value = isset($data[$sequence]) ? [$sequence => $data[$sequence]] : $this->connection->lastInsertId();
            $this->bind($entity, [$sequence => $value]);
        }
    }

    /**
     * @param Entity $entity
     */
    public function update(Entity $entity)
    {
        $data = $this->flatten($entity);
        $identifier = [];
        foreach ($this->fields as $field => $definition) {
            if ($definition['primary'] && isset($data[$field])) {
                $identifier[$field] = $data[$field];
                unset($data[$field]);
            }
        }

        $this->connection->update($this->table(), $data, $identifier);
    }

    /**
     * @param Entity $entity
     */
    public function save(Entity $entity)
    {
        $this[$entity->isNew() ? 'insert' : 'update']($entity);
    }

    /**
     * @param Entity $entity
     */
    public function delete(Entity $entity)
    {
        $data = $this->flatten($entity);
        $identifier = [];
        $sequence = null;
        foreach ($this->fields as $field => $definition) {
            if ($definition['primary'] && isset($data[$field])) {
                $identifier[$field] = $data[$field];
            }

            if ($definition['sequence']) {
                $sequence = $field;
            }
        }

        $this->connection->delete($this->table(), $identifier);
        $entity->setNew(true);

        if ($sequence) {
            $this->bind($entity, [$sequence => null]);
        }
    }

    /**
     * @param array $conditions
     * @return Query
     */
    public function find(array $conditions = [])
    {
        return $this->connection->select($this->table(), $conditions)->mapper($this);
    }

    /**
     * @param array $results
     * @return Collection
     */
    public function collection(array $results)
    {
        $entities = [];
        foreach ($results as $result) {
            $entity = $this->entity();
            $this->expand($entity, $result);
            $entity->setNew(false);

            $entities[] = $entity;
        }

        return new $this->collectionClass($entities);
    }

    /**
     * @param string $entityClass
     * @param array $conditions
     * @return Entity|null
     */
    public function one($entityClass, array $conditions)
    {
        if (!is_subclass_of($entityClass, Entity::class)) {
            throw new \InvalidArgumentException('Related $entityClass must be a subclass of ' . Entity::class);
        }

        return $this->connection->mapper($entityClass)->find($conditions)->first();
    }

    /**
     * @param string $entityClass
     * @param array $conditions
     * @return Query
     */
    public function many($entityClass, array $conditions)
    {
        if (!is_subclass_of($entityClass, Entity::class)) {
            throw new \InvalidArgumentException('Related $entityClass must be a subclass of ' . Entity::class);
        }

        return $this->connection->mapper($entityClass)->find($conditions);
    }

    /**
     * @param string $entityClass
     * @param string $through
     * @param array $conditions
     * @param string $field
     * @return Query
     */
    public function manyThrough($entityClass, $through, array $conditions, $field = 'id')
    {
        if (!is_subclass_of($entityClass, Entity::class)) {
            throw new \InvalidArgumentException('Related $entityClass must be a subclass of ' . Entity::class);
        }

        $values = [];
        list($tableThrough, $fieldThrough) = explode('.', $through);
        foreach ($this->connection->select($tableThrough, $conditions) as $data) {
            $values[] = $data[$fieldThrough];
        }

        return $this->connection->mapper($entityClass)->find([$field => $values]);
    }

    /**
     * @param Entity $entity
     * @param array $data
     */
    protected function expand(Entity $entity, array $data)
    {
        $result = [];
        $fields = $this->fields;
        $platform = $this->connection()->getDatabasePlatform();
        foreach ($data as $field => $value) {
            if (!isset($fields[$field])) {
                continue;
            }

            $result[$field] = Type::getType($fields[$field][0])->convertToPHPValue($value, $platform);
        }

        $this->bind($entity, $result);
    }

    /**
     * @param Entity $entity
     * @return array
     */
    protected function flatten(Entity $entity)
    {
        $result = [];
        $fields = $this->fields;
        $data = array_intersect_key($entity->data(), $fields);
        $platform = $this->connection()->getDatabasePlatform();
        foreach ($data as $field => $value) {
            $result[$field] = Type::getType($fields[$field][0])->convertToDatabaseValue($value, $platform);
        }

        return $result;
    }

    /**
     * @param Entity $entity
     * @param array $data
     */
    protected function bind(Entity $entity, array $data)
    {
        foreach ($data as $field => $value) {
            $entity->{'set' . $this->camelize($field)}($value);
        }
    }

    /**
     * @param array|string $definition
     * @return array
     */
    protected function field($definition)
    {
        return array_merge([
            'default' => null,
            'primary' => false,
            'sequence' => false,
        ], (array)$definition);
    }

    /**
     * @param string $str
     * @return string
     */
    protected function camelize($str)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $str)));
    }
}

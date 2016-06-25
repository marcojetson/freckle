<?php

namespace Freckle;

use Doctrine\DBAL\Types\Type;

class Mapper
{
    use Partial\Camelize;

    /** @var Connection */
    protected $connection;

    /** @var Mapping */
    protected $mapping;

    /** @var array */
    protected $identityMap;

    /**
     * @param Connection $connection
     * @param Mapping $mapping
     */
    public function __construct(Connection $connection, Mapping $mapping)
    {
        $this->connection = $connection;
        $this->mapping = $mapping;
    }

    /**
     * @param array $data
     * @return Entity
     */
    public function entity(array $data = [])
    {
        /** @var Entity $entity */
        $entityClass = $this->mapping->entityClass();
        $entity = new $entityClass;

        foreach ($this->mapping->fields() as $field => $definition) {
            if (isset($data[$field]) || !isset($definition['default'])) {
                continue;
            }

            $data[$field] = is_callable($definition['default']) ? $definition['default']() : $definition['default'];
        }

        foreach ($this->mapping->relations() as $relation => $definition) {
            $data[$relation] = function (Entity $entity) use ($definition) {
                return $this->relation($entity, $definition);
            };
        }

        $this->bind($entity, $data);
        $entity->flag(Entity::FLAG_NEW);

        return $entity;
    }

    /**
     * @param array $data
     * @return Entity
     */
    public function create(array $data)
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
        $this->connection->insert($this->mapping->table(), $data);

        $entity->unflag(Entity::FLAG_NEW | Entity::FLAG_DIRTY);

        $sequence = $this->mapping->sequence();
        if ($sequence) {
            $value = isset($data[$sequence['field']]) ? $data[$sequence['field']] : $this->connection->lastInsertId($sequence['name']);
            $this->bind($entity, [$sequence['field'] => $value]);
        }

        $this->identityMap[$this->key($data)] = $entity;
    }

    /**
     * @param Entity $entity
     */
    public function update(Entity $entity)
    {
        if (!$entity->flagged(Entity::FLAG_DIRTY)) {
            return;
        }

        $data = $this->flatten($entity);
        $identifier = [];

        foreach (array_keys(array_intersect_key($data, $this->mapping->identifier())) as $field) {
            $identifier[$field] = $data[$field];
            unset($data[$field]);
        }

        $this->connection->update($this->mapping->table(), $data, $identifier);
    }

    /**
     * @param Entity $entity
     */
    public function save(Entity $entity)
    {
        $this->{$entity->flagged(Entity::FLAG_NEW) ? 'insert' : 'update'}($entity);
    }

    /**
     * @param array $data
     * @param array $conditions
     * @return Entity
     */
    public function upsert(array $data = [], array $conditions = [])
    {
        $entity = $this->find($conditions)->first() ? : $this->entity();
        $this->bind($entity, $data);
        $this->save($entity);

        return $entity;
    }

    /**
     * @param Entity $entity
     */
    public function delete(Entity $entity)
    {
        $data = $this->flatten($entity);
        $identifier = [];
        $sequence = null;

        foreach (array_intersect_key($data, $this->mapping->identifier()) as $field => $value) {
            $identifier[$field] = $value;
        }

        $this->connection->delete($this->mapping->table(), $identifier);
        $entity->flag(Entity::FLAG_NEW);

        $sequence = $this->mapping->sequence();
        if ($sequence) {
            $this->bind($entity, [$sequence['field'] => null]);
        }

        unset($this->identityMap[$this->key($data)]);
    }

    /**
     * @param array $conditions
     * @return Query
     */
    public function find(array $conditions = [])
    {
        return $this->connection->select($this->mapping->table(), $conditions)->mapper($this);
    }

    /**
     * @param array $conditions
     * @return Entity|null
     */
    public function first(array $conditions = [])
    {
        $key = $this->key($conditions);
        return isset($this->identityMap[$key]) ? $this->identityMap[$key] : $this->find($conditions)->first();
    }

    /**
     * @param array $results
     * @return array
     */
    public function collection(array $results)
    {
        $collection = [];

        foreach ($results as $result) {
            $key = $this->key($result);

            if (!isset($this->identityMap[$key])) {
                $entity = $this->entity();

                $this->expand($entity, $result);
                $entity->unflag(Entity::FLAG_NEW);

                $this->identityMap[$key] = $entity;
            }

            $collection[] = $this->identityMap[$key];
        }

        return $collection;
    }

    /**
     * @param Entity $entity
     * @param array $data
     */
    protected function expand(Entity $entity, array $data)
    {
        $result = [];
        $fields = $this->mapping->fields();
        $data = array_intersect_key($data, $fields);
        $platform = $this->connection->getDatabasePlatform();

        foreach ($data as $field => $value) {
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
        $fields = $this->mapping->fields();
        $data = array_intersect_key($entity->data(), $fields);
        $platform = $this->connection->getDatabasePlatform();

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
     * @param Entity $entity
     * @param $definition
     * @return Query
     */
    protected function relation(Entity $entity, $definition)
    {
        list($type, $entityClass, $conditions) = $definition;

        if (!is_subclass_of($entityClass, Entity::class)) {
            throw new \InvalidArgumentException('Related $entityClass must be a subclass of ' . Entity::class);
        }

        foreach ($conditions as $key => $value) {
            if (!is_string($value) || !preg_match('/^this\.(?P<field>.+)$/', $value, $match)) {
                continue;
            }

            $conditions[$key] = $entity->{'get' . $this->camelize($match['field'])}();
        }

        if (isset($definition['through'])) {
            $values = [];
            list($tableThrough, $fieldThrough) = explode('.', $definition['through']);
            foreach ($this->connection->select($tableThrough, $conditions) as $data) {
                $values[] = $data[$fieldThrough];
            }

            $conditions = [isset($definition['field']) ? $definition['field'] : 'id' => $values];
        }

        $method = str_replace(['one', 'many'], ['first', 'find'], $type);

        return $this->connection->mapper($entityClass)->$method($conditions);
    }

    /**
     * @param array $data
     * @return string
     */
    protected function key(array $data)
    {
        ksort($data);
        $data = array_intersect_key($data, $this->mapping->identifier());
        return http_build_query($data);
    }
}

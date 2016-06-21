<?php

namespace Freckle;

use Doctrine\DBAL\Types\Type;

class Mapper
{
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

        foreach ($this->relations as $relation => $definition) {
            $values[$relation] = function (Entity $entity) use ($definition) {
                $query = $this->relation($entity, $definition);
                return $definition[0] === 'one' ? $query->first() : $query;
            };
        }

        $this->bind($entity, $values);
        $entity->flag(Entity::FLAG_NEW);

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

        $this->connection->insert($this->table, $data);

        $entity->unflag(Entity::FLAG_NEW);
        $entity->unflag(Entity::FLAG_DIRTY);

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
        if (!$entity->flagged(Entity::FLAG_DIRTY)) {
            return;
        }

        $data = $this->flatten($entity);
        $identifier = [];

        foreach ($this->fields as $field => $definition) {
            if ($definition['primary'] && isset($data[$field])) {
                $identifier[$field] = $data[$field];
                unset($data[$field]);
            }
        }

        $this->connection->update($this->table, $data, $identifier);
    }

    /**
     * @param Entity $entity
     */
    public function save(Entity $entity)
    {
        $this[$entity->flagged(Entity::FLAG_NEW) ? 'insert' : 'update']($entity);
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

        $this->connection->delete($this->table, $identifier);
        $entity->flag(Entity::FLAG_NEW);

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
        return $this->connection->select($this->table, $conditions)->mapper($this);
    }

    /**
     * @param array $results
     * @return array
     */
    public function collection(array $results)
    {
        $entities = [];
        foreach ($results as $result) {
            $entity = $this->entity();
            $this->expand($entity, $result);
            $entity->unflag(Entity::FLAG_NEW);

            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * @param Entity $entity
     * @param array $data
     */
    protected function expand(Entity $entity, array $data)
    {
        $result = [];
        $fields = $this->fields;
        $platform = $this->connection->getDatabasePlatform();

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
     * @param Entity $entity
     * @param $definition
     * @return Query
     */
    protected function relation(Entity $entity, $definition)
    {
        list(, $entityClass, $conditions) = $definition;

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

        return $this->connection->mapper($entityClass)->find($conditions);
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

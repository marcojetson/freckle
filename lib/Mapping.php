<?php

namespace Freckle;

class Mapping
{
    /** @var string */
    protected $entityClass;

    /** @var string */
    protected $mapperClass = Mapper::class;

    /** @var string */
    protected $table;

    /** @var array */
    protected $fields = [];

    /** @var string */
    protected $sequence;

    /** @var string */
    protected $sequenceName;

    /** @var array */
    protected $identifier = [];

    /** @var array */
    protected $relations = [];

    /**
     * @param string $entityClass
     */
    public function __construct($entityClass)
    {
        if (!is_subclass_of($entityClass, Entity::class)) {
            throw new \InvalidArgumentException('$entityClass must be a subclass of ' . Entity::class);
        }

        $definition = call_user_func([$entityClass, 'definition']);

        $this->entityClass = $entityClass;

        if (isset($definition['mapper'])) {
            $this->mapperClass = $definition['mapper'];
        }

        $this->table = $definition['table'];

        foreach ($definition['fields'] as $field => $options) {
            $options = array_merge([
                'default' => null,
                'primary' => false,
                'sequence' => false,
            ], (array)$options);

            $this->fields[$field] = $options;

            if ($options['primary']) {
                $this->identifier[$field] = $options;
            }

            if ($options['sequence']) {
                $this->sequence = $field;
                $this->sequenceName = $options['sequence'];
            }
        }

        if (isset($definition['relations'])) {
            $this->relations = $definition['relations'];
        }
    }

    /**
     * @return string
     */
    public function entityClass()
    {
        return $this->entityClass;
    }

    /**
     * @return string
     */
    public function mapperClass()
    {
        return $this->mapperClass;
    }

    /**
     * @return string
     */
    public function table()
    {
        return $this->table;
    }

    /**
     * @return array
     */
    public function fields()
    {
        return $this->fields;
    }

    /**
     * @return string
     */
    public function sequence()
    {
        return $this->sequence;
    }

    /**
     * @return string
     */
    public function sequenceName()
    {
        return $this->sequenceName;
    }

    /**
     * @return array
     */
    public function identifier()
    {
        return $this->identifier;
    }

    /**
     * @return array
     */
    public function relations()
    {
        return $this->relations;
    }
}

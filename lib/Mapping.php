<?php

namespace Freckle;

class Mapping
{
    use Partial\Camelize;
    
    /** @var string */
    protected $entityClass;

    /** @var string */
    protected $mapperClass = Mapper::class;

    /** @var string */
    protected $table;

    /** @var array */
    protected $fields;

    /** @var array */
    protected $sequence;

    /** @var array */
    protected $identifier;

    /** @var array */
    protected $relations;

    /**
     * @param array $definition
     */
    public function __construct(array $definition)
    {
        if (!isset($definition['class']) && isset($definition['table'])) {
            $namespace = isset($definition['namespace']) ? trim($definition['namespace'], '\\') . '\\' : '';
            $definition['class'] = $namespace . $this->camelize($definition['table']);
        }

        if (isset($definition['class']) && !isset($definition['table'])) {
            $definition['table'] = $this->uncamelize($definition['class']);
        }

        $this->entityClass = $definition['class'];

        if (isset($definition['mapper'])) {
            $this->mapperClass = $definition['mapper'];
        }

        $this->table = $definition['table'];

        $this->fields = [];
        $this->identifier = [];
        foreach ($definition['fields'] as $field => $options) {
            $options = array_merge([
                'default' => null,
                'primary' => false,
                'sequence' => false,
                'property' => $this->camelize($field),
            ], (array)$options);

            $this->fields[$field] = $options;

            if ($options['primary']) {
                $this->identifier[$field] = $options;
            }

            if ($options['sequence']) {
                $this->sequence = [
                    'field' => $field,
                    'name' => $options['sequence'],
                ];
            }
        }

        $this->relations = isset($definition['relations']) ? $definition['relations'] : [];
    }

    /**
     * @param string $entityClass
     * @return static
     */
    public static function fromClass($entityClass)
    {
        if (!is_subclass_of($entityClass, Entity::class)) {
            throw new \InvalidArgumentException('$entityClass must be a subclass of ' . Entity::class);
        }

        return new static(array_merge(['class' => $entityClass], call_user_func([$entityClass, 'definition'])));
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
     * @return array
     */
    public function sequence()
    {
        return $this->sequence;
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

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        $header = '';

        $parts = explode('\\', $this->entityClass());
        $class = $parts[sizeof($parts) - 1];

        if (sizeof($parts) > 1) {
            $header .= 'namespace ' . join('\\', array_slice($parts, 0, -1)) . ';' . str_repeat(PHP_EOL, 2);
        }

        $fields = '';
        $header .= '/**' . PHP_EOL . ' * Class ' . $class . PHP_EOL;
        foreach ($this->fields as $field => $definition) {
            $internalType = 'mixed';

            $header .= ' *' . PHP_EOL;
            $header .= ' * @method ' . $internalType . ' get' . $definition['property'] . '()' . PHP_EOL;
            $header .= ' * @method set' . $definition['property'] . '(' . $internalType . ' $' . lcfirst($definition['property']) . ')' . PHP_EOL;

            $fields .= PHP_EOL . str_repeat(' ', 16) . '\'' . $field . '\' => [\'' . $definition[0] . '\'';

            if ($definition['sequence']) {
                $sequence = is_string($definition['sequence']) ? '\'' . $definition['sequence'] . '\'' : 'true';
                $fields .= ', \'sequence\' => ' . $sequence;
            }

            if ($definition['primary']) {
                $fields .= ', \'primary\' => true';
            }

            $fields .= '],';
        }
        $header .= ' */';

        $fields .= PHP_EOL . str_repeat(' ', 12);

        return <<<CODE
<?php

{$header}
class {$class} extends \Freckle\Entity
{
    /**
     * @inheritdoc
     */
    public static function definition()
    {
        return [
            'table' => '{$this->table}',
            'fields' => [{$fields}],  
        ];
    }
}

CODE;
    }
}

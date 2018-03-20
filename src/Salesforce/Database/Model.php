<?php
namespace Stratease\Salesforcery\Salesforce\Database;

use Stratease\Salesforcery\Salesforce\Connection\REST\Client;

abstract class Model
{
    /**
     * @var Client Our REST client library
     */
    protected static $connection;
    /**
     * @var string Salesforce object 'name'
     */
    public static $resourceName;
    /**
     * @var string
     */
    public $primaryKey = 'Id';
    /**
     * @var array The field => value for this model
     */
    protected $attributes = [];
    /**
     * @var array Fields that have changed since model hydration with their previous values, field => prevValue
     */
    protected $changed = [];

    /**
     * Model constructor.
     *
     * @param array $data Salesforce fields
     */
    public function __construct($data = [])
    {
        //@todo init schema inspector
        $this->hydrate($data);
    }

    /**
     * @param $connection Client
     */
    public static function registerConnection(Client $connection)
    {
        self::$connection = $connection;
    }

    /**
     * @param $data
     *
     * @return Model
     */
    public static function hydrateFactory($data)
    {
        $instanceName = static::class;
        $instance     = new $instanceName();
        $instance->hydrate($data);

        return $instance;
    }


    /**
     * @param $field
     * @param $value
     *
     * @return Collection
     */
    public static function findBy()
    {
        $instance = new static;
        $args = func_get_args() ? func_get_args() : [[]];
        $query = call_user_func_array([$instance, 'where'], $args);

        return $query->get();
    }


    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    /**
     * Convert the model to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * @return QueryBuilder
     */
    public function newQuery()
    {
        return (new QueryBuilder(self::$connection))
            ->from(static::resolveObjectName())
            ->select(array_keys(static::getSchema()))->setModel($this);
    }

    /**
     * @param $field
     * @param $value
     *
     * @return Model
     */
    public static function findOneBy()
    {
        $results = self::findBy(...func_get_args());
        if(isset($results[0])) {

            return $results[0];
        }

        return null;
    }

    /**
     * @return array
     */
    public static function getSchema()
    {
        SchemaInspector::registerConnection(self::$connection);
        return SchemaInspector::getSchema(self::resolveObjectName());
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function hydrate(array $data)
    {
        $schema = self::getSchema();

        foreach ($schema as $field => $dataType) {
            $this->hydrateField($field, isset($data[$field]) ? $data[$field] : null);
        }

        return $this;
    }

    /**
     * @return bool|string Attempts to detect the Salesforce object name
     */
    public static function resolveObjectName()
    {
        if (static::$resourceName) {

            return static::$resourceName;
        }

        return substr(static::class, strrpos(static::class, '\\') + 1);
    }

    /**
     * Will update this entry in the database
     *
     * @return $this
     */
    public function update()
    {
        $primaryKey = $this->primaryKey;
        if ($this->$primaryKey) {
            if (self::$connection->update(self::resolveObjectName(),
                $this->$primaryKey,
                $this->getChanges())) {

                $this->discardChanges();
            }
        }

        return $this;
    }

    /**
     * Will insert a new entry into the database
     *
     * @return $this
     */
    public function insert()
    {
        if ($id = self::$connection->create(self::resolveObjectName(),
            $this->getChanges())) {
            $this->hydrateField($this->primaryKey, $id);
            $this->discardChanges();
        }

        return $this;
    }

    /**
     * Resets any pending changes made since instance was initially hydrated
     *
     * @return $this
     */
    public function discardChanges()
    {
        $this->changed = [];

        return $this;
    }

    /**
     * @return array Gets an array of field => val with pending updates to be pushed to the database
     */
    public function getChanges()
    {
        return array_intersect_key($this->attributes, $this->changed);
    }

    /**
     * @return array Returns an array with all current values for this resource
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Inspects this objects fields to detect if a field is valid
     *
     * @param $field
     *
     * @return bool
     */
    public static function isValidField($field)
    {
        SchemaInspector::registerConnection(self::$connection);

        return isset(SchemaInspector::getSchema(self::resolveObjectName())[$field]);
    }

    /**
     * Track a field changed event
     *
     * @param $field    string
     * @param $oldValue mixed
     */
    protected function fireChange($field, $oldValue)
    {
        $this->changed[$field] = $oldValue;
    }

    /**
     * Has this field been modified since model hydration?
     *
     * @param $field
     *
     * @return bool
     */
    public function hasChanged($field)
    {
        return isset($this->changed[$field]);
    }

    /**
     * @param $field
     * @param $value
     *
     * @return mixed
     */
    public function __set($field, $value)
    {
        $setter = 'set' . $field;
        // do change operation
        if (self::isValidField($field)) {
            $this->fireChange($field, $this->$field);
        }

        return $this->$setter($value);
    }

    /**
     * @param $field
     *
     * @return mixed
     */
    public function __get($field)
    {
        $getter = 'get' . $field;

        return $this->$getter();
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return $this|null|mixed
     */
    public function __call($name, $arguments)
    {
        // getter?
        if (substr($name, 0, 3) === 'get') {
            $field = substr($name, 3);
            if (self::isValidField($field)) {
                return $this->attributes[$field] ?: null;
            }
            trigger_error("Invalid field: " . self::resolveObjectName() . "->" . $field . " being accessed.", E_USER_WARNING);
        }

        // setter?
        if (substr($name, 0, 3) === 'set') {
            $field = substr($name, 3);
            $this->fireChange($field, $this->attributes[$field] ?: null);
            $this->attributes[$field] = $arguments[0]; // @todo datatypeing?

            return $this;
        }

        if (in_array($name, ['increment', 'decrement'])) {
            return $this->$name(...$arguments);
        }

        return $this->newQuery()->$name(...$arguments);
    }

    /**
     * @param $field
     * @param $value
     *
     * @return $this
     */
    protected function hydrateField($field, $value)
    {
        $this->attributes[$field] = $value;

        return $this;
    }

    /**
     * Will either do an update or insert on the database.
     *
     * @return $this
     */
    public function save()
    {
        $primaryKey = $this->primaryKey;

        if (!$this->$primaryKey) {
            // do insert
            return $this->insert();
        }

        // we exist, do update
        return $this->update();
    }

    /**
     * @return $this
     */
    public function delete()
    {
        $connection = self::$connection;
        $primaryKey = $this->primaryKey;
        $connection->delete(self::resolveObjectName(), $this->$primaryKey);

        return $this;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: edwindaniels
 * Date: 3/1/18
 * Time: 5:21 PM
 */

namespace Stratease\Salesforcery\Salesforce\Database;

use Stratease\Salesforcery\Salesforce\Connection\REST\Client;

class SchemaInspector
{
    /**
     * @var Client
     */
    protected static $connection;
    /**
     * @var array Object => details
     */
    protected static $schema = [];

    /**
     * @param Client $connection
     */
    public static function registerConnection(Client $connection) {
        self::$connection = $connection;
    }

    /**
     * Utilizes the Salesforce Describe endpoint
     * @param $object string Salesforce object name
     *
     * @return array A list of the fields for this object
     */
    public static function getSchema($object) {

        if(!isset(self::$schema[$object])) {

            self::$schema[$object] = self::$connection->describe($object);

            // transform fields array into hashed array
            $keys = array_map(function($field) {
                    return $field['name'];
                },
                self::$schema[$object]['fields']);
            $transformedFields = array_combine($keys, self::$schema[$object]['fields']);
            self::$schema[$object]['fields'] = $transformedFields;
        }

        return self::$schema[$object]['fields'];
    }

    /**
     * @return mixed Returns all the objects defined in Salesforce.
     */
    public static function getObjects() {
        return self::$connection->describeObjects();
    }
}
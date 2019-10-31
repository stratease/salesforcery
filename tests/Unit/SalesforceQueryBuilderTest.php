<?php
/**
 * Created by PhpStorm.
 * User: edwindaniels
 * Date: 3/1/18
 * Time: 2:33 PM
 */

namespace Unit;

use PHPUnit\Framework\TestCase;
use Stratease\Salesforcery\Salesforce\Connection\REST\Authentication\PasswordAuthentication;
use Stratease\Salesforcery\Salesforce\Connection\REST\Client;
use Stratease\Salesforcery\Salesforce\Database\Model;
use Stratease\Salesforcery\Salesforce\Database\QueryBuilder;
use Stratease\Salesforcery\Tests\Account;

class SalesforceQueryBuilderTest extends TestCase
{
    /**
     * @var Client
     */
    public static $client;

    /**
     *
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $options = require __DIR__ . '/../../config/test.php';

        $salesforce = new PasswordAuthentication($options['salesforce']);
        $salesforce->setEndpoint('https://test.salesforce.com/');
        $client = new Client($salesforce);
        Model::registerConnection($client);

        self::$client = $client;
    }

    /**
     * @test
     */
    public function build_simple_where()
    {
        $builder = new QueryBuilder(self::$client);
        $builder->setModel(new Account());
        $sql = $builder->where('Name', 'Frank')
            ->select('Name')
            ->from('Account')
            ->toSql();

        $this->assertEquals('SELECT Name FROM Account WHERE Name = \'Frank\'', $sql);
    }

    /**
     * @test
     */
    public function build_where_with_array()
    {
        $builder = new QueryBuilder(self::$client);
        $builder->setModel(new Account());
        $sql = $builder->where(['Name' => 'Frank',
            'Email'                        => 'frank@test.com'])
            ->select(['Name', 'Email'])
            ->from('Account')
            ->toSql();

        $this->assertEquals("SELECT Name, Email FROM Account WHERE Name = 'Frank' AND Email = 'frank@test.com'", $sql);
    }

    /**
     * @test
     */
    public function build_where_with_operators()
    {
        $builder = new QueryBuilder(self::$client);
        $builder->setModel(new Account());
        $sql = $builder->where('Name', '!=', 'Frank')
            ->where('Email', '>', 'frank@test.com')
            ->select(['Name', 'Email'])
            ->from('Account')
            ->toSql();

        $this->assertEquals("SELECT Name, Email FROM Account WHERE Name != 'Frank' AND Email > 'frank@test.com'", $sql);
    }

}
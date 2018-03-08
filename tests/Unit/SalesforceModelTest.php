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
use Stratease\Salesforcery\Salesforce\Database\Collection;
use Stratease\Salesforcery\Salesforce\Database\Model;
use Stratease\Salesforcery\Tests\Account;

class SalesforceModelTest extends TestCase
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
        $options = require(__DIR__.'/../../config/test.php');

        $salesforce = new PasswordAuthentication($options['salesforce']);
        $salesforce->setEndpoint('https://test.salesforce.com/');
        $client = new Client($salesforce);
        Model::registerConnection($client);

        self::$client = $client;
    }

    /**
     * @test
     */
    public function can_see_resource_name()
    {
        $this->assertEquals('Account', Account::resolveObjectName());
    }

    /**
     * @test
     */
    public function can_fetch_model()
    {
        // create one to be fetched
        $obj = new Account();
        $obj->Name = 'Testing Fetch';
        $obj->save();

        $obj2 = Account::findOneBy('Id', $obj->Id);

        $this->assertEquals($obj->Id, $obj2->Id, 'Id should match the previously generated model.');
    }

    /**
     * @test
     */
    public function can_access_collection()
    {
       $collection = Account::findBy();

       $this->assertInstanceOf('Traversable', $collection);
       $this->assertInstanceOf(Collection::class, $collection);
       $this->assertContainsOnlyInstancesOf(Account::class, $collection);
    }

    /**
     * @test
     */
    public function can_mass_call_collection()
    {
        $collection = Account::findBy();
        // this verifies both getting, setting, and calling a method on the collection children
        $collection->Name = 'Bob';
        $names = $collection->Name;
        $list = $collection->toArray();
        $this->assertContains('Bob', $names, 'Expected ability to set and retrieve the Name field on our collection.');
        $this->assertArraySubset([['Name' => 'Bob']], $list, 'Expected ability to set and retrieve the Name field on our collection.');
    }

    /**
     * @test
     */
    public function can_fetch_model_schema()
    {
        $this->assertGreaterThan(5, count(Account::getSchema()), 'There are more fields expected in the model\'s schema');
    }

    /**
     * @test
     */
    public function can_set_model_field()
    {
        $obj = new Account();
        $name = 'Awesome '.uniqid();
        $obj->Name = $name;

        $this->assertEquals($name, $obj->Name, 'Expected ability to set Name field on Model');
        $this->assertArraySubset(['Name' => $name], $obj->toArray(), 'Expected change to show in toArray()');
    }

    /**
     * @test
     */
    public function can_insert_model()
    {
        $obj = new Account();
        $obj->Name = "Testing Insert ".uniqid();
        $obj->insert();

        $this->assertNotEmpty($obj->Id, 'Should have hydrated an Id from the insert.');
        $this->assertEmpty($obj->getChanges(), 'Changes should have been reset on insert.');
    }

    /**
     * @test
     */
    public function can_update_model()
    {
        $newName = 'Updated - Test'.uniqid();

        $obj = Account::findOneBy(); // fetch fresh instance to perform update on
        $obj->Name = $newName;
        $obj->update();

        $verifyUpdate = Account::findOneBy('Id', $obj->Id); // fetch new one
        $this->assertEquals($newName, $verifyUpdate->Name, 'Name failed to change in the database.');
    }

    /**
     * @test
     */
    public function can_delete_model()
    {
        $obj = new Account();
        $obj->Name = "Delete Me";
        $obj->save();

        $id = $obj->Id;
        $obj->delete();

        $this->assertNull(Account::findOneBy('Id', $id), "Should not be able to fetch just deleted object.");
    }
}
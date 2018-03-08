<?php
namespace Unit;
use PHPUnit\Framework\TestCase;
use Stratease\Salesforcery\Salesforce\Connection\REST\Authentication\AuthenticationInterface;
use Stratease\Salesforcery\Salesforce\Connection\REST\Authentication\PasswordAuthentication;
use Stratease\Salesforcery\Salesforce\Connection\REST\Client;

class SalesforceConnectionTest extends TestCase
{
    public static $client;

    public $configFile = __DIR__.'/../../config/test.php';


    /**
     * @test
     */
    public function can_auth()
    {
        $options = require($this->configFile);

        $authentication = new PasswordAuthentication($options['salesforce']);
        $authentication->setEndpoint('https://test.salesforce.com/');
        $this->assertArrayHasKey('access_token', $authentication->authenticate(), 'Unable to connect. Check database configuration');

        return $authentication;
    }

    /**
     * @depends can_auth
     * @test
     */
    public function can_see_resource_with_client(AuthenticationInterface $authentication)
    {
        $client = new Client($authentication);

        $results = $client->query("SELECT Id FROM Account LIMIT 1");
        $this->assertArrayHasKey('records', $results, 'Unable to access resource. Response: '.json_encode($results));
    }

}
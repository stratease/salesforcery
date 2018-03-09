[![Latest Stable Version](https://poser.pugx.org/stratease/salesforcery/v/stable)](https://packagist.org/packages/stratease/salesforcery) [![Total Downloads](https://poser.pugx.org/stratease/salesforcery/downloads)](https://packagist.org/packages/stratease/salesforcery) [![License](https://poser.pugx.org/stratease/salesforcery/license)](https://packagist.org/packages/stratease/salesforcery)

# Setup

### 1. Setting up a Connected App
You need to create a [Connected App](https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/intro_defining_remote_access_applications.htm) to generate the `client_id` and `client_secret` values for the OAuth2 connection. Once created use the credentials to register a connection.

### 2. Installation
```bash
composer require stratease/salesforcery
```

### 3. Connection
```php
use Stratease\Salesforcery\Salesforce\Connection\REST\Authentication\PasswordAuthentication;
use Stratease\Salesforcery\Salesforce\Connection\REST\Client;
use Stratease\Salesforcery\Salesforce\Database\Model;


$authentication = new PasswordAuthentication([
     'grant_type' => 'password',
     'client_id' => 'your app ID',
     'client_secret' => 'your app secret',
     'username' => 'salesforce@user.com',
     'password' => 'password+token',
     'authorization_url' => 'https://test.salesforce.com/'
 ]);

// Typically https://login.salesforce.com, for testing use https://test.salesforce.com 
$authentication->setEndpoint('https://test.salesforce.com/');

$client = new Client($authentication);
$response = $client->query("SELECT Id FROM Account LIMIT 1");

// Should output a response with a 'records' key that conains an array of results.
print_r($response); 

// Register your connection so the ORM can connect
Model::registerConnection($client);

// Your model to connect to the Salesforce Account object
$account = Your/Models/Account::findOneBy('Id', 'abc123');
     
```



# Examples
### Defining a model

For a simple setup...
```php
use Stratease\Salesforcery\Salesforce\Database\Model;

class Account extends Model
{
    // Account maps to Account in Salesforce, nothing else required.
}
```
More advanced usage...
```php
use Stratease\Salesforcery\Salesforce\Database\Model;

class Product extends Model
{
    public static $resourceName = 'Product2';
}
```

- Extend `Stratease\Salesforcery\Salesforce\Database\Model` and you're set! Sorta...
- The model's Salesforce Object name is mapped by default via the {ClassName} -> {ObjectName}. You can override by defining the `resourceName` property.
- Explicit field definitions allowed with option to override. `get{Field}()`
- Will use `SchemaInspector` to discover fields for this resource and automatically hydrate instances with the fields

### Query Objects
```php
// Account is returned
$account = Account::findOneBy('Name', 'Acme Corp');
// An iterable Collection is returned
$accountCollection = Account::findBy(['OwnerId' => '123', 'Status' => 'Active']); // performs an AND expression on the associative array
foreach($accountCollection as $account) {
    echo "\n".$account->Name;
}
```
### Modify Objects
```php
// create
$account = new Account();
$account->Name = 'Acme Uber Corp';
$account->save(); 

// update
$account2 = Account::findOneBy('Name', 'Acme Uber Corp');
$account2->Name = 'Acme Uber Inc.';
$account2->save(); 

// delete
$account2->delete();

```
# Salesforce
This library uses Salesforces [REST API](https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/resources_list.htm). Tested on version 39. 
# Todo
- Object relations mapping, i.e. `$contact->phone->isMobile`
- Add more REST endpoint support
- Batch REST integration
- Object -> field schema cache. As it stands it will request the schema `n * Models` times per run. So if you do a series of requests utilizing `Account` and `User` models, it will request the schema twice, once for each model.
- Query builder object
- Look into extended Eloquent more formally.
- Test with other php versions. Was developed on `7.1`
- Docker environment for tests. As it stands now tests require a connection to a sandbox environment. Preferred setup for now in order to validate various Salesforce REST protocols are passing muster.  
- Currently only support for OAuth2 `password` grant type. Accepting pull requests for other authorization protocols.
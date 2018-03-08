<?php

namespace Stratease\Salesforcery\Salesforce\Connection\REST\Authentication;

use Stratease\Salesforcery\Salesforce\Connection\REST\Exception\Authentication as Exception;
use GuzzleHttp\Client;

class PasswordAuthentication implements AuthenticationInterface
{
    protected $client;
    protected $endPoint;
    protected $options;
    protected $access_token;
    protected $instance_url;

    public function __construct(array $options)
    {
        $this->setEndpoint($options['authorization_url']);
        $this->options = $options;
    }

    public function authenticate()
    {
        $client = new Client();

        $request = $client->request('post', $this->endPoint . 'services/oauth2/token',
            ['form_params' =>
                 [
                     'grant_type' => $this->options['grant_type'],
                     'client_id' => $this->options['client_id'],
                     'client_secret' => $this->options['client_secret'],
                     'username' => $this->options['username'],
                     'password' => $this->options['password']
                 ]
            ]);
        $response = json_decode($request->getBody(), true);

        if ($response) {
            $this->access_token = $response['access_token'];
            $this->instance_url = $response['instance_url'];

            return $response;
        } else {
            throw new Exception($request->getBody());
        }
    }

    public function setEndpoint($endPoint)
    {
        $this->endPoint = $endPoint;

        return $this;
    }

    public function getAccessToken()
    {
        if(!$this->access_token) {
            $this->authenticate();
        }

        return $this->access_token;
    }

    public function getInstanceUrl()
    {
        if(!$this->instance_url) {
            $this->authenticate();
        }

        return $this->instance_url;
    }
}

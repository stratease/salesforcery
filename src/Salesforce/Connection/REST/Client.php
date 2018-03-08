<?php

namespace Stratease\Salesforcery\Salesforce\Connection\REST;

use GuzzleHttp\Client as GuzzleClient;

use GuzzleHttp\Exception\ClientException;
use Stratease\Salesforcery\Salesforce\Connection\REST\Authentication\AuthenticationInterface;

class Client
{
    protected $authentication;
    public $apiVersion;
    public function __construct(AuthenticationInterface $authentication, $apiVersion = '39.0')
    {
        $this->authentication = $authentication;
        $this->apiVersion = $apiVersion;
    }

    public function queryAll($query)
    {
        $url = $this->authentication->getInstanceUrl()."/services/data/v".$this->apiVersion."/queryAll";

        $client = new GuzzleClient();
        try {
            $request = $client->request('GET', $url, [
                'headers' => [
                    'Authorization' => "OAuth " . $this->authentication->getAccessToken()
                ],
                'query'   => [
                    'q' => $query
                ]
            ]);
        } catch (ClientException $e) {

            throw new \Exception($e->getResponse()->getBody()->getContents());
        }

        return json_decode($request->getBody(), true);
    }

    public function query($query)
    {
        $url = $this->authentication->getInstanceUrl()."/services/data/v".$this->apiVersion."/query";

        $client = new GuzzleClient();
        try {
            $request = $client->request('GET', $url, [
                'headers' => [
                    'Authorization' => "OAuth " . $this->authentication->getAccessToken()
                ],
                'query'   => [
                    'q' => $query
                ]
            ]);
        } catch (ClientException $e) {

            throw new \Exception($e->getResponse()->getBody()->getContents());
        }

        return json_decode($request->getBody(), true);
    }

    public function create($object, array $data)
    {
        $url = $this->authentication->getInstanceUrl()."/services/data/v".$this->apiVersion."/sobjects/$object/";

        $response = $this->request('POST', $url, $data);
        $status = $response->getStatusCode();

        if ($status != 201) {
            throw new \Exception("Error: call to URL $url failed with status $status, response: " . $response->getReasonPhrase());
        }

        $response = json_decode($response->getBody(), true);
        $id = $response["id"];

        return $id;

    }

    public function update($object, $id, array $data)
    {
        $url = $this->authentication->getInstanceUrl()."/services/data/v".$this->apiVersion."/sobjects/$object/$id";

        $request = $this->request('PATCH', $url, $data);

        $status = $request->getStatusCode();

        if ($status != 204) {
            throw new \Exception("Error: call to URL $url failed with status $status, response: " . $request->getReasonPhrase());
        }

        return true;
    }

    public function describeObjects()
    {
        $url = $this->authentication->getInstanceUrl()."/services/data/v".$this->apiVersion."/sobjects/";

        $response = $this->request('GET', $url);

        return json_decode($response->getBody(),true);
    }

    public function describe($object)
    {
        $url = $this->authentication->getInstanceUrl()."/services/data/v".$this->apiVersion."/sobjects/$object/describe/";

        $response = $this->request('GET', $url);

        return json_decode($response->getBody(),true);
    }

    /**
     * @param       $verb
     * @param       $url
     * @param array $params
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function request($verb, $url, $params = []) {

        $client = new GuzzleClient();
        return $client->request($verb, $url, [
            'headers' => [
                'Authorization' => "OAuth ".$this->authentication->getAccessToken(),
                'Content-Type' => 'application/json'
            ],
            'json' => $params
        ]);

    }


    public function delete($object, $id)
    {
        $url = $this->authentication->getInstanceUrl()."/services/data/v".$this->apiVersion."/sobjects/$object/$id";

        $response = $this->request('DELETE', $url);
        $status = $response->getStatusCode();

        if ($status != 204) {
            die("Error: call to URL $url failed with status $status, response: " . $request->getReasonPhrase());
        }

        return true;
    }


}
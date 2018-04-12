<?php

namespace Stratease\Salesforcery\Salesforce\Connection\REST;

use GuzzleHttp\Client as GuzzleClient;

use GuzzleHttp\Exception\ClientException;
use Stratease\Salesforcery\Salesforce\Connection\REST\Authentication\AuthenticationInterface;

class Client
{
    public $authentication;
    public $apiVersion;
    public function __construct(AuthenticationInterface $authentication, $apiVersion = '39.0')
    {
        $this->authentication = $authentication;
        $this->apiVersion = $apiVersion;
    }

    public function queryAll($query, array $headers = [])
    {
        $url = $this->authentication->getInstanceUrl()."/services/data/v".$this->apiVersion."/queryAll";
        $headers = array_merge($headers, ['Authorization' => "OAuth " . $this->authentication->getAccessToken()]);
        $client = new GuzzleClient();
        try {
            $request = $client->request('GET', $url, [
                'headers' => $headers,
                'query'   => [
                    'q' => $query
                ]
            ]);
        } catch (ClientException $e) {

            throw new \Exception($e->getResponse()->getBody()->getContents());
        }

        return json_decode($request->getBody(), true);
    }

    public function query($query, array $headers = [])
    {
        $url = $this->authentication->getInstanceUrl()."/services/data/v".$this->apiVersion."/query";
        $headers = array_merge($headers, ['Authorization' => "OAuth " . $this->authentication->getAccessToken()]);
        $client = new GuzzleClient();
        try {
            $request = $client->request('GET', $url, [
                'headers' => $headers,
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
     * @param array $headers
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function request($verb, $url, $params = [], $headers = []) {

        $client = new GuzzleClient();
        $headers = array_merge($headers, ['Authorization' => "OAuth ".$this->authentication->getAccessToken(),
                                          'Content-Type' => 'application/json']);
        return $client->request($verb, $url, [
            'headers' => $headers,
            'json' => $params
        ]);

    }


    public function delete($object, $id)
    {
        $url = $this->authentication->getInstanceUrl()."/services/data/v".$this->apiVersion."/sobjects/$object/$id";

        $response = $this->request('DELETE', $url);
        $status = $response->getStatusCode();

        if ($status != 204) {
            throw new \Exception("Error: call to URL $url failed with status $status, response: " . $response->getReasonPhrase());
        }

        return true;
    }


}
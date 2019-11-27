<?php

namespace OperatorHub\API;

use GuzzleHttp\Client;
use InvalidArgumentException;
use SilverStripe\Core\Injector\Factory;

class OperatorHubAPIFactory implements Factory
{
    /**
     * Creates a new service instance.
     *
     * @param string $service The class name of the service.
     * @param array  $params  The constructor parameters.
     * @return OperatorHubAPI
     */
    public function create($service, array $params = array())
    {
        // Config is first constructor argument
        $config = reset($params);

        // Validate all arguments are present
        if (!isset($config['URL'])) {
            throw new InvalidArgumentException("API URL is required");
        }
        if (!isset($config['Token'])) {
            throw new InvalidArgumentException("API Token is required");
        }

        // Build guzzle connector
        $client = new Client([
            'base_uri' => $config['URL'],
        ]);

        // Build API
        $api = new OperatorHubAPI();
        $api->setClient($client);
        $api->setToken($config['Token']);
        return $api;
    }
}

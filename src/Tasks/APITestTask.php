<?php

namespace OperatorHub\Tasks;

use BadMethodCallException;
use InvalidArgumentException;
use OperatorHub\API\OperatorHubEngine;
use OperatorHub\Model\OperatorHubBookingConfiguration;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\BuildTask;
use SilverStripe\View\HTML;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;

/**
 * Example call:
 *
 * dev/tasks/OHAPITestTask?id=3&method=order&args[orderid]=6081598
 */
class APITestTask extends BuildTask
{
    private static $segment = 'OHAPITestTask';

    protected $title = 'OperatorHub API Tester';

    protected $description = 'Test an API method. call with id, method, and args params';

    /**
     * Implement this method in the task subclass to
     * execute via the TaskRunner
     *
     * @param HTTPRequest $request
     * @return
     */
    public function run($request)
    {
        $id = $request->getVar('id');
        if (!$id) {
            throw new BadMethodCallException("Missing id query param");
        }

        $method = $request->getVar('method');
        if (!$method) {
            throw new BadMethodCallException("Missing method query param");
        }

        /** @var OperatorHubBookingConfiguration $config */
        $config = OperatorHubBookingConfiguration::get()->byID($id);
        if (!$config) {
            throw new InvalidArgumentException("No config with id {$id}");
        }

        /** @var OperatorHubEngine $engine */
        $engine = $config->getBookingEngine();
        $api = $engine->getApi();

        if (!method_exists($api, $method)) {
            throw new BadMethodCallException("No method {$method} exists");
        }

        $args = $this->getArgs($request);

        $result = call_user_func_array([$api, $method], $args);
        $output = HTML::createTag('pre', [], json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo $output;
    }

    /**
     * Get arguments
     *
     * @param HTTPRequest $request
     * @return array|mixed
     */
    protected function getArgs($request)
    {
        $args = $request->getVar('args');
        if (is_array($args)) {
            return $args;
        }
        if ($args) {
            return json_decode($args, true);
        }
        return [];
    }
}

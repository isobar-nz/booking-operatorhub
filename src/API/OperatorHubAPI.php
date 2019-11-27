<?php

namespace OperatorHub\API;

use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use OperatorHub\Model\JSON\JSONObject;
use OperatorHub\Model\JSON\JSONTrait;
use OperatorHub\Model\JSON\OrderObject;
use OperatorHub\Model\JSON\TimedEventObject;
use function GuzzleHttp\json_decode;

/**
 * Low level api calls for all operatorhub methods (v1)
 *
 * See https://github.com/Junction6/API/blob/V1/Docs/available-requests.md
 */
class OperatorHubAPI
{
    use JSONTrait;

    /**
     * Guzzle client
     *
     * @var Client
     */
    protected $client = null;

    const AVAILABILITY = 'availability';

    const CLEARORDER = 'clearorder';

    const ORDER = 'order';

    const ORDERADDITEM = 'orderadditem';

    const PRODUCTS = 'products';

    const NEWORDER = 'neworder';

    /**
     * Security token argument
     *
     * @var string
     */
    protected $token = null;

    /**
     * Get guzzle client
     *
     * @return Client|null
     */
    public function getClient(): ?Client
    {
        return $this->client;
    }

    /**
     * @param Client $client
     * @return $this
     */
    public function setClient(Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Get all products
     *
     * @link https://github.com/Junction6/API/blob/V1/Docs/available-requests.md#products
     * @param array $args
     * @return array
     */
    public function products($args = [])
    {
        // Whitelist API args
        $defaults = [
            'live'             => true,
            'token'            => $this->getToken(),
            'limit'            => 10,
            'productcode'      => null,
            'productclassname' => null,
        ];
        return $this->query(self::PRODUCTS, $defaults, $args);
    }

    /**
     * Get a single product
     *
     * @link https://github.com/Junction6/API/blob/V1/Docs/available-requests.md#products-1
     * @param int $productId
     * @return array
     */
    public function product(int $productId)
    {
        $query = [
            'token'     => $this->getToken(),
            'productid' => $productId,
        ];
        return $this->query(self::PRODUCTS, $query);
    }

    /**
     * Get a single order
     *
     * @param int $orderId E.g. 6081598
     * @return array
     */
    public function order(int $orderId)
    {
        $query = [
            'token'   => $this->getToken(),
            'orderid' => $orderId,
        ];
        return $this->query(self::ORDER, $query);
    }

    /**
     * Add a product to an order
     *
     * @param int   $orderId   ID of the order
     * @param int   $productId ID of the product
     * @param array $args      Additional arguments.
     * @return array
     */
    public function orderadditem(int $orderId, int $productId, $args = [])
    {
        // If variation is supplied, do not need productid
        $effectiveProductID = isset($args['productvariation']) ? null : $productId;
        $defaults = [
            'token'            => $this->getToken(),
            'orderid'          => $orderId,
            'productid'        => $effectiveProductID,
            'productvariation' => null,
            'quantity'         => 0,
            // Required for limited availability items
            'eventid'          => null,
            // Optional parameters for Gift Voucher items
            'firstname'        => null,
            'surname'          => null,
            'email'            => null,
            'giftmessage'      => null,
            'editprice'        => null,
            'description'      => null,
            'editexpirydate'   => null,
        ];

        // Validate name
        $requiresFirstName = !empty($args['email']) || !empty($args['surname']);
        if ($requiresFirstName && empty($args['firstname'])) {
            throw new InvalidArgumentException("firstname cannot be omitted if surname or email are set");
        }

        return $this->query(self::ORDERADDITEM, $defaults, $args);
    }

    /**
     * Clear items in an order
     *
     * @param int $orderId
     * @return array
     */
    public function clearorder(int $orderId)
    {
        $defaults = [
            'token'   => $this->getToken(),
            'orderid' => $orderId,
        ];
        return $this->query(self::CLEARORDER, $defaults);
    }

    /**
     * Create a new order
     *
     * @param array $args
     * @return OrderObject
     */
    public function neworder($args = [])
    {
        $defaults = [
            'token'      => $this->getToken(),
            'customerid' => null,
            'customer'   => [],
        ];
        /** @var OrderObject $order */
        $order = $this->query(self::NEWORDER, $defaults, $args);
        return $order;
    }

    /**
     * Get availability for a product between two dates
     *
     * @example dev/tasks/OHAPITestTask?id=3&method=availability&args[productid]=4788&args[date]=2019-11-30
     * @param int                  $productId
     * @param DateTime|string      $start Start date
     * @param DateTime|string|null $end   Optional end date (if after start date)
     * @return TimedEventObject[] List of availabilities
     */
    public function availability(int $productId, $start, $end = null)
    {
        // Normalise args
        if (is_string($start)) {
            $start = new DateTime($start);
        }
        if (is_string($end)) {
            $end = new DateTime($end);
        }

        $args = [
            'token'     => $this->getToken(),
            'productid' => $productId,
        ];
        if ($end) {
            $args['startdate'] = $start->format('Y-m-d');
            $args['enddate'] = $end->format('Y-m-d');
        } else {
            $args['date'] = $start->format('Y-m-d');
        }
        return $this->query(self::AVAILABILITY, $args);
    }

    /**
     * Query a method with a set of given arguments
     *
     * @param string $method
     * @param array  $baseArgs Default allowed arguments
     * @param array  $userArgs Query args provided by user
     * @return array|JSONObject|JSONObject[] Array, or json encoded type
     */
    protected function query($method, $baseArgs, $userArgs = [])
    {
        $unknownArguments = array_diff_key($userArgs, $baseArgs);
        if ($unknownArguments) {
            $unknownArgumentsStr = implode(', ', array_keys($unknownArguments));
            throw new InvalidArgumentException("Unknown arguments: {$unknownArgumentsStr}");
        }

        // Build query
        $query = array_filter(array_merge($baseArgs, $userArgs), function ($arg) {
            return isset($arg);
        });
        $options = array_filter([
            RequestOptions::QUERY => $query,
        ]);
        $result = $this->getClient()->get($method, $options);
        $json = json_decode($result->getBody()->getContents(), true);

        // Cast to type
        return $this->typify($json['response']);
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return $this
     */
    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }
}

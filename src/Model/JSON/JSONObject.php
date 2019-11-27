<?php

namespace OperatorHub\Model\JSON;

use SilverStripe\View\ViewableData;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;

/**
 * Represents an OperatorHub object serialised as JSON
 *
 * @property int    $ID
 * @property string $APIObjectType
 * @property string $Created
 * @property string $LastEdited
 */
abstract class JSONObject extends ViewableData
{
    use JSONTrait;

    /**
     * JSON Blob representing this object
     *
     * @var array
     */
    protected $json = [];

    public function __get($name)
    {
        // Get from json blob
        if (array_key_exists($name, $this->json)) {
            return $this->typify($this->json[$name]);
        }

        return parent::__get($name);
    }

    public function __isset($property)
    {
        return parent::__isset($property) || array_key_exists($property, $this->json);
    }

    /**
     * Create object with array data
     *
     * @param string|array $json
     */
    public function __construct($json)
    {
        // Normalise json to array
        if (is_string($json)) {
            $json = json_decode($json, true);
        }
        $this->json = $json;
        parent::__construct();
    }

    /**
     * @return array
     */
    public function getJson(): array
    {
        return $this->json;
    }

    /**
     * @param array $json
     * @return $this
     */
    public function setJson(array $json): self
    {
        $this->json = $json;
        return $this;
    }

    /**
     * Return json blob
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->getJson(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}

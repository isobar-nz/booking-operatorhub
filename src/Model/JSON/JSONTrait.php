<?php

namespace OperatorHub\Model\JSON;

trait JSONTrait
{
    /**
     * Map an object to a real type
     *
     * @param array|null $value
     * @return array|JSONObject
     */
    protected function typify($value)
    {
        // Handle empty values
        if (empty($value)) {
            return $value;
        }

        // Single typed items
        $type = $value['APIObjectType'] ?? null;
        if ($type) {
            $typeClass = __NAMESPACE__ . '\\' . $type;
            if (class_exists($typeClass)) {
                return new $typeClass($value);
            }
        }

        // List of typed items
        if (is_array($value) && isset($value[0]['APIObjectType'])) {
            $items = [];
            foreach ($value as $item) {
                $items[] = $this->typify($item);
            }
            return $items;
        }

        return $value;
    }
}

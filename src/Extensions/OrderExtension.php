<?php

namespace OperatorHub\Extensions;

use App\Booking\Model\Order;
use OperatorHub\Model\JSON\OrderObject;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\HTML;

/**
 * @property Order|OrderExtension $owner
 * @property string               $OperatorHubOrder
 */
class OrderExtension extends DataExtension
{
    private static $db = [
        'OperatorHubOrder' => 'Text',
    ];

    /**
     * Get typed order object
     *
     * @return OrderObject
     */
    public function getOperatorHubOrderObject()
    {
        if ($this->owner->OperatorHubOrder) {
            return new OrderObject($this->owner->OperatorHubOrder);
        }
        return null;
    }

    /**
     * Set order data
     *
     * @param OrderObject $object
     * @return Order
     */
    public function setOperatorHubOrderObject(OrderObject $object)
    {
        $this->owner->OperatorHubOrder = $object->__toString();
        return $this->owner;
    }


    public function updateCMSFields(FieldList $fields)
    {
        if ($this->owner->OperatorHubOrder) {
            $fields->addFieldsToTab('Root.OperatorHub', [
                LiteralField::create('OperatorHubOrderDisplay', 'Operator Hub Order')
                    ->setReadonly(true)
                    ->setValue(HTML::createTag('pre', [], $this->owner->OperatorHubOrder)),
            ]);
        }
    }
}

<?php

namespace OperatorHub\Extensions;

use App\Booking\Model\UsesBookingEngine;
use OperatorHub\API\OperatorHubEngine;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataExtension;

/**
 * @property int $OperatorHubID
 */
class ActivityExtension extends DataExtension
{
    use UsesBookingEngine;

    private static $db = [
        'OperatorHubID' => 'Int',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        if (!$this->isEnabled()) {
            return;
        }

        $fields->addFieldsToTab('Root.OperatorHub', [
            NumericField::create('OperatorHubID', 'Operator Hub Product ID')
                ->setHTML5(true)
        ]);
    }

    public function updateSummaryFields(&$fields)
    {
        if (!$this->isEnabled()) {
            return;
        }

        $fields['OperatorHubID'] = 'Operator Hub Product ID';
    }

    /**
     * @return bool
     */
    protected function isEnabled(): bool
    {
        return $this->getBookingEngine() instanceof OperatorHubEngine;
    }
}

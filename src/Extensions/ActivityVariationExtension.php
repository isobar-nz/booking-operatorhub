<?php

namespace OperatorHub\Extensions;

use App\Booking\Model\UsesBookingEngine;
use App\Products\Model\ActivityVariation;
use OperatorHub\API\OperatorHubEngine;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataExtension;

/**
 * @property int                                          $OperatorHubID
 * @property ActivityVariation|ActivityVariationExtension $owner
 */
class ActivityVariationExtension extends DataExtension
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
            NumericField::create('OperatorHubID', 'Operator Hub Variation ID')
                ->setHTML5(true)
        ]);
    }

    public function updateSummaryFields(&$fields)
    {
        if (!$this->isEnabled()) {
            return;
        }

        $fields['OperatorHubID'] = 'Operator Variation Hub ID';
    }

    /**
     * @return bool
     */
    protected function isEnabled(): bool
    {
        return $this->getBookingEngine() instanceof OperatorHubEngine;
    }

    public function updateInlineEditableFields(&$fields)
    {
        if (!$this->isEnabled()) {
            return;
        }

        $fields['OperatorHubID'] = [
            'title'    => 'Operator Hub Variation ID',
            'callback' => function () {
                return NumericField::create('OperatorHubID')
                    ->setHTML5(true);
            }
        ];
    }
}

<?php

namespace OperatorHub\Model\JSON;

use App\BookingEngine\API\ActivityAvailability;
use App\Products\Model\Activity;
use DateTime;

/**
 * @property string $Date
 * @property string $Start
 * @property string $End
 * @property int    $Quantity
 * @property int    $Remaining
 * @property bool   $CanBook
 * @property bool   $IsPastCutOff
 * @property bool   $Unlimited
 * @property string $Description
 * @property string $ManifestStatus
 */
class EventObject extends JSONObject implements ActivityAvailability
{
    /**
     * @var Activity
     */
    protected $activity = null;

    public function getActivity()
    {
        return $this->activity;
    }

    public function setActivity(Activity $activity): self
    {
        $this->activity = $activity;
        return $this;
    }

    public function getAvailabilitySlots()
    {
        return $this->Remaining;
    }

    public function getAvailability()
    {
        // Unlimited availability
        if ($this->Unlimited) {
            return ActivityAvailability::FREE;
        }

        if (empty($this->Remaining)) {
            return ActivityAvailability::FULL;
        }

        // If < 20% it's busy
        if ($this->Remaining * 5 < $this->Quantity) {
            return ActivityAvailability::BUSY;
        }

        return ActivityAvailability::FREE;
    }

    public function getDateTime()
    {
        return new DateTime("{$this->Date} {$this->Start}");
    }

    public function getDuration()
    {
        $start = $this->getDateTime();
        $end = new DateTime("{$this->Date} {$this->End}");
        return $start->diff($end);
    }

    /**
     * Get ID for this availability. note: Can be ID or string
     *
     * @return string
     */
    public function getID()
    {
        return $this->ID;
    }
}

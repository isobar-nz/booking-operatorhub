<?php

namespace OperatorHub\API;

use App\Booking\Model\DBOrderStatus;
use App\Booking\Model\Order;
use App\Booking\Model\OrderActivityVariation;
use App\BookingEngine\API\ActivityAvailability;
use App\BookingEngine\API\BookingEngine;
use App\BookingEngine\Model\BookingConfiguration;
use App\PaymentEngine\API\PaymentGateway;
use App\Products\Model\Activity;
use App\Products\Model\ActivityVariation;
use BadMethodCallException;
use DateTime;
use Exception;
use InvalidArgumentException;
use OperatorHub\Extensions\ActivityExtension;
use OperatorHub\Extensions\ActivityVariationExtension;
use OperatorHub\Extensions\OrderExtension;
use OperatorHub\Model\JSON\OrderObject;
use OperatorHub\Model\JSON\TimedEventObject;
use OperatorHub\Model\OperatorHubBookingConfiguration;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ValidationException;

class OperatorHubEngine implements BookingEngine
{
    use Injectable;

    /**
     * @var OperatorHubBookingConfiguration
     */
    protected $configuration = null;

    /**
     * Low level API helper
     *
     * @var OperatorHubAPI
     */
    protected $api = null;

    /**
     * Check that an order can be validated. If
     * this returns true, then confirmOrder() can be called.
     *
     * Is allowed to send the booking to the engine, so long as it's
     * left in an incomplete state.
     *
     * @param Order|OrderExtension $order
     * @return bool Status of true / false if the booking can be completed.s
     * @throws ValidationException
     * @throws Exception
     */
    public function createOrder(Order $order)
    {
        // Ensure order can be checked out
        if (!$order->canCheckout()) {
            throw new BadMethodCallException('Order cannot be checked out');
        }

        // validate that order is configured
        $this->validateOrder($order);

        // Create order
        $orderObject = $order->getOperatorHubOrderObject();
        if (!$orderObject) {
            $orderObject = $this->getApi()->neworder();
        }

        // Update items to upstream order
        if ($orderObject->Items) {
            $this->getApi()->clearorder($orderObject->ID);
        }

        // Loop each product, add each variation
        foreach ($order->OrderActivities() as $orderActivity) {
            foreach ($orderActivity->VariationsWithQuantity() as $orderVariation) {
                $orderObject = $this->addVariationToOrder($orderVariation, $orderObject);
            }
        }

        // Ensure status is processing
        $order->setOperatorHubOrderObject($orderObject);
        $order->Status = DBOrderStatus::PROCESSING;
        $order->write();
        return true;
    }

    /**
     * Send booking to engine, creating the booking.
     *
     * This method should be called after validateOrder() is called,
     * and the booking has been paid for.
     *
     * @param Order $booking
     * @throws ValidationException
     */
    public function completeOrder(Order $booking)
    {
        // @todo - Implement
        $booking->Status = DBOrderStatus::PAID;
        $booking->write();
    }

    /**
     * Check availability for a product between a date range
     *
     * @param Activity|ActivityExtension $activity
     * @param DateTime                   $startDate
     * @param DateTime                   $endDate
     * @return ActivityAvailability[]
     */
    public function checkAvailability(Activity $activity, DateTime $startDate, DateTime $endDate)
    {
        $items = $this->getApi()->availability($activity->OperatorHubID, $startDate, $endDate);

        // Save activity on each
        foreach ($items as $item) {
            $item->setActivity($activity);
        }

        return $items;
    }

    /**
     * Title for this engine
     *
     * @return string
     */
    public function getTitle()
    {
        return 'Dummy booking engine';
    }

    /**
     * Description for this engine
     *
     * @return string
     */
    public function getDescription()
    {
        return 'This booking engine is used for testing and does not handle any bookings';
    }

    /**
     * @param BookingConfiguration $configuration
     * @return $this
     */
    public function setConfiguration(BookingConfiguration $configuration)
    {
        if (!$configuration instanceof OperatorHubBookingConfiguration) {
            throw new InvalidArgumentException("Invalid configuration");
        }

        $this->configuration = $configuration;
        return $this;
    }

    /**
     * Configuration for this booking engine
     *
     * @return OperatorHubBookingConfiguration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Get all configured gateways
     *
     * @return PaymentGateway[]
     */
    public function getPaymentGateways()
    {
        $gateways = [];
        foreach ($this->getConfiguration()->PaymentGateways() as $gateway) {
            $gateways[] = $gateway->getPaymentGateway();
        }
        return $gateways;
    }

    /**
     * @return OperatorHubAPI
     */
    public function getApi(): OperatorHubAPI
    {
        // Return saved API
        if ($this->api) {
            return $this->api;
        }

        // Check config
        $configuration = $this->getConfiguration();
        if (!$configuration) {
            throw new BadMethodCallException("No configuration available");
        }

        // Make new API
        $api = Injector::inst()->create(OperatorHubAPI::class, [
            'URL'   => $configuration->URL,
            'Token' => $configuration->Token,
        ]);

        // Save and return
        $this->setApi($api);
        return $api;
    }

    /**
     * @param OperatorHubAPI $api
     * @return $this
     */
    public function setApi(OperatorHubAPI $api): self
    {
        $this->api = $api;
        return $this;
    }

    /**
     * Check that every item in this order has a valid J6 ID
     *
     * @param Order $order
     */
    protected function validateOrder(Order $order)
    {
    }

    /**
     * Add a variation to the given order
     *
     * @param OrderActivityVariation $orderVariation
     * @param OrderObject            $orderObject
     * @return OrderObject
     * @throws Exception
     */
    protected function addVariationToOrder(OrderActivityVariation $orderVariation, OrderObject $orderObject)
    {
        $orderActivity = $orderVariation->Activity();

        // Get EventID
        $timeSlot = new DateTime($orderActivity->TimeSlot);

        /** @var Activity|ActivityExtension $activity */
        $activity = $orderActivity->Activity();

        // Get events for this day
        $event = $this->findActivityEvent($activity, $timeSlot);

        /** @var ActivityVariation|ActivityVariationExtension $variation */
        $variation = $orderVariation->Variation();

        /** @var OrderObject $updatedOrderObject */
        $updatedOrderObject = $this->getApi()->orderadditem(
            $orderObject->ID,
            $activity->OperatorHubID,
            [
                'productvariation' => $variation->OperatorHubID,
                'eventid'          => $event->ID,
                'quantity'         => $orderVariation->Quantity,
            ]
        );
        return $updatedOrderObject;
    }

    /**
     * Find event for an activity and given timeslot
     *
     * @todo cache these
     *
     * @param Activity|ActivityExtension $activity
     * @param DateTime                   $timeSlot
     * @throws Exception
     * @return TimedEventObject
     */
    protected function findActivityEvent($activity, DateTime $timeSlot): TimedEventObject
    {
        $events = $this->getApi()->availability($activity->OperatorHubID, $timeSlot);
        foreach ($events as $event) {
            if ($event->getDateTime() == $timeSlot) {
                return $event;
            }
        }
        throw new Exception("Could not find event with chosen timeslot");
    }
}

<?php
namespace HrxApi;

use HrxApi\Helper;
use HrxApi\Receiver;
use HrxApi\Shipment;

class Order
{
    /* Class variables */
    private $pickup_location_id;
    private $delivery_location_id;
    private $receiver;
    private $shipment;

    /**
     * Constructor
     * @since 1.0.0
     */
    public function __construct()
    {

    }

    /**
     * Set pickup location ID
     * @since 1.0.0
     * 
     * @param (string) $pickup_location_id - Pickup location ID
     * @return (object) - Edited this class object
     */
    public function setPickupLocationId( $pickup_location_id )
    {
        $this->pickup_location_id = $pickup_location_id;

        return $this;
    }

    /**
     * Set delivery location ID
     * @since 1.0.0
     * 
     * @param (string) $delivery_location_id - Delivery location ID
     * @return (object) - Edited this class object
     */
    public function setDeliveryLocation( $delivery_location_id )
    {
        $this->delivery_location_id = $delivery_location_id;

        return $this;
    }

    /**
     * Set receiver
     * @since 1.0.0
     * 
     * @param (object) $receiver - Receiver
     * @return (object) - Edited this class object
     */
    public function setReceiver( Receiver $receiver )
    {
        $this->receiver = $receiver;

        return $this;
    }

    /**
     * Set shipment
     * @since 1.0.0
     * 
     * @param (object) $shipment - Shipment
     * @return (object) - Edited this class object
     */
    public function setShipment( Shipment $shipment )
    {
        $this->shipment = $shipment;

        return $this;
    }

    /**
     * Check and prepare data for new Order
     * @since 1.0.0
     * 
     * @return (array) - Organized data of new Order
     */
    public function prepareOrderData()
    {
        if ( ! $this->pickup_location_id ) $this->errorMissingField('pickup_location_id');
        if ( ! $this->delivery_location_id ) $this->errorMissingField('delivery_location_id');
        if ( ! $this->receiver ) $this->errorMissingField('receiver');
        if ( ! $this->shipment ) $this->errorMissingField('shipment');

        $order_data = array(
           'sender_reference' => $this->shipment->getReference(),
           'sender_comment' => $this->shipment->getComment(),
           'pickup_location_id' => $this->pickup_location_id,
           'delivery_location_id' => $this->delivery_location_id,
           'length_cm' => $this->shipment->getLength(),
           'width_cm' => $this->shipment->getWidth(),
           'height_cm' => $this->shipment->getHeight(),
           'weight_kg' => $this->shipment->getWeight(),
           'recipient_name' => $this->receiver->getName(),
           'recipient_email' => $this->receiver->getEmail(),
           'recipient_phone' => $this->receiver->getPhone(),
        );

        return $order_data;
    }

    /**
     * Throw exception with message for specific element
     * @since 1.0.0
     */
    private function errorMissingField( $field_name )
    {
        return Helper::throwError('All the fields must be filled. ' . $field_name . ' is missing.');
    }
}

# HRX-API library

Its a library for HRX WOP API.

## Using HRX-API library

- `__PATH_TO_LIB__` is path to where HRX-API is placed. This will load HrxApi namespace
```php
require __PATH_TO_LIB__ . 'hrx-api/vendor/autoload.php';
```

Validations, checks, etc. throws Exception and calls to library classes should be wrapped in: blocks 
```php
try {
  // ...
} catch (Exception $e) {
  // ...
}
```

Any function starting with `add` or `set` returns its class so functions can be chained.

## Authentication

Uses supplied user `$token`. It is called during API object creation.
- Initialize new API library using: `$api = new API($token);`

## Getting locations

```php
$pickup_locations = $api->getPickupLocations(1, 100); // Get pickup locations. First param - page number, second param - elements per page
$delivery_locations = $api->getDeliveryLocations(1, 100); // Get delivery locations. First param - page number, second param - elements per page
```

## Creating Receiver

`use HrxApi\Receiver;` will allow creating Receiver object.

Minimum required setup:
```php
use HrxApi\Receiver;

$receiver = new Receiver();

$receiver
  ->setName('Tester') // Receiver name
  ->setEmail('test@test.ts') // Receiver email
  ->setPhone('58000000', "^6[0-9]{7}$"); // Phone number without code and a second parameter is for check the phone value according to the regex specified in delivery location information
```

## Creating shipment

`use HrxApi\Shipment;` will allow creating Shipment object.

Minimum required setup:
```php
use HrxApi\Shipment;

$shipment = new Shipment();

$shipment
  ->setReference('REF001') // Package ID or other identifier. Optional.
  ->setComment('Comment') // Comment for shipment. Optional.
  ->setLength(15) // Dimensions values in cm. Must be between the min and max values specified for the delivery location. If min or max value in delivery location is null, then value not have min/max limit
  ->setWidth(15)
  ->setHeight(15)
  ->setWeight(1); // kg
```

## Creating order

`use HrxApi\Order;` will allow creating Order object.

Minimum required setup:
```php
use HrxApi\Order;

$order = new Order();

$order
  ->setPickupLocationId('bcaac6c5-3a69-44e1-9e29-809b8150c997') // Pickup location ID retrieved from the API
  ->setDeliveryLocation('14fce476-f610-4ff8-a81e-9f6c653ac116') // Delivery location ID retrieved from the API
  ->setReceiver($receiver) // Receiver object
  ->setShipment($shipment); // Shipment object

$order_data = $order->prepareOrderData(); // Organized and prepared data for sending to API
```

## Generating order

All process syntax:
```php
use HrxApi\API;
use HrxApi\Receiver;
use HrxApi\Shipment;
use HrxApi\Order;

$api = new API($token);

$pickup_locations = $api->getPickupLocations(1, 10);
$delivery_locations = $api->getDeliveryLocations(1, 10);

$receiver = new Receiver();

$receiver
  ->setName('Tester')
  ->setEmail('test@test.ts')
  ->setPhone('58000000', $delivery_locations[0]['recipient_phone_regexp']);

$shipment = new Shipment();

$shipment
  ->setReference('PACK-12345')
  ->setComment('Comment')
  ->setLength(15) // cm
  ->setWidth(15) // cm
  ->setHeight(15) // cm
  ->setWeight(1); // kg

$order = new Order();

$order
  ->setPickupLocationId($pickup_locations[0]['id'])
  ->setDeliveryLocation($delivery_locations[0]['id'])
  ->setReceiver($receiver)
  ->setShipment($shipment)
$order_data = $order->prepareOrderData();

$order_response = $api->generateOrder($order_data); // Data sending to the API for shipment generation
```

## Getting all orders from API

```php
$orders_list = $api->getOrders(1, 100); // Get orders. First param - page number, second param - elements per page
```

## Getting single order data from API

Get order:
```php
$order = $api->getOrder('e161c889-782b-4ba2-a691-13dc4baf7b62'); // Order ID
```

Get label (when the tracking number is successfully generated):
```php
$label = $api->getLabel('e161c889-782b-4ba2-a691-13dc4baf7b62'); // Order ID
```

Get return label:
```php
$return_label = $api->getReturnLabel('e161c889-782b-4ba2-a691-13dc4baf7b62'); // Order ID
```

Get tracking events:
```php
$tracking_events = $api->getTrackingEvents('e161c889-782b-4ba2-a691-13dc4baf7b62'); // Order ID
```

## Getting public tracking information

- The tracking number is indicated in the order data received from the API, if the order was registered without errors
```php
$tracking_information = $api->getTrackingInformation('TRK0099999999'); // Tracking number
```

## Cancel order

```php
$canceled_order = $api->cancelOrder('e161c889-782b-4ba2-a691-13dc4baf7b62'); // Order ID
```

## Examples

Check **src/examples/** for this API library examples. The file **index.php** shows all the functions.

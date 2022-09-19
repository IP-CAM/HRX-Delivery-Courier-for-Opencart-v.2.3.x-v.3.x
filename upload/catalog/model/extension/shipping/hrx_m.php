<?php

use Mijora\DVDoug\BoxPacker\ItemList;
use Mijora\DVDoug\BoxPacker\VolumePacker;
use Mijora\HrxOpencart\Helper;
use Mijora\HrxOpencart\Model\DeliveryCourier;
use Mijora\HrxOpencart\Model\DeliveryPoint;
use Mijora\HrxOpencart\Model\ParcelBox;
use Mijora\HrxOpencart\Model\ParcelDefault;
use Mijora\HrxOpencart\Model\ParcelItem;
use Mijora\HrxOpencart\Model\ParcelProduct;
use Mijora\HrxOpencart\Model\Price;
use Mijora\HrxOpencart\Params;

require_once(DIR_SYSTEM . 'library/hrx_m/vendor/autoload.php');

class ModelExtensionShippingHrxM extends Model
{
    public function getQuote($address)
    {
        $this->load->language('extension/shipping/hrx_m');

        $setting_prefix = '';
        if (version_compare(VERSION, '3.0.0', '>=')) {
            $setting_prefix = 'shipping_';
        }

        // general geozone
        if ($this->config->get(Params::PREFIX . 'geo_zone_id')) {
            $query = $this->db->query("
                SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone 
                WHERE geo_zone_id = '" . (int) $this->config->get(Params::PREFIX . 'geo_zone_id') . "' 
                    AND country_id = '" . (int) $address['country_id'] . "' 
                    AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')
            ");

            if (!$query->num_rows) {
                return [];
            }
        }

        $receiver_country_code = $address['iso_code_2'];

        // check if there is prices set for this country
        $priceObj = Price::getPrice($this->db, $receiver_country_code);

        /** @var ParcelProduct[] */
        $product_dimensions = ParcelDefault::getProductDimmensions($this->cart->getProducts(), $this->db, $this->weight, $this->length);

        $item_list = new ItemList();

        foreach ($product_dimensions as $key => $dimensions) {
            $item = new ParcelItem(
                $dimensions->length,
                $dimensions->width,
                $dimensions->height,
                $dimensions->weight,
                'product_' . $key
            );
            $item_list->insert($item, $dimensions->quantity);
        }

        $courier_quote = $this->getCourierQuote($priceObj, $receiver_country_code, $item_list);
        $terminal_quote = $this->getTerminalQuote($priceObj, $receiver_country_code, $item_list);

        $internal_sort_order = (int) $this->config->get(Params::PREFIX . 'sort_order_internal');

        $quotes_data = $internal_sort_order === Params::SORT_ORDER_INTERNAL_COURIER_TERMINAL ?
            array_merge($courier_quote, $terminal_quote) :
            array_merge($terminal_quote, $courier_quote);

        // if neither courier nor terminal options available return empty array
        if (empty($quotes_data)) {
            return [];
        }

        $method_data = [
            'code'       => Params::SETTINGS_CODE,
            'title'      => $this->language->get('text_title'),
            'quote'      => $quotes_data,
            'sort_order' => $this->config->get($setting_prefix . Params::PREFIX . 'sort_order'),
            'error'      => false
        ];

        return $method_data;
    }

    protected function getCourierQuote(Price $price, $receiver_country_code, ItemList $item_list)
    {
        $price_value = $price->getCourierPriceValue();

        // price value must exist
        if (!$price_value) {
            return [];
        }

        $delivery_courier = DeliveryCourier::getDeliveryLocationByCountryCode($receiver_country_code, $this->db);

        // if no country or its not active - disable option
        if (!$delivery_courier->country || !$delivery_courier->active) {
            return [];
        }

        $max_courier_weight = $delivery_courier->getMaxWeight();

        if ($max_courier_weight !== 0.0 && $max_courier_weight < $this->getCartWeightInKg()) {
            return [];
        }

        // make sure parcel fits into limitations
        if (!Helper::doesParcelFitBox($delivery_courier, $item_list)) {
            return [];
        }

        // calculate courier option cost
        $cost = $this->calculateCost($price_value, $price->getCourierRangeTypeValue());

        // cost cant be lower than 0
        if ($cost < 0) {
            return [];
        }

        $tax_class_id = $this->config->get(Params::PREFIX . 'tax_class_id');

        return [
            'courier' => [
                'code'         => Params::SETTINGS_CODE . '.courier',
                'title'        => $this->language->get('hrx_m_quote_title_courier'),
                'cost'         => $cost,
                'tax_class_id' => $tax_class_id,
                'text'         => $this->currency->format(
                    $this->tax->calculate(
                        $cost,
                        $tax_class_id,
                        $this->config->get('config_tax')
                    ),
                    $this->session->data['currency']
                )
            ]
        ];
    }

    protected function getTerminalQuote(Price $price, $receiver_country_code, ItemList $item_list)
    {
        $price_value = $price->getPriceValue();

        if (!$price_value) {
            return [];
        }

        $cost = $this->calculateCost($price_value, $price->getRangeTypeValue());
        if ($cost < 0) {
            return [];
        }

        // get shipping options with just receiver country data
        /** @var DeliveryPoint[] */
        $delivery_points = DeliveryPoint::getDeliveryPointsByCountryCode($this->db, $receiver_country_code);

        if (empty($delivery_points)) {
            return [];
        }

        // Add shipping options
        $tax_class_id = $this->config->get(Params::PREFIX . 'tax_class_id');

        $cart_weight = $this->getCartWeightInKg();

        $quote_data = [];

        $prefix = $this->language->get('hrx_m_quote_terminal_title_prefix');

        $can_fit = []; // cache for checked dimensions

        foreach ($delivery_points as $delivery_point) {
            $location_max_weight = $delivery_point->getMaxWeight();

            // skip locations which max weight is les than current cart weight
            if ($location_max_weight !== 0 && $location_max_weight < $cart_weight) {
                continue;
            }

            // $dimensions_array = $delivery_point->getMaxDimensions(false);
            $box_key = $delivery_point->getMaxDimensions() . ' ' . $location_max_weight; // to cache result for this kind of dimension box

            if (!isset($can_fit[$box_key])) {
                $can_fit[$box_key] = Helper::doesParcelFitBox($delivery_point, $item_list);
            }

            if ($can_fit[$box_key] === false) {
                continue;
            }

            $key = 'terminal_' . $delivery_point->id;

            $quote_data[$key] = array(
                'code'         => Params::SETTINGS_CODE . '.' . $key,
                'title'        => $prefix . $delivery_point->address,
                'cost'         => $cost,
                'tax_class_id' => $tax_class_id,
                'text'         => $this->currency->format(
                    $this->tax->calculate(
                        $cost,
                        $tax_class_id,
                        $this->config->get('config_tax')
                    ),
                    $this->session->data['currency']
                )
            );
        }

        return $quote_data;
    }

    protected function getCartWeightInKg()
    {
        // Get cart weight
        $total_weight = $this->cart->getWeight();
        $kg_weight_class_id = (int) Helper::getWeightClassId($this->db);
        // Make sure its in kg (we do not support imperial units, so assume weight is in metric units)
        $weight_class_id = (int) $this->config->get('config_weight_class_id');

        // already in kg
        if ($kg_weight_class_id === $weight_class_id) {
            return (float) $total_weight;
        }

        // weight classes different need to convert into kg
        return (float) $this->weight->convert($total_weight, $weight_class_id, $kg_weight_class_id);
    }

    /**
     * Determines if cost setting has weight:price formating and extracts cost by cart weight. 
     * In case of incorrect formating will return -1.
     * If no format identifier (:) found in string will return original $cost_ranges.
     * 
     * @param string|float $cost_ranges price setting, can be in weight:price range formating (string)
     * 
     * @return string|float Extracted cost from format according to cart weight.
     */
    protected function getCostByWeight($cost_ranges, $cart_weight)
    {
        $cost = -1;
        $ranges = explode(';', $cost_ranges);
        if (!is_array($ranges)) {
            return $cost;
        }

        foreach ($ranges as $range) {
            $weight_cost = explode(':', trim($range));
            // check it is valid weight cost pair, skip otherwise
            if (!is_array($weight_cost) || count($weight_cost) != 2) {
                continue;
            }

            // if cart weight is higher than set weight use this ranges cost
            // formating is assumed to go from lowest to highest weight
            // and cost will be the last lower or equal to cart weight
            if ((float) trim($weight_cost[0]) <= $cart_weight) {
                $cost = (float) trim($weight_cost[1]);
            }
        }

        return $cost;
    }

    protected function getCostByCartTotal($cost_ranges)
    {
        $cost = -1;
        $ranges = explode(';', $cost_ranges);
        if (!is_array($ranges)) {
            return $cost;
        }

        $cart_price = $this->cart->getSubTotal();
        $cart_price = $this->currency->format($cart_price, $this->session->data['currency'], false, false);

        foreach ($ranges as $range) {
            $cart_cost = explode(':', trim($range));
            // check it is valid weight cost pair, skip otherwise
            if (!is_array($cart_cost) || count($cart_cost) != 2) {
                continue;
            }

            // if cart price is higher than set price use this range cost
            // formating is assumed to go from lowest to highest cart price
            // and cost will be the last lower or equal to cart price
            if ((float) trim($cart_cost[0]) <= $cart_price) {
                $cost = (float) trim($cart_cost[1]);
            }
        }

        return $cost;
    }

    protected function calculateCost($cost, $range_type, $shipping_type = null)
    {
        // $shipping_type currently not used
        // empty values assumed as disabled
        if ($cost === '') {
            return -1;
        }

        // Check if $cost_ranges is in cart_total:price ; cart_total:price format
        if (!Price::isPriceRangeFormat($cost)) {
            return $cost; // not formated return as is
        }

        if ($range_type === Price::RANGE_TYPE_WEIGHT) {
            $cart_weight = $this->getCartWeightInKg();
            $cost = $this->getCostByWeight($cost, $cart_weight);
        }

        if ($range_type === Price::RANGE_TYPE_CART_PRICE) {
            $cost = $this->getCostByCartTotal($cost);
        }

        return $cost;
    }

    public function getFrontData()
    {
        $this->load->language('extension/shipping/' . Params::SETTINGS_CODE);

        $js_keys = [
            'modal_header',
            'terminal_list_header',
            'seach_header',
            'search_btn',
            'modal_open_btn',
            'geolocation_btn',
            'your_position',
            'nothing_found',
            'no_cities_found',
            'geolocation_not_supported',
            'search_placeholder',
            'workhours_header',
            'contacts_header',
            'select_pickup_point',
            'no_pickup_points',
            'select_btn',
            'back_to_list_btn',
            'no_information',
            'no_terminal_selected',
            'shipping_method_terminal'
        ];

        foreach ($js_keys as $key) {
            $data[$key] = $this->language->get(Params::PREFIX . 'js_' . $key);
        }

        return $data;
    }
}

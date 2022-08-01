<?php

namespace Mijora\HrxOpencart\Model;

use JsonSerializable;
use Mijora\HrxOpencart\OpenCart\DbTables;
use Mijora\HrxOpencart\Params;

class DeliveryPoint implements JsonSerializable
{
    const FIELD_PARAMS = 'params';
    const FIELD_ACTIVE = 'active';

    public $id;
    public $country;
    public $city;
    public $zip;
    public $address;
    // public $max_length_cm;
    // public $max_width_cm;
    // public $max_height_cm;
    // public $max_weight_kg;
    // public $min_length_cm;
    // public $min_width_cm;
    // public $min_height_cm;
    // public $min_weight_kg;
    // public $recipient_phone_prefix;
    // public $recipient_phone_regexp;
    public $latitude;
    public $longitude;

    // custom flag to mark if terminal is active in api
    public $active = false;

    public $params = []; // rest of parameters

    // maps api field to class attribute, fields not in this list will be stored in $params attribute
    private $api_to_class_attr = [
        "id" => 'id',
        "country" => 'country',
        "city" => 'city',
        "zip" => 'zip',
        "address" => 'address',
        // "max_length_cm" => 'max_length_cm',
        // "max_width_cm" => 'max_width_cm',
        // "max_height_cm" => 'max_height_cm',
        // "max_weight_kg" => 'max_weight_kg',
        // "min_length_cm" => 'min_length_cm',
        // "min_width_cm" => 'min_width_cm',
        // "min_height_cm" => 'min_height_cm',
        // "min_weight_kg" => 'min_weight_kg',
        // "recipient_phone_prefix" => 'recipient_phone_prefix',
        // "recipient_phone_regexp" => 'recipient_phone_regexp',
        "latitude" => 'latitude',
        "longitude" => 'longitude'
    ];

    public function __construct($data_array = null, $from_api = true)
    {
        $this->parseDataArray($data_array, $from_api);
    }

    public function parseDataArray($data_array, $from_api = true)
    {
        if (!is_array($data_array)) {
            return $this;
        }

        foreach ($data_array as $field => $value) {
            // check if data is from DB and in that case params field is handled differently
            if (!$from_api && $field === self::FIELD_PARAMS) {
                $this->params = array_merge($this->params, json_decode($value, true));
                continue;
            }

            if (!$from_api && $field === self::FIELD_ACTIVE) {
                $this->active = (bool) $value;
                continue;
            }

            if (!isset($this->api_to_class_attr[$field])) {
                $this->params[$field] = $value;
                continue;
            }

            $this->{$this->api_to_class_attr[$field]} = $value;
        }

        // if data from api set terminal as active
        if ($from_api) {
            $this->active = true;
        }

        // foreach ($this->api_fields as $api_field => $class_field) {
        //     if (!isset($data_array[$api_field])) {
        //         continue;
        //     }

        //     $this->{$class_field} = $data_array[$api_field];
        // }

        return $this;
    }

    public function getStringAsMySqlValues($db)
    {
        // return "(
        //     '" . $db->escape($this->id) . "', '" . $db->escape($this->country) . "', '" . $db->escape($this->city) . "',
        //     '" . $db->escape($this->zip) . "', '" . $db->escape($this->address) . "', '" . (float) $this->max_length_cm . "',
        //     '" . (float) $this->max_width_cm . "', '" . (float) $this->max_height_cm . "', '" . (float) $this->max_weight_kg . "',
        //     '" . (float) $this->min_length_cm . "', '" . (float) $this->min_width_cm . "', '" . (float) $this->min_height_cm . "',
        //     '" . (float) $this->min_weight_kg . "', '" . $db->escape($this->recipient_phone_prefix) . "', '" . $db->escape($this->recipient_phone_regexp) . "',
        //     '" . $this->latitude . "', '" . $this->longitude . "'
        // )";
        return "(
            '" . $db->escape($this->id) . "', '" . $db->escape($this->country) . "', '" . $db->escape($this->city) . "',
            '" . $db->escape($this->zip) . "', '" . $db->escape($this->address) . "',
            '" . $this->latitude . "', '" . $this->longitude . "',
            '" . $db->escape(json_encode($this->params)) . "', '" . (int) $this->active . "'
        )";
    }

    public function getParams()
    {
        return json_encode($this->params, JSON_PRETTY_PRINT);
    }

    public function getMinDimensions($formated = true)
    {
        $length = (float) (isset($this->params['min_length_cm']) ? $this->params['min_length_cm'] : 0);
        $width = (float) (isset($this->params['min_width_cm']) ? $this->params['min_width_cm'] : 0);
        $height = (float) (isset($this->params['min_height_cm']) ? $this->params['min_height_cm'] : 0);

        return $formated ? "$length x $width x $height" : [
            'L' => $length,
            'W' => $width,
            'H' => $height
        ];
    }

    public function getMaxDimensions($formated = true)
    {
        $length = (float) (isset($this->params['max_length_cm']) ? $this->params['max_length_cm'] : 0);
        $width = (float) (isset($this->params['max_width_cm']) ? $this->params['max_width_cm'] : 0);
        $height = (float) (isset($this->params['max_height_cm']) ? $this->params['max_height_cm'] : 0);

        return $formated ? "$length x $width x $height" : [
            'L' => $length,
            'W' => $width,
            'H' => $height
        ];
    }

    public function getMinWeight()
    {
        return (float) (isset($this->params['min_weight_kg']) ? $this->params['min_weight_kg'] : 0);
    }

    public function getMaxWeight()
    {
        return (float) (isset($this->params['max_weight_kg']) ? $this->params['max_weight_kg'] : 0);
    }

    public function getRecipientPhoneRegexp()
    {
        return (string) (isset($this->params['recipient_phone_regexp']) ? $this->params['recipient_phone_regexp'] : '');
    }

    public function getRecipientPhonePrefix()
    {
        return (string) (isset($this->params['recipient_phone_prefix']) ? $this->params['recipient_phone_prefix'] : '');
    }

    public function jsonSerialize()
    {
        return [
            "id" => $this->id,
            "country" => $this->country,
            "city" => $this->city,
            "zip" => $this->zip,
            "address" => $this->address,
            // "max_length_cm" => $this->max_length_cm,
            // "max_width_cm" => $this->max_width_cm,
            // "max_height_cm" => $this->max_height_cm,
            // "max_weight_kg" => $this->max_weight_kg,
            // "min_length_cm" => $this->min_length_cm,
            // "min_width_cm" => $this->min_width_cm,
            // "min_height_cm" => $this->min_height_cm,
            // "min_weight_kg" => $this->min_weight_kg,
            // "recipient_phone_prefix" => $this->recipient_phone_prefix,
            // "recipient_phone_regexp" => $this->recipient_phone_regexp,
            "latitude" => $this->latitude,
            "longitude" => $this->longitude,
            "params" => $this->params,
            "active" => $this->active,
            // fields needed for terminal mapping
            "coords" => [
                'lat' => $this->latitude,
                'lng' => $this->longitude
            ],
            // "name" => $this->address, // for now empty name to see how it looks on map
            "identifier" => Params::SETTINGS_CODE // use module internal identifier
        ];
    }

    /**
     * @param DeliveryPoint[] $delivery_points_array array of DeliveryPoint objects
     * @param object $db OpenCart database object
     * 
     * @return [type]
     */
    public static function insertIntoDb($delivery_points_array, $db)
    {
        $sql_values = array_map(function (DeliveryPoint $item) use ($db) {
            return $item->getStringAsMySqlValues($db);
        }, $delivery_points_array);

        /*
        `max_length_cm`, `max_width_cm`, `max_height_cm`, `max_weight_kg`,
                `min_length_cm`, `min_width_cm`, `min_height_cm`, `min_weight_kg`,
                `recipient_phone_prefix`, `recipient_phone_regexp`,
        */
        $sql = 'INSERT INTO ' . DbTables::TABLE_DELIVERY_POINT . ' 
            (
                `id`, `country`, `city`, `zip`, `address`,
                `latitude`, `longitude`, `params`, `active`
            )
            VALUES ' . implode(', ', $sql_values) . '
            ON DUPLICATE KEY UPDATE 
                `id` = VALUES(`id`), `country` = VALUES(`country`), `city` = VALUES(`city`),`zip` = VALUES(`zip`), 
                `address` = VALUES(`address`), `latitude` = VALUES(`latitude`), `longitude` = VALUES(`longitude`),
                `params` = VALUES(`params`), `active` = VALUES(`active`)
        ';

        $db->query($sql);
    }

    public static function getPage($page, $limit, $db)
    {
        if ($page < 0) {
            $page = 1;
        }
        $offset = ((int) $page - 1) * (int) $limit;

        $sql_result = $db->query(
            '
            SELECT *
            FROM ' . DbTables::TABLE_DELIVERY_POINT . '
            ORDER BY `latitude`, `longitude`
            LIMIT ' . $offset . ', ' . (int) $limit
        );

        if (empty($sql_result->rows)) {
            return [];
        }

        $result = [];
        foreach ($sql_result->rows as $row) {
            $result[$row['id']] = new DeliveryPoint($row, false);
        }

        return $result;
    }

    public static function disableAllPoints($db)
    {
        $db->query('
            UPDATE `' . DbTables::TABLE_DELIVERY_POINT . '`
            SET `active` = "0"
            WHERE `active` = "1"
        ');
    }

    public static function getTotalPoints($db, $active_only = true)
    {
        $where = '';
        if ($active_only) {
            $where = ' WHERE `active` = "1" ';
        }

        $sql_result = $db->query('SELECT COUNT(id) as total_points FROM `' . DbTables::TABLE_DELIVERY_POINT . '` ' . $where);

        return empty($sql_result->row) ? 0 : (int) $sql_result->row['total_points'];
    }

    public static function getDeliveryPointById($id, $db)
    {
        $delivery_point = new DeliveryPoint();
        $sql_result = $db->query('
            SELECT * FROM `' . DbTables::TABLE_DELIVERY_POINT . '`
            WHERE `id` = "' . $db->escape($id) . '"
        ');

        return empty($sql_result->row) ? $delivery_point : $delivery_point->parseDataArray($sql_result->row, false);
    }

    public static function getDeliveryPointsByCountryCode($db, $country_code)
    {
        $sql_result = $db->query('
            SELECT *
            FROM ' . DbTables::TABLE_DELIVERY_POINT . '
            WHERE `country` = "' . $db->escape($country_code) . '" AND `active` = "1"
            ORDER BY `latitude`, `longitude`
        ');

        if (empty($sql_result->rows)) {
            return [];
        }

        $result = [];
        foreach ($sql_result->rows as $row) {
            $result[$row['id']] = new DeliveryPoint($row, false);
        }

        return $result;
    }

    // public static function getDefaultWarehouse($db)
    // {
    //     $warehouse = new Warehouse();

    //     $sql_result = $db->query('
    //         SELECT `id`, `name`, `country`, `city`, `zip`, `address`, `is_test`, `is_default`
    //         FROM ' . DbTables::TABLE_WAREHOUSE . '
    //         WHERE `is_default` = "1"
    //         LIMIT 1
    //     ');

    //     if (empty($sql_result->row)) {
    //         return $warehouse;
    //     }

    //     return $warehouse->parseDataArray($sql_result->row);
    // }

    // public static function getWarehouse($id, $db)
    // {
    //     $warehouse = new Warehouse();
    //     $sql_result = $db->query('
    //         SELECT `id`, `name`, `country`, `city`, `zip`, `address`, `is_test`, `is_default`
    //         FROM ' . DbTables::TABLE_WAREHOUSE . '
    //         WHERE `id` = "' . $db->escape($id) . '"
    //         LIMIT 1
    //     ');

    //     if (empty($sql_result->row)) {
    //         return $warehouse;
    //     }

    //     return $warehouse->parseDataArray($sql_result->row);
    // }

    // public static function setDefaultWarehouse($id, $db)
    // {
    //     $warehouse = self::getWarehouse($id, $db);

    //     // make sure warehouse exist
    //     if (!$warehouse->id) {
    //         return $warehouse;
    //     }

    //     // remove current defaults
    //     $db->query('
    //         UPDATE `' . DbTables::TABLE_WAREHOUSE . '`
    //         SET 
    //             `is_default` = "0"
    //         WHERE `is_default` = "1"
    //     ');

    //     // set new default
    //     $db->query('
    //         UPDATE `' . DbTables::TABLE_WAREHOUSE . '`
    //         SET 
    //             `is_default` = "1"
    //         WHERE `id` = "' . $db->escape($id) . '"
    //     ');

    //     $warehouse->is_default = true;

    //     return $warehouse;
    // }
}

/* API Warehouse object
 {
    "id": "81f32a75-4cf9-4bcf-8536-7eb2836f4d23",
    "country": "LV",
    "city": "Rīga",
    "zip": "LV-1050",
    "address": "Stacijas laukums 2",
    "max_length_cm": 63.99,
    "max_width_cm": 37.99,
    "max_height_cm": 31.99,
    "max_weight_kg": 19.9999,
    "min_length_cm": 13.99,
    "min_width_cm": 7.99,
    "min_height_cm": 4.99,
    "min_weight_kg": 0.4999,
    "recipient_phone_prefix": "+371",
    "recipient_phone_regexp": "^2[0-9]{7}$",
    "latitude": 56.94697,
    "longitude": 24.118524
  }
 */
<?php

namespace Mijora\HrxOpencart\Model;

use JsonSerializable;
use Mijora\HrxOpencart\Interfaces\DeliveryPointInterface;
use Mijora\HrxOpencart\OpenCart\DbTables;

class DeliveryCourier implements JsonSerializable, DeliveryPointInterface
{
    const FIELD_PARAMS = 'params';
    const FIELD_ACTIVE = 'active';

    public $country;

    // custom flag to mark if destination is active in api
    public $active = false;

    public $params = []; // rest of parameters

    // maps api field to class attribute, fields not in this list will be stored in $params attribute
    private $api_to_class_attr = [
        "country" => 'country'
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

        // if data from api set destination as active
        if ($from_api) {
            $this->active = true;
        }

        return $this;
    }

    public function getStringAsMySqlValues($db)
    {
        return "(
            '" . $db->escape($this->country) . "',
            '" . $db->escape(json_encode($this->params)) . "',
            '" . (int) $this->active . "'
        )";
    }

    public function getId()
    {
        return null; // courier does not have id
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
            ParcelProduct::DIMENSION_LENGTH => $length,
            ParcelProduct::DIMENSION_WIDTH => $width,
            ParcelProduct::DIMENSION_HEIGHT => $height
        ];
    }

    public function getMaxDimensions($formated = true)
    {
        $length = (float) (isset($this->params['max_length_cm']) ? $this->params['max_length_cm'] : 0);
        $width = (float) (isset($this->params['max_width_cm']) ? $this->params['max_width_cm'] : 0);
        $height = (float) (isset($this->params['max_height_cm']) ? $this->params['max_height_cm'] : 0);

        return $formated ? "$length x $width x $height" : [
            ParcelProduct::DIMENSION_LENGTH => $length,
            ParcelProduct::DIMENSION_WIDTH => $width,
            ParcelProduct::DIMENSION_HEIGHT => $height
        ];
    }

    public function getMinWeight(): float
    {
        return (float) (isset($this->params['min_weight_kg']) ? $this->params['min_weight_kg'] : 0);
    }

    public function getMaxWeight(): float
    {
        return (float) (isset($this->params['max_weight_kg']) ? $this->params['max_weight_kg'] : 0);
    }

    public function getRecipientPhoneRegexp(): string
    {
        return (string) (isset($this->params['recipient_phone_regexp']) ? $this->params['recipient_phone_regexp'] : '');
    }

    public function getRecipientPhonePrefix(): string
    {
        return (string) (isset($this->params['recipient_phone_prefix']) ? $this->params['recipient_phone_prefix'] : '');
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            "country" => $this->country,
            "params" => $this->params,
            "active" => $this->active
        ];
    }

    public static function getCountryList($db, $active_only = true)
    {
        $where = '';
        if ($active_only) {
            $where = ' AND hmdc.active = 1';
        }
        $sql = "
            SELECT DISTINCT hmdc.`country` as `iso_code_2` FROM `" . DbTables::TABLE_DELIVERY_COURIER . "` hmdc 
            WHERE hmdc.`country` IS NOT NULL " . $where
        ;

        $result = $db->query($sql);

        return !$result->rows ? [] : $result->rows;
    }

    /**
     * @param DeliveryCourier[] $locations_array array of DeliveryCourier objects
     * @param object $db OpenCart database object
     * 
     * @return [type]
     */
    public static function insertIntoDb($locations_array, $db)
    {
        $sql_values = array_map(function (DeliveryCourier $item) use ($db) {
            return $item->getStringAsMySqlValues($db);
        }, $locations_array);

        /*
        `max_length_cm`, `max_width_cm`, `max_height_cm`, `max_weight_kg`,
                `min_length_cm`, `min_width_cm`, `min_height_cm`, `min_weight_kg`,
                `recipient_phone_prefix`, `recipient_phone_regexp`,
        */
        $sql = 'INSERT INTO ' . DbTables::TABLE_DELIVERY_COURIER . ' 
            (
                `country`, `params`, `active`
            )
            VALUES ' . implode(', ', $sql_values) . '
            ON DUPLICATE KEY UPDATE 
                `country` = VALUES(`country`),
                `params` = VALUES(`params`),
                `active` = VALUES(`active`)
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
            FROM ' . DbTables::TABLE_DELIVERY_COURIER . '
            ORDER BY `country`
            LIMIT ' . $offset . ', ' . (int) $limit
        );

        if (empty($sql_result->rows)) {
            return [];
        }

        $result = [];
        foreach ($sql_result->rows as $row) {
            $result[$row['country']] = new DeliveryCourier($row, false);
        }

        return $result;
    }

    public static function disableAllLocations($db)
    {
        $db->query('
            UPDATE `' . DbTables::TABLE_DELIVERY_COURIER . '`
            SET `active` = "0"
            WHERE `active` = "1"
        ');
    }

    public static function getTotalLocations($db, $active_only = true)
    {
        $where = '';
        if ($active_only) {
            $where = ' WHERE `active` = "1" ';
        }

        $sql_result = $db->query('SELECT COUNT(country) as total_locations FROM `' . DbTables::TABLE_DELIVERY_COURIER . '` ' . $where);

        return empty($sql_result->row) ? 0 : (int) $sql_result->row['total_locations'];
    }

    public static function getDeliveryLocationByCountryCode($country_code, $db)
    {
        $delivery_point = new DeliveryCourier();
        $sql_result = $db->query('
            SELECT * FROM `' . DbTables::TABLE_DELIVERY_COURIER . '`
            WHERE `country` = "' . $db->escape(mb_strtoupper($country_code)) . '"
        ');

        return empty($sql_result->row) ? $delivery_point : $delivery_point->parseDataArray($sql_result->row, false);
    }
}

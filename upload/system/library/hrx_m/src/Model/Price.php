<?php

namespace Mijora\HrxOpencart\Model;

use JsonSerializable;
use Mijora\HrxOpencart\OpenCart\DbTables;

class Price implements JsonSerializable
{
    const RANGE_TYPE_CART_PRICE = 0;

    const RANGE_TYPE_WEIGHT = 1;

    const RANGE_TYPE = [
        self::RANGE_TYPE_CART_PRICE,
        self::RANGE_TYPE_WEIGHT
    ];

    const COUNTRY_CODE_LENGTH = 2; // ISO 3166-1 alpha-2 is 2 symbols length 

    const PRICE_DATA_FIELDS = [
        'country_code' => 'string', // also a table field
        'country_name' => 'string',
        'price' => 'string',
        'price_range_type' => 'int'
    ];

    private $country_code;
    private $data;

    private $db;

    public function __construct($db, $data = null, $from_db = false)
    {
        $this->db = $db;

        if (is_array($data)) {
            $this->parseData($data, $from_db);
        }
    }

    public function parseData($data, $from_db = false)
    {
        if (!$from_db && isset($data['country_code'])) {
            $data['country_code'] = mb_strtoupper($data['country_code']);
        }

        $this->country_code = isset($data['country_code']) ? $data['country_code'] : null;

        if (!$from_db && isset($data['price'])) {
            $data['price'] = self::cleanPriceRangeData($data['price']);
            if (!isset($data['country_name']) || empty($data['country_name'])) {
                $data['country_name'] = $this->getCountryName($this->country_code);
            }
            $this->data = $data;
            return $this;
        }

        // if from database need to decode price_data field
        $this->data = json_decode($data['price_data'], true);

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'country_code' => $this->country_code,
            'data' => $this->data
        ];
    }

    public function getCountryName($country_code)
    {
        if (!self::isCountryCodeValid($country_code)) {
            return '';
        }

        $result = $this->db->query("
            SELECT `name` FROM `" . DB_PREFIX . "country`
            WHERE `iso_code_2` = '" . $this->db->escape(mb_strtoupper($country_code)) . "'
            LIMIT 1
        ");

        return !$result->rows ? '' : $result->row['name'];
    }

    public function savePrice()
    {
        $json_data = json_encode($this->data);

        return $this->db->query("
            INSERT INTO `" . DbTables::TABLE_PRICE . "` (country_code, price_data) 
            VALUES('" . $this->db->escape($this->country_code) . "', '" . $this->db->escape($json_data) . "') 
            ON DUPLICATE KEY UPDATE price_data = VALUES(price_data)
        ");
    }

    public function deletePrice()
    {
        if (!$this->country_code) {
            return false;
        }

        return self::deletePriceStatic($this->db, $this->country_code);
    }

    public function getCountryCodeValue()
    {
        return $this->country_code;
    }

    public function getCountryNameValue()
    {
        return isset($this->data['country_name']) ? $this->data['country_name'] : null;
    }

    public function getPriceValue()
    {
        return isset($this->data['price']) ? $this->data['price'] : null;
    }

    public function getRangeTypeValue()
    {
        return (int) (isset($this->data['price_range_type']) ? $this->data['price_range_type'] : self::RANGE_TYPE_CART_PRICE);
    }

    public function getBase64String()
    {
        return base64_encode(json_encode($this));
    }

    public static function deletePriceStatic($db, $country_code)
    {
        return $db->query("DELETE FROM `" . DbTables::TABLE_PRICE . "` WHERE `country_code` = '" . $db->escape(mb_strtoupper($country_code)) . "'");
    }

    public static function isCountryCodeValid($string)
    {
        return is_string($string) && strlen($string) === self::COUNTRY_CODE_LENGTH;
    }

    /**
     * Returns countries that has yet to have price set
     * 
     * @param object $db Opencart database object
     * 
     * @return array array with country name and is_code_2
     */
    public static function getPriceCountries($db)
    {
        $sql = "
            SELECT DISTINCT hmdp.`country` as `iso_code_2`, c.`name` FROM `" . DbTables::TABLE_DELIVERY_POINT . "` hmdp 
            LEFT JOIN `" . DbTables::TABLE_PRICE . "` hmp ON hmp.`country_code` = hmdp.`country`
            LEFT JOIN `" . DB_PREFIX . "country` c ON c.`iso_code_2` = hmdp.`country`
            WHERE hmdp.`active` IN (1) AND c.`name` IS NOT NULL AND hmp.`country_code` IS NULL
        ";

        $result = $db->query($sql);

        return !$result->rows ? [] : $result->rows;
    }

    public static function getPrice($db, $country_code)
    {
        $price = new Price($db);
        $result = $db->query("
            SELECT * FROM `" . DbTables::TABLE_PRICE . "`
            WHERE `country_code` = '" . $db->escape(mb_strtoupper($country_code)) . "'
            LIMIT 1
        ");

        return !$result->rows ? $price : $price->parseData($result->row, true);
    }

    public static function getPrices($db)
    {
        $sql_result = $db->query("
            SELECT * FROM `" . DbTables::TABLE_PRICE . "`
            ORDER BY `country_code`
        ");

        if (!$sql_result->rows) {
            return [];
        }

        $result = [];
        foreach ($sql_result->rows as $row) {
            $result[] = new Price($db, $row, true);
        }

        return $result;
    }

    public static function isPriceRangeFormat($range_string)
    {
        // Check if $cost_ranges is in cart_total:price ; cart_total:price format
        return strpos($range_string, ':') === false ? false : true;
    }

    public static function cleanPriceRangeData($range_string)
    {
        // if not range format return trimmed string
        if (empty($range_string) || !self::isPriceRangeFormat($range_string)) {
            return trim($range_string);
        }

        $ranges = explode(';', $range_string);

        // in case explode returns false - should never happen
        if (!is_array($ranges)) {
            return '';
        }

        $result = [];

        foreach ($ranges as $range_data) {
            // explode into range and cost parts
            $range_data_array = explode(':', trim($range_data));

            // resulting range data array must be array with 2 elements [range, cost]
            if (!is_array($range_data_array) || count($range_data_array) != 2) {
                continue;
            }

            // if either of two values is empty skip it
            if (trim($range_data_array[0]) === '' || trim($range_data_array[1]) === '') {
                continue;
            }

            $range = trim($range_data_array[0]);
            $cost = (float) trim($range_data_array[1]);

            // store data into array using range as key
            $result[$range] = $cost;
        }

        // sort by keys from lowest to highest
        uksort($result, function ($a, $b) {
            return (float) $a - (float) $b;
        });

        // merge everything back into string
        $result_string = implode(' ; ', array_map(
            function ($key, $value) {
                return "$key:$value";
            },
            array_keys($result),
            array_values($result)
        ));

        return $result_string;
    }
}

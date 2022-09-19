<?php

namespace Mijora\HrxOpencart\Model;

use JsonSerializable;
use Mijora\HrxOpencart\OpenCart\DbTables;

class Warehouse implements JsonSerializable
{
    const ALL_WAREHOUSES = -1;
    public $id;
    public $name;
    public $country;
    public $city;
    public $zip;
    public $address;
    public $is_test = false;
    public $is_default = false;

    private $api_fields = [
        "id" => 'id',
        "name" => 'name',
        "country" => 'country',
        "city" => 'city',
        "zip" => 'zip',
        "address" => 'address'
    ];

    public function __construct($data_array = null)
    {
        $this->parseDataArray($data_array);
    }

    public function parseDataArray($data_array)
    {
        if (!is_array($data_array)) {
            return $this;
        }

        foreach ($this->api_fields as $api_field => $class_field) {
            if (!isset($data_array[$api_field])) {
                continue;
            }

            $this->{$class_field} = $data_array[$api_field];
        }

        // special fields existing only in database
        if (isset($data_array['is_default'])) {
            $this->is_default = (bool) $data_array['is_default'];
        }

        if (isset($data_array['is_test'])) {
            $this->is_test = (bool) $data_array['is_test'];
        }

        return $this;
    }

    public function getNameWithAddress()
    {
        return $this->name . ' [ ' . $this->address . ', ' . $this->zip . ' ' . $this->city . ', ' . $this->country . ' ]';
    }

    public function getStringAsMySqlValues($db)
    {
        return "(
            '" . $db->escape($this->id) . "', '" . $db->escape($this->name) . "', '" . $db->escape($this->country) . "',
            '" . $db->escape($this->city) . "', '" . $db->escape($this->zip) . "', '" . $db->escape($this->address) . "',
            '" . (int) $this->is_test . "', '" . (int) $this->is_default . "'
        )";
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'country' => $this->country,
            'city' => $this->city,
            'zip' => $this->zip,
            'address' => $this->address,
            'is_test' => $this->is_test,
            'is_default' => $this->is_default
        ];
    }

    /**
     * @param Warehouse[] $warehouse_array array of Warehouse objects
     * @param object $db OpenCart database object
     * 
     * @return [type]
     */
    public static function insertIntoDb($warehouse_array, $db)
    {
        $sql_values = array_map(function (Warehouse $item) use ($db) {
            return $item->getStringAsMySqlValues($db);
        }, $warehouse_array);

        $sql = 'INSERT INTO ' . DbTables::TABLE_WAREHOUSE . ' 
            (`id`, `name`, `country`, `city`, `zip`, `address`, `is_test`, `is_default`)
            VALUES ' . implode(', ', $sql_values);

        $db->query($sql);
    }

    public static function getPage($page, $limit, $db)
    {
        $limit_sql = '';

        if ((int) $page < 0) {
            $page = 1;
        }

        if ($limit !== self::ALL_WAREHOUSES) {
            $offset = ((int) $page - 1) * (int) $limit;
            $limit_sql = 'LIMIT ' . $offset . ', ' . (int) $limit;
        }

        $sql_result = $db->query(
            '
            SELECT `id`, `name`, `country`, `city`, `zip`, `address`, `is_test`, `is_default`
            FROM ' . DbTables::TABLE_WAREHOUSE . '
            ORDER BY `is_default` DESC, `name`
            ' . $limit_sql
        );

        if (empty($sql_result->rows)) {
            return [];
        }

        $result = [];
        foreach ($sql_result->rows as $row) {
            $result[$row['id']] = new Warehouse($row);
        }

        return $result;
    }

    public static function getDefaultWarehouse($db)
    {
        $warehouse = new Warehouse();

        $sql_result = $db->query('
            SELECT `id`, `name`, `country`, `city`, `zip`, `address`, `is_test`, `is_default`
            FROM ' . DbTables::TABLE_WAREHOUSE . '
            WHERE `is_default` = "1"
            LIMIT 1
        ');

        if (empty($sql_result->row)) {
            return $warehouse;
        }

        return $warehouse->parseDataArray($sql_result->row);
    }

    public static function getWarehouse($id, $db)
    {
        $warehouse = new Warehouse();
        $sql_result = $db->query('
            SELECT `id`, `name`, `country`, `city`, `zip`, `address`, `is_test`, `is_default`
            FROM ' . DbTables::TABLE_WAREHOUSE . '
            WHERE `id` = "' . $db->escape($id) . '"
            LIMIT 1
        ');

        if (empty($sql_result->row)) {
            return $warehouse;
        }

        return $warehouse->parseDataArray($sql_result->row);
    }

    public static function setDefaultWarehouse($id, $db)
    {
        $warehouse = self::getWarehouse($id, $db);

        // make sure warehouse exist
        if (!$warehouse->id) {
            return $warehouse;
        }

        // remove current defaults
        $db->query('
            UPDATE `' . DbTables::TABLE_WAREHOUSE . '`
            SET 
                `is_default` = "0"
            WHERE `is_default` = "1"
        ');

        // set new default
        $db->query('
            UPDATE `' . DbTables::TABLE_WAREHOUSE . '`
            SET 
                `is_default` = "1"
            WHERE `id` = "' . $db->escape($id) . '"
        ');

        $warehouse->is_default = true;

        return $warehouse;
    }

    public static function getTotalWarehouse($db, $active_only = true)
    {
        $where = '';
        // if ($active_only) {
        //     $where = ' WHERE `active` = "1" ';
        // }

        $sql_result = $db->query('SELECT COUNT(id) as total_points FROM `' . DbTables::TABLE_WAREHOUSE . '` ' . $where);

        return empty($sql_result->row) ? 0 : (int) $sql_result->row['total_points'];
    }
}

/* API Warehouse object
 {
    "id": "81f32a75-4cf9-4bcf-8536-7eb2836f4d23",
    "name": "Warehouse One",
    "country": "LV",
    "city": "Rīga",
    "zip": "LV-1050",
    "address": "Stacijas laukums 2"
  }
 */
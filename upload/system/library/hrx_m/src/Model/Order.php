<?php

namespace Mijora\HrxOpencart\Model;

use JsonSerializable;
use Mijora\HrxOpencart\OpenCart\DbTables;
use Mijora\HrxOpencart\Params;

class Order implements JsonSerializable
{
    const SQL_DATA_FIELDS = [
        'order_id' => 'int',
        'customer' => 'string',
        'order_status' => 'string',
        'order_status_id' => 'int',
        'shipping_code' => 'string',
        'total' => 'float',
        'currency_code' => 'string',
        'currency_value' => 'float',
        'date_added' => 'string',
        'date_modified' => 'string',
        // custom
        'hrx_order_id' => 'string',
        'hrx_order' => 'json',
        'hrx_data' => 'json',
    ];

    const IS_CLASS_VARIABLE = [
        'order_id',
        'hrx_order_id',
        'hrx_order',
        'hrx_data',
    ];

    private $order_id;

    private $data = [];

    private $hrx_order_id;
    private $hrx_order;
    private $hrx_data;

    private $db;

    public function __construct($db, $data = null, $from_db = false)
    {
        $this->db = $db;
        if (is_array($data) && $from_db) {
            $this->parseData($data);
        }
    }

    private function parseData($data)
    {
        foreach (self::SQL_DATA_FIELDS as $field => $cast_type) {
            if (!isset($data[$field])) {
                continue;
            }

            if ($cast_type === 'json') {
                $this->$field = json_decode($data[$field], true);
            }

            $data_value = $data[$field];

            if ($cast_type === 'json') {
                $data_value = json_decode($data[$field], true);
            } else {
                settype($data_value, $cast_type);
            }

            if (in_array($field, self::IS_CLASS_VARIABLE)) {
                $this->$field = $data_value;
                continue;
            }

            $this->data[$field] = $data_value;
        }
    }

    public function jsonSerialize()
    {
        return [
            'order_id' => $this->order_id,
            'data' => $this->data,
            'hrx_order_id' => $this->hrx_order_id,
            'hrx_order' => $this->hrx_order,
            'hrx_data' => $this->hrx_data
        ];
    }

    public function setHrxOrderData($hrx_order_data)
    {
        $this->hrx_order_id = isset($hrx_order_data['id']) ? $hrx_order_data['id'] : null;
        $this->hrx_order = $hrx_order_data;

        return $this;
    }

    public function getOrderId()
    {
        return $this->order_id;
    }

    public function getCustomer()
    {
        return isset($this->data['customer']) ? $this->data['customer'] : null;
    }

    public function getOrderStatus()
    {
        return isset($this->data['order_status']) ? $this->data['order_status'] : null;
    }

    public function getOrderStatusId()
    {
        return isset($this->data['order_status_id']) ? $this->data['order_status_id'] : null;
    }

    public function getShippingCode($hrx_id = false)
    {
        if (!isset($this->data['shipping_code'])) {
            return null;
        }

        if (!$hrx_id) {
            return $this->data['shipping_code'];
        }

        return str_ireplace(['hrx_m.terminal_', 'hrx_m.'], '', $this->data['shipping_code']);
    }

    public function getHrxOrderId()
    {
        return $this->hrx_order_id;
    }

    public function getHrxOrder()
    {
        return $this->hrx_order;
    }

    public function getHrxData()
    {
        return $this->hrx_data;
    }

    public function canPrintReturnLabel()
    {
        return isset($this->hrx_order['can_print_return_label']) ? (bool) $this->hrx_order['can_print_return_label'] : false;
    }

    public function save()
    {
        $json_hrx_data = json_encode($this->hrx_data);
        $json_hrx_order = json_encode($this->hrx_order);

        return $this->db->query("
            INSERT INTO `" . DbTables::TABLE_ORDER . "` (order_id, hrx_order_id, hrx_order, hrx_data) 
            VALUES(
                '" . (int) $this->order_id . "',
                '" . $this->db->escape($this->hrx_order_id) . "',
                '" . $this->db->escape($json_hrx_order) . "',
                '" . $this->db->escape($json_hrx_data) . "'
            ) 
            ON DUPLICATE KEY UPDATE hrx_order_id = VALUES(hrx_order_id), hrx_order = VALUES(hrx_order), hrx_data = VALUES(hrx_data)
        ");
    }

    public static function getManifestOrder($db, $order_id, $id_language)
    {
        $filter = [
            'page' => 1,
            'limit' => 1,
            'filter_order_id' => (int) $order_id,
            'filter_customer' => null,
            'filter_hrx_id' => null,
            'filter_order_status_id' => null,
            'filter_is_registered' => null,
            'filter_has_manifest' => null,
        ];

        $sql = self::buildManifestQuery($db, $filter, $id_language);

        $result = $db->query($sql);

        if (!$result->rows) {
            return null;
        }

        return new Order($db, $result->row, true);
    }

    public static function getManifestOrders($db, $filter, $id_language)
    {
        $sql = self::buildManifestQuery($db, $filter, $id_language);

        $result = $db->query($sql);

        if (!$result->rows) {
            return [];
        }

        $orders = [];
        foreach ($result->rows as $row) {
            $orders[] = new Order($db, $row, true);
        }

        return $orders;
    }

    public static function getManifestOrdersTotal($db, $filter, $id_language)
    {
        $sql = self::buildManifestQuery($db, $filter, $id_language, true);

        $result = $db->query($sql);

        return isset($result->row['total_orders']) ? (int) $result->row['total_orders'] : 0;
    }

    public static function buildManifestQuery($db, $filter, $id_language, $count_only = false)
    {
        $sql = "
            SELECT o.order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer, 
            (
                SELECT os.name FROM " . DB_PREFIX . "order_status os 
                WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int) $id_language . "'
            ) AS order_status, o.order_status_id, o.shipping_code, o.total, o.currency_code, o.currency_value,
            o.date_added, o.date_modified,
            hrx_order.hrx_order_id, hrx_order.hrx_order, hrx_order.hrx_data
        ";

        /*
            omod.manifest_id, 
            omlh.barcodes, omlh.is_error
         */

        if ($count_only) {
            $sql = "
                SELECT COUNT(o.order_id) as total_orders
            ";
        }

        $sql .= "
            FROM `" . DB_PREFIX . "order` o
            LEFT JOIN `" . DbTables::TABLE_ORDER . "` hrx_order ON hrx_order.order_id = o.order_id
            #LEFT JOIN `" . DB_PREFIX . "omniva_m_label_history` omlh ON omlh.order_id = o.order_id AND omlh.`id_label_history` IN (
            #	SELECT MAX(id_label_history) as latest_history_id 
            #    FROM `" . DB_PREFIX . "omniva_m_label_history`
            #    GROUP BY order_id
            #)
            WHERE o.shipping_code LIKE '" . Params::SETTINGS_CODE . ".%'
        ";

        if ((int) $filter['filter_order_status_id'] > 0) {
            $sql .= "
                AND o.order_status_id = '" . $db->escape((int) $filter['filter_order_status_id']) . "'
            ";
        } else {
            $sql .= "
                AND o.order_status_id > '0'
            ";
        }

        if ((int) $filter['filter_order_id'] > 0) {
            $sql .= "
                AND o.order_id = '" . $db->escape((int) $filter['filter_order_id']) . "'
            ";
        }

        if (!empty($filter['filter_customer'])) {
            $sql .= "
                AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $db->escape($filter['filter_customer']) . "%'
            ";
        }

        if (!empty($filter['filter_hrx_id'])) {
            $sql .= "
                AND hrx_order.hrx_order_id LIKE '%" . $db->escape((int) $filter['filter_hrx_id']) . "%'
            ";
        }

        if ((int) $filter['filter_is_registered'] > 0) {
            switch ((int) $filter['filter_is_registered']) {
                case 1: // orders not registered
                    $sql .= "
                        AND (hrx_order.hrx_order_id IS NULL OR hrx_order.hrx_order_id = '')
                    ";
                    break;
                case 2: // orders registered
                    $sql .= "
                        AND (hrx_order.hrx_order_id IS NOT NULL AND hrx_order.hrx_order_id <> '')
                    ";
                    break;
            }
        }

        if (!$count_only) {
            $page = $filter['page'];
            $limit = $filter['limit'];

            $offset = ($page - 1) * $limit;

            $sql .= "
            ORDER BY o.order_id DESC
            LIMIT " . $offset . ", " . $limit;
        }

        return $sql;
    }
}

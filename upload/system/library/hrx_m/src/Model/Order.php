<?php

namespace Mijora\HrxOpencart\Model;

use JsonSerializable;
use Mijora\HrxOpencart\OpenCart\DbTables;
use Mijora\HrxOpencart\Params;

class Order implements JsonSerializable
{
    const REFRESH_ORDER_DATA_MAX_PER_PAGE = 10;

    const ORDER_TYPE_COURIER = 'courier'; // getShippingCode will return this in case of courier otherwise terminal ID

    // delivery kinds for API
    const TYPE_DELIVERY_COURIER = 'courier';
    const TYPE_DELIVERY_TERMINAL = 'delivery_location';

    const HRX_STATUS_NEW = 'new';
    const HRX_STATUS_READY = 'ready';
    const HRX_STATUS_IN_DELIVERY = 'in_delivery';
    const HRX_STATUS_IN_RETURN = 'in_return';
    const HRX_STATUS_RETURNED = 'returned';
    const HRX_STATUS_DELIVERED = 'delivered';
    const HRX_STATUS_CANCELED = 'cancelled';
    const HRX_STATUS_ERROR = 'error';

    const VALID_FOR_CANCEL = [
        self::HRX_STATUS_NEW,
        self::HRX_STATUS_READY,
        self::HRX_STATUS_ERROR
    ];

    const VALID_FOR_UPDATE_STATE = [
        self::HRX_STATUS_NEW,
        self::HRX_STATUS_READY
    ];

    const VALID_FOR_REREGISTER = [
        self::HRX_STATUS_CANCELED
    ];

    const NO_DATA_REFRESH = [
        self::HRX_STATUS_RETURNED,
        self::HRX_STATUS_DELIVERED,
        self::HRX_STATUS_CANCELED
    ];

    const SQL_DATA_FIELDS = [
        'order_id' => 'int',
        'customer' => 'string',
        'order_status' => 'string',
        'order_status_id' => 'int',
        'shipping_code' => 'string',
        'country_code_iso' => 'string',
        'total' => 'float',
        'currency_code' => 'string',
        'currency_value' => 'float',
        'date_added' => 'string',
        'date_modified' => 'string',
        // custom
        'hrx_order_id' => 'string',
        'hrx_order' => 'json', // HRX order json object from API
        'hrx_data' => 'json', // local modifications to order as json object
        'hrx_status' => 'string',
        'hrx_tracking_number' => 'string'
    ];

    const IS_CLASS_VARIABLE = [
        'order_id',
        'hrx_order_id',
        'hrx_order',
        'hrx_data',
    ];

    private $order_id;

    public $oc_order; // opencart order data loaded using oc sale/order model

    private $data = [];

    private $hrx_order_id;
    private $hrx_order;
    private $hrx_data;

    private $db;

    // cached associations
    private $_delivery_point;

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

    public function preloadOcOrder($oc_order_model)
    {
        $this->oc_order = $oc_order_model->getOrder((int) $this->order_id);

        return $this;
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

    public function setCustomHrxData($key, $value)
    {
        $this->hrx_data[$key] = $value;
        return $this;
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

    public function getCountryCode()
    {
        return isset($this->data['country_code_iso']) ? $this->data['country_code_iso'] : null;
    }

    public function getCustomWarehouseId()
    {
        return isset($this->hrx_data['warehouse_id']) ? $this->hrx_data['warehouse_id'] : null;
    }

    public function getRegisteredDimensions()
    {
        return [
            'weight' => isset($this->hrx_order['weight_kg']) ? $this->hrx_order['weight_kg'] : null,
            'width' => isset($this->hrx_order['width_cm']) ? $this->hrx_order['width_cm'] : null,
            'length' => isset($this->hrx_order['length_cm']) ? $this->hrx_order['length_cm'] : null,
            'height' => isset($this->hrx_order['height_cm']) ? $this->hrx_order['height_cm'] : null
        ];
    }

    public function hasValidRegisteredDimensions()
    {
        return !$this->isCancelled() && isset($this->hrx_order['weight_kg']) && isset($this->hrx_order['width_cm']) && isset($this->hrx_order['length_cm']) && isset($this->hrx_order['height_cm']);
    }

    public function getCustomDimensions()
    {
        return [
            'weight' => isset($this->hrx_data['weight']) ? $this->hrx_data['weight'] : null,
            'width' => isset($this->hrx_data['width']) ? $this->hrx_data['width'] : null,
            'length' => isset($this->hrx_data['length']) ? $this->hrx_data['length'] : null,
            'height' => isset($this->hrx_data['height']) ? $this->hrx_data['height'] : null
        ];
    }

    public function hasValidCustomDimensions()
    {
        return isset($this->hrx_data['weight']) && isset($this->hrx_data['width']) && isset($this->hrx_data['length']) && isset($this->hrx_data['height']);
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

    public function getComment()
    {
        return isset($this->hrx_data['comment']) ? $this->hrx_data['comment'] : '';
    }

    public function canPrintReturnLabel()
    {
        return isset($this->hrx_order['can_print_return_label']) ? (bool) $this->hrx_order['can_print_return_label'] : false;
    }

    public function getHrxOrderStatus()
    {
        return isset($this->hrx_order['status']) ? $this->hrx_order['status'] : null;
    }

    public function isReadyForPickup()
    {
        return $this->getHrxOrderStatus() === self::HRX_STATUS_READY;
    }

    public function isCancelled()
    {
        return $this->getHrxOrderStatus() === self::HRX_STATUS_CANCELED;
    }

    public function canBeCancelled()
    {
        return in_array($this->getHrxOrderStatus(), self::VALID_FOR_CANCEL);
    }

    public function canUpdateReadyState()
    {
        return in_array($this->getHrxOrderStatus(), self::VALID_FOR_UPDATE_STATE);
    }

    public function canRegisterAgain()
    {
        return in_array($this->getHrxOrderStatus(), self::VALID_FOR_REREGISTER);
    }

    public function isRefreshable()
    {
        return $this->getHrxOrderId() && !in_array($this->getHrxOrderStatus(), self::NO_DATA_REFRESH);
    }

    public function getHrxTrackingNumber()
    {
        return isset($this->hrx_order['tracking_number']) ? $this->hrx_order['tracking_number'] : null;
    }

    public function getHrxTrackingUrl()
    {
        return isset($this->hrx_order['tracking_url']) ? $this->hrx_order['tracking_url'] : null;
    }

    public function getDeliveryAddress()
    {
        $shipping_id = $this->getShippingCode(true);

        if ($shipping_id === self::ORDER_TYPE_COURIER) {
            return null;
        }

        return 'Terminal: ' . $this->loadDeliveryPoint($shipping_id)->getFormatedAddress();
    }

    public function getHrxDeliveryAddress()
    {
        $address = $this->getShippingCode(true) !== self::ORDER_TYPE_COURIER ? 'Terminal: ' : '';

        if (isset($this->hrx_order['delivery_location_address'])) {
            $address .= $this->hrx_order['delivery_location_address'];
        }
        if (isset($this->hrx_order['delivery_location_city'])) {
            $address .= ', ' . $this->hrx_order['delivery_location_city'];
        }
        if (isset($this->hrx_order['delivery_location_zip'])) {
            $address .= ', ' . $this->hrx_order['delivery_location_zip'];
        }
        if (isset($this->hrx_order['delivery_location_country'])) {
            $address .= ', ' . $this->hrx_order['delivery_location_country'];
        }

        return $address;
    }

    private function loadDeliveryPoint($shipping_id): DeliveryPoint
    {
        if ($this->_delivery_point) {
            return $this->_delivery_point;
        }

        $this->_delivery_point = DeliveryPoint::getDeliveryPointById($shipping_id, $this->db);

        return $this->_delivery_point;
    }

    public function isMarkedForPickup()
    {
        return $this->getHrxOrderStatus() === self::HRX_STATUS_READY;
    }

    public function save()
    {
        $json_hrx_data = json_encode($this->hrx_data);
        $json_hrx_order = json_encode($this->hrx_order);

        return $this->db->query("
            INSERT INTO `" . DbTables::TABLE_ORDER . "` (order_id, hrx_order_id, hrx_order, hrx_data, hrx_status, hrx_tracking_number) 
            VALUES(
                '" . (int) $this->order_id . "',
                '" . $this->db->escape($this->hrx_order_id) . "',
                '" . $this->db->escape($json_hrx_order) . "',
                '" . $this->db->escape($json_hrx_data) . "',
                '" . $this->db->escape($this->getHrxOrderStatus()) . "',
                '" . $this->db->escape($this->getHrxTrackingNumber()) . "'
            ) 
            ON DUPLICATE KEY UPDATE hrx_order_id = VALUES(hrx_order_id), hrx_order = VALUES(hrx_order), hrx_data = VALUES(hrx_data),
                hrx_status = VALUES(hrx_status), hrx_tracking_number = VALUES(hrx_tracking_number)
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
            'filter_hrx_tracking_num' => null,
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

    public static function getManifestOrders($db, $filter, $id_language, $paginate = true)
    {
        $sql = self::buildManifestQuery($db, $filter, $id_language, false, $paginate);

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

    public static function buildManifestQuery($db, $filter, $id_language, $count_only = false, $paginate = true)
    {
        $sql = "
            SELECT o.order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer, 
            (
                SELECT os.name FROM " . DB_PREFIX . "order_status os 
                WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int) $id_language . "'
            ) AS order_status, o.order_status_id, o.shipping_code, o.total, o.currency_code, o.currency_value,
            o.date_added, o.date_modified,
            (
                SELECT c.iso_code_2 FROM " . DB_PREFIX . "country c 
                WHERE c.country_id = o.shipping_country_id
            ) AS country_code_iso,
            hrx_order.hrx_order_id, hrx_order.hrx_order, hrx_order.hrx_data, hrx_order.hrx_status, hrx_order.hrx_tracking_number
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

        $select_multiple_orders = false;
        if (isset($filter['filter_order_ids']) && is_array($filter['filter_order_ids'])) {
            $sql .= "
                AND o.order_id IN ('" . implode("', '", $filter['filter_order_ids']) . "')
            ";
            $select_multiple_orders = true;
        }

        if ((int) $filter['filter_order_id'] > 0 && !$select_multiple_orders) {
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

        if (!empty($filter['filter_hrx_tracking_num'])) {
            $sql .= "
                AND hrx_order.hrx_tracking_number LIKE '%" . $db->escape($filter['filter_hrx_tracking_num']) . "%'
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

        if (!$count_only && $paginate) {
            $page = $filter['page'];
            $limit = $filter['limit'];

            $offset = ($page - 1) * $limit;

            $sql .= "
            ORDER BY o.order_id DESC
            LIMIT " . $offset . ", " . $limit;
        }

        return $sql;
    }

    public static function getProductsDataByOrder($order_id, $db)
    {
        $product_ids_sql = $db->query(
            '
            SELECT product_id, quantity FROM ' . DB_PREFIX . 'order_product 
            WHERE order_id = ' . (int) $order_id
        );

        if (!$product_ids_sql->rows) {
            return [];
        }

        // product info from order
        $products = [];
        foreach ($product_ids_sql->rows as $row) {
            $product_id = (int) $row['product_id'];
            $products[$product_id] = [
                'product_id' => $product_id,
                'quantity' => $row['quantity']
            ];
        }

        $product_ids = array_keys($products);

        // add in product dimmensions information
        $product_table_cols = ['product_id', 'shipping', 'width', 'height', 'length', 'weight', 'weight_class_id', 'length_class_id'];
        $products_sql = $db->query('
            SELECT ' . implode(', ', $product_table_cols) . ' FROM ' . DB_PREFIX . 'product 
            WHERE product_id IN (' . implode(', ', $product_ids) . ')
        ');

        foreach ($products_sql->rows as $row) {
            $product_id = (int) $row['product_id'];

            if (!isset($products[$product_id])) {
                continue;
            }

            foreach ($product_table_cols as $col) {
                $products[$product_id][$col] = $row[$col];
            }
        }

        return $products;
    }
}

<?php 

namespace Mijora\HrxOpencart\OpenCart;

use Mijora\HrxOpencart\Params;

class DbTables
{
	const TABLE_WAREHOUSE = DB_PREFIX . Params::PREFIX . 'warehouse';
	const TABLE_DELIVERY_POINT = DB_PREFIX . Params::PREFIX . 'delivery_point';
	const TABLE_DELIVERY_COURIER = DB_PREFIX . Params::PREFIX . 'delivery_courier';
	const TABLE_PRICE = DB_PREFIX . Params::PREFIX . 'price';
	const TABLE_ORDER = DB_PREFIX . Params::PREFIX . 'order';
	const TABLE_PARCEL_DEFAULT = DB_PREFIX . Params::PREFIX . 'parcel_default';

	private $db;

	/**
	 * @param object $db OpenCart Database object
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

    public function install()
    {
        $sql_array = [
            "
            CREATE TABLE IF NOT EXISTS `" . self::TABLE_WAREHOUSE . "` (
                `id` varchar(255) NOT NULL,
                `name` varchar(255) NOT NULL,
                `country` varchar(128) NOT NULL,
                `city` varchar(128) NOT NULL,
                `zip` varchar(10) NOT NULL,
                `address` varchar(255) NOT NULL,
				`is_test` tinyint(1) NOT NULL DEFAULT '0',
				`is_default` tinyint(1) NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            "
            CREATE TABLE IF NOT EXISTS `" . self::TABLE_DELIVERY_POINT . "` (
                `id` varchar(255) NOT NULL,
                `country` varchar(128) NOT NULL,
                `city` varchar(128) NOT NULL,
                `zip` varchar(10) NOT NULL,
                `address` varchar(255) NOT NULL,
				`latitude`	DOUBLE NOT NULL,
				`longitude`	DOUBLE NOT NULL,
				`params` MEDIUMTEXT,
				`active` tinyint(1) NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            "
            CREATE TABLE IF NOT EXISTS `" . self::TABLE_DELIVERY_COURIER . "` (
                `country` varchar(5) NOT NULL,
				`params` MEDIUMTEXT,
				`active` tinyint(1) NOT NULL DEFAULT '0',
                PRIMARY KEY (`country`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
			"
			CREATE TABLE IF NOT EXISTS `" . self::TABLE_PRICE . "` (
                `country_code` varchar(2) NOT NULL,
                `price_data` text,
                PRIMARY KEY (`country_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
			",
			"
			CREATE TABLE IF NOT EXISTS `" . self::TABLE_ORDER . "` (
                `order_id` int(11) NOT NULL,
				`hrx_order_id` varchar(255) DEFAULT NULL,
				`hrx_order` text,
				`hrx_data` text,
                `hrx_status` varchar(255) DEFAULT NULL,
                `hrx_tracking_number` text,
				PRIMARY KEY (`order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
			",
			"
			CREATE TABLE IF NOT EXISTS `" . self::TABLE_PARCEL_DEFAULT . "` (
                `category_id` int(11) unsigned NOT NULL,
                `weight` decimal(15,8) NOT NULL DEFAULT '1.00000000',
                `length` decimal(15,8) NOT NULL DEFAULT '10.00000000',
                `width` decimal(15,8) NOT NULL DEFAULT '10.00000000',
                `height` decimal(15,8) NOT NULL DEFAULT '10.00000000',
                PRIMARY KEY (`category_id`),
                UNIQUE KEY `category_id` (`category_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
			",
            // add global default
            "
            INSERT INTO " . self::TABLE_PARCEL_DEFAULT . " (category_id) VALUES (0)
                ON DUPLICATE KEY UPDATE category_id = 0
            ",
            // "
            // CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "omniva_int_m_option_country` (
            //     `option_id` int(11) unsigned NOT NULL,
            //     `country_code` varchar(4) NOT NULL DEFAULT '',
            //     `offer_priority` tinyint(1) DEFAULT NULL,
            //     `price_type` tinyint(1) DEFAULT NULL,
            //     `price` decimal(15,4) DEFAULT NULL,
            //     `free_shipping` decimal(15,4) DEFAULT NULL,
            //     PRIMARY KEY (`option_id`,`country_code`)
            //   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            // ",
            // "
            // CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "omniva_int_m_order` (
            //     `order_id` int(11) unsigned NOT NULL,
            //     `selected_service` varchar(50) DEFAULT NULL,
            //     `offer_data` text,
            //     `terminal_id` varchar(200) DEFAULT NULL,
            //     `terminal_data` text,
            //     `added_at` datetime DEFAULT NULL,
            //     `updated_at` datetime DEFAULT NULL,
            //     PRIMARY KEY (`order_id`)
            //   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            // ",
            // "
            // CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "omniva_int_m_order_api` (
            //     `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            //     `order_id` int(11) unsigned NOT NULL,
            //     `api_cart_id` varchar(255) DEFAULT NULL,
            //     `api_shipment_id` varchar(255) DEFAULT NULL,
            //     `created_at` datetime DEFAULT NULL,
            //     `canceled` tinyint(1) NOT NULL DEFAULT '0',
            //     PRIMARY KEY (`id`),
            //     KEY `order_id` (`order_id`),
            //     KEY `api_cart_id` (`api_cart_id`),
            //     KEY `api_shipment_id` (`api_shipment_id`)
            //   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            // ",
            // "
            // CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "omniva_int_m_parcel_default` (
            //     `category_id` int(11) unsigned NOT NULL,
            //     `weight` decimal(15,8) NOT NULL DEFAULT '1.00000000',
            //     `length` decimal(15,8) NOT NULL DEFAULT '10.00000000',
            //     `width` decimal(15,8) NOT NULL DEFAULT '10.00000000',
            //     `height` decimal(15,8) NOT NULL DEFAULT '10.00000000',
            //     `hs_code` varchar(255) DEFAULT NULL,
            //     PRIMARY KEY (`category_id`),
            //     UNIQUE KEY `category_id` (`category_id`)
            //   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            // ",
            // "
            // INSERT INTO " . DB_PREFIX . "omniva_int_m_parcel_default (category_id) VALUES (0)
            // "
        ];

        foreach ($sql_array as $sql) {
            $this->db->query($sql);
        }
    }

	public function uninstall()
	{
		$sql_array = [
            "DROP TABLE IF EXISTS `" . self::TABLE_WAREHOUSE . "`",
            "DROP TABLE IF EXISTS `" . self::TABLE_DELIVERY_POINT . "`",
            "DROP TABLE IF EXISTS `" . self::TABLE_DELIVERY_COURIER . "`",
            "DROP TABLE IF EXISTS `" . self::TABLE_PRICE . "`",
            "DROP TABLE IF EXISTS `" . self::TABLE_ORDER . "`",
            "DROP TABLE IF EXISTS `" . self::TABLE_PARCEL_DEFAULT . "`",
            // "DROP TABLE IF EXISTS `" . DB_PREFIX . "omniva_int_m_option`",
            // "DROP TABLE IF EXISTS `" . DB_PREFIX . "omniva_int_m_option_country`",
            // "DROP TABLE IF EXISTS `" . DB_PREFIX . "omniva_int_m_order`",
            // "DROP TABLE IF EXISTS `" . DB_PREFIX . "omniva_int_m_order_api`",
            // "DROP TABLE IF EXISTS `" . DB_PREFIX . "omniva_int_m_parcel_default`"
        ];

        foreach ($sql_array as $sql) {
            $this->db->query($sql);
        }
	}

	public static function truncateTable($table, $db)
	{
		$db->query('TRUNCATE TABLE ' . $table);
	}
}
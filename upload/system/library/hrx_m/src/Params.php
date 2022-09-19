<?php

namespace Mijora\HrxOpencart;

class Params
{
    const VERSION = '0.9.9';

    const PREFIX = 'hrx_m_';

    const SETTINGS_CODE = 'hrx_m';

    const DIR_MAIN = DIR_SYSTEM . 'library/hrx_m/';

    const GIT_VERSION_CHECK = 'https://api.github.com/repos/mijora/hrx-opencart-demo/releases/latest';
    const GIT_URL = 'https://github.com/mijora/hrx-opencart-demo/releases/latest';
    const GIT_CHECK_EVERY_HOURS = 24; // how often to check git for version. Default 24h

    const BASE_MOD_XML = 'hrx_m_base.ocmod.xml';
    const BASE_MOD_XML_SOURCE_DIR = self::DIR_MAIN . 'ocmod/'; // should have subfolders based on oc version
    const BASE_MOD_XML_SYSTEM = DIR_SYSTEM . self::BASE_MOD_XML;

    const MOD_SOURCE_DIR_OC_3_0 = '3_0/';
    const MOD_SOURCE_DIR_OC_2_3 = '2_3/';

    const COUNTRY_CHECK_TIME = 24 * 60 * 60; // 24h
    const COUNTRY_CHECK_TIME_RETRY = 60 * 60; // 1h

    const CONFIG_TOKEN = self::PREFIX . 'api_token';
    const CONFIG_TEST_MODE = self::PREFIX . 'api_test_mode';
    const CONFIG_WAREHOUSE_LAST_UPDATE = self::PREFIX . 'warehouse_last_update';
    const CONFIG_DELIVERY_POINTS_LAST_UPDATE = self::PREFIX . 'delivery_points_last_update';
    const CONFIG_DELIVERY_COURIER_LAST_UPDATE = self::PREFIX . 'delivery_courier_last_update';

    const SYNC_WAREHOUSE_PER_PAGE = 30;
    const SYNC_DELIVERY_POINTS_PER_PAGE = 150;

    const SORT_ORDER_INTERNAL_COURIER_TERMINAL = 0;
    const SORT_ORDER_INTERNAL_TERMINAL_COURIER = 1;
}

<?php

use HrxApi\API as HrxApi;
use HrxApi\Receiver as HrxReceiver;
use HrxApi\Shipment as HrxShipment;
use HrxApi\Order as HrxOrder;
use Mijora\DVDoug\BoxPacker\ItemList;
use Mijora\HrxOpencart\Helper;
use Mijora\HrxOpencart\Interfaces\DeliveryPointInterface;
use Mijora\HrxOpencart\Model\AjaxResponse;
use Mijora\HrxOpencart\Model\DeliveryCourier;
use Mijora\HrxOpencart\Model\DeliveryPoint;
use Mijora\HrxOpencart\Model\Order;
use Mijora\HrxOpencart\Model\ParcelDefault;
use Mijora\HrxOpencart\Model\ParcelItem;
use Mijora\HrxOpencart\Model\ParcelProduct;
use Mijora\HrxOpencart\Model\Price;
use Mijora\HrxOpencart\Model\Warehouse;
use Mijora\HrxOpencart\OpenCart\DbTables;
use Mijora\HrxOpencart\Params;

require_once(DIR_SYSTEM . 'library/hrx_m/vendor/autoload.php');

class ControllerExtensionShippingHrxM extends Controller
{
    private $error = array();

    private $tabs = [
        'general', 'api', 'warehouse', 'price', 'terminals', 'parcel-defaults'
    ];

    private $_cache = [];

    private $hrx_translations = [];

    public function install()
    {
        $hrx_tables = new DbTables($this->db);
        $hrx_tables->install();
    }

    public function uninstall()
    {
        $this->load->model('setting/setting');

        $this->model_setting_setting->deleteSetting(Params::SETTINGS_CODE);

        $hrx_tables = new DbTables($this->db);
        $hrx_tables->uninstall();

        // remove modification file
        Helper::removeModificationXml();
    }

    public function index()
    {
        // $this->install();
        $this->hrx_translations = $this->load->language('extension/shipping/hrx_m');
        $data = $this->mergeTranslationsIntoData([]);

        $this->document->setTitle($this->language->get('heading_title'));

        if (isset($this->request->get['fixdb']) && $this->validate()) {
            $this->fixDb();
            $this->response->redirect($this->url->link('extension/shipping/' . Params::SETTINGS_CODE, $this->getUserToken(), true));
        }

        if (isset($this->request->get['fixxml']) && $this->validate()) {
            Helper::copyModificationXml();
            $this->session->data['success'] = $this->language->get(Params::PREFIX . 'xml_updated');
            $this->response->redirect($this->url->link(Helper::getExtensionHomeString() . '/modification', $this->getUserToken(), true));
        }

        $current_tab = 'tab-general';
        if (isset($this->request->get['tab']) && in_array($this->request->get['tab'], $this->tabs)) {
            $current_tab = 'tab-' . $this->request->get['tab'];
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
            $this->prepPostData();

            if (isset($this->request->post['general_settings_update'])) {
                unset($this->request->post['general_settings_update']);
                $this->saveSettings($this->request->post);
                $this->session->data['success'] = $this->language->get(Params::PREFIX . 'msg_setting_saved');
                $current_tab = 'general';
            }

            if (isset($this->request->post['api_settings_update'])) {
                unset($this->request->post['api_settings_update']);
                $this->clearWarehouses($this->request->post);
                $this->saveSettings($this->request->post);
                $this->session->data['success'] = $this->language->get(Params::PREFIX . 'msg_setting_saved');
                $current_tab = 'api';
            }

            $this->response->redirect($this->url->link('extension/shipping/' . Params::SETTINGS_CODE, $this->getUserToken() . '&tab=' . $current_tab, true));
        }

        $data[Params::PREFIX . 'version'] = Params::VERSION;

        $data['success'] = '';
        $data['error_warning'] = '';

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        $data['breadcrumbs'] = $this->getBreadcrumbs();

        $data['form_action'] = $this->url->link('extension/shipping/' . Params::SETTINGS_CODE, $this->getUserToken(), true);

        $this->load->model('localisation/tax_class');

        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $data['ajax_url'] = $this->getAjaxUrl();

        // opencart 3 expects status and sort_order begin with shipping_ 
        $setting_prefix = '';
        if (version_compare(VERSION, '3.0.0', '>=')) {
            $setting_prefix = 'shipping_';
        }

        $oc_settings = [
            'status', 'sort_order'
        ];

        foreach ($oc_settings as $value) {
            if (isset($this->request->post[$setting_prefix . Params::PREFIX . $value])) {
                $data[Params::PREFIX . $value] = $this->request->post[$setting_prefix . Params::PREFIX . $value];
                continue;
            }

            $data[Params::PREFIX . $value] = $this->config->get($setting_prefix . Params::PREFIX . $value);
        }

        // Load saved settings or values from post request
        $module_settings = [
            // general tab
            'tax_class_id', 'geo_zone_id', 'sort_order_internal',
            // api tab
            'api_token', 'api_test_mode',
        ];

        foreach ($module_settings as $key) {
            if (isset($this->request->post[Params::PREFIX . $key])) {
                $data[Params::PREFIX . $key] = $this->request->post[Params::PREFIX . $key];
                continue;
            }

            $data[Params::PREFIX . $key] = $this->config->get(Params::PREFIX . $key);
        }

        $data[Params::PREFIX . 'sort_order_internal'] = (int) $data[Params::PREFIX . 'sort_order_internal'];

        $data['internal_sort_orders'] = [
            Params::SORT_ORDER_INTERNAL_COURIER_TERMINAL => $this->language->get(Params::PREFIX . 'sort_order_internal_' . Params::SORT_ORDER_INTERNAL_COURIER_TERMINAL),
            Params::SORT_ORDER_INTERNAL_TERMINAL_COURIER => $this->language->get(Params::PREFIX . 'sort_order_internal_' . Params::SORT_ORDER_INTERNAL_TERMINAL_COURIER)
        ];

        $partial_tab_general = $this->load->view('extension/shipping/hrx_m/partial/tab_general', $data);
        $partial_tab_api = $this->load->view('extension/shipping/hrx_m/partial/tab_api', $data);

        $data['default_warehouse'] = Warehouse::getDefaultWarehouse($this->db);
        $partial_tab_warehouse = $this->getWarehousePagePartial(1, $data['default_warehouse']);

        $data['sync_warehouse_per_page'] = Params::SYNC_WAREHOUSE_PER_PAGE;
        $data['sync_delivery_points_per_page'] = Params::SYNC_DELIVERY_POINTS_PER_PAGE;

        $partial_tab_delivery_point = $this->getDeliveryPointsPagePartial(1);
        $partial_tab_delivery_courier_location = $this->getDeliveryLocationsPagePartial(1);

        $partial_tab_prices = $this->getPricesPagePartial(1);

        $partial_tab_parcel_default = $this->getParcelDefaultTab();


        $version_check = @json_decode($this->config->get(Params::PREFIX . 'version_check_data'), true);
        if (empty($version_check) || Helper::isTimeToCheckVersion($version_check['timestamp'])) {
            $git_version = Helper::hasGitUpdate();
            $version_check = [
                'timestamp' => time(),
                'git_version' => $git_version
            ];
            $this->saveSettings([
                Params::PREFIX . 'version_check_data' => json_encode($version_check)
            ]);
        }

        $data[Params::PREFIX . 'git_version'] = $version_check['git_version'];

        //check if we still need to show notification
        if ($version_check['git_version'] !== false && !Helper::isModuleVersionNewer($version_check['git_version']['version'])) {
            $data[Params::PREFIX . 'git_version'] = false;
        }

        $data[Params::PREFIX . 'db_check'] = Helper::checkDbTables($this->db);
        $data[Params::PREFIX . 'db_fix_url'] = $this->url->link('extension/shipping/' . Params::SETTINGS_CODE, $this->getUserToken() . '&fixdb', true);

        $data[Params::PREFIX . 'xml_check'] = Helper::isModificationNewer();
        $data[Params::PREFIX . 'xml_fix_url'] = $this->url->link('extension/shipping/' . Params::SETTINGS_CODE, $this->getUserToken() . '&fixxml', true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['partial_tab_general'] = $partial_tab_general;
        $data['partial_tab_api'] = $partial_tab_api;
        $data['partial_tab_warehouse'] = $partial_tab_warehouse;
        $data['partial_tab_delivery_point'] = $partial_tab_delivery_point;
        $data['partial_tab_delivery_courier_location'] = $partial_tab_delivery_courier_location;
        $data['partial_tab_prices'] = $partial_tab_prices;
        $data['partial_tab_parcel_default'] = $partial_tab_parcel_default;

        $data['mijora_common_js_path'] = $this->getMijoraCommonJsPath();

        $this->response->setOutput($this->load->view('extension/shipping/hrx_m/settings', $data));
    }

    protected function getUserToken()
    {
        if (version_compare(VERSION, '3.0.0', '>=')) {
            return 'user_token=' . $this->session->data['user_token'];
        }

        return 'token=' . $this->session->data['token'];
    }


    protected function fixDb()
    {
        $db_check = Helper::checkDbTables($this->db);
        if (!$db_check) {
            return; // nothing to fix
        }

        foreach ($db_check as $table => $fix) {
            $this->db->query($fix);
        }
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/shipping/hrx_m')) {
            $this->error['warning'] = $this->language->get(Params::PREFIX . 'error_permission');
            return false; // skip the rest
        }

        return !$this->error;
    }

    protected function saveSettings($data)
    {
        Helper::saveSettings($this->db, $data);
    }

    /**
     * Converts certain settings that comes as array into string
     */
    protected function prepPostData()
    {
        // // we want to json_encode email template for better storage into settings
        // if (isset($this->request->post[Params::PREFIX . 'tracking_email_template'])) {
        //     $this->request->post[Params::PREFIX . 'tracking_email_template'] = json_encode($this->request->post[Params::PREFIX . 'tracking_email_template']);
        // }

        // Opencart 3 expects status to be shipping_*_status
        if (version_compare(VERSION, '3.0.0', '>=') && isset($this->request->post[Params::PREFIX . 'status'])) {
            $this->request->post['shipping_' . Params::PREFIX . 'status'] = $this->request->post[Params::PREFIX . 'status'];
            unset($this->request->post[Params::PREFIX . 'status']);
        }

        // Opencart 3 expects sort_order to be shipping_*_sort_order
        if (version_compare(VERSION, '3.0.0', '>=') && isset($this->request->post[Params::PREFIX . 'sort_order'])) {
            $this->request->post['shipping_' . Params::PREFIX . 'sort_order'] = $this->request->post[Params::PREFIX . 'sort_order'];
            unset($this->request->post[Params::PREFIX . 'sort_order']);
        }
    }

    private function getPaginationHtml($current_page, $total_pages, $js_function = '')
    {
        return $this->load->view('extension/shipping/' . Params::SETTINGS_CODE . '/partial/pagination', [
            'current_page' => (int) $current_page,
            'total_pages' => (int) $total_pages,
            'js_function' => $js_function
        ]);
    }

    private function clearWarehouses($post)
    {
        $hrx_token = isset($post['hrx_m_api_token']) ? $post['hrx_m_api_token'] : null;
        $test_mode = isset($post['hrx_m_api_test_mode']) ? $post['hrx_m_api_test_mode'] : null;

        // do nothing if missing one of the values
        if ($hrx_token === null || $test_mode === null) {
            return;
        }

        $current_token = $this->config->get(Params::CONFIG_TOKEN);
        $current_test_mode = (int) $this->config->get(Params::CONFIG_TEST_MODE);

        // if token and mode state still same no need to remove data
        if ($current_test_mode === (int) $test_mode && $current_token === $hrx_token) {
            return;
        }

        // api settings changed, clear warehouses
        DbTables::truncateTable(DbTables::TABLE_WAREHOUSE, $this->db);
        Helper::deleteSetting($this->db, Params::CONFIG_WAREHOUSE_LAST_UPDATE);
    }

    public function loadOrderListJsData()
    {
        return [
            'trans' => $this->getOrderListJsTranslations(),
            // 'call_courier_address' => $this->getSenderInformation(),
            'ajax_url' => $this->getAjaxUrl()
        ];
    }

    public function getOrderListJsTranslations()
    {
        $this->load->language('extension/shipping/' . Params::SETTINGS_CODE);

        $strings = [
            'filter_label_hrx_only', 'filter_option_yes', 'filter_option_no'
        ];

        $translations = [];

        foreach ($strings as $string) {
            $translations[$string] = $this->language->get(Params::PREFIX . 'js_' . $string);
        }

        return $translations;
    }

    public function getAjaxUrl()
    {
        return 'index.php?route=extension/shipping/' . Params::SETTINGS_CODE . '/ajax&' . $this->getUserToken();
    }

    public function ajax()
    {
        $this->response->addHeader('Content-Type: application/json');

        $this->hrx_translations = $this->load->language('extension/shipping/hrx_m');

        $response = new AjaxResponse();
        if (!$this->validate()) {
            $response->setError(implode(" \n", $this->error));
            $this->response->setOutput(json_encode($response));
            exit();
        }

        switch ($_GET['action']) {
            case 'testToken':
                $this->ajaxTestToken($response);
                break;
            case 'syncWarehouse':
                $this->ajaxSyncWarehouse($response);
                break;
            case 'getWarehousePage':
                $this->ajaxGetWarehousePage($response);
                break;
            case 'setDefaultWarehouse':
                $this->ajaxSetDefaultWarehouse($response);
                break;
            case 'syncDeliveryPoints':
                $this->ajaxSyncDeliveryPoints($response);
                break;
            case 'getDeliveryPointsPage':
                $this->ajaxGetDeliveryPointsPage($response);
                break;
            case 'syncCourierDeliveryLocations':
                $this->ajaxSyncCourierDeliveryLocations($response);
                break;
            case 'getDeliveryLocationsPage':
                $this->ajaxGetDeliveryLocationsPage($response);
                break;
            case 'savePrice':
                $response->addData('action', 'savePrice');
                $this->savePrice($response);
                break;
            case 'deletePrice':
                $response->addData('action', 'deletePrice');
                $this->ajaxDeletePrice($response);
                break;
            case 'getParcelDefaultPage':
                $response->addData('action', 'getParcelDefaultPage');
                $this->ajaxGetParcelDefaultPage($response);
                break;
            case 'saveParcelDefault':
                $response->addData('action', 'saveParcelDefault');
                $this->ajaxSaveParcelDefault($response);
                break;
            case 'resetParcelDefault':
                $response->addData('action', 'resetParcelDefault');
                $this->ajaxResetParcelDefault($response);
                break;
                // MANIFEST PAGE
            case 'getManifestPage':
                $response->addData('action', 'getManifestPage');
                $this->ajaxGetManifestPage($response);
                break;
            case 'registerHrxOrder':
                $response->addData('action', 'registerHrxOrder');
                $this->ajaxRegisterHrxOrder($response);
                break;
            case 'getLabel':
                $response->addData('action', 'getLabel');
                $this->ajaxGetLabel($response);
                break;
            case 'getMultipleLabels':
                $response->addData('action', 'getMultipleLabels');
                $this->ajaxGetMultipleLabels($response);
                break;
            case 'getHrxOrderData':
                $response->addData('action', 'getHrxOrderData');
                $this->ajaxGetHrxOrderData($response);
                break;
            case 'changeHrxOrderState':
                $response->addData('action', 'changeHrxOrderState');
                $this->ajaxChangeHrxOrderState($response);
                break;
            case 'massChangeHrxOrderState':
                $response->addData('action', 'massChangeHrxOrderState');
                $this->ajaxMassChangeHrxOrderState($response);
                break;
            case 'cancelHrxOrder':
                $response->addData('action', 'cancelHrxOrder');
                $this->ajaxCancelHrxOrder($response);
                break;
            case 'refreshOrdersDataFromApi':
                $response->addData('action', 'refreshOrdersDataFromApi');
                $this->ajaxRefreshOrdersDataFromApi($response);
                break;
                // ORDER PANEL
            case 'editOrder':
                $response->addData('action', 'editOrder');
                $this->ajaxEditOrder($response);
                break;
            case 'getHrxTrackingInfo':
                $response->addData('action', 'getHrxTrackingInfo');
                $this->ajaxGetHrxTrackingInfo($response);
                break;

            default:
                $response->setError('Restricted');
                // die(json_encode($response));
                break;
        }

        $this->response->setOutput(json_encode($response));
    }

    private function ajaxTestToken(AjaxResponse $response)
    {
        $token = isset($this->request->post['hrx_tokent']) ? $this->request->post['hrx_tokent'] : '';
        $test_mode = (bool) (isset($this->request->post['hrx_test_mode']) ? $this->request->post['hrx_test_mode'] : false);

        $response
            ->addData('hrx_token', $token)
            ->addData('hrx_test_mode', $test_mode)
            ->addData('token', Helper::checkToken($token, $test_mode, false));
    }

    private function ajaxSyncWarehouse(AjaxResponse $response)
    {
        $token = $this->config->get(Params::CONFIG_TOKEN);
        $test_mode = (bool) $this->config->get(Params::CONFIG_TEST_MODE);

        $page = (int) (isset($this->request->post['page']) ? $this->request->post['page'] : 1);
        $per_page = (int) (isset($this->request->post['per_page']) ? $this->request->post['per_page'] : 10);

        $api = new HrxApi($token, $test_mode);
        $response
            ->addData('page', $page)
            ->addData('per_page', $per_page);
        try {
            $warehouses = $api->getPickupLocations($page, $per_page);

            /** @var Warehouse[] */
            $warehouse_array = [];
            foreach ($warehouses as $api_warehouse) {
                $new_warehouse = new Warehouse($api_warehouse);
                $new_warehouse->is_test = $test_mode;
                $warehouse_array[] = $new_warehouse;
            }

            if (!empty($warehouse_array)) {
                // if page set to 1 means start of sync
                if ($page === 1) {
                    DbTables::truncateTable(DbTables::TABLE_WAREHOUSE, $this->db);
                }

                Warehouse::insertIntoDb($warehouse_array, $this->db);
            }

            $has_more = (bool) count($warehouses);

            if (!$has_more) {
                $this->saveSettings([
                    Params::CONFIG_WAREHOUSE_LAST_UPDATE => date('Y-m-d H:i:s')
                ]);
            }

            $response
                // ->addData('warehouses', $warehouses)
                ->addData('hasMore', $has_more);
        } catch (\Exception $e) {
            $response->setError($e->getMessage());
        }
    }

    private function ajaxGetWarehousePage(AjaxResponse $response)
    {
        $page = (int) (isset($this->request->post['page']) ? $this->request->post['page'] : 1);

        $response->addData('html', $this->getWarehousePagePartial($page));
    }

    private function getWarehousePagePartial($page, $default_warehouse = null)
    {
        if ((int) $page <= 0) {
            $page = 1;
        }

        $page_limit = (int) $this->config->get('config_limit_admin');
        if ($page_limit <= 0) {
            $page_limit = 30;
        }

        $data = $this->mergeTranslationsIntoData([]);

        $data['warehouse_last_update'] = $this->config->get(Params::CONFIG_WAREHOUSE_LAST_UPDATE);
        $data['warehouses'] = Warehouse::getPage($page, $page_limit, $this->db);
        $data['default_warehouse'] = $default_warehouse;

        if ($default_warehouse === null) {
            $data['default_warehouse'] = Warehouse::getDefaultWarehouse($this->db);
        }

        $total_pages = ceil(Warehouse::getTotalWarehouse($this->db, false) / $page_limit);

        $data['warehouse_pagination'] = $this->getPaginationHtml($page, $total_pages, 'getWarehousePage');

        return $this->load->view('extension/shipping/hrx_m/partial/tab_warehouse', $data);
    }

    private function ajaxSetDefaultWarehouse(AjaxResponse $response)
    {
        $id = isset($this->request->post['warehouse_id']) ? $this->request->post['warehouse_id'] : null;

        if (empty($id)) {
            $response->setError('Warehouse ID required');
            return;
        }

        $response->addData('defaultWarehouse', Warehouse::setDefaultWarehouse($id, $this->db));
    }

    private function ajaxSyncDeliveryPoints(AjaxResponse $response)
    {
        $token = $this->config->get(Params::CONFIG_TOKEN);
        $test_mode = (bool) $this->config->get(Params::CONFIG_TEST_MODE);

        $page = (int) (isset($this->request->post['page']) ? $this->request->post['page'] : 1);
        $per_page = (int) (isset($this->request->post['per_page']) ? $this->request->post['per_page'] : 10);

        $api = new HrxApi($token, $test_mode);
        $response
            ->addData('page', $page)
            ->addData('per_page', $per_page);
        try {
            $delivery_points = $api->getDeliveryLocations($page, $per_page);

            /** @var DeliveryPoint[] */
            $delivery_points_array = [];
            foreach ($delivery_points as $api_delivery_point) {
                $new_delivery_point = new DeliveryPoint($api_delivery_point);
                $delivery_points_array[] = $new_delivery_point;
            }

            if (!empty($delivery_points_array)) {
                // if page set to 1 means start of sync and we need to set all terminals active=0
                if ($page === 1) {
                    DeliveryPoint::disableAllPoints($this->db);
                }

                DeliveryPoint::insertIntoDb($delivery_points_array, $this->db);
            }

            $has_more = (bool) count($delivery_points);

            if (!$has_more) {
                $this->saveSettings([
                    Params::CONFIG_DELIVERY_POINTS_LAST_UPDATE => date('Y-m-d H:i:s')
                ]);
            }

            $response
                // ->addData('delivery_points', $delivery_points)
                ->addData('hasMore', $has_more);
        } catch (\Exception $e) {
            $response->setError($e->getMessage());
        }
    }

    private function ajaxGetDeliveryPointsPage(AjaxResponse $response)
    {
        $page = (int) (isset($this->request->post['page']) ? $this->request->post['page'] : 1);

        $response->addData('html', $this->getDeliveryPointsPagePartial($page));
    }

    private function getDeliveryPointsPagePartial($page)
    {
        if ((int) $page <= 0) {
            $page = 1;
        }

        $page_limit = (int) $this->config->get('config_limit_admin');
        if ($page_limit <= 0) {
            $page_limit = 30;
        }

        $data = $this->mergeTranslationsIntoData([]);

        $data['delivery_points'] = DeliveryPoint::getPage($page, $page_limit, $this->db);

        $total_pages = ceil(DeliveryPoint::getTotalPoints($this->db, false) / $page_limit);

        $data['delivery_points_pagination'] = $this->getPaginationHtml($page, $total_pages, 'getDeliveryPointsPage');
        $data['delivery_points_last_update'] = $this->config->get(Params::CONFIG_DELIVERY_POINTS_LAST_UPDATE);

        return $this->load->view('extension/shipping/hrx_m/partial/tab_delivery', $data);
    }

    private function ajaxSyncCourierDeliveryLocations(AjaxResponse $response)
    {
        $token = $this->config->get(Params::CONFIG_TOKEN);
        $test_mode = (bool) $this->config->get(Params::CONFIG_TEST_MODE);

        $page = (int) (isset($this->request->post['page']) ? $this->request->post['page'] : 1);

        $api = new HrxApi($token, $test_mode);
        // $response
        //     ->addData('page', $page)
        //     ->addData('per_page', $per_page);
        try {
            $delivery_locations = $api->getCourierDeliveryLocations();

            /** @var DeliveryCourier[] */
            $delivery_locations_array = [];
            foreach ($delivery_locations as $api_delivery_location) {
                $new_delivery_point = new DeliveryCourier($api_delivery_location);
                $delivery_locations_array[] = $new_delivery_point;
            }

            if (!empty($delivery_locations_array)) {
                // if page set to 1 means start of sync and we need to set all terminals active=0
                if ($page === 1) {
                    DeliveryCourier::disableAllLocations($this->db);
                }

                DeliveryCourier::insertIntoDb($delivery_locations_array, $this->db);
            }

            $has_more = false; // currently delivery locations resutls are not paginated

            if (!$has_more) {
                $this->saveSettings([
                    Params::CONFIG_DELIVERY_COURIER_LAST_UPDATE => date('Y-m-d H:i:s')
                ]);
            }

            $response
                // ->addData('delivery_points', $delivery_locations)
                ->addData('locations_loaded', count($delivery_locations))
                ->addData('hasMore', $has_more);
        } catch (\Exception $e) {
            $response->setError($e->getMessage());
        }
    }

    private function ajaxGetDeliveryLocationsPage(AjaxResponse $response)
    {
        $page = (int) (isset($this->request->post['page']) ? $this->request->post['page'] : 1);

        $response->addData('html', $this->getDeliveryLocationsPagePartial($page));
    }

    private function getDeliveryLocationsPagePartial($page)
    {
        if ((int) $page <= 0) {
            $page = 1;
        }

        $page_limit = (int) $this->config->get('config_limit_admin');
        if ($page_limit <= 0) {
            $page_limit = 30;
        }

        $data = $this->mergeTranslationsIntoData([]);

        $data['delivery_locations'] = DeliveryCourier::getPage($page, $page_limit, $this->db);

        $total_pages = ceil(DeliveryCourier::getTotalLocations($this->db, false) / $page_limit);

        $data['delivery_locations_pagination'] = $this->getPaginationHtml($page, $total_pages, 'getDeliveryLocationsPage');
        $data['delivery_locations_last_update'] = $this->config->get(Params::CONFIG_DELIVERY_COURIER_LAST_UPDATE);

        return $this->load->view('extension/shipping/hrx_m/partial/tab_delivery_courier', $data);
    }

    private function getPricesPagePartial($page)
    {
        if ((int) $page <= 0) {
            $page = 1;
        }

        $page_limit = (int) $this->config->get('config_limit_admin');
        if ($page_limit <= 0) {
            $page_limit = 30;
        }

        $data = [];
        $data = $this->mergeTranslationsIntoData($data);
        $data['countries'] = Price::getPriceCountries($this->db);
        $data['price_range_types'] = $this->getPriceRangeTypeArray();

        $prices = Price::getPrices($this->db);

        $data['hrx_m_prices'] = [];
        foreach ($prices as $price) {
            $data['hrx_m_prices'][] = $this->load->view('extension/shipping/hrx_m/partial/table_price_row', [
                'price' => $price,
                'price_range_types' => $this->getPriceRangeTypeArray()
            ]);
        }

        return $this->load->view('extension/shipping/hrx_m/partial/tab_prices', $data);
    }

    private function getPriceRangeTypeArray()
    {
        return [
            Price::RANGE_TYPE_CART_PRICE => $this->language->get(Params::PREFIX . 'range_type_cart'),
            Price::RANGE_TYPE_WEIGHT => $this->language->get(Params::PREFIX . 'range_type_weight')
        ];
    }

    private function savePrice(AjaxResponse $response)
    {
        $data = [];
        $errors = [];
        foreach (Price::PRICE_DATA_FIELDS as $field => $cast_type) {
            if (!isset($this->request->post[$field])) {
                $errors[] = 'Missing: ' . $field;
                continue;
            }

            $data_value = $this->request->post[$field];
            settype($data_value, $cast_type);
            $data[$field] = $data_value;
        }

        if ($errors) {
            $response->setError(implode(" <br>\n", $errors));
            return;
        }

        try {
            $price = new Price($this->db, $data);

            $template_data = [
                'price' => $price,
                'price_range_types' => $this->getPriceRangeTypeArray()
            ];

            $response->addData('result', $price->savePrice());
            $response->addData('price_data', $price);
            $response->addData('price_row_html', $this->load->view('extension/shipping/hrx_m/partial/table_price_row', $template_data));
        } catch (\Throwable $th) {
            $response->addData('result', false);
            $response->setError('Failed to save price');
        }
    }

    private function ajaxDeletePrice(AjaxResponse $response)
    {
        $country_code = isset($this->request->post['country_code']) ? $this->request->post['country_code'] : null;

        if (!Price::isCountryCodeValid($country_code)) {
            $response->setError('Invalid coutry code');
            return;
        }

        $response->addData('result', Price::deletePriceStatic($this->db, $country_code));
    }

    private function getParcelDefaultTab()
    {
        $data = $this->mergeTranslationsIntoData([]);

        $data['global_parcel_default'] = ParcelDefault::getGlobalDefault($this->db);

        $data['parcel_default_global_html'] = $this->load->view('extension/shipping/hrx_m/partial/parcel_default_global', $data);

        $data['parcel_default_category_table'] = $this->getParcelDefaultPagePartial(1);

        return $this->load->view('extension/shipping/hrx_m/partial/tab_parcel_default', $data);
    }

    private function ajaxGetParcelDefaultPage(AjaxResponse $response)
    {
        $page = (int) (isset($this->request->post['page']) ? $this->request->post['page'] : 1);

        $response->addData('html', $this->getParcelDefaultPagePartial($page));
    }

    private function getParcelDefaultPagePartial($page)
    {
        if ((int) $page <= 0) {
            $page = 1;
        }

        $page_limit = (int) $this->config->get('config_limit_admin');
        if ($page_limit <= 0) {
            $page_limit = 30;
        }

        $this->load->model('catalog/category');

        // $data = [];

        $filter_data = array(
            'sort'  => 'name',
            'order' => 'ASC',
            'start' => ($page - 1) * $page_limit,
            'limit' => $page_limit
        );

        $total_categories = $this->model_catalog_category->getTotalCategories();
        $total_pages = ceil($total_categories / $page_limit);

        $data = $this->mergeTranslationsIntoData([]);

        $oc_categories = ParcelDefault::addDefaultsIntoOcCategoryData(
            $this->model_catalog_category->getCategories($filter_data),
            $this->db
        );

        $data['category_list'] = [];

        $template_data = $this->mergeTranslationsIntoData([]);
        foreach ($oc_categories as $oc_category) {
            $template_data['oc_category_row'] = $oc_category;
            $data['category_list'][] = $this->load->view('extension/shipping/hrx_m/partial/parcel_default_category_table_row', $template_data);
        }

        $data['parcel_default_pagination'] = $this->getPaginationHtml($page, $total_pages, 'getParcelDefaultPage');

        return $this->load->view('extension/shipping/hrx_m/partial/parcel_default_category_table', $data);
    }

    private function ajaxResetParcelDefault(AjaxResponse $response)
    {
        $category_id = (int) $this->request->post['category_id'];

        if ($category_id <= 0) {
            return;
        }

        if (!ParcelDefault::remove($category_id, $this->db)) {
            $response->setError('Failed to remove default dimmension for category ID ' . $category_id);
            return;
        }

        $this->load->model('catalog/category');

        $oc_category = $this->model_catalog_category->getCategory($category_id);
        $oc_category['hrx_parcel_default'] = null;

        $template_data = $this->mergeTranslationsIntoData([]);
        $template_data['oc_category_row'] = $oc_category;

        $response->addData(
            'html',
            $this->load->view('extension/shipping/hrx_m/partial/parcel_default_category_table_row', $template_data)
        );
    }

    private function ajaxSaveParcelDefault(AjaxResponse $response)
    {
        $parcel_default = new ParcelDefault($this->db);

        $parcel_default->category_id = (int) $this->request->post['category_id'];
        $parcel_default->weight = (float) $this->request->post['weight'];
        $parcel_default->length = (float) $this->request->post['length'];
        $parcel_default->width = (float) $this->request->post['width'];
        $parcel_default->height = (float) $this->request->post['height'];

        $validation = $parcel_default->fieldValidation();
        $response->addData('validation', $validation);
        $response->addData('parcel_default', $parcel_default);

        $is_valid = true;
        foreach ($validation as $validation_result) {
            if (!$validation_result) {
                $is_valid = false;
                break;
            }
        }

        $response->addData('validated', $is_valid);

        if (!$is_valid) {
            $response->addData('save_result', false);
            return;
        }

        $response->addData('save_result', $parcel_default->save());

        $template_data = $this->mergeTranslationsIntoData([]);

        if ($parcel_default->category_id === 0) {
            $template_data['global_parcel_default'] = $parcel_default;
            $response->addData(
                'html',
                $this->load->view('extension/shipping/hrx_m/partial/parcel_default_global', $template_data)
            );
            return;
        }

        $this->load->model('catalog/category');

        $oc_category = $this->model_catalog_category->getCategory($parcel_default->category_id);
        $oc_category['hrx_parcel_default'] = $parcel_default;

        $template_data['oc_category_row'] = $oc_category;
        $response->addData(
            'html',
            $this->load->view('extension/shipping/hrx_m/partial/parcel_default_category_table_row', $template_data)
        );
    }

    private function getBreadcrumbs($function_name = null)
    {
        $result = [
            [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', $this->getUserToken(), true)
            ],
            [
                'text' => $this->language->get(Params::PREFIX . 'text_extension'),
                'href' => $this->url->link(Helper::getExtensionHomeString() . '/extension', $this->getUserToken() . '&type=shipping', true)
            ]
        ];

        // no function name given means last crumb is for main settings
        if ($function_name === null) {
            $result[] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/shipping/' . Params::SETTINGS_CODE, $this->getUserToken(), true)
            );

            return $result;
        }

        $getPageTitle = $function_name . 'GetPageTitle';
        if (!method_exists($this, $getPageTitle)) {
            return $result;
        }

        $result[] = array(
            'text' => $this->$getPageTitle(),
            'href' => $this->url->link('extension/shipping/' . Params::SETTINGS_CODE . '/' . $function_name, $this->getUserToken(), true)
        );

        return $result;
    }

    private function getMijoraCommonJsPath()
    {
        return HTTPS_CATALOG . 'catalog/view/javascript/hrx_m/common.js?20220920';
    }

    private function mergeTranslationsIntoData($data)
    {
        // in opencart 3.0+ translations are merged automatically
        if (version_compare(VERSION, '3.0.0', '>=')) {
            return $data;
        }

        return array_merge($data, $this->hrx_translations);
    }

    /**
     * MANIFEST PAGE
     */
    public function manifestGetPageTitle()
    {
        return $this->language->get(Params::PREFIX . 'manifest_page_title');
    }

    public function manifest()
    {
        $this->hrx_translations = $this->load->language('extension/shipping/' . Params::SETTINGS_CODE);
        $data = $this->mergeTranslationsIntoData([]);

        $this->document->setTitle($this->language->get(Params::PREFIX . 'manifest_page_title'));

        $data['breadcrumbs'] = $this->getBreadcrumbs('manifest');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['mijora_common_js_path'] = $this->getMijoraCommonJsPath();

        $data['partial_manifest_list'] = $this->getManifestPagePartial(1);

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['hrx_m_data'] = [
            'url_ajax' => $this->getAjaxUrl(),
            'default_warehouse' => Warehouse::getDefaultWarehouse($this->db),
            'ts' => [
                'no_default_warehouse' => $this->language->get(Params::PREFIX . 'alert_no_default_warehouse')
            ]
        ];

        $this->response->setOutput($this->load->view('extension/shipping/' . Params::SETTINGS_CODE . '/manifest', $data));
    }

    private function ajaxGetManifestPage(AjaxResponse $response)
    {
        $page = (int) (isset($this->request->post['page']) ? $this->request->post['page'] : 1);

        $response->addData('html', $this->getManifestPagePartial($page));
    }

    private function getManifestPagePartial($page = 1)
    {
        $page_limit = (int) $this->config->get('config_limit_admin');
        if ($page_limit <= 0) {
            $page_limit = 30;
        }

        // default filter values
        $filter = [
            'page' => 1,
            'limit' => $page_limit,
            'filter_order_id' => null,
            'filter_customer' => null,
            'filter_hrx_id' => null,
            'filter_hrx_tracking_num' => null,
            'filter_order_status_id' => null,
            'filter_is_registered' => null,
            'filter_has_manifest' => null,
        ];

        if (isset($this->request->post['page'])) {
            $filter['page'] = (int) $this->request->post['page'];
            if ($filter['page'] < 1) {
                $filter['page'] = 1;
            }
        }

        if (isset($this->request->post['filter_order_id'])) {
            $filter['filter_order_id'] = (int) $this->request->post['filter_order_id'];
            if ($filter['filter_order_id'] < 1) {
                $filter['filter_order_id'] = null;
            }
        }

        if (isset($this->request->post['filter_customer']) && !empty($this->request->post['filter_customer'])) {
            $filter['filter_customer'] = $this->request->post['filter_customer'];
        }

        if (isset($this->request->post['filter_hrx_id']) && !empty($this->request->post['filter_hrx_id'])) {
            $filter['filter_hrx_id'] = $this->request->post['filter_hrx_id'];
        }

        if (isset($this->request->post['filter_hrx_tracking_num']) && !empty($this->request->post['filter_hrx_tracking_num'])) {
            $filter['filter_hrx_tracking_num'] = $this->request->post['filter_hrx_tracking_num'];
        }

        if (isset($this->request->post['filter_order_status_id'])) {
            $filter['filter_order_status_id'] = (int) $this->request->post['filter_order_status_id'];
            if ($filter['filter_order_status_id'] < 1) {
                $filter['filter_order_status_id'] = null;
            }
        }

        if (isset($this->request->post['filter_is_registered'])) {
            $filter['filter_is_registered'] = (int) $this->request->post['filter_is_registered'];
            if ($filter['filter_is_registered'] < 1) {
                $filter['filter_is_registered'] = null;
            }
        }

        if (isset($this->request->post['filter_has_manifest'])) {
            $filter['filter_has_manifest'] = (int) $this->request->post['filter_has_manifest'];
            if ($filter['filter_has_manifest'] < 1) {
                $filter['filter_has_manifest'] = null;
            }
        }

        $id_language = (int) $this->config->get('config_language_id');
        $total = Order::getManifestOrdersTotal($this->db, $filter, $id_language);
        $total_pages = ceil($total / $page_limit);

        if ($total_pages < 1) {
            $total_pages = 1;
        }

        if ($filter['page'] > $total_pages) {
            $filter['page'] = $total_pages;
        }

        $orders = Order::getManifestOrders($this->db, $filter, $id_language);

        $template_data = $this->mergeTranslationsIntoData([]);
        $order_rows = [];
        foreach ($orders as $order) {
            $template_data['order'] = $order;
            $template_data['order_url'] = $this->url->link('sale/order/info', $this->getUserToken(), true);

            $order_rows[] = $this->load->view('extension/shipping/' . Params::SETTINGS_CODE . '/partial/manifest_list_row', $template_data);
        }

        $data = $this->mergeTranslationsIntoData([]);
        
        $data['order_rows'] = $order_rows;
        $data['order_url'] = $this->url->link('sale/order/info', $this->getUserToken(), true);

        $data['manifest_list_pagination'] = $this->getPaginationHtml($filter['page'], $total_pages, 'getManifestPage');

        return $this->load->view('extension/shipping/' . Params::SETTINGS_CODE . '/partial/manifest_list', $data);
    }

    private function ajaxRegisterHrxOrder(AjaxResponse $response)
    {
        $order_id = isset($this->request->post['order_id']) ? $this->request->post['order_id'] : null;
        $is_order_panel = (bool) (isset($this->request->post['is_order_panel']) ? $this->request->post['is_order_panel'] : false);

        if (!$order_id) {
            $response->setError('Order ID required');
            return;
        }

        $id_language = (int) $this->config->get('config_language_id');

        $order = Order::getManifestOrder($this->db, $order_id, $id_language);

        if ($order->getHrxOrderId() && !$order->canRegisterAgain()) {
            $response->setError($this->language->get(Params::PREFIX . 'notify_is_registered'));
            return;
        }

        $shipping_id = $order->getShippingCode(true);

        try {

            $token = $this->config->get(Params::CONFIG_TOKEN);
            $test_mode = (bool) $this->config->get(Params::CONFIG_TEST_MODE);

            $api = new HrxApi($token, $test_mode);

            $this->load->model('sale/order');

            $oc_order = $this->model_sale_order->getOrder($order->getOrderId());

            $delivery_location = $shipping_id === Order::ORDER_TYPE_COURIER ?
                DeliveryCourier::getDeliveryLocationByCountryCode($oc_order['shipping_iso_code_2'], $this->db) :
                DeliveryPoint::getDeliveryPointById($shipping_id, $this->db);

            if (!$delivery_location->country || !$delivery_location->active) {
                throw new Exception($this->language->get(Params::PREFIX . 'exception_delivery_location_unavailable'));
            }

            $default_warehouse = Warehouse::getDefaultWarehouse($this->db);

            $warehouse_id = $default_warehouse->id;
            if ($order->getCustomWarehouseId()) {
                $warehouse_id = Warehouse::getWarehouse($order->getCustomWarehouseId(), $this->db)->id;
            }

            if (!$warehouse_id) {
                throw new Exception($this->language->get(Params::PREFIX . 'notify_warehouse_not_found'));
            }

            // get set parcel box size or if none is set try and predict the size
            $parcel_box = [];
            if ($order->hasValidCustomDimensions()) {
                // get rotated sizes (as parcel would fit destination)
                $parcel_box = $this->getCustomParcelBoxSize($order, $delivery_location, true);
            } else {
                $parcel_box = $this->getPredictedParcelBoxSize($order, $delivery_location);
            }

            // check that box is marked as fitting destination limits
            if (!isset($parcel_box['fits']) || !$parcel_box['fits']) {
                throw new Exception($this->language->get(Params::PREFIX . 'warning_does_not_fit'));
            }

            $min_box_size = $delivery_location->getMinDimensions(false);

            /*** Create order ***/
            $phone = str_replace($delivery_location->getRecipientPhonePrefix(), '', $oc_order['telephone']);
            $response->addData('phone', [$delivery_location->getRecipientPhonePrefix(), $phone]);

            $receiver = new HrxReceiver();

            if ($shipping_id !== Order::ORDER_TYPE_COURIER) {
                $receiver
                    ->setName($order->getCustomer())
                    ->setEmail($oc_order['email'])
                    ->setPhone($phone); // $delivery_location->getRecipientPhoneRegexp()
            } else {
                $receiver
                    ->setName($order->getCustomer()) // Receiver name
                    ->setEmail($oc_order['email']) // Receiver email
                    ->setPhone($phone, $delivery_location->getRecipientPhoneRegexp()) // Phone number without code and a second parameter is for check the phone value according to the regex specified in delivery location information
                    ->setAddress($oc_order['shipping_address_1']) // Receiver address
                    ->setPostcode($oc_order['shipping_postcode']) // Receiver postcode (zip code)
                    ->setCity($oc_order['shipping_city']) // Receiver city
                    ->setCountry($oc_order['shipping_iso_code_2']); // Receiver country code
            }


            $shipment = new HrxShipment();
            $shipment->setReference($order->getOrderId());
            $shipment->setComment($order->getComment());
            $shipment->setLength(max((float) $parcel_box['length'], (float) $min_box_size[ParcelProduct::DIMENSION_LENGTH]));
            $shipment->setWidth(max((float) $parcel_box['width'], (float) $min_box_size[ParcelProduct::DIMENSION_WIDTH]));
            $shipment->setHeight(max((float) $parcel_box['height'], (float) $min_box_size[ParcelProduct::DIMENSION_HEIGHT]));
            $shipment->setWeight(max((float) $parcel_box['weight'], (float) $delivery_location->getMinWeight()));
            // $shipment->setWeight($this->getOrderWeightInKg($order->getOrderId()));

            $hrx_order_obj = new HrxOrder();
            $hrx_order_obj->setPickupLocationId($default_warehouse->id);

            if ($shipping_id !== Order::ORDER_TYPE_COURIER) {
                $hrx_order_obj
                    ->setDeliveryKind(Order::TYPE_DELIVERY_TERMINAL)
                    ->setDeliveryLocation($delivery_location->id);
            } else { // asume its courier
                $hrx_order_obj
                    ->setDeliveryKind(Order::TYPE_DELIVERY_COURIER);
            }

            $hrx_order_obj->setReceiver($receiver);
            $hrx_order_obj->setShipment($shipment);
            $hrx_order_data = $hrx_order_obj->prepareOrderData();

            $response->addData('hrx_order_data', $hrx_order_data);

            $order_response = $api->generateOrder($hrx_order_data);
            // $order_response = [
            //     "id" => "81f32a75-4cf9-4bcf-8536-7eb2836f4d23",
            //     "sender_reference" => "PACK-12345",
            //     "sender_comment" => "Deliver with care",
            //     "tracking_number" => "TRK0099999999",
            //     "tracking_url" => "https://woptest.hrx.eu/public/tracking/TRK0099999999",
            //     "partner_tracking_url" => "https://example.com/track/TRK0099999999",
            //     "recipient_name" => "Jānis Bērziņš",
            //     "recipient_email" => "janis.berzins@example.com",
            //     "recipient_phone" => "+37120000000",
            //     "delivery_location_id" => "81f32a75-4cf9-4bcf-8536-7eb2836f4d23",
            //     "delivery_location_country" => "LV",
            //     "delivery_location_city" => "Rīga",
            //     "delivery_location_zip" => "LV-1050",
            //     "delivery_location_address" => "Stacijas laukums 2",
            //     "pickup_location_id" => "81f32a75-4cf9-4bcf-8536-7eb2836f4d23",
            //     "pickup_location_country" => "LV",
            //     "pickup_location_city" => "Rīga",
            //     "pickup_location_zip" => "LV-1050",
            //     "pickup_location_address" => "Stacijas laukums 2",
            //     "length_cm" => 63.99,
            //     "width_cm" => 37.99,
            //     "height_cm" => 31.99,
            //     "weight_kg" => 19.9999,
            //     "status" => "new",
            //     "can_print_return_label" => true,
            //     "created_at" => "2022-01-01T00:00:00Z"
            // ];
            $hrx_order_id = isset($order_response['id']) ? $order_response['id'] : false;

            if ($hrx_order_id) {
                $order->setHrxOrderData($order_response);
                $order->save();
                $response->addData('order', $order);
                $response->addData('registered', $this->language->get(Params::PREFIX . 'notify_registered_success'));

                if ($is_order_panel) {
                    // when registering from panel it will request data refresh from js
                    return;
                }

                $template_data = $this->mergeTranslationsIntoData([
                    'order' => $order,
                    'order_url' => $this->url->link('sale/order/info', $this->getUserToken(), true)
                ]);

                $response->addData('html', $this->load->view('extension/shipping/' . Params::SETTINGS_CODE . '/partial/manifest_list_row', $template_data));
            }
        } catch (\Throwable $th) {
            $response->setError($th->getMessage());
        }
    }

    private function ajaxGetMultipleLabels(AjaxResponse $response)
    {
        $order_ids = isset($this->request->post['order_id']) ? $this->request->post['order_id'] : [];
        $label_type = isset($this->request->post['label_type']) ? $this->request->post['label_type'] : null;

        if (!$order_ids) {
            $response->setError('Order ID required');
            return;
        }

        if (!$label_type || !in_array($label_type, ['shipment', 'return'])) {
            $response->setError('Bad label type');
            return;
        }

        $id_language = (int) $this->config->get('config_language_id');

        $filter = [
            'page' => 1, // doesnt matter
            'limit' => 1, // doesnt matter
            'filter_order_id' => null,
            'filter_order_ids' => $order_ids,
            'filter_customer' => null,
            'filter_hrx_id' => null,
            'filter_hrx_tracking_num' => null,
            'filter_order_status_id' => null,
            'filter_is_registered' => null,
            'filter_has_manifest' => null,
        ];

        /** @var Order[] */
        $orders = Order::getManifestOrders($this->db, $filter, $id_language, false);

        $errors = [];
        $labels = [];
        foreach ($orders as $order) {
            try {
                $labels[$order->getOrderId()] = $this->getLabelFromApi($order, $label_type);
            } catch (\Throwable $th) {
                $errors[$order->getOrderId()] = $th->getMessage();
            }
        }

        $response->addData('ids', $order_ids);
        $response->addData('orders', $orders);
        $response->addData('errors', $errors);
        $response->addData('labels', $labels);
        $response->addData('type', $label_type);
    }

    private function ajaxGetLabel(AjaxResponse $response)
    {
        $order_id = isset($this->request->post['order_id']) ? $this->request->post['order_id'] : null;
        $label_type = isset($this->request->post['label_type']) ? $this->request->post['label_type'] : null;

        if (!$order_id) {
            $response->setError('Order ID required');
            return;
        }

        if (!$label_type || !in_array($label_type, ['shipment', 'return'])) {
            $response->setError('Bad label type');
            return;
        }

        $id_language = (int) $this->config->get('config_language_id');

        $order = Order::getManifestOrder($this->db, $order_id, $id_language);

        $response->addData('order', $order);

        // needs registration
        if (!$order->getHrxOrderId()) {
            $response->setError($this->language->get(Params::PREFIX . 'error_order_not_registered'));
            return;
        }

        try {
            $token = $this->config->get(Params::CONFIG_TOKEN);
            $test_mode = (bool) $this->config->get(Params::CONFIG_TEST_MODE);

            $api = new HrxApi($token, $test_mode);
            if ($label_type === 'shipment') {
                $response->addData('label', $api->getLabel($order->getHrxOrderId()));
                return;
            }

            $response->addData('label', $api->getReturnLabel($order->getHrxOrderId()));
        } catch (\Throwable $th) {
            $response->setError($th->getMessage());
        }
    }

    private function getLabelFromApi(Order $order, $label_type = 'shipment')
    {
        // needs registration
        if (!$order->getHrxOrderId()) {
            throw new Exception($this->language->get(Params::PREFIX . 'error_order_not_registered'));
        }

        // order canceled
        if ($order->isCancelled()) {
            throw new Exception($this->language->get(Params::PREFIX . 'error_order_canceled'));
        }

        // if label type not shipment, but return label, make sure order has return labels
        if ($label_type !== 'shipment' && !$order->canPrintReturnLabel()) {
            throw new Exception($this->language->get(Params::PREFIX . 'error_order_no_return_label'));
        }

        try {
            $token = $this->config->get(Params::CONFIG_TOKEN);
            $test_mode = (bool) $this->config->get(Params::CONFIG_TEST_MODE);

            $api = new HrxApi($token, $test_mode);
            if ($label_type === 'shipment') {
                return $api->getLabel($order->getHrxOrderId());
            }

            return $api->getReturnLabel($order->getHrxOrderId());
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    private function getOrderWeightInKg($order_id)
    {
        $kg_weight_class_id = (int) Helper::getWeightClassId($this->db);

        $total_order_weight = 0;

        $this->load->model('sale/order');

        $this->load->model('catalog/product');

        $products = $this->model_sale_order->getOrderProducts($order_id);

        foreach ($products as $product) {
            $option_weight = 0;

            $product_info = $this->model_catalog_product->getProduct($product['product_id']);

            if (!$product_info) {
                continue;
            }

            $options = $this->model_sale_order->getOrderOptions($order_id, $product['order_product_id']);

            foreach ($options as $option) {
                if ($option['type'] = 'file') {
                    continue;
                }

                $product_option_value_info = $this->model_catalog_product->getProductOptionValue($product['product_id'], $option['product_option_value_id']);

                if (!empty($product_option_value_info['weight'])) {
                    if ($product_option_value_info['weight_prefix'] == '+') {
                        $option_weight += $product_option_value_info['weight'];
                    } elseif ($product_option_value_info['weight_prefix'] == '-') {
                        $option_weight -= $product_option_value_info['weight'];
                    }
                }
            }

            $weight_in_kg = $this->weight->convert(($product_info['weight'] + (float)$option_weight) * $product['quantity'], $product_info['weight_class_id'], $kg_weight_class_id);

            $total_order_weight += (float) $weight_in_kg;
        }

        return (float) $total_order_weight;
    }

    private function ajaxGetHrxOrderData(AjaxResponse $response)
    {
        $order_id = isset($this->request->post['order_id']) ? $this->request->post['order_id'] : null;
        $is_order_panel = (bool) (isset($this->request->post['is_order_panel']) ? $this->request->post['is_order_panel'] : false);

        if (!$order_id) {
            $response->setError('Order ID required');
            return;
        }

        $id_language = (int) $this->config->get('config_language_id');

        $order = Order::getManifestOrder($this->db, $order_id, $id_language);

        try {
            $this->updateHrxDataFromApi($order);

            if ($is_order_panel) {
                $response->addData('html', $this->getOrderPanelPartial($order_id, $order));
                return;
            }

            $template_data = $this->mergeTranslationsIntoData([
                'order' => $order,
                'order_url' => $this->url->link('sale/order/info', $this->getUserToken(), true)
            ]);

            $response->addData('html', $this->load->view('extension/shipping/' . Params::SETTINGS_CODE . '/partial/manifest_list_row', $template_data));
        } catch (\Throwable $th) {
            $response->setError($th->getMessage());
        }
    }

    private function updateHrxDataFromApi(Order $order)
    {
        $token = $this->config->get(Params::CONFIG_TOKEN);
        $test_mode = (bool) $this->config->get(Params::CONFIG_TEST_MODE);

        // needs registration
        if (!$order->getHrxOrderId()) {
            throw new Exception($this->language->get(Params::PREFIX . 'error_order_not_registered'));
        }

        // order canceled
        if ($order->isCancelled()) {
            throw new Exception($this->language->get(Params::PREFIX . 'error_order_canceled'));
        }

        // can order data be refreshed based on hrx status
        if (!$order->isRefreshable()) {
            throw new Exception($this->language->get(Params::PREFIX . 'error_order_not_refreshable'));
        }

        try {
            $api = new HrxApi($token, $test_mode);
            $order_data = $api->getOrder($order->getHrxOrderId());

            if (isset($order_data['id'])) {
                $order->setHrxOrderData($order_data);
                return $order->save();
            }
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }

        return false;
    }

    private function ajaxRefreshOrdersDataFromApi(AjaxResponse $response)
    {
        $page = (int) (isset($this->request->post['refresh_page']) ? $this->request->post['refresh_page'] : 1);

        if ($page <= 0) {
            $response->setError('No more to refresh');
            return;
        }

        $response->addData('page', $page);

        $id_language = (int) $this->config->get('config_language_id');

        $filter = [
            'page' => $page,
            'limit' => Order::REFRESH_ORDER_DATA_MAX_PER_PAGE,
            'filter_order_id' => null,
            'filter_customer' => null,
            'filter_hrx_id' => null,
            'filter_hrx_tracking_num' => null,
            'filter_order_status_id' => null,
            'filter_is_registered' => 2, // we want only registered orders
            'filter_has_manifest' => null,
        ];

        /** @var Order[] */
        $orders = Order::getManifestOrders($this->db, $filter, $id_language, true);

        // $response->addData('orders', $orders);
        $response->addData('refreshed_count', count($orders));

        $errors = [];
        foreach ($orders as $order) {
            try {
                $this->updateHrxDataFromApi($order);
            } catch (\Throwable $th) {
                $errors[$order->getOrderId()] = $th->getMessage();
            }
        }

        $response->addData('errors', $errors);
    }

    private function ajaxMassChangeHrxOrderState(AjaxResponse $response)
    {
        $order_ids = isset($this->request->post['order_id']) ? $this->request->post['order_id'] : [];
        $state = (bool) (isset($this->request->post['state']) ? $this->request->post['state'] : false);

        if (!$order_ids) {
            $response->setError('Order ID required');
            return;
        }

        $id_language = (int) $this->config->get('config_language_id');

        $filter = [
            'page' => 1, // doesnt matter
            'limit' => 1, // doesnt matter
            'filter_order_id' => null,
            'filter_order_ids' => $order_ids,
            'filter_customer' => null,
            'filter_hrx_id' => null,
            'filter_hrx_tracking_num' => null,
            'filter_order_status_id' => null,
            'filter_is_registered' => null,
            'filter_has_manifest' => null,
        ];

        /** @var Order[] */
        $orders = Order::getManifestOrders($this->db, $filter, $id_language, false);

        $errors = [];
        $state_change = [];
        foreach ($orders as $order) {
            try {
                $state_change[$order->getOrderId()] = $this->changeOrderStateFromApi($order, $state);
            } catch (\Throwable $th) {
                $errors[$order->getOrderId()] = $th->getMessage();
            }
        }

        $response->addData('ids', $order_ids);
        // $response->addData('orders', $orders);
        $response->addData('errors', $errors);
        $response->addData('reload_page', !empty($state_change));
        $response->addData('state', $state);
    }

    private function ajaxChangeHrxOrderState(AjaxResponse $response)
    {
        $state = (bool) (isset($this->request->post['state']) ? $this->request->post['state'] : false);

        $order_id = isset($this->request->post['order_id']) ? $this->request->post['order_id'] : null;
        $is_order_panel = (bool) (isset($this->request->post['is_order_panel']) ? $this->request->post['is_order_panel'] : false);

        if (!$order_id) {
            $response->setError('Order ID required');
            return;
        }

        $id_language = (int) $this->config->get('config_language_id');

        $order = Order::getManifestOrder($this->db, $order_id, $id_language);

        try {
            $this->changeOrderStateFromApi($order, $state);

            if ($is_order_panel) {
                $response->addData('html', $this->getOrderPanelPartial($order_id, $order));
                return;
            }

            $template_data = $this->mergeTranslationsIntoData([
                'order' => $order,
                'order_url' => $this->url->link('sale/order/info', $this->getUserToken(), true)
            ]);

            $response->addData('html', $this->load->view('extension/shipping/' . Params::SETTINGS_CODE . '/partial/manifest_list_row', $template_data));
        } catch (\Throwable $th) {
            $response->setError($th->getMessage());
        }
    }

    private function changeOrderStateFromApi(Order $order, bool $state)
    {
        $token = $this->config->get(Params::CONFIG_TOKEN);
        $test_mode = (bool) $this->config->get(Params::CONFIG_TEST_MODE);

        // needs registration
        if (!$order->getHrxOrderId()) {
            throw new Exception($this->language->get(Params::PREFIX . 'error_order_not_registered'));
        }

        // order canceled
        if ($order->isCancelled()) {
            throw new Exception($this->language->get(Params::PREFIX . 'error_order_canceled'));
        }

        // order cant change status
        if (!$order->canUpdateReadyState()) {
            throw new Exception($this->language->get(Params::PREFIX . 'notify_cant_change_status'));
        }

        // no need to change state if order allready in that state (at this point it can be only new or ready since canUpdateReadyState() validates this)
        if (($state && $order->isReadyForPickup()) || (!$state && !$order->isReadyForPickup())) {
            throw new Exception($this->language->get(Params::PREFIX . 'notify_no_change_status'));
        }

        try {
            $api = new HrxApi($token, $test_mode);

            $order_data = $api->changeOrderReadyState($order->getHrxOrderId(), $state);

            if (isset($order_data['id'])) {
                $order->setHrxOrderData($order_data);
                $order->save();

                return true;
            }
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }

        return false;
    }

    private function ajaxCancelHrxOrder(AjaxResponse $response)
    {
        $order_id = isset($this->request->post['order_id']) ? $this->request->post['order_id'] : null;
        $is_order_panel = (bool) (isset($this->request->post['is_order_panel']) ? $this->request->post['is_order_panel'] : false);

        if (!$order_id) {
            $response->setError('Order ID required');
            return;
        }

        $token = $this->config->get(Params::CONFIG_TOKEN);
        $test_mode = (bool) $this->config->get(Params::CONFIG_TEST_MODE);

        $id_language = (int) $this->config->get('config_language_id');

        $order = Order::getManifestOrder($this->db, $order_id, $id_language);

        if (!$order->canBeCancelled()) {
            $response->setError($this->language->get(Params::PREFIX . 'notify_cant_cancel'));
            return;
        }

        try {
            $api = new HrxApi($token, $test_mode);

            $order_data = $api->cancelOrder($order->getHrxOrderId());

            if (isset($order_data['id'])) {
                $order->setHrxOrderData($order_data);
                $order->save();
            }

            $response->addData('hrx_order', $order_data);

            if ($is_order_panel) {
                $response->addData('html', $this->getOrderPanelPartial($order_id, $order));
                return;
            }

            $template_data = $this->mergeTranslationsIntoData([
                'order' => $order,
                'order_url' => $this->url->link('sale/order/info', $this->getUserToken(), true)
            ]);

            $response->addData('html', $this->load->view('extension/shipping/' . Params::SETTINGS_CODE . '/partial/manifest_list_row', $template_data));
        } catch (\Throwable $th) {
            $response->setError($th->getMessage());
        }
    }

    /**
     * Order Panel
     */
    public function orderInfoPanel($order_data = [])
    {
        if (!isset($order_data['order_id'])) {
            return null;
        }

        $this->hrx_translations = $this->load->language('extension/shipping/hrx_m');
        $data = $this->mergeTranslationsIntoData([]);

        $data['order_data'] = array_filter($order_data, function ($key) {
            return !in_array($key, ['header', 'footer', 'column_left']);
        }, ARRAY_FILTER_USE_KEY);

        $data['mijora_common_js_path'] = $this->getMijoraCommonJsPath();

        $id_language = (int) $this->config->get('config_language_id');
        $hrx_order = Order::getManifestOrder($this->db, $order_data['order_id'], $id_language);
        try {
            $this->updateHrxDataFromApi($hrx_order);
            $data['refresh_result'] = true;
        } catch (\Throwable $th) {
            $data['refresh_result'] = $th->getMessage();
        }

        $data['hrx_order_panel_partial'] = $this->getOrderPanelPartial($order_data['order_id'], $hrx_order);

        $default_warehouse = Warehouse::getDefaultWarehouse($this->db);
        $data['url_ajax'] = $this->getAjaxUrl();
        $data['default_warehouse'] = $default_warehouse;
        $data['order_id'] = $order_data['order_id'];

        return $this->load->view('extension/shipping/hrx_m/order_panel', $data);
    }

    public function getOrderPanelPartial($order_id, ?Order $hrx_order = null)
    {
        $this->hrx_translations = $this->load->language('extension/shipping/hrx_m');
        $data = $this->mergeTranslationsIntoData([]);

        if (!$hrx_order) {
            $id_language = (int) $this->config->get('config_language_id');
            $hrx_order = Order::getManifestOrder($this->db, $order_id, $id_language);
        }
        $data['hrx_order'] = $hrx_order;

        $delivery_point = $this->getFromCache('delivery_point');
        if (!$delivery_point) {
            $delivery_point = $this->getDeliveryPoint($hrx_order);
        }

        $delivery_address = 'Courier';
        if ($hrx_order->getShippingCode(true) !== Order::ORDER_TYPE_COURIER) {
            $delivery_address = $delivery_point->address;
        }

        $data['parcel_dimensions'] = 'predicted';
        if ($hrx_order->hasValidRegisteredDimensions()) {
            $data['parcel_dimensions'] = 'registered';
            $box_size = $this->getRegisteredParcelBoxSize($hrx_order);
        } elseif ($hrx_order->hasValidCustomDimensions()) {
            $data['parcel_dimensions'] = 'saved';
            $box_size = $this->getCustomParcelBoxSize($hrx_order, $delivery_point);
        } else {
            $box_size = $this->getPredictedParcelBoxSize($hrx_order, $delivery_point);
        }

        $box_size['address'] = $delivery_address;

        $data['box_size'] = $box_size;

        $data['delivery_point'] = $delivery_point;

        $data['warehouses'] = Warehouse::getPage(1, Warehouse::ALL_WAREHOUSES, $this->db);
        $default_warehouse = Warehouse::getDefaultWarehouse($this->db);

        $data['default_warehouse'] = $default_warehouse;

        $data['selected_warehouse'] = $default_warehouse->id;
        $data['notify_missing_warehouse'] = null;

        $custom_warehouse_id = $hrx_order->getCustomWarehouseId();

        if ($custom_warehouse_id && !isset($data['warehouses'][$custom_warehouse_id])) {
            $this->language->get('hrx_m_warning_notify_missing_warehouse');
        }

        if ($custom_warehouse_id && isset($data['warehouses'][$custom_warehouse_id])) {
            $data['selected_warehouse'] = $custom_warehouse_id;
        }

        $translation_key = 'hrx_m_panel_order_status_' . $hrx_order->getHrxOrderStatus();
        $translated_string = $this->language->get($translation_key);
        $data['hrx_m_panel_order_status'] = $translation_key === $translated_string ? $hrx_order->getHrxOrderStatus() : $translated_string;

        $data['events_partial'] = $this->load->view(
            'extension/shipping/hrx_m/partial/order_panel_tracking_partial',
            $this->mergeTranslationsIntoData([
                'track_events' => [],
                'is_placeholder' => true
            ])
        );

        return $this->load->view('extension/shipping/hrx_m/partial/order_panel_partial', $data);
    }

    private function getDeliveryPoint(Order $hrx_order)
    {
        $shipping_code = $hrx_order->getShippingCode(true);

        if ($shipping_code === Order::ORDER_TYPE_COURIER) {
            return DeliveryCourier::getDeliveryLocationByCountryCode($hrx_order->getCountryCode(), $this->db);
        }

        return DeliveryPoint::getDeliveryPointById($shipping_code, $this->db);
    }

    private function getRegisteredParcelBoxSize(Order $hrx_order)
    {
        $registered_box_size = $hrx_order->getRegisteredDimensions();
        $registered_box_size['fits'] = true;

        return $registered_box_size;
    }

    private function getCustomParcelBoxSize(Order $hrx_order, DeliveryPointInterface $delivery_point, $rotate_to_fit_destination = false)
    {
        $custom_box_size = $hrx_order->getCustomDimensions();

        $item_list = new ItemList();
        $item = new ParcelItem(
            $custom_box_size['length'],
            $custom_box_size['width'],
            $custom_box_size['height'],
            $custom_box_size['weight'],
            'custom_parcel'
        );
        $item_list->insert($item, 1);

        $packed_box = Helper::getPackedBox($delivery_point, $item_list);

        $custom_box_size['fits'] = $packed_box->getItems()->count() === $item_list->count();

        return $rotate_to_fit_destination === false ? $custom_box_size : [
            'fits' => $custom_box_size['fits'],
            'weight' => $packed_box->getWeight() / 1000,
            'width' => $packed_box->getUsedWidth() / 10,
            'length' => $packed_box->getUsedLength() / 10,
            'height' => $packed_box->getUsedDepth() / 10
        ];
    }

    private function getPredictedParcelBoxSize(Order $hrx_order, DeliveryPointInterface $delivery_point)
    {
        $products = Order::getProductsDataByOrder($hrx_order->getOrderId(), $this->db);
        /** @var ParcelProduct[] */
        $product_dimensions = ParcelDefault::getProductDimmensions($products, $this->db, $this->weight, $this->length);

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

        $packed_box = Helper::getPackedBox($delivery_point, $item_list);

        return [
            'fits' => $packed_box->getItems()->count() === $item_list->count(),
            'weight' => $packed_box->getWeight() / 1000,
            'width' => $packed_box->getUsedWidth() / 10,
            'length' => $packed_box->getUsedLength() / 10,
            'height' => $packed_box->getUsedDepth() / 10
        ];
    }

    private function ajaxEditOrder(AjaxResponse $response)
    {
        $order_id = isset($this->request->post['order_id']) ? $this->request->post['order_id'] : null;

        if (!$order_id) {
            $response->setError('Order ID required');
            return;
        }

        $id_language = (int) $this->config->get('config_language_id');
        $hrx_order = Order::getManifestOrder($this->db, $order_id, $id_language);

        /**
         * Check for changed comment
         */
        $comment = isset($this->request->post['hrx_comment']) ? $this->request->post['hrx_comment'] : null;

        if ($comment && (strlen($comment) > 255 || !preg_match('/^[\d\s\p{L}\_\-]+$/', $comment))) {
            $response->setError($this->language->get('hrx_m_warning_bad_comment'));
        }

        if ($comment !== null) {
            $hrx_order->setCustomHrxData('comment', $comment);
        }
        /**
         * Comment check end
         */

        /**
         * Check for changed warehouse
         */
        $warehouse_id = isset($this->request->post['hrx_warehouse']) ? $this->request->post['hrx_warehouse'] : null;
        if ($warehouse_id) {
            $custom_warehouse = Warehouse::getWarehouse($warehouse_id, $this->db);
            if (!$custom_warehouse->id) {
                $response->setError($this->language->get('hrx_m_warning_warehouse_not_found'));
                return;
            }

            $hrx_order->setCustomHrxData('warehouse_id', $custom_warehouse->id);
        }
        /**
         * Warehouse check end
         */

        /**
         * Parcel size change detection
         */
        $new_parcel_size = [];
        foreach (['width', 'length', 'height', 'weight'] as $key) {
            $new_parcel_size[$key] = (float) (isset($this->request->post['hrx_' . $key]) ? $this->request->post['hrx_' . $key] : null);
        }

        $new_parcel_size = array_filter($new_parcel_size, function ($item) {
            return $item !== null && $item > 0.0;
        });

        if (count($new_parcel_size) !== 4) {
            $response->setError($this->language->get('hrx_m_warning_missing_dimensions'));
            return;
        }

        $delivery_point = $this->getDeliveryPoint($hrx_order);

        $item_list = new ItemList();

        $item = new ParcelItem(
            $new_parcel_size['length'],
            $new_parcel_size['width'],
            $new_parcel_size['height'],
            $new_parcel_size['weight'],
            'custom_parcel'
        );
        $item_list->insert($item, 1);

        $packed_box = Helper::getPackedBox($delivery_point, $item_list);

        if ($packed_box->getItems()->count() !== $item_list->count()) {
            $response->setError($this->language->get('hrx_m_warning_does_not_fit'));
            return;
        }

        $this->setToCache('delivery_point', $delivery_point);

        foreach ($new_parcel_size as $key => $value) {
            $hrx_order->setCustomHrxData($key, $value);
        }
        /**
         * Parcel size change detection end
         */

        try {
            $hrx_order->save();
        } catch (\Throwable $th) {
            $response->setError('Exception: ' . $th->getMessage());
        }

        $response->addData('hrx_order', $hrx_order);
        $response->addData('html', $this->getOrderPanelPartial($order_id, $hrx_order));
    }

    private function ajaxGetHrxTrackingInfo(AjaxResponse $response)
    {
        $order_id = isset($this->request->post['order_id']) ? $this->request->post['order_id'] : null;

        if (!$order_id) {
            $response->setError('Order ID required');
            return;
        }

        $token = $this->config->get(Params::CONFIG_TOKEN);
        $test_mode = (bool) $this->config->get(Params::CONFIG_TEST_MODE);

        $id_language = (int) $this->config->get('config_language_id');

        $order = Order::getManifestOrder($this->db, $order_id, $id_language);

        $token = $this->config->get(Params::CONFIG_TOKEN);
        $test_mode = (bool) $this->config->get(Params::CONFIG_TEST_MODE);

        try {
            $api = new HrxApi($token, $test_mode);
            $tracking_events = $api->getTrackingEvents($order->getHrxOrderId());
            $tracking_events = array_reverse($tracking_events);
            $response->addData('getTrackingEvents', $tracking_events);

            $response->addData(
                'html',
                $this->load->view(
                    'extension/shipping/hrx_m/partial/order_panel_tracking_partial',
                    $this->mergeTranslationsIntoData([
                        'track_events' => $tracking_events,
                        'is_placeholder' => false
                    ])
                )
            );
        } catch (\Throwable $th) {
            $response->setError($th->getMessage());
        }
    }

    private function setToCache($key, $data)
    {
        $this->_cache[$key] = $data;
    }

    private function getFromCache($key)
    {
        return isset($this->_cache[$key]) ? $this->_cache[$key] : null;
    }
}

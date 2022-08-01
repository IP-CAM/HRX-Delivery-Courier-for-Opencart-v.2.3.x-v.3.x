<?php

use HrxApi\API as HrxApi;
use HrxApi\Receiver as HrxReceiver;
use HrxApi\Shipment as HrxShipment;
use HrxApi\Order as HrxOrder;
use Mijora\HrxOpencart\Helper;
use Mijora\HrxOpencart\Model\AjaxResponse;
use Mijora\HrxOpencart\Model\DeliveryPoint;
use Mijora\HrxOpencart\Model\Order;
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

    // private function installCountries()
    // {
    //     $config_key = Params::PREFIX . 'country_last_update';
    //     $last_country_update = $this->config->get($config_key);

    //     if (!$last_country_update) {
    //         $last_country_update = 0;
    //     }

    //     $last_country_update = (int) $last_country_update;

    //     $countries_installed = $this->db->query("
    //         SELECT COUNT(id) as total FROM `" . DB_PREFIX . "omniva_int_m_country` 
    //     ");

    //     $has_countries = true;
    //     if (!$countries_installed->rows || (int) $countries_installed->row['total'] === 0) {
    //         $has_countries = false;
    //     }

    //     // check if need to update
    //     if ($has_countries && time() < $last_country_update) {
    //         return;
    //     }

    //     // if ($countries_installed->rows && (int) $countries_installed->row['total'] > 0) {
    //     //     return;
    //     // }

    //     if (!Helper::$token) {
    //         Helper::setApiStaticToken($this->config);
    //     }

    //     $all_countries = Helper::getCountries(true);

    //     if (empty($all_countries)) {
    //         Helper::saveSettings($this->db, [
    //             $config_key => time() + Params::COUNTRY_CHECK_TIME_RETRY
    //         ]);
    //         return;
    //     }

    //     $this->db->query("
    //         TRUNCATE TABLE " . DB_PREFIX . "omniva_int_m_country
    //     ");

    //     $offset = 0;
    //     while ($slice = array_slice($all_countries, $offset, 50)) {
    //         $offset += 50;

    //         $data = array_map(function ($item) {
    //             return "('" . $item->id . "', '" . $item->code . "', '" . $this->db->escape($item->name) . "', '" . $this->db->escape($item->en_name) . "')";
    //         }, $slice);

    //         $sql = "INSERT INTO `" . DB_PREFIX . "omniva_int_m_country` (`id`, `code`, `name`, `en_name`)
    //             VALUES " . implode(', ', $data);
    //         $this->db->query($sql);
    //     }

    //     Helper::saveSettings($this->db, [
    //         $config_key => time() + Params::COUNTRY_CHECK_TIME
    //     ]);

    //     $this->session->data['success'] = 'API Countries updated';
    // }

    public function index()
    {
        $this->load->language('extension/shipping/hrx_m');

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


            // if (isset($this->request->post['sender_settings_update'])) {
            //     unset($this->request->post['sender_settings_update']);
            //     $this->saveSettings($this->request->post);
            //     $this->session->data['success'] = $this->language->get(Params::PREFIX . 'msg_setting_saved');
            //     $current_tab = 'sender-info';
            // }

            $this->response->redirect($this->url->link('extension/shipping/' . Params::SETTINGS_CODE, $this->getUserToken() . '&tab=' . $current_tab, true));
        }

        $data[Params::PREFIX . 'version'] = Params::VERSION;

        // // set static tokens
        // Helper::setApiStaticToken($this->config);

        // // update API coutry list if needed
        // $this->installCountries();

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

        // $data['cancel'] = $this->url->link(Helper::getExtensionHomeString() . '/extension', $this->getUserToken() . '&type=shipping', true);

        // // $data['cod_options'] = $this->loadPaymentOptions();

        $this->load->model('localisation/tax_class');

        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $data['ajax_url'] = 'index.php?route=extension/shipping/' . Params::SETTINGS_CODE . '/ajax&' . $this->getUserToken();

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
            'tax_class_id', 'geo_zone_id',
            // api tab
            'api_token', 'api_test_mode',
            // // sender-info tab
            // 'sender_name', 'sender_street', 'sender_postcode',
            // 'sender_city', 'sender_country', 'sender_phone', 'sender_email',
        ];

        foreach ($module_settings as $key) {
            if (isset($this->request->post[Params::PREFIX . $key])) {
                $data[Params::PREFIX . $key] = $this->request->post[Params::PREFIX . $key];
                continue;
            }

            $data[Params::PREFIX . $key] = $this->config->get(Params::PREFIX . $key);
        }

        $partial_tab_general = $this->load->view('extension/shipping/hrx_m/partial/tab_general', $data);
        $partial_tab_api = $this->load->view('extension/shipping/hrx_m/partial/tab_api', $data);

        $data['default_warehouse'] = Warehouse::getDefaultWarehouse($this->db);
        $partial_tab_warehouse = $this->getWarehousePagePartial(1, $data['default_warehouse']);

        $data['sync_warehouse_per_page'] = Params::SYNC_WAREHOUSE_PER_PAGE;
        $data['sync_delivery_points_per_page'] = Params::SYNC_DELIVERY_POINTS_PER_PAGE;

        // $data['delivery_points'] = [
        //     DeliveryPoint::getDeliveryPointById('000ea774-6e17-4112-8a74-251585dd917e', $this->db)
        // ];
        // var_dump(DeliveryPoint::getDeliveryPointById('000ea774-6e17-4112-8a74-251585dd917e', $this->db));
        // $data['delivery_points'] = DeliveryPoint::getPage(1, 30, $this->db);
        // $total_pages = ceil(DeliveryPoint::getTotalPoints($this->db, false) / 30);
        // $data['delivery_points_pagination'] = $this->getPaginationHtml(1, $total_pages, 'getDeliveryPointsPage');
        $partial_tab_delivery_point = $this->getDeliveryPointsPagePartial(1);
        // $this->load->view('extension/shipping/hrx_m/partial/tab_delivery', $data);

        $partial_tab_prices = $this->getPricesPagePartial(1);


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

        // $pd_categories_data = $this->getPdCategories();

        // $data = array_merge($data, $pd_categories_data);

        $data[Params::PREFIX . 'xml_check'] = Helper::isModificationNewer();
        $data[Params::PREFIX . 'xml_fix_url'] = $this->url->link('extension/shipping/' . Params::SETTINGS_CODE, $this->getUserToken() . '&fixxml', true);

        // // $data['required_settings_set'] = $this->isRequiredSettingsSet($data);

        // // Load dynamic strings
        // $data['dynamic_strings'] = $this->getDynamicStrings();

        // $partial_data = [
        //     'omniva_int_options' => ShippingOption::getShippingOptions($this->db),
        //     'dynamic_strings' => $data['dynamic_strings']
        // ];
        // $data['omniva_int_shipping_options'] = $this->load->view('extension/shipping/omniva_int_m/shipping_options_partial', $partial_data);

        // $data['services'] = Helper::getServices($this->session);

        // $data['countries'] = Country::getAllCountries($this->db);

        // $data['sender_tab_partial'] = $this->load->view('extension/shipping/omniva_int_m/sender_tab_partial', $data);

        // $data['price_types'] = [];
        // foreach (Offer::OFFER_PRICE_AVAILABLE as $type) {
        //     $data['price_types'][$type] = $this->language->get(Offer::getOfferPriceTranslationString($type));
        // }

        // $data['price_type_addons'] = Offer::OFFER_PRICE_ADDONS;

        // $data['priority_types'] = [];
        // foreach (Offer::OFFER_PRIORITY_AVAILABLE as $type) {
        //     $data['priority_types'][$type] = $this->language->get(Offer::getOfferPriorityTranslationString($type));
        // }

        // $data['shipping_types'] = [];
        // foreach (Service::TYPE_AVAILABLE as $type) {
        //     $data['shipping_types'][$type] = $this->language->get(Service::getTypeTranslationString($type));
        // }

        // $data['services_list'] = [];

        // foreach ($data['services'] as $service) {
        //     $data['services_list'][$service->get(Service::SERVICE_CODE)] = [
        //         'name' => $service->get(Service::NAME),
        //         'shippingType' => empty($service->get(Service::PARCEL_TERMINAL_TYPE)) ? Service::TYPE_COURIER : Service::TYPE_TERMINAL
        //     ];
        // }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['partial_tab_general'] = $partial_tab_general;
        $data['partial_tab_api'] = $partial_tab_api;
        $data['partial_tab_warehouse'] = $partial_tab_warehouse;
        $data['partial_tab_delivery_point'] = $partial_tab_delivery_point;
        $data['partial_tab_prices'] = $partial_tab_prices;

        $data['mijora_common_js_path'] = $this->getMijoraCommonJsPath();

        $this->response->setOutput($this->load->view('extension/shipping/hrx_m/settings', $data));
    }

    // protected function isRequiredSettingsSet($data)
    // {
    //     $required = [
    //         // api tab
    //         'api_user', 'api_pass',
    //         // sender-info tab
    //         'sender_name', 'sender_street', 'sender_postcode',
    //         'sender_city', 'sender_country', 'sender_phone', 'sender_email'
    //     ];

    //     foreach ($required as $key) {
    //         if (!isset($data[Params::PREFIX . $key]) || empty($data[Params::PREFIX . $key])) {
    //             return false;
    //         }
    //     }

    //     return true;
    // }

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

    // protected function getCronUrl()
    // {
    //     $secret = $this->config->get(Params::PREFIX . 'cron_secret');
    //     if (!$secret) { // first time create a secret
    //         $secret = uniqid();
    //         $this->saveSettings(array(Params::PREFIX . 'cron_secret' => $secret));
    //     }

    //     return HTTPS_CATALOG . 'index.php?route=extension/module/omniva_int_m/ajax&action=terminalUpdate&secret=' . $secret;
    // }

    protected function saveSettings($data)
    {
        Helper::saveSettings($this->db, $data);
    }

    // protected function loadPaymentOptions()
    // {
    //     $result = array();

    //     if (version_compare(VERSION, '3.0.0', '>=')) {
    //         $this->load->model('setting/extension');
    //         $payments = $this->model_setting_extension->getInstalled('payment');
    //     } else {
    //         $this->load->model('extension/extension');
    //         $payments = $this->model_extension_extension->getInstalled('payment');
    //     }

    //     foreach ($payments as $payment) {
    //         $this->load->language('extension/payment/' . $payment);
    //         $result[$payment] = $this->language->get('heading_title');
    //     }

    //     return $result;
    // }

    /**
     * Converts certain settings that comes as array into string
     */
    protected function prepPostData()
    {
        // when no checkboxes is selected post doesnt send it, make sure settings is updated correctly
        // if (isset($this->request->post['cod_settings_update'])) {
        //     $post_cod_options = [];
        //     if (isset($this->request->post[Params::PREFIX . 'cod_options'])) {
        //         $post_cod_options = $this->request->post[Params::PREFIX . 'cod_options'];
        //     }
        //     $this->request->post[Params::PREFIX . 'cod_options'] = json_encode($post_cod_options);
        // }

        // // we want to json_encode email template for better storage into settings
        // if (isset($this->request->post[Params::PREFIX . 'tracking_email_template'])) {
        //     $this->request->post[Params::PREFIX . 'tracking_email_template'] = json_encode($this->request->post[Params::PREFIX . 'tracking_email_template']);
        // }

        // // Opencart 3 expects status to be shipping_omniva_int_m_status
        if (version_compare(VERSION, '3.0.0', '>=') && isset($this->request->post[Params::PREFIX . 'status'])) {
            $this->request->post['shipping_' . Params::PREFIX . 'status'] = $this->request->post[Params::PREFIX . 'status'];
            unset($this->request->post[Params::PREFIX . 'status']);
        }

        // Opencart 3 expects sort_order to be shipping_omniva_int_m_sort_order
        if (version_compare(VERSION, '3.0.0', '>=') && isset($this->request->post[Params::PREFIX . 'sort_order'])) {
            $this->request->post['shipping_' . Params::PREFIX . 'sort_order'] = $this->request->post[Params::PREFIX . 'sort_order'];
            unset($this->request->post[Params::PREFIX . 'sort_order']);
        }
    }

    // protected function hasAccess()
    // {
    //     // if (!$this->user->hasPermission('modify', 'sale/order')) {
    //     //     $this->error['warning'] = $this->language->get('error_permission');
    //     // }

    //     // return !$this->error;
    // }

    // private function getDynamicStrings()
    // {
    //     $strings = [];

    //     foreach (Service::TYPE_AVAILABLE as $service_type) {
    //         $service_type_key = Service::getTypeTranslationString($service_type);
    //         $strings[$service_type_key] = $this->language->get($service_type_key);
    //     }

    //     foreach (Offer::OFFER_PRIORITY_AVAILABLE as $priority_type) {
    //         $priority_type_key = Offer::getOfferPriorityTranslationString($priority_type);
    //         $strings[$priority_type_key] = $this->language->get($priority_type_key);
    //     }

    //     foreach (Offer::OFFER_PRICE_AVAILABLE as $price_type) {
    //         $price_type_key = Offer::getOfferPriceTranslationString($price_type);
    //         $strings[$price_type_key] = $this->language->get($price_type_key);
    //     }

    //     return $strings;
    // }

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
            'ajax_url' => 'index.php?route=extension/shipping/' . Params::SETTINGS_CODE . '/ajax&' . $this->getUserToken()
        ];
    }

    public function getOrderListJsTranslations()
    {
        $this->load->language('extension/shipping/' . Params::SETTINGS_CODE);

        $strings = [
            'filter_label_hrx_only', 'filter_option_yes', 'filter_option_no'
            // 'order_saved', 'order_not_saved', 'bad_response', 'label_registered', 'no_data_changes',
            // 'confirm_new_label', 'refresh_now_btn', 'btn_no', 'btn_yes', 'tooltip_btn_print_register',
            // 'tooltip_btn_call_courier', 'confirm_call_courier', 'alert_no_orders', 'confirm_print_labels',
            // 'alert_response_error', 'alert_no_pdf', 'alert_bad_response', 'notify_courrier_called',
            // 'notify_courrier_call_failed', 'option_yes', 'option_no', 'tooltip_btn_manifest',
            // 'filter_label_omniva_only', 'filter_label_has_label', 'filter_label_in_manifest',
            // 'no_results', 'confirm_create_manifest'
        ];

        $translations = [];

        foreach ($strings as $string) {
            $translations[$string] = $this->language->get(Params::PREFIX . 'js_' . $string);
        }

        return $translations;
    }

    public function ajax()
    {
        $this->response->addHeader('Content-Type: application/json');

        $this->load->language('extension/shipping/hrx_m');

        $response = new AjaxResponse();
        if (!$this->validate()) {
            $response->setError(implode(" \n", $this->error));
            $this->response->setOutput(json_encode($response));
            exit();
        }

        switch ($_GET['action']) {
            case 'testToken':
                $this->testToken($response);
                break;
            case 'syncWarehouse':
                $this->syncWarehouse($response);
                break;
            case 'getWarehousePage':
                $this->getWarehousePage($response);
                break;
            case 'setDefaultWarehouse':
                $this->setDefaultWarehouse($response);
                break;
            case 'syncDeliveryPoints':
                $this->syncDeliveryPoints($response);
                break;
            case 'getDeliveryPointsPage':
                $this->getDeliveryPointsPage($response);
                break;
            case 'savePrice':
                $response->addData('action', 'savePrice');
                $this->savePrice($response);
                break;
            case 'deletePrice':
                $response->addData('action', 'deletePrice');
                $this->deletePrice($response);
                break;
                // MANIFEST PAGE
            case 'getManifestPage':
                $response->addData('action', 'getManifestPage');
                $this->getManifestPage($response);
                break;
            case 'registerHrxOrder':
                $response->addData('action', 'registerHrxOrder');
                $this->registerHrxOrder($response);
                break;
            case 'getLabel':
                $response->addData('action', 'getLabel');
                $this->getLabel($response);
                break;

            default:
                $response->setError('Restricted');
                // die(json_encode($response));
                break;
        }

        $this->response->setOutput(json_encode($response));
    }

    private function testToken(AjaxResponse $response)
    {
        $token = isset($this->request->post['hrx_tokent']) ? $this->request->post['hrx_tokent'] : '';
        $test_mode = (bool) (isset($this->request->post['hrx_test_mode']) ? $this->request->post['hrx_test_mode'] : false);

        $response
            ->addData('hrx_token', $token)
            ->addData('hrx_test_mode', $test_mode)
            ->addData('token', Helper::checkToken($token, $test_mode, false));
    }

    private function syncWarehouse(AjaxResponse $response)
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

    private function getWarehousePage(AjaxResponse $response)
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

        $data = [];

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

    private function setDefaultWarehouse(AjaxResponse $response)
    {
        $id = isset($this->request->post['warehouse_id']) ? $this->request->post['warehouse_id'] : null;

        if (empty($id)) {
            $response->setError('Warehouse ID required');
            return;
        }

        $response->addData('defaultWarehouse', Warehouse::setDefaultWarehouse($id, $this->db));
    }

    private function syncDeliveryPoints(AjaxResponse $response)
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

    private function getDeliveryPointsPage(AjaxResponse $response)
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

        $data = [];
        $data['delivery_points'] = DeliveryPoint::getPage($page, $page_limit, $this->db);

        $total_pages = ceil(DeliveryPoint::getTotalPoints($this->db, false) / $page_limit);

        $data['delivery_points_pagination'] = $this->getPaginationHtml($page, $total_pages, 'getDeliveryPointsPage');
        $data['delivery_points_last_update'] = $this->config->get(Params::CONFIG_DELIVERY_POINTS_LAST_UPDATE);

        return $this->load->view('extension/shipping/hrx_m/partial/tab_delivery', $data);
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
        // $country = isset($this->request->post['country_code']) ? $this->request->post['country_code'] : null;
        // $country = isset($this->request->post['country']) ? $this->request->post['country'] : null;
        // $price = isset($this->request->post['price']) ? $this->request->post['price'] : null;
        // $price_range_type = isset($this->request->post['price_range_type']) ? $this->request->post['price_range_type'] : null;

        // if (empty($country) || empty($price) || $price_range_type === null) {
        //     $response->setError('Country, price or range of prices and price range type are required');
        //     return;
        // }

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

            $response->addData('result', $price->savePrice());
            $response->addData('price_data', $price);
            $response->addData('price_row_html', $this->load->view('extension/shipping/hrx_m/partial/table_price_row', [
                'price' => $price,
                'price_range_types' => $this->getPriceRangeTypeArray()
            ]));
        } catch (\Throwable $th) {
            $response->addData('result', false);
            $response->setError('Failed to save price');
        }
    }

    private function deletePrice(AjaxResponse $response)
    {
        $country_code = isset($this->request->post['country_code']) ? $this->request->post['country_code'] : null;

        if (!Price::isCountryCodeValid($country_code)) {
            $response->setError('Invalid coutry code');
            return;
        }

        $response->addData('result', Price::deletePriceStatic($this->db, $country_code));
    }

    // private function resetPdCategory()
    // {
    //     $result = [
    //         'post' => $this->request->post,
    //         'update_result' => false
    //     ];

    //     if (!isset($this->request->post['category_id'])) {
    //         $result['error'] = 'No category ID given';
    //         return $result;
    //     }

    //     $category_id = (int) $this->request->post['category_id'];

    //     if ($category_id <= 0) {
    //         $result['error'] = 'Category ID must be > 0';
    //         return $result;
    //     }

    //     $result['update_result'] = ParcelDefault::remove($category_id, $this->db);

    //     return $result;
    // }

    // private function savePdCategory()
    // {
    //     $result = [
    //         'post' => $this->request->post,
    //         'update_result' => false
    //     ];

    //     ParcelDefault::$db = $this->db;
    //     $parcel_default = new ParcelDefault();

    //     $parcel_default->category_id = (int) $this->request->post['category_id'];
    //     $parcel_default->weight = (float) $this->request->post['weight'];
    //     $parcel_default->length = (float) $this->request->post['length'];
    //     $parcel_default->width = (float) $this->request->post['width'];
    //     $parcel_default->height = (float) $this->request->post['height'];
    //     $parcel_default->hs_code = $this->request->post['hs_code'];

    //     $result['validation'] = $parcel_default->fieldValidation();
    //     $result['parcel_default'] = $parcel_default;

    //     $is_valid = true;
    //     foreach ($result['validation'] as $validation_result) {
    //         if (!$validation_result) {
    //             $is_valid = false;
    //             break;
    //         }
    //     }

    //     if ($is_valid) {
    //         $result['update_result'] = $parcel_default->save();
    //     }

    //     return $result;
    // }

    // private function getPdCategories($page = 1)
    // {
    //     $data = [];

    //     $page_limit = (int) $this->config->get('config_limit_admin');
    //     $this->load->model('catalog/category');
    //     $filter_data = array(
    //         'sort'  => 'name',
    //         'order' => 'ASC',
    //         'start' => ($page - 1) * $page_limit,
    //         'limit' => $page_limit
    //     );

    //     $partial_data = [];
    //     $partial_data['omniva_int_m_categories'] = $this->model_catalog_category->getCategories($filter_data);

    //     $this->associateWithParcelDefault($partial_data['omniva_int_m_categories']);

    //     $data['global_parcel_default'] = ParcelDefault::getGlobalDefault($this->db);
    //     $data['pd_categories_partial'] = $this->load->view(
    //         'extension/shipping/omniva_int_m/pd_categories_partial',
    //         $partial_data
    //     );

    //     $category_total = $this->model_catalog_category->getTotalCategories();
    //     $data['pd_categories_paginator'] = $this->getPagination($page, ceil($category_total / $page_limit));

    //     return $data;
    // }

    // private function associateWithParcelDefault(&$categories)
    // {
    //     $categories_id = array_map(function ($item) {
    //         return $item['category_id'];
    //     }, $categories);

    //     $parcel_defaults = ParcelDefault::getMultipleParcelDefault($categories_id, $this->db);

    //     $categories = array_map(function ($item) use ($parcel_defaults) {
    //         $item['default_data'] = [];
    //         if (isset($parcel_defaults[$item['category_id']])) {
    //             $item['default_data'] = $parcel_defaults[$item['category_id']];
    //         }
    //         return $item;
    //     }, $categories);
    // }

    // private function getOrderPanel()
    // {
    //     $result = [];
    //     if (!isset($this->request->post['order_id'])) {
    //         $result['error'] = 'Missing Order ID';
    //         return $result;
    //     }

    //     $order_id =  (int) $this->request->post['order_id'];

    //     $data = [];

    //     $data['order_id'] = $order_id;

    //     $order_data = Offer::getOrderOffer($order_id, $this->db);

    //     if (empty($order_data)) {
    //         return [
    //             'error' => 'Omniva International: Order has no offers associated'
    //         ];
    //     }

    //     $data['offer'] = Helper::base64Decode($order_data['offer_data'], false);
    //     $data['is_terminal'] = (bool) $data['offer']['parcel_terminal_type'];

    //     $data['terminal_data'] = Helper::base64Decode($order_data['terminal_data'], false);
    //     $data['terminal_id'] = $order_data['terminal_id'];

    //     $data['api_url'] = (bool) $this->config->get(Params::PREFIX . 'api_test_mode') ? Params::API_URL_TEST : Params::API_URL;

    //     $this->load->model('sale/order');
    //     $order_products =  $this->model_sale_order->getOrderProducts($order_id);
    //     $products_data = ParcelCtrl::getProductsDataByOrder($order_id, $this->db);

    //     foreach ($order_products as $key => $product) {
    //         $product = array_merge($product, $products_data[$product['product_id']]);
    //         $order_products[$key] = $product;
    //     }

    //     $data['products'] = $order_products;
    //     $data['order'] = $this->model_sale_order->getOrder($order_id);

    //     $data['parcels'] = ParcelCtrl::makeParcelsFromCart($order_products, $this->db, $this->weight, $this->length);
    //     $country = new Country($data['order']['shipping_iso_code_2'], $this->db);
    //     $data['items'] = ParcelCtrl::makeItemsFromProducts($order_products, $country);

    //     $data['shipment_status'] = 'Shipment has yet to be registered';

    //     $sql = $this->db->query("
    //         SELECT api_cart_id, api_shipment_id FROM " . DB_PREFIX . "omniva_int_m_order_api 
    //         WHERE order_id = " . (int) $order_id . " AND canceled = 0
    //         ORDER BY order_id DESC 
    //         LIMIT 1
    //     ");

    //     $data['api_data'] = false;
    //     if ($sql->rows) {
    //         $data['api_data'] = [
    //             'manifest_id' => $sql->row['api_cart_id'],
    //             'shipment_id' => $sql->row['api_shipment_id']
    //         ];

    //         $data['shipment_status'] = 'Registered. Generating label';
    //     }

    //     // $data['next_manifest'] = null;
    //     try {
    //         // $token = $this->config->get(Params::PREFIX . 'api_token');
    //         // $test_mode = $this->config->get(Params::PREFIX . 'api_test_mode');

    //         Helper::setApiStaticToken($this->config);
    //         $api = Helper::getApiInstance(); //new API($token, $test_mode);

    //         $this->load->model('sale/order');

    //         $order_products =  $this->model_sale_order->getOrderProducts($order_id);
    //         $products_data = ParcelCtrl::getProductsDataByOrder($order_id, $this->db);

    //         foreach ($order_products as $key => $product) {
    //             $product = array_merge($product, $products_data[$product['product_id']]);
    //             $order_products[$key] = $product;
    //         }

    //         $data['parcels'] = ParcelCtrl::makeParcelsFromCart($order_products, $this->db, $this->weight, $this->length);

    //         if ($data['api_data']) {
    //             try {
    //                 $result['label_response'] = $api->getLabel($data['api_data']['shipment_id']);
    //                 // $result['label_response'] = $api->getLabel('INT0330371805'); 
    //                 $data['label_status'] = $result['label_response'];
    //                 if (isset($result['label_response']->base64pdf) && $result['label_response']->base64pdf) {
    //                     $data['shipment_status'] = 'Registered. Label generated';
    //                 }
    //             } catch (\Exception $e) {
    //                 $result['label_response'] = $e->getMessage();
    //                 $data['label_status'] = null;
    //             }
    //             try {
    //                 $result['track_response'] = $api->trackOrder($data['api_data']['shipment_id']);
    //             } catch (\Exception $e) {
    //                 $result['track_response'] = $e->getMessage();
    //             }
    //         }
    //     } catch (\Throwable $th) {
    //         return [
    //             'error' => 'An error occured while trying to generate Omniva International panel: ' . $th->getMessage()
    //         ];
    //     } catch (\Exception $th) {
    //         return [
    //             'error' => 'An error occured while trying to generate Omniva International panel: ' . $th->getMessage()
    //         ];
    //     }

    //     $result['panelHtml'] = $this->load->view('extension/shipping/omniva_int_m/order_panel', $data);
    //     return $result;
    // }

    // private function registerShipment()
    // {
    //     $result = [];
    //     if (!isset($this->request->post['order_id'])) {
    //         $result['error'] = 'Missing Order ID';
    //         return $result;
    //     }

    //     $order_id =  (int) $this->request->post['order_id'];

    //     $order_offer = Offer::getOrderOffer($order_id, $this->db);

    //     if (empty($order_offer)) {
    //         return [
    //             'error' => 'Failed to load order offer information from database'
    //         ];
    //     }

    //     try {
    //         $service_code = $order_offer['selected_service'];
    //         $offer_data = Helper::base64Decode($order_offer['offer_data'], false);
    //         $terminal_data = null;
    //         if ($offer_data['parcel_terminal_type']) {
    //             $terminal_data = Helper::base64Decode($order_offer['terminal_data'], false);
    //         }

    //         $this->load->model('sale/order');
    //         $order =  $this->model_sale_order->getOrder($order_id);
    //         $order_products =  $this->model_sale_order->getOrderProducts($order_id);

    //         $country = new Country($order['shipping_iso_code_2'], $this->db);

    //         $products_data = ParcelCtrl::getProductsDataByOrder($order_id, $this->db);

    //         foreach ($order_products as $key => $product) {
    //             $product = array_merge($product, $products_data[$product['product_id']]);
    //             $order_products[$key] = $product;
    //         }

    //         // HS Code for now using global default
    //         $parcel_default = ParcelDefault::getGlobalDefault($this->db);
    //         $order['omniva_int_m_hs_code'] = $parcel_default->hs_code;


    //         $parcels = ParcelCtrl::makeParcelsFromCart($order_products, $this->db, $this->weight, $this->length);
    //         $items = ParcelCtrl::makeItemsFromProducts($order_products, $country);

    //         $option_id = str_replace('omniva_int_m.', '', $order['shipping_code']);
    //         $option_id = explode('_', $option_id)[1];

    //         $shipping_option = ShippingOption::getShippingOption($option_id, $this->db, false);

    //         if (!$shipping_option) {
    //             throw new \Exception("Omniva International shipping option ID $option_id no longer available", 1);
    //         }

    //         $api_order = new Order();
    //         $api_order
    //             ->setServiceCode($service_code)
    //             ->setSender(Helper::getSender($this->config, $this->db))
    //             ->setReceiver(Helper::getReceiver($order, $country, $shipping_option->type, $terminal_data))
    //             ->setReference($order_id)
    //             ->setParcels($parcels)
    //             ->setItems($items);

    //         // $token = $this->config->get(Params::PREFIX . 'api_token');
    //         // $test_mode = $this->config->get(Params::PREFIX . 'api_test_mode');

    //         Helper::setApiStaticToken($this->config);
    //         $api = Helper::getApiInstance();
    //         // $api = new API($token, $test_mode);
    //         $response = $api->generateOrder($api_order);

    //         if (!$response || !isset($response->cart_id) || !isset($response->shipment_id)) {
    //             throw new Exception("Failed to receive response from API", 1);
    //         }

    //         $created_at = DateTime::createFromFormat("Y-m-d\TH:i:s.uP", $response->created_at);

    //         $this->db->query("
    //             INSERT INTO " . DB_PREFIX . "omniva_int_m_order_api
    //             (order_id, api_cart_id, api_shipment_id, created_at)
    //             VALUES (" . (int) $order_id . ", '" . $response->cart_id . "', '" . $response->shipment_id . "', '" . $created_at->format('Y-m-d H:i:s') . "')
    //         ");
    //     } catch (\Exception $th) {
    //         return [
    //             'error' => $th->getMessage()
    //         ];
    //     }

    //     $data['parcels'] = $parcels;
    //     $data['api_data'] = [
    //         'shipment_id' => $response->shipment_id,
    //         'manifest_id' => $response->cart_id
    //     ];
    //     $data['shipment_status'] = 'Registered. Generating label';

    //     return [
    //         // 'panelHtml' => $this->load->view('extension/shipping/omniva_int_m/order_panel', $data),
    //         'api_order' => json_decode($api_order->returnJson()),
    //         'response' => $response
    //     ];
    // }

    // private function getLabel()
    // {
    //     $result = [];
    //     if (!isset($this->request->post['order_id'])) {
    //         $result['error'] = 'Missing Order ID';
    //         return $result;
    //     }

    //     $order_id =  (int) $this->request->post['order_id'];

    //     try {
    //         $sql = $this->db->query("
    //             SELECT api_cart_id, api_shipment_id FROM " . DB_PREFIX . "omniva_int_m_order_api 
    //             WHERE order_id = " . (int) $order_id . " AND canceled = 0
    //             ORDER BY order_id DESC 
    //             LIMIT 1
    //         ");

    //         if (!$sql->rows) {
    //             return [
    //                 'error' => 'Order hasnt been registered yet'
    //             ];
    //         }

    //         $shipment_id = $sql->row['api_shipment_id'];
    //         $cart_id = $sql->row['api_cart_id'];

    //         // $token = $this->config->get(Params::PREFIX . 'api_token');
    //         // $test_mode = $this->config->get(Params::PREFIX . 'api_test_mode');
    //         // $shipment_id = 'INT0330371805';
    //         Helper::setApiStaticToken($this->config);
    //         $api = Helper::getApiInstance();
    //         // $api = new API($token, $test_mode);
    //         $response = $api->getLabel($shipment_id);
    //     } catch (\Exception $th) {
    //         return [
    //             'error' => $th->getMessage()
    //         ];
    //     }

    //     return [
    //         'manifest_id' => $cart_id,
    //         'shipment_id' => $shipment_id,
    //         'response' => $response
    //     ];
    // }

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
        return HTTPS_CATALOG . 'catalog/view/javascript/hrx_m/common.js?20220730';
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
        $this->load->language('extension/shipping/' . Params::SETTINGS_CODE);

        $this->document->setTitle($this->language->get(Params::PREFIX . 'manifest_page_title'));

        $data['breadcrumbs'] = $this->getBreadcrumbs('manifest');

        // $data['breadcrumbs'][] = array(
        //     'text' => $this->language->get(Params::PREFIX . 'manifest_page_title'),
        //     'href' => $this->url->link('extension/shipping/omniva_int_m/manifest', $this->getUserToken(), true)
        // );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['mijora_common_js_path'] = $this->getMijoraCommonJsPath();

        // $data['manifests_partial'] = $this->getManifestsPartial();

        $data['partial_manifest_list'] = $this->getManifestPagePartial(1);

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['hrx_m_data'] = [
            'url_ajax' => 'index.php?route=extension/shipping/' . Params::SETTINGS_CODE . '/ajax&' . $this->getUserToken(),
            'default_warehouse' => Warehouse::getDefaultWarehouse($this->db),
            'ts' => [
                'no_default_warehouse' => $this->language->get(Params::PREFIX . 'alert_no_default_warehouse')
            ]
        ];

        $this->response->setOutput($this->load->view('extension/shipping/' . Params::SETTINGS_CODE . '/manifest', $data));
    }

    private function getManifestPage(AjaxResponse $response)
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

        $order_rows = [];
        foreach ($orders as $order) {
            $order_rows[] = $this->load->view('extension/shipping/' . Params::SETTINGS_CODE . '/partial/manifest_list_row', [
                'order' => $order,
                'order_url' => $this->url->link('sale/order/info', $this->getUserToken(), true)
            ]);
        }

        $data = [
            'order_rows' => $order_rows,
            'order_url' => $this->url->link('sale/order/info', $this->getUserToken(), true)
        ];

        $data['manifest_list_pagination'] = $this->getPaginationHtml($filter['page'], $total_pages, 'getManifestPage');

        return $this->load->view('extension/shipping/' . Params::SETTINGS_CODE . '/partial/manifest_list', $data);
    }

    private function registerHrxOrder(AjaxResponse $response)
    {
        $order_id = isset($this->request->post['order_id']) ? $this->request->post['order_id'] : null;

        if (!$order_id) {
            $response->setError('Order ID required');
            return;
        }

        $id_language = (int) $this->config->get('config_language_id');

        $order = Order::getManifestOrder($this->db, $order_id, $id_language);

        if ($order->getHrxOrderId()) {
            $response->setError($this->language->get(Params::PREFIX . 'notify_is_registered'));
            return;
        }

        try {

            $token = $this->config->get(Params::CONFIG_TOKEN);
            $test_mode = (bool) $this->config->get(Params::CONFIG_TEST_MODE);

            $api = new HrxApi($token, $test_mode);

            $this->load->model('sale/order');

            $oc_order = $this->model_sale_order->getOrder($order->getOrderId());
            $terminal = DeliveryPoint::getDeliveryPointById($order->getShippingCode(true), $this->db);
            $default_warehouse = Warehouse::getDefaultWarehouse($this->db);
            /*** Create order ***/
            $phone = str_replace($terminal->getRecipientPhonePrefix(), '', $oc_order['telephone']);
            $response->addData('phone', [$terminal->getRecipientPhonePrefix(), $phone]);

            $receiver = new HrxReceiver();
            $receiver->setName($order->getCustomer());
            $receiver->setEmail($oc_order['email']);
            $receiver->setPhone($phone); // $terminal->getRecipientPhoneRegexp()

            $shipment = new HrxShipment();
            $shipment->setReference($order->getOrderId());
            $shipment->setComment('Comment here');
            $shipment->setLength(15);
            $shipment->setWidth(15);
            $shipment->setHeight(15);
            $shipment->setWeight($this->getOrderWeightInKg($order->getOrderId()));

            $hrx_order_obj = new HrxOrder();
            $hrx_order_obj->setPickupLocationId($default_warehouse->id);
            $hrx_order_obj->setDeliveryLocation($terminal->id);
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
                $response->addData('html', $this->load->view('extension/shipping/' . Params::SETTINGS_CODE . '/partial/manifest_list_row', [
                    'order' => $order,
                    'order_url' => $this->url->link('sale/order/info', $this->getUserToken(), true)
                ]));
            }
        } catch (\Throwable $th) {
            $response->setError($th->getMessage());
        }
    }

    private function getLabel(AjaxResponse $response)
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

    private function getOrderWeightInKg($order_id)
    {
        // Get cart weight
        // $total_weight = $this->getOrderWeight();
        $kg_weight_class_id = (int) Helper::getWeightClassId($this->db);
        // Make sure its in kg (we do not support imperial units, so assume weight is in metric units)
        $weight_class_id = (int) $this->config->get('config_weight_class_id');

        // already in kg
        // if ($kg_weight_class_id === $weight_class_id) {
        //     return (float) $total_weight;
        // }

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

        // weight classes different need to convert into kg
        return (float) $total_order_weight;
    }

    // private function getManifestsPartial($page = 1)
    // {
    //     $page = (int) $page;
    //     $page_limit = (int) $this->config->get('config_limit_admin');
    //     if ($page_limit <= 0) {
    //         $page_limit = 30;
    //     }

    //     $total = ManifestCtrl::getTotal($this->db);
    //     $total_pages = ceil($total / $page_limit);

    //     if ($total_pages < 1) {
    //         $total_pages = 1;
    //     }

    //     if ($page < 1) {
    //         $page = 1;
    //     }

    //     if ($page > $total_pages) {
    //         $page = $total_pages;
    //     }

    //     $offset = ($page - 1) * $page_limit;
    //     $data = [
    //         'manifests' => ManifestCtrl::list($this->db, $offset, $page_limit)
    //     ];


    //     $data['paginator'] = '';
    //     if ($total_pages > 1) {
    //         $data['paginator'] = $this->getPagination($page, $total_pages);
    //     }

    //     return $this->load->view('extension/shipping/omniva_int_m/manifests_partial', $data);
    // }

    // private function loadManifestPage()
    // {
    //     $page = 1;
    //     if (isset($this->request->post['page'])) {
    //         $page = (int) $this->request->post['page'];
    //     }

    //     return [
    //         'html' => $this->getManifestsPartial($page)
    //     ];
    // }

    // private function getManifest()
    // {
    //     $result = [];
    //     if (!isset($this->request->post['order_id']) && !isset($this->request->post['manifest_id'])) {
    //         $result['error'] = 'Missing Order or Manifest ID';
    //         return $result;
    //     }

    //     $manifest_id = null;

    //     if (isset($this->request->post['order_id'])) {
    //         $order_id = (int) $this->request->post['order_id'];

    //         $sql = $this->db->query("
    //             SELECT api_cart_id, api_shipment_id FROM " . DB_PREFIX . "omniva_int_m_order_api 
    //             WHERE order_id = " . (int) $order_id . "  AND canceled = 0
    //             ORDER BY order_id DESC 
    //             LIMIT 1
    //         ");

    //         if (!$sql->rows) {
    //             return [
    //                 'error' => 'Order hasnt been registered yet'
    //             ];
    //         }

    //         $shipment_id = $sql->row['api_shipment_id'];
    //         $manifest_id = $sql->row['api_cart_id'];
    //     }

    //     if (isset($this->request->post['manifest_id'])) {
    //         $manifest_id = strip_tags($this->request->post['manifest_id']);
    //     }

    //     if ($manifest_id === null) {
    //         return [
    //             'error' => 'Missing manifest ID'
    //         ];
    //     }
    //     // $manifest_id = 'INCC0425105255'; // to test manifest download
    //     try {
    //         // $token = $this->config->get(Params::PREFIX . 'api_token');
    //         // $test_mode = $this->config->get(Params::PREFIX . 'api_test_mode');

    //         Helper::setApiStaticToken($this->config);
    //         $api = Helper::getApiInstance();
    //         // $api = new API($token, $test_mode);

    //         $response = $api->generateManifest($manifest_id);
    //     } catch (\Exception $th) {
    //         return [
    //             'error' => $th->getMessage(),
    //             'config' => [
    //                 'token' => Helper::$token,
    //                 'mode' => Helper::$test_mode,
    //                 'url' => Helper::getApiUrl(Helper::$test_mode)
    //             ]
    //         ];
    //     }

    //     return [
    //         'manifest_id' => $manifest_id,
    //         'shipment_id' => isset($shipment_id) ? $shipment_id : null,
    //         'response' => $response
    //     ];
    // }

    // private function updateSelectedTerminal()
    // {
    //     $terminal_id = isset($this->request->post['terminal_id']) ? $this->request->post['terminal_id'] : null;
    //     $order_id = isset($this->request->post['order_id']) ? $this->request->post['order_id'] : null;

    //     if (!$terminal_id || !$order_id) {
    //         return [
    //             'error' => 'Terminal ID and/or Order ID missing'
    //         ];
    //     }

    //     $terminal_id = $this->db->escape(strip_tags($terminal_id));
    //     $order_id = $this->db->escape(strip_tags($order_id));

    //     $terminal_data = [
    //         'terminal_id' => $terminal_id,
    //         'address' => isset($this->request->post['address']) ? strip_tags($this->request->post['address']) : null,
    //         'city' => isset($this->request->post['city']) ? strip_tags($this->request->post['city']) : null,
    //         'comment' => isset($this->request->post['comment']) ? strip_tags($this->request->post['comment']) : null,
    //         'country_code' => isset($this->request->post['country_code']) ? strip_tags($this->request->post['country_code']) : null,
    //         'identifier' => isset($this->request->post['identifier']) ? strip_tags($this->request->post['identifier']) : null,
    //         'name' => isset($this->request->post['name']) ? strip_tags($this->request->post['name']) : null,
    //         'zip' => isset($this->request->post['zip']) ? strip_tags($this->request->post['zip']) : null
    //     ];

    //     try {
    //         $result = $this->db->query("
    //             UPDATE " . DB_PREFIX . "omniva_int_m_order
    //             SET
    //                 terminal_id = '" . $terminal_id . "',
    //                 terminal_data = '" . Helper::base64Encode($terminal_data, true) . "'
    //             WHERE order_id = '" . (int) $order_id . "'
    //         ");
    //         return [
    //             'result' => $result
    //         ];
    //     } catch (\Exception $th) {
    //         return [
    //             'error' => $th->getMessage()
    //         ];
    //     }
    // }
}

<?php

use Mijora\HrxOpencart\Helper;
use Mijora\HrxOpencart\Model\AjaxResponse;
use Mijora\HrxOpencart\Model\DeliveryPoint;
use Mijora\HrxOpencart\Params;

require_once(DIR_SYSTEM . 'library/hrx_m/vendor/autoload.php');

class ControllerExtensionModuleHrxM extends Controller
{
    public function ajax()
    {
        $action = isset($this->request->get['action']) ? $this->request->get['action'] : 'bad_action';

        $response = new AjaxResponse();

        switch ($action) {
            case 'getTerminals':
                $this->getTerminals($response);
                break;
            default:
                header('HTTP/1.0 403 Forbidden');
                exit();
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    private function getTerminals(AjaxResponse $response)
    {
        $country_code = isset($this->request->get['country_code']) ? $this->request->get['country_code'] : '';

        if (empty($country_code) || strlen($country_code) > 3) {
            return [];
        }

        $terminal_list = DeliveryPoint::getDeliveryPointsByCountryCode($this->db, $country_code);
        $session_methods = isset($this->session->data['shipping_methods'][Params::SETTINGS_CODE]['quote']) ? $this->session->data['shipping_methods'][Params::SETTINGS_CODE]['quote'] : [];

        $terminal_list = array_filter($terminal_list, function (DeliveryPoint $item) use ($session_methods) {
            return isset($session_methods['terminal_' . $item->id]);
        });

        $response->addData('terminals', array_values($terminal_list));
    }
}

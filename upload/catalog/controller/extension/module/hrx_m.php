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
                // $response->addData('session', $this->session->data);
                // echo json_encode(['data' => $terminal_list]);
                break;
                // case 'terminalUpdate':
                //     $secret = $this->config->get(Params::PREFIX . 'cron_secret');
                //     if (isset($this->request->get['secret']) && $secret && $secret === $this->request->get['secret']) {
                //         $data = Helper::ajaxUpdateTerminals($this->db);
                //         header('Content-Type: application/json');
                //         echo json_encode(['data' => $data]);
                //         exit();
                //     }

                //     header('HTTP/1.0 403 Forbidden');
                //     exit();
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

        // $response->addData('session_methods', $session_methods);
        $terminal_list = array_filter($terminal_list, function (DeliveryPoint $item) use ($session_methods) {
            return isset($session_methods['terminal_' . $item->id]);
        });

        $response->addData('terminals', array_values($terminal_list));
        // return array_values($terminal_list);
    }
}

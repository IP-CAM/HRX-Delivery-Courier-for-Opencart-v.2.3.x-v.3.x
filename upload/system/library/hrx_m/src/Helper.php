<?php

namespace Mijora\HrxOpencart;

use HrxApi\API;
use Mijora\DVDoug\BoxPacker\ItemList;
use Mijora\DVDoug\BoxPacker\PackedBox;
use Mijora\DVDoug\BoxPacker\VolumePacker;
use Mijora\HrxOpencart\Interfaces\DeliveryPointInterface;
use Mijora\HrxOpencart\Model\ParcelBox;
use Mijora\HrxOpencart\Model\ParcelProduct;

class Helper
{
    public static function checkToken($token, $test_mode, $just_validity = true)
    {
        $response = [
            'valid' => false,
            'message' => null
        ];
        try {
            $api = new API($token, $test_mode);
            $response['valid'] = (bool) $api->getDeliveryLocations(1, 1);
        } catch (\Throwable $th) {
            $response['message'] = $th->getMessage();
        }

        if ($just_validity) {
            return $response['valid'];
        }

        return $response;
    }

    public static function saveSettings($db, $data)
    {
        foreach ($data as $key => $value) {
            $query = $db->query("SELECT setting_id FROM `" . DB_PREFIX . "setting` WHERE `code` = '" . Params::SETTINGS_CODE . "' AND `key` = '" . $db->escape($key) . "'");
            if ($query->num_rows) {
                $id = $query->row['setting_id'];
                $db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $db->escape($value) . "', serialized = '0' WHERE `setting_id` = '$id'");
            } else {
                $db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id = '0', `code` = '" . Params::SETTINGS_CODE . "', `key` = '$key', `value` = '" . $db->escape($value) . "'");
            }
        }
    }

    public static function deleteSetting($db, $target_key)
    {
        $db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = '" . Params::SETTINGS_CODE . "' AND `key` = '" . $db->escape($target_key) . "'");
    }

    public static function getModificationXmlVersion($file)
    {
        if (!is_file($file)) {
            return null;
        }

        $xml = file_get_contents($file);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXml($xml);

        $version = $dom->getElementsByTagName('version')->item(0)->nodeValue;

        return $version;
    }

    public static function getModificationSourceFilename()
    {
        return Params::BASE_MOD_XML_SOURCE_DIR . self::getModificationXmlDirByVersion() . Params::BASE_MOD_XML;
    }

    public static function isModificationNewer()
    {
        return version_compare(
            self::getModificationXmlVersion(self::getModificationSourceFilename()),
            self::getModificationXmlVersion(Params::BASE_MOD_XML_SYSTEM),
            '>'
        );
    }

    public static function getModificationXmlDirByVersion()
    {
        if (version_compare(VERSION, '3.0.0', '>=')) {
            return Params::MOD_SOURCE_DIR_OC_3_0;
        }

        if (version_compare(VERSION, '2.3.0', '>=')) {
            return Params::MOD_SOURCE_DIR_OC_2_3;
        }

        // by default return latest version modifications dir
        return Params::MOD_SOURCE_DIR_OC_3_0;
    }

    public static function copyModificationXml()
    {
        self::removeModificationXml();
        return copy(self::getModificationSourceFilename(), Params::BASE_MOD_XML_SYSTEM);
    }

    public static function removeModificationXml()
    {
        if (is_file(Params::BASE_MOD_XML_SYSTEM)) {
            @unlink(Params::BASE_MOD_XML_SYSTEM);
        }
    }

    /**
     * @param mixed $data data to be encoded
     * @param bool $convert_to_json should data first be JSON encoded
     * 
     * @return string
     */
    public static function base64Encode($data, $convert_to_json = true)
    {
        return base64_encode($convert_to_json ? json_encode($data) : $data);
    }

    /**
     * @param string $base64_string BASE64 encoded string
     * @param bool $convert_to_object should decoded string be then json_decoded as StdObject
     * 
     * @return mixed
     */
    public static function base64Decode($base64_string, $convert_to_object = true)
    {
        return json_decode(base64_decode($base64_string), !$convert_to_object);
    }

    public static function hasGitUpdate()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => Params::GIT_VERSION_CHECK,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_USERAGENT => 'HRX_M_VERSION_CHECK_v1.0',
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $version_data = @json_decode($response, true);

        if (empty($version_data)) {
            return false;
        }

        $git_version = isset($version_data['tag_name']) ? $version_data['tag_name'] : null;

        if ($git_version === null) {
            return false;
        }

        $git_version = str_ireplace('v', '', $git_version);

        if (!self::isModuleVersionNewer($git_version)) {
            return false;
        }

        return [
            'version' => $git_version,
            'download_url' => isset($version_data['assets'][0]['browser_download_url'])
                ? $version_data['assets'][0]['browser_download_url']
                : Params::GIT_URL
        ];
    }

    public static function isModuleVersionNewer($git_version)
    {
        return version_compare($git_version, Params::VERSION, '>');
    }

    public static function isTimeToCheckVersion($timestamp)
    {
        return time() > (int) $timestamp + (Params::GIT_CHECK_EVERY_HOURS * 60 * 60);
    }

    public static function getWeightClassId($db)
    {
        $weight_sql = $db->query("
            SELECT weight_class_id FROM `" . DB_PREFIX . "weight_class_description` WHERE `unit` = 'kg' LIMIT 1
        ");

        if (!$weight_sql->rows) {
            return null;
        }

        return (int) $weight_sql->row['weight_class_id'];
    }

    public static function getLengthClassId($db)
    {
        $weight_sql = $db->query("
            SELECT length_class_id FROM `" . DB_PREFIX . "length_class_description` WHERE `unit` = 'cm' LIMIT 1
        ");

        if (!$weight_sql->rows) {
            return null;
        }

        return (int) $weight_sql->row['length_class_id'];
    }

    /**
     * @param Object $db Opencart DB object
     * 
     * @return array Array with tablenames as keys and queries to run as values
     */
    public static function checkDbTables($db)
    {
        $result = array();

        // OC3 has too small default type for session (terminals takes a lot of space)
        if (version_compare(VERSION, '3.0.0', '>=')) {
            $session_table = $db->query("DESCRIBE `" . DB_PREFIX . "session`")->rows;
            foreach ($session_table as $col) {
                if (strtolower($col['Field']) != 'data') {
                    continue;
                }
                if (strtolower($col['Type']) == 'text') {
                    // needs to be MEDIUMTEXT or LONGTEXT
                    $result['session'] = "
                        ALTER TABLE `" . DB_PREFIX . "session` 
                        MODIFY `data` MEDIUMTEXT;
                    ";
                }
                break;
            }
        }

        return $result;
    }

    public static function getExtensionHomeString()
    {
        if (version_compare(VERSION, '3.0.0', '>=')) {
            return 'marketplace';
        }

        // for versions bellow 3.0
        return 'extension';
    }

    public static function getPackedBox(DeliveryPointInterface $delivery_point, ItemList $item_list): PackedBox
    {
        $dimensions_array = $delivery_point->getMaxDimensions(false);

        $max_length = $dimensions_array[ParcelProduct::DIMENSION_LENGTH] === 0.0 ? ParcelProduct::UNLIMITED : $dimensions_array[ParcelProduct::DIMENSION_LENGTH];
        $max_width = $dimensions_array[ParcelProduct::DIMENSION_WIDTH] === 0.0 ? ParcelProduct::UNLIMITED : $dimensions_array[ParcelProduct::DIMENSION_WIDTH];
        $max_height = $dimensions_array[ParcelProduct::DIMENSION_HEIGHT] === 0.0 ? ParcelProduct::UNLIMITED : $dimensions_array[ParcelProduct::DIMENSION_HEIGHT];
        $max_weight = $delivery_point->getMaxWeight() === 0.0 ? ParcelProduct::UNLIMITED : $delivery_point->getMaxWeight();


        $box = new ParcelBox(
            $max_length,
            $max_width,
            $max_height,
            $max_weight,
            $delivery_point->getMaxDimensions() . ' ' . $max_weight // using formated dimensions string as reference + max weight
        );

        $packer = new VolumePacker($box, $item_list);

        return $packer->pack();
    }

    public static function doesParcelFitBox(DeliveryPointInterface $delivery_point, ItemList $item_list)
    {
        $packed_box = self::getPackedBox($delivery_point, $item_list);

        return $packed_box->getItems()->count() === $item_list->count();
    }
}

<?php

namespace Mijora\HrxOpencart\Model;

use Mijora\HrxOpencart\Helper;
use Mijora\HrxOpencart\OpenCart\DbTables;

class ParcelDefault implements \JsonSerializable
{
    const PARCEL_DIMENSIONS = [
        'weight',
        'width',
        'length',
        'height'
    ];

    public $category_id = 0;
    public $weight = 1.0;
    public $length = 1.0;
    public $width = 1.0;
    public $height = 1.0;

    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function jsonSerialize()
    {
        return $this->getAllValues();
    }

    public function getAllValues()
    {
        return [
            'category_id' => $this->category_id,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    public function getAttributesJsonAsBase64()
    {
        return base64_encode(json_encode($this->getAllValues()));
    }

    /**
     * @param object $db
     * 
     * @return ParcelDefault
     */
    public static function getGlobalDefault($db)
    {
        $parcel_default = new ParcelDefault($db);

        $result = $db->query("
            SELECT `category_id`, `weight`, `length`, `width`, `height`
            FROM `" . DbTables::TABLE_PARCEL_DEFAULT . "`
            WHERE `category_id` = 0 LIMIT 1
        ");

        if (empty($result->rows)) {
            return $parcel_default;
        }

        $parcel_default->category_id = (int) $result->row['category_id'];
        $parcel_default->weight = (float) $result->row['weight'];
        $parcel_default->length = (float) $result->row['length'];
        $parcel_default->width = (float) $result->row['width'];
        $parcel_default->height = (float) $result->row['height'];

        return $parcel_default;
    }

    /**
     * @param mixed $category_ids
     * @param mixed $db
     * 
     * @return ParcelDefault[]
     */
    public static function getMultipleParcelDefault($category_ids, $db)
    {
        $sql_result = $db->query("
            SELECT `category_id`, `weight`, `length`, `width`, `height`
            FROM `" . DbTables::TABLE_PARCEL_DEFAULT . "`
            WHERE `category_id` IN ('" . implode("', '", $category_ids) . "')
        ");

        if (empty($sql_result->rows)) {
            return [];
        }

        $result = [];
        foreach ($sql_result->rows as $row) {
            $parcel_default = new ParcelDefault($db);
            $parcel_default->category_id = (int) $row['category_id'];
            $parcel_default->weight = (float) $row['weight'];
            $parcel_default->length = (float) $row['length'];
            $parcel_default->width = (float) $row['width'];
            $parcel_default->height = (float) $row['height'];

            $result[(int) $row['category_id']] = $parcel_default;
        }

        return $result;
    }

    public function fieldValidation()
    {
        $result = [
            'category_id' => true,
            'weight' => true,
            'length' => true,
            'width' => true,
            'height' => true,
        ];

        if (empty($this->weight) || (float) $this->weight <= 0) {
            $result['weight'] = false;
        }
        if (empty($this->length) || (float) $this->length <= 0) {
            $result['length'] = false;
        }
        if (empty($this->width) || (float) $this->width <= 0) {
            $result['width'] = false;
        }
        if (empty($this->height) || (float) $this->height <= 0) {
            $result['height'] = false;
        }

        return $result;
    }

    public static function remove($category_id, $db)
    {
        return $db->query("
            DELETE FROM `" . DbTables::TABLE_PARCEL_DEFAULT . "` WHERE `category_id` = '" . (int) $category_id . "'
        ");
    }

    public function save()
    {
        self::remove($this->category_id, $this->db);

        return $this->db->query("
            INSERT INTO `" . DbTables::TABLE_PARCEL_DEFAULT . "` 
            (`category_id`, `weight`, `length`, `width`, `height`)
            VALUES ('" . (int) $this->category_id . "', '" . (float) $this->weight . "', '" . (float) $this->length . "',
             '" . (float) $this->width . "', '" . (float) $this->height . "')
        ");
    }

    public static function getProductDimmensions($cart_products, $db, $weight_class = null, $length_class = null)
    {
        $product_ids = array_map(function ($product) {
            return (int) $product['product_id'];
        }, $cart_products);

        $result = $db->query('
            SELECT product_id, category_id FROM ' . DB_PREFIX . 'product_to_category 
            WHERE product_id IN (' . implode(', ', $product_ids) . ')
        ');

        $product_categories = [];
        $categories = [];
        if ($result->rows) {
            foreach ($result->rows as $row) {
                $product_id = $row['product_id'];
                $category_id = $row['category_id'];
                if (!isset($product_categories[$product_id])) {
                    $product_categories[$product_id] = [];
                }

                if (!isset($categories[$category_id])) {
                    $categories[$category_id] = $category_id;
                }

                $product_categories[$product_id][] = $row['category_id'];
            }
        }

        foreach ($product_categories as $product_id => $category_ids) {
            $product_categories[$product_id] = ParcelDefault::getMultipleParcelDefault($category_ids, $db);
        }

        $defaults = ParcelDefault::getGlobalDefault($db);

        $kg_weight_class_id = Helper::getWeightClassId($db);
        $cm_length_class_id = Helper::getLengthClassId($db);

        // must have kg setup
        if (!$kg_weight_class_id) {
            return [];
        }

        $product_dimensions = [];
        foreach ($cart_products as $product) {
            if ((int) $product['shipping'] === 0) {
                continue;
            }

            $product_id = $product['product_id'];
            $weight = (float) $product['weight'];
            $width = (float) $product['width'];
            $length = (float) $product['length'];
            $height = (float) $product['height'];

            // make sure weight and legth are in correct units
            if ($weight_class) {
                $weight = (float) $weight_class->convert($weight, $product['weight_class_id'], $kg_weight_class_id);
            }

            if ($length_class) {
                $width = (float) $length_class->convert($width, $product['length_class_id'], $cm_length_class_id);
                $length = (float) $length_class->convert($length, $product['length_class_id'], $cm_length_class_id);
                $height = (float) $length_class->convert($height, $product['length_class_id'], $cm_length_class_id);
            }

            foreach (self::PARCEL_DIMENSIONS as $dimmension) {
                if ($$dimmension > 0) {
                    continue;
                }

                $$dimmension = 0;
                if (isset($product_categories[$product_id])) {
                    foreach ($product_categories[$product_id] as $category_id => $parcel_default) {
                        if ($$dimmension < $parcel_default->$dimmension) {
                            $$dimmension = $parcel_default->$dimmension;
                        }
                    }
                }

                $$dimmension = $$dimmension === 0 ? $defaults->$dimmension : $$dimmension;
            }


            $parcel_product = new ParcelProduct();
            $parcel_product->quantity = (int) $product['quantity'];
            $parcel_product->weight = round($weight / (int) $product['quantity'], 2);
            $parcel_product->width = ceil($width);
            $parcel_product->length = ceil($length);
            $parcel_product->height = ceil($height);

            $product_dimensions[] = $parcel_product;
        }

        return $product_dimensions;
    }

    public static function addDefaultsIntoOcCategoryData($oc_categories, $db)
    {
        $categorie_ids = array_map(function ($item) {
            return $item['category_id'];
        }, $oc_categories);

        $parcel_defaults = self::getMultipleParcelDefault($categorie_ids, $db);

        return array_map(function ($item) use ($parcel_defaults) {
            $item['hrx_parcel_default'] = null;
            if (isset($parcel_defaults[$item['category_id']])) {
                $item['hrx_parcel_default'] = $parcel_defaults[$item['category_id']];
            }
            return $item;
        }, $oc_categories);
    }
}

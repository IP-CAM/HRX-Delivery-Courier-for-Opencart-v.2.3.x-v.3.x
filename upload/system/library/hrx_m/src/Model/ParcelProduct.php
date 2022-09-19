<?php

namespace Mijora\HrxOpencart\Model;

class ParcelProduct implements \JsonSerializable
{
    const DIMENSION_LENGTH = 'L';
    const DIMENSION_WIDTH = 'W';
    const DIMENSION_HEIGHT = 'H';

    const UNLIMITED = 9999; // used for box size checking instead of 0 which means unlimited in API data

    const PARCEL_DIMENSIONS = [
        'weight',
        'width',
        'length',
        'height'
    ];

    public $category_id = 0;
    public $weight;
    public $length;
    public $width;
    public $height;
    public $quantity;

    public function __construct()
    {
        //
    }

    public function jsonSerialize()
    {
        return [
            'category_id' => $this->category_id,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'quantity' => $this->quantity,
        ];
    }
}

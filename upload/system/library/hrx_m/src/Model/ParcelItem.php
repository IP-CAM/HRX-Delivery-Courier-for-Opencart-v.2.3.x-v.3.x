<?php

namespace Mijora\HrxOpencart\Model;

use Mijora\DVDoug\BoxPacker\Item;
use Mijora\DVDoug\BoxPacker\Rotation;

class ParcelItem implements Item
{

    private $length;
    private $width;
    private $height;
    private $weight;
    private $description;

    public function __construct($length, $width, $height, $weight, $reference)
    {
        $this->length = $length * 10;
        $this->width = $width * 10;
        $this->height = $height * 10;
        $this->weight = $weight * 1000;
        $this->description = $reference;
    }

    public function getAllowedRotation(): int
    {
        return Rotation::BEST_FIT;
    }

    public function getDepth(): int
    {
        return $this->height;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
<?php

namespace Mijora\HrxOpencart\Model;

use Mijora\DVDoug\BoxPacker\Box;

class ParcelBox implements Box
{
    private $length; // mm
    private $width; // mm
    private $height; // mm
    private $weight; // g
    private $reference;

    public function __construct($length, $width, $height, $max_weight, $reference)
    {
        $this->length = $length * 10;
        $this->width = $width * 10;
        $this->height = $height * 10;
        $this->weight = $max_weight * 1000;
        $this->reference = $reference;
    }

    public function getEmptyWeight(): int
    {
        return 0;
    }

    public function getMaxWeight(): int
    {
        return (int) $this->weight;
    }

    public function getInnerLength(): int
    {
        return (int) $this->length;
    }

    public function  getOuterLength(): int
    {
        return $this->getInnerLength();
    }

    public function getInnerWidth(): int
    {
        return (int) $this->width;
    }

    public function getOuterWidth(): int
    {
        return $this->getInnerWidth();
    }

    public function getInnerDepth(): int
    {
        return (int) $this->height;
    }

    public function getOuterDepth(): int
    {
        return $this->getInnerDepth();
    }

    public function getReference(): string
    {
        return $this->reference;
    }
}
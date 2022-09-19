<?php

namespace Mijora\HrxOpencart\Interfaces;

interface DeliveryPointInterface
{
    public function getId();

    public function getParams();

    public function getMinDimensions($formated = true);

    public function getMaxDimensions($formated = true);

    public function getMinWeight(): float;

    public function getMaxWeight(): float;

    public function getRecipientPhoneRegexp(): string;

    public function getRecipientPhonePrefix(): string;
}

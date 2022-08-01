<?php

namespace Mijora\HrxOpencart\Model;

use JsonSerializable;

class AjaxResponse implements JsonSerializable
{
    private $data = [];
    private $error;

    public function __construct()
    {
        //
    }

    public function addData($key, $value)
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function setError($error_message)
    {
        $this->error = $error_message;

        return $this;
    }

    public function jsonSerialize()
    {
        $return = [
            'data' => $this->data
        ];

        if ($this->error) {
            $return['error'] = $this->error;
        }

        return $return;
    }
}

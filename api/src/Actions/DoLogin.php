<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class DoLogin extends EdisActionGeneric
{
    public function __construct($params)
    {
        $id = 91;
        $descriptor = "LightningLoginFormController/ACTION\$login";
        $callingDescriptor = "WP_LoginForm";
        $extras = [];

        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
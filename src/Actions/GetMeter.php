<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class GetMeter extends EdisActionGeneric
{
    public function __construct($params)
    {
        $id = 522;
        $descriptor = "WP_ContadorICP_F2_CTRL/ACTION\$consultarContador";
        $callingDescriptor = "WP_Reconnect_Detail";
        $extras = [];

        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
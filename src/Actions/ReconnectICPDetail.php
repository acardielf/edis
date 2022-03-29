<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class ReconnectICPDetail extends EdisActionGeneric
{
    public function __construct($params)
    {
        $id = 261;
        $descriptor = "WP_ContadorICP_F2_CTRL/ACTION\$reconectarICP";
        $callingDescriptor = "WP_Reconnect_Detail_F2";
        $extras = [];

        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
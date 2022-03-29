<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class GetCupsInfo extends EdisActionGeneric
{
    public function __construct($params)
    {
        $id = 489;
        $descriptor = "WP_ContadorICP_F2_CTRL/ACTION\$getCupsInfo";
        $callingDescriptor = "WP_Reconnect_Detail";
        $extras = [];

        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class GetLoginInfo extends EdisActionGeneric
{

    public function __construct()
    {
        $id = 215;
        $descriptor = "WP_Monitor_CTRL/ACTION\$getLoginInfo";
        $callingDescriptor = "WP_Monitor";
        $params = ["serviceNumber" => "S011"];
        $extras = [];

        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
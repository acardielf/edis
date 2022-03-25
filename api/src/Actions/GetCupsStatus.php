<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class GetCupsStatus extends EdisActionGeneric
{
    public function __construct($params)
    {
        $id = 629;
        $descriptor = "WP_CUPSDetail_CTRL/ACTION\$getStatus";
        $callingDescriptor = "WP_cupsDetail";
        $extras = [];

        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
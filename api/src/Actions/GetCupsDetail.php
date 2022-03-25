<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class GetCupsDetail extends EdisActionGeneric
{
    public function __construct($params)
    {
        $id = 490;
        $descriptor = "WP_CUPSDetail_CTRL/ACTION\$getCUPSDetail";
        $callingDescriptor = "WP_cupsDetail";
        $extras = [];

        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
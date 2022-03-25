<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class GetListCycles extends EdisActionGeneric
{
    public function __construct($params)
    {
        $id = 1190;
        $descriptor = "WP_Measure_v3_CTRL/ACTION\$getInfo";
        $callingDescriptor = "WP_Measure_Detail_v4";
        $extras = [
            "longRunning" => true
        ];
        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
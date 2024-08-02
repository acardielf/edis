<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class GetMeasInterval extends EdisActionGeneric
{
    public function __construct($params)
    {
        $id = 1362;
        $descriptor = "WP_Measure_v3_CTRL/ACTION\$getChartPointsByRange";
        $callingDescriptor = "WP_Measure_Detail_Filter_Advanced_v3";
        $extras = [];
        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
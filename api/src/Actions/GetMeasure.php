<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class GetMeasure extends EdisActionGeneric
{
    public function __construct($params)
    {
        $id = 1164;
        $descriptor = "WP_Measure_v3_CTRL/ACTION\$getChartPointsByRange";
        $callingDescriptor = "WP_Measure_Detail_Filter_By_Dates_v3";
        $extras = [
            'longRunning' => true
        ];

        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
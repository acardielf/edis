<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class GetMaximeter extends EdisActionGeneric
{
    public function __construct($params)
    {
        //{"actions":[{"id":"1962;a","descriptor":"apex://WP_MaximeterHistogram_CTRL/ACTION$getHistogramPoints","callingDescriptor":"markup://c:WP_MaximeterHistogramDetail","params":{"mapParams":{"startDate":"2/2021","endDate":"2/2022","id":"a0r2400000GHwfmAAD","sIdentificador":"a5U2o0000015gVGEAY"}}}]}
        $id = 1962;
        $descriptor = "WP_MaximeterHistogram_CTRL/ACTION\$getHistogramPoints";
        $callingDescriptor = "WP_MaximeterHistogramDetail";
        $extras = [
            'longRunning' => true
        ];

        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
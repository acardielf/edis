<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class GetMaximeter extends EdisActionGeneric
{
    public function __construct($params)
    {
        $id = 1962;
        $descriptor = "WP_MaximeterHistogram_CTRL/ACTION\$getHistogramPoints";
        $callingDescriptor = "WP_MaximeterHistogramDetail";
        $extras = [
            'longRunning' => true
        ];

        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
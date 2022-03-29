<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class GetListCups extends EdisActionGeneric
{
    public function __construct($params)
    {
        $id = 1086;
        $descriptor = "WP_Measure_v3_CTRL/ACTION\$getListCups";
        $callingDescriptor = "WP_Measure_List_v4";
        $extras = [];

        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
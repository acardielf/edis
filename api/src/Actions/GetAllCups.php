<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class GetAllCups extends EdisActionGeneric
{
    public function __construct($params)
    {
        $id = 294;
        $descriptor = "WP_ConsultaSuministros/ACTION\$getAllCUPS";
        $callingDescriptor = "WP_MySuppliesForm";
        $extras = [];

        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
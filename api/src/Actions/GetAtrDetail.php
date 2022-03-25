<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class GetAtrDetail extends EdisActionGeneric
{
    public function __construct($params)
    {
        $id = 62;
        $descriptor = "WP_ContractATRDetail_CTRL/ACTION\$getATRDetail";
        $callingDescriptor = "WP_SuppliesATRDetailForm";
        $extras = [];

        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
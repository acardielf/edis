<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class GetSolicitudAtrDetail extends EdisActionGeneric
{
    public function __construct($params)
    {
        $id = 56;
        $descriptor = "WP_SolicitudATRDetail_CTRL/ACTION\$getSolicitudATRDetail";
        $callingDescriptor = "WP_ATR_Requests_Detail_Form";
        $extras = [];

        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
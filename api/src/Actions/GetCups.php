<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class GetCups extends EdisActionGeneric
{
    public function __construct($params)
    {
        $id = 270;
        $descriptor = "WP_ContadorICP_F2_CTRL/ACTION\$getCUPSReconectarICP";
        $callingDescriptor = "WP_Reconnect_ICP";
        $extras = [];
        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
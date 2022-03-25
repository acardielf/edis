<?php

namespace Edistribucion\Actions;

use Edistribucion\EdisActionGeneric;

class ReconnectICPModal extends EdisActionGeneric
{
    public function __construct($params)
    {
        $id = 287;
        $descriptor = "WP_ContadorICP_F2_CTRL/ACTION\$goToReconectarICP";
        $callingDescriptor = "WP_Reconnect_Modal";
        $extras = [];

        parent::__construct($id, $descriptor, $callingDescriptor, $params, $extras);
    }
}
<?php

namespace Edistribucion\Traits;

use Edistribucion\EdisConfigStatic;
use Monolog\Handler\StreamHandler;

trait Logger
{

    public function createLogger(): void
    {
        $this->log = new \Monolog\Logger('EdisLog');
        $this->log->pushHandler(new StreamHandler(EdisConfigStatic::LOGGER_OUTPUT, EdisConfigStatic::LOGLEVEL));
    }


}
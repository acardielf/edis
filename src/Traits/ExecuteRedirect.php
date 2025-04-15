<?php

namespace Edistribucion\Traits;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

trait ExecuteRedirect
{
    private function execute_redirect(Response $response): ResponseInterface
    {
        $url = preg_match('/window\.location\.replace\([\'"]([^)\'"]*)/', $response->getBody()->getContents(), $matches);
        if ($url) {
            $newResponse = $this->get_url($matches[1]);
            return $this->execute_redirect($newResponse);
        }
        $response->getBody()->rewind();
        return $response;
    }

}
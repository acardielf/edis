<?php

namespace Edistribucion\Traits;

use Edistribucion\EdisConfigStatic;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

trait GetUrl {
    private function get_url(string $url, $options = []): ResponseInterface
    {
        $default = [
            'method' => 'GET',
            'headers' => [
                'User-Agent' => EdisConfigStatic::USER_AGENT
            ],
            'cookies' => null,
            'query' => null,
            'data' => null
        ];

        $options = array_merge($default, $options);
        if ($options['data']) {
            $options['query'] = http_build_query($options['data'], "", "&");
        }

        $request = new Request($options['method'], $url, $options['headers']);
        $this->log->debug("==> Sending " . $request->getMethod() . " request to " . $request->getUri()->getPath(),
            [
                urldecode($request->getUri()->getQuery())
            ]
        );
        $response = $this->client->send($request, array_filter($options));
        $this->log->debug("<== Response with code: " . $response->getStatusCode());
        $this->log->debug("");


        if ($response->getStatusCode() >= 400) {
            try {
                $e = json_encode($response->getBody()->getContents());
                $msg = "Error {}";
                $this->log->debug('Response error in JSON format');
                if (in_array('error', $e)) {
                    $msg .= ":";
                    if (in_array('errorCode', $e['error'])) {
                        $msg .= ' [{}]';
                    }
                    if (in_array('description', $e['error']['errorCode'])) {
                        $msg .= ' ' . $e['error']['description'];
                    }
                }
            } catch (\Exception $e) {
                $this->log->debug("Response error is not JSON format");
            }
        }
        return $response;
    }

}
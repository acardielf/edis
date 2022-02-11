<?php
namespace Edistribucion;

use Edistribucion\EdisError;
use Edistribucion\EdistribucionMessageAction;
use Edistribucion\UrlError;
use GuzzleHttp\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Edistribucion
{

    private $session;
    private $SESSION_FILE;
    private $ACCESS_FILE;
    private $token;
    private $credentials;
    private $dashboard;
    private $command_index;
    private $identities;
    private $appInfo;
    private $context;
    private $access_date;
    private $log;
    private $client;

    public function __construct()
    {
        $this->log = new Logger('name');
        $this->log->pushHandler(new StreamHandler('php://stdout', Logger::WARNING));
        $this->client = new Client([
            'base_uri' => "https://zonaprivada.edistribucion.com/areaprivada/s/"
        ]);
    }

    public function login(String $username, String $password)
    {
        $this->log->info("Loging...");
        if (!$this->check_tokens()){
            $this->session = "";
            return $this->force_login();
        }
        return true;

    }

    private function force_login()
    {
        $this->log->warning("Forcing login");
        $r = $this->get_url('login?ec=302&startURL=%2Fareaprivada%2Fs%2F');

    }

    private function get_url(String $url, $get = null, $post = null, $json = null, $cookies = null, $headers = null )
    {
        $h = [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.14; rv:77.0) Gecko/20100101 Firefox/77.0',
        ];

        if ($headers) {
            $h = $headers;
        }

        $response = $this->client->get($url);
        $html =  $response->getBody(true)->getContents();
        $htmlDOM = new \DOMDocument();
        @$htmlDOM->loadHTML($html);
        $scripts = $htmlDOM->getElementsByTagName('script');
        foreach ($scripts as $tag) {
            $src = $tag->getAttribute('src');
            print_r($src);

        }


    }

    public function check_tokens()
    {
        return false;
    }

    public function __toString()
    {
        return "Hola";
    }

}

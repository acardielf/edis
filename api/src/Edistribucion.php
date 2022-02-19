<?php

namespace Edistribucion;

use Edistribucion\EdisError;
use Edistribucion\EdistribucionMessageAction;
use Edistribucion\UrlError;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Cookie\SessionCookieJar;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Http\Message\ResponseInterface;

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
    private string $username;
    private string $password;

    public function __construct()
    {
        $jar = new SessionCookieJar('CookieJar', true);
        $this->log = new Logger('name');
        $this->log->pushHandler(new StreamHandler('php://stdout', Logger::INFO));
        $this->client = new Client([
            'base_uri' => "https://zonaprivada.edistribucion.com/",
            'cookies' => $jar
        ]);

    }

    public function login(string $username, string $password)
    {
        $this->log->info("Loging...");
        $this->username = $username;
        $this->password = $password;
        $this->dashboard = "https://zonaprivada.edistribucion.com/areaprivada/s/sfsites/aura?";
        if (!$this->check_tokens()) {
            $this->session = "";
            return $this->force_login();
        }
        return true;

    }

    /**
     * @throws \Exception
     */
    private function force_login()
    {
        $this->log->warning("Forcing login");
        $r = $this->get_url('areaprivada/s/login?ec=302&startURL=%2Fareaprivada%2Fs%2F');
        $rContents = $r->getBody()->getContents();
        $ix = strpos($rContents, "auraConfig");

        if (!$ix) {
            throw new \Exception('auraConfig not found. Cannot continue');
        }

        $htmlDOM = new \DOMDocument();
        @$htmlDOM->loadHTML($rContents);
        $scripts = $htmlDOM->getElementsByTagName('script');
        $this->log->info("Loading scripts");
        foreach ($scripts as $tag) {
            $src = $tag->getAttribute('src');
            if (!$src) {
                continue;
            }
            $ups = parse_url($src);
            $r = $this->get_url($src);
            if (strpos($src, "resources.js")) {
                $unq = rawurldecode($src);
                $fo = strpos($unq, "{");
                $lo = strrpos($unq, "}");
                $sub = substr($unq, $fo, $lo - $fo + 1);
                $this->context = $sub;
                $this->appInfo = json_decode($sub);
            }
        }
        $this->log->info('Performing login routine');

        $params = [
            "username" => $this->username,
            "password" => $this->password,
            "startUrl" => "/areaprivada/s/"
        ];

        $action = new EdistribucionMessageAction(
            91,
            "LightningLoginFormController/ACTION\$login",
            "WP_LoginForm",
            $params
        );

        $data = [
            'message' => '{"actions":[' . $action . ']}',
            'aura.context' => $this->context,
            'aura.pageURI' => '/areaprivada/s/login/?language=es&startURL=%2Fareaprivada%2Fs%2F&ec=302',
            'aura.token' => 'undefined',
        ];

        $r = $this->get_url($this->dashboard . 'other.LightningLoginForm.login=1', [
            "method" => "POST",
            "data" => $data,
        ]);

        $rText = $r->getBody()->getContents();

        if (str_contains($rText, "/*ERROR*/")) {
            if (str_contains($rText, "invalidSession")) {
                //self.__session = requests.Session();
                //self.__force_login(recursive=True);
                throw new \Exception("Unexpected error in loginForm. Cannot continue");
            }
        }
        $rJSON = json_decode($rText, true);
        if (!array_key_exists('events', $rJSON)) {
            throw new \Exception("Wrong login response. Cannot continue");
        }
        $this->log->info('Accessing to frontdoor');
        $this->log->info("URL: " . $rJSON['events'][0]['attributes']['values']['url']);
        $r = $this->get_url($rJSON['events'][0]['attributes']['values']['url']);
        $rContents = $r->getBody()->getContents();
        $this->log->info('Accessing to landing page');
        $r = $this->get_url("https://zonaprivada.edistribucion.com/areaprivada/s/");
        $rContents = $r->getBody()->getContents();
        $ix = strpos($rContents, "auraConfig");
        if (!$ix) {
            throw new \Exception('auraConfig not found. Cannot continue');
        }
        $ix = strpos($rContents, "{", $ix);
        $ed = strpos($rContents, ";", $ix);
        $sub = substr($rContents, $ix, $ed - $ix);
        $jr = json_decode($sub, true);
        if (!array_key_exists('token', $jr)) {
            throw new \Exception("Wrong login response. Cannot continue");
        }
        $this->token = $jr['token'];
        $this->log->info('Token received!');
        $this->log->debug($this->token);
        $this->log->info('Retreiving account info');


        file_put_contents('file.html', $rContents);

    }

    private function get_url(string $url, $options = []): ResponseInterface
    {
        $default = [
            'method' => 'GET',
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.14; rv:77.0) Gecko/20100101 Firefox/77.0'
            ],
            'cookies' => null,
            'data' => null
        ];

        $headers = (array_key_exists("headers", $options) && !empty($options['headers'])) ? $options['headers'] : $default['headers'];
        $method = (array_key_exists("method", $options) && !empty($options['method'])) ? $options['method'] : $default['method'];
        $cookies = (array_key_exists("cookies", $options) && !empty($options['cookies'])) ? $options['cookies'] : $default['cookies'];
        $data = (array_key_exists("data", $options) && !empty($options['data'])) ? $options['data'] : $default['data'];
        $query = null;

        if ($method == 'GET') {
            $request = new Request($method, $url, $headers);
        } elseif ($method == 'POST') {
            $query = http_build_query($data, null, "&");
            $request = new Request($method, $url, $headers);
        } else {
            throw new \Exception('Method not allowed');
        }


        $options = array_merge([
            'headers' => $headers,
            'cookies' => $cookies,
            'query' => $query
        ]);

        $response = $this->client->send($request, array_filter($options));

        //TODO: Set correct vars
        $this->log->info("Sending " . $request->getMethod() . " request to " . $request->getUri());
        $this->log->debug("Parameters: " . $request->getRequestTarget());
        //$this->log->debug("Headers: ". print_r($request->getHeaders()));
        $this->log->info("Response with code: " . $response->getStatusCode());
        //$this->log->debug("Headers: ". print_r($response->getHeaders()));
        $this->log->debug("History: HISTORY");
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
                        $msg .= ' ' + $e['error']['description'];
                    }
                }
            } catch (\Exception $e) {
                $this->log->debug("Response error is not JSON format");
            }
        }
        return $response;
    }

    public function check_tokens()
    {
        return false;
    }

    public function __toString()
    {
        return "Hola";
    }

    private function save_access()
    {
        $t = [];
        $date = new \DateTime('now');
        $t['token'] = $this->token;
        $t['identities'] = $this->identities;
        $t['context'] = $this->context;
        $t['date'] = $date->format("Y-m-d H:i:s");

        serialize($t);

        file_put_contents($this->ACCESS_FILE, serialize($t));
        $this->log->info('Saving access to file');
    }

    private function get_login_info()
    {
        $action = new EdistribucionMessageAction(
            215,
            "WP_Monitor_CTRL/ACTION\$getLoginInfo",
            "WP_Monitor",
            ["serviceNumber" => "S011"]
        );

        return $this->run_action_command($action);
    }

    private function run_action_command(EdistribucionMessageAction $action, $command = null)
    {
        $data = [
            'message' => '{"actions":[' . $action . ']}'
        ];

        if (!$command){
            $command = $action->getCommand();
        }

        return $this->command("other.{command}=1", $data);
    }

    private function command()
    {

    }

}

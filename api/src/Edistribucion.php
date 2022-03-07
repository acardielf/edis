<?php

namespace Edistribucion;

use Cassandra\Date;
use DateTime;
use Edistribucion\EdisError;
use Edistribucion\EdistribucionMessageAction;
use Edistribucion\UrlError;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Cookie\SessionCookieJar;
use http\Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Http\Message\ResponseInterface;

class Edistribucion
{

    private $session;
    private $SESSION_FILE;
    private $ACCESS_FILE;
    private string $token;
    private array $credentials;
    private string $dashboard;
    private int $command_index;
    private array $identities;
    private $appInfo;
    private $context;
    private DateTime $access_date;
    private Logger $log;
    private Client $client;
    private string $username;
    private string $password;
    private $jar;

    public function __construct()
    {
        $this->jar = new SessionCookieJar('CookieJar', true);
        $this->log = new Logger('name');
        $this->log->pushHandler(new StreamHandler('php://stdout', Logger::INFO));
        $this->client = new Client([
            'base_uri' => "https://zonaprivada.edistribucion.com/",
            'cookies' => $this->jar
        ]);
        $this->SESSION_FILE = "edistribucion.session";
        $this->ACCESS_FILE = "edistribucion.access";
        $this->session = $this->jar->toArray();
        $this->token = "undefined";
        $this->credentials = [];
        $this->dashboard = "https://zonaprivada.edistribucion.com/areaprivada/s/sfsites/aura?";
        $this->command_index = 1;
        $this->identities = [];
        $this->appInfo = null;
        $this->context = null;
        $this->access_date = new DateTime("now");
        $this->processSessionFile();
        $this->processAccessFile();
    }

    private function processSessionFile()
    {
        try {
            if (file_exists($this->SESSION_FILE)) {
                $sessions = unserialize(file_get_contents($this->SESSION_FILE));
                foreach ($sessions as $sesion){
                    $this->jar->setCookie(new SetCookie($sesion));
                }

                $this->jar = $sessions;
            } else {
                $this->log->warning("Access file not found");
            }
        } catch (\Exception $e){
            $this->log->warning("Session file not found");
        }
    }

    private function processAccessFile()
    {
        try  {
            if (file_exists($this->ACCESS_FILE)) {
                $access = unserialize(file_get_contents($this->ACCESS_FILE));
                $this->token = $access['token'];
                $this->identities = $access['identities'];
                $this->context = $access['context'];
                $this->access_date = new DateTime($access['date']);
            } else {
                $this->log->warning("Access file not found");
            }

        } catch (\Exception $e){
            $this->log->warning("Access file not found");
        }
    }

    public function login(string $username, string $password)
    {
        $this->log->info("Logging...");
        $this->username = $username;
        $this->password = $password;
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
        $this->log->info('Retrieving account info');
        $r = $this->get_login_info();
        $this->identities['account_id'] = $r['visibility']['Id'];
        $this->identities['name'] = $r['Name'];
        $this->log->info("Received name: " . $r['Name'] . " (". $r['visibility']['Visible_Account__r']['Identity_number__c'].")");
        $this->log->debug("Account_id: " . $this->identities['account_id']);

        $file = fopen($this->SESSION_FILE,  "w+");
        fwrite($file, serialize($this->jar->toArray()));
        fclose($file);
        chmod($this->SESSION_FILE, 0700);
        $this->log->debug("Saving session");
        $this->save_access();;

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

    public function check_tokens(): bool
    {
        $this->log->debug("Checking tokens");
        return $this->token != 'undefined' && $this->access_date->modify("+10 minutes") > new DateTime("NOW");
    }

    public function __toString(): string
    {
        return "to string...";
    }

    private function save_access()
    {
        $t = [];
        $date = new \DateTime('now');
        $t['token'] = $this->token;
        $t['identities'] = $this->identities;
        $t['context'] = $this->context;
        $t['date'] = $date->format("Y-m-d H:i:s");

        $file = fopen($this->ACCESS_FILE, "w+");
        fwrite($file, serialize($t));
        fclose($file);
        chmod($this->ACCESS_FILE, 0700);
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

        return $this->command("other.{command}=1", ['data' => $data]);
    }

    /**
     * @param $command
     * @param $options
     * @return mixed|string
     * @throws \Exception
     */
    private function command($command, $options)
    {

        $default = [
            'data' => null,
            'dashboard' => null,
            'accept' => '*/*',
            'content_type' => null,
            'recursive' => false,
        ];

        $data = (array_key_exists("data", $options) && !empty($options['data'])) ? $options['data'] : $default['data'];
        $dashboard = (array_key_exists("dashboard", $options) && !empty($options['dashboard'])) ? $options['dashboard'] : $default['dashboard'];
        $accept = (array_key_exists("accept", $options) && !empty($options['accept'])) ? $options['accept'] : $default['accept'];
        $content_type = (array_key_exists("content_type", $options) && !empty($options['content_type'])) ? $options['content_type'] : $default['content_type'];
        $recursive = (array_key_exists("recursive", $options) && !empty($options['recursive'])) ? $options['recursive'] : $default['recursive'];
        $headers = [];

        if (!$dashboard){
            $dashboard = $this->dashboard;
        }

        if ($this->command_index){
            $command = "r=" . $this->command_index . "&";
            $this->command_index  += 1;
        }

        $this->log->info("Preparing command: ". $command);

        if ($data) {
            $data['aura.context'] = $this->context;
            $data['aura.pageURI'] = '/areaprivada/s/wp-online-access';
            $data['aura.token'] = $this->token;
            $this->log->debug("POST DATA: " . json_encode($data));
        }

        $this->log->debug("Dashboard: ". $dashboard);

        if ($accept){
            $this->log->debug("Accept: " . $accept);
            $headers['Accept'] = $accept;
        }

        if ($content_type) {
            $this->log->debug("Content-type: " . $content_type);
            $headers['Content-Type'] = $content_type;
        }

        $r = $this->get_url($dashboard.$command, [
            "method" => "POST",
            "data" => $data,
            "headers" => $headers,
        ]);

        $rText = $r->getBody()->getContents();
        $rHeaderContent = $r->getHeader("Content-Type");

        if (str_contains($rText, "window.location.href") || str_contains($rText, "clientOutOfSync")) {
            if (!$recursive){
                $this->log->info("Redirection received. Fetching credentials again");
                $this->session = $_SESSION;
                $this->force_login();
                $options['recursive'] = true;
                $this->command($command, $options);
            } else {
                $this->log->warning("Redirection received twice. Aborting command.");
            }
        }

        if (str_contains($rHeaderContent[0], "json")){
            $jr = json_decode($rText, true);
            if ($jr['actions'][0]['state'] != "SUCCESS") {
                if (!$recursive) {
                    $this->log->info("Error received. Fetching credentials again");
                    $this->session = $_SESSION;;
                    $this->force_login();
                    $options['recursive'] = true;
                    $this->command($command, $options);
                } else {
                    $this->log->warning("Error received twice. Aborting command.");
                    throw new \Exception("Error procesing command: //TODO message");
                }
            }
            return $jr['actions'][0]['returnValue'];
        }
        return $rText;
    }

    public function get_cups(){
        $action = new EdistribucionMessageAction(
            270,
            "WP_ContadorICP_F2_CTRL/ACTION\$getCUPSReconectarICP",
            "WP_Reconnect_ICP",
            ["visSelected" => $this->identities['account_id']]
        );

        return $this->run_action_command($action);
    }

}

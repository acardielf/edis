<?php

namespace Edistribucion;

use Cassandra\Date;
use DateTime;
use Edistribucion\Actions\GetAllCups;
use Edistribucion\Actions\GetAtrDetail;
use Edistribucion\Actions\GetCupsStatus;
use Edistribucion\Actions\GetMaximeter;
use Edistribucion\Actions\GetMeasure;
use Edistribucion\Actions\GetMeas;
use Edistribucion\Actions\GetLoginInfo;
use Edistribucion\Actions\DoLogin;
use Edistribucion\Actions\GetCups;
use Edistribucion\Actions\GetMeter;
use Edistribucion\Actions\GetSolicitudAtrDetail;
use Edistribucion\Actions\ReconnectICPDetail;
use Edistribucion\Actions\ReconnectICPModal;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Cookie\SessionCookieJar;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Http\Message\ResponseInterface;

class Edistribucion
{

    const USER_AGENT = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:98.0) Gecko/20100101 Firefox/98.0';
    const EDIS_BASE_URI = "https://zonaprivada.edistribucion.com";
    const EDIS_AREAPRIVADA_URL = 'areaprivada/s/login?ec=302&startURL=%2Fareaprivada%2Fs%2F';
    const EDIS_DASHBOARD = "/areaprivada/s/sfsites/aura?";
    const URL_EXECUTE_COMMAND = '/areaprivada/s/wp-online-access';
    const AREAPRIVADA_S = "/areaprivada/s/";
    const STORE_PATH = 'tmp/';
    const LOGLEVEL = Logger::INFO;

    private string $file_session_path;
    private string $file_access_path;

    private DateTime $access_date;
    private array $identities;
    private ?string $token;

    private int $command_index;
    private string $dashboard;

    private Client $client;
    private Logger $log;

    private string $username;
    private string $password;

    private SessionCookieJar $jar;

    /**
     * @throws EdisError
     */
    public function __construct(string $username, string $password)
    {
        $this->jar = new SessionCookieJar('EdisSession', true);

        $this->log = new Logger('EdisLog');
        $this->log->pushHandler(new StreamHandler('php://stdout', self::LOGLEVEL));

        $this->client = new Client([
            'base_uri' => self::EDIS_BASE_URI,
            'cookies' => $this->jar
        ]);

        $this->username = $username;
        $this->password = $password;

        $this->file_session_path = self::STORE_PATH . sprintf("edistribucion.%s.session", $this->username);
        $this->file_access_path = self::STORE_PATH . sprintf("edistribucion.%s.access", $this->username);

        $this->token = null;

        $this->dashboard = self::EDIS_BASE_URI . self::EDIS_DASHBOARD;

        $this->command_index = 1;
        $this->identities = [];
        $this->access_date = new DateTime("now");

        $this->processSessionFile();
        $this->processAccessFile();

    }

    /**
     * @throws EdisError
     */
    private function getDeserializeContentOfFile(string $filepath)
    {
        try {
            if (!file_exists($filepath)) {
                $this->log->debug("File not found: " . $filepath);
                return null;
            }

            if (!file_get_contents($filepath)) {
                $this->log->warning("File can't be read or it's empty");
                return null;
            }
            return unserialize(file_get_contents($filepath));
        } catch (EdisError $exception) {
            throw new EdisError("Error processing file: " . $filepath);
        }


    }

    /**
     * @return void
     * @throws EdisError
     */
    private function processSessionFile(): void
    {
        $sessions = $this->getDeserializeContentOfFile($this->file_session_path);

        if (!$sessions) {
            return;
        }

        foreach ($sessions as $session) {
            $this->jar->setCookie(new SetCookie($session));
        }
        $this->log->info("Session restored");
    }

    /**
     * @return void
     * @throws EdisError
     */
    private function processAccessFile(): void
    {
        $access = $this->getDeserializeContentOfFile($this->file_access_path);

        if (!$access) {
            return;
        }

        if (
            !array_key_exists('token', $access) ||
            !array_key_exists('identities', $access) ||
            !array_key_exists('context', $access) ||
            !array_key_exists('date', $access)
        ) {
            $this->log->warning("Access file malformed. Some key it's missing");
            return;
        }

        $this->token = $access['token'];
        $this->identities = $access['identities'];
        $this->context = $access['context'];
        $this->access_date = new DateTime($access['date']);
        $this->log->info("Access details restored");
    }


    /**
     * @throws \Exception
     */
    public function login(): bool
    {
        if (!$this->check_tokens()) {
            $this->log->info("Not exist previous session or it's expired...");
            return $this->force_login();
        }
        $this->log->info("You're logged. You should be able to execute actions");
        $this->jar->save();
        return true;

    }

    /**
     * @throws \Exception
     */
    private function force_login($recursive = false): bool
    {
        $this->log->warning("Forcing login");

        $response = $this->get_url(self::EDIS_AREAPRIVADA_URL)->getBody()->getContents();

        if (!strpos($response, "auraConfig")) {
            throw new EdisError('auraConfig not found. Cannot continue');
        }

        $this->log->info("Loading scripts");
        $scripts = $this->getTagFromHTML('script', $response);
        $this->log->debug("Founded " . sizeof($scripts) . " scripts ");


        foreach ($scripts as $tag) {
            $src = $tag->getAttribute('src');
            if (!$src) {
                continue;
            }
            if (strpos($src, "resources.js")) {
                $unq = rawurldecode($src);
                $fo = strpos($unq, "{");
                $lo = strrpos($unq, "}");
                $this->context = substr($unq, $fo, $lo - $fo + 1);
                $this->log->debug("Founded JSON APP Info!", [(json_decode($this->context))]);
                $this->appInfo = json_decode($this->context);
            }
        }

        $this->log->info('Performing login routine:');

        $params = [
            "username" => $this->username,
            "password" => $this->password,
            "startUrl" => self::AREAPRIVADA_S
        ];

        $action = new DoLogin($params);


        $response = $this->get_url($this->dashboard . 'other.LightningLoginForm.login=1', [
            "method" => "POST",
            "data" => [
                'message' => '{"actions":[' . $action . ']}',
                'aura.context' => $this->context,
                'aura.pageURI' => self::EDIS_AREAPRIVADA_URL,
                'aura.token' => 'undefined',
            ],
        ])->getBody()->getContents();


        if (str_contains($response, "/*ERROR*/")) {
            if (str_contains($response, "invalidSession") && !$recursive) {
                $this->jar->clear();
                $this->force_login(true);
                return true;
            }
            $this->log->error("Error executing command. Response: ", [$response]);
            throw new EdisError("Unexpected error in loginForm. Cannot continue.");
        }

        $json = json_decode($response, true);

        if (!array_key_exists('events', $json)) {
            throw new EdisError("Wrong login response. Cannot continue");
        }


        //Accessing to frontdoor
        $this->log->info('Accessing to frontdoor:');
        $this->log->debug("URL: " . $json['events'][0]['attributes']['values']['url']);
        $this->get_url($json['events'][0]['attributes']['values']['url'])->getBody()->getContents();

        //Accessing to landing page
        $this->log->info('Accessing to landing page:');
        $response = $this->get_url(self::EDIS_BASE_URI . self::AREAPRIVADA_S)->getBody()->getContents();

        if (!strpos($response, "auraConfig")) {
            throw new EdisError('auraConfig not found. Cannot continue');
        }

        $ix = strpos($response, "{", strpos($response, "auraConfig"));
        $ed = strpos($response, ";", $ix);
        $json = json_decode(substr($response, $ix, $ed - $ix), true);


        if (!array_key_exists('token', $json)) {
            throw new EdisError("Wrong login response. Cannot continue");
        }

        $this->token = $json['token'];

        $this->log->debug('');
        $this->log->debug('===============');
        $this->log->info('TOKEN RECEIVED!');
        $this->log->debug('===============');
        $this->log->debug('');
        $this->log->debug($this->token);

        $this->log->info('Retrieving account info:');

        $r = $this->get_login_info();

        $this->identities['account_id'] = $r['visibility']['Id'];
        $this->identities['name'] = $r['Name'];
        $this->log->info("Received name: " . $r['Name'] . " (" . $r['visibility']['Visible_Account__r']['Identity_number__c'] . ")");
        $this->log->debug("Account_id: " . $this->identities['account_id']);


        $this->save_session_file();
        $this->save_access_file();
        $this->jar->save();

        return true;

    }

    private function get_url(string $url, $options = []): ResponseInterface
    {
        $default = [
            'method' => 'GET',
            'headers' => [
                'User-Agent' => self::USER_AGENT
            ],
            'cookies' => null,
            'query' => null,
            'data' => null
        ];

        $options = array_merge($default, $options);
        if ($options['data']) {
            $options['query'] = http_build_query($options['data'], null, "&");
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

    public function check_tokens(): bool
    {
        $this->log->debug("Checking tokens");
        return $this->token && $this->access_date->modify("+10 minutes") > new DateTime("NOW");
    }

    public function __toString(): string
    {
        return "to string...";
    }

    private function save_session_file()
    {
        $file = fopen($this->file_session_path, "w+");
        fwrite($file, serialize($this->jar->toArray()));
        fclose($file);
        chmod($this->file_session_path, 0700);
        $this->log->debug("Saving session");
    }

    private function save_access_file()
    {
        $t = [];
        $date = new \DateTime('now');
        $t['token'] = $this->token;
        $t['identities'] = $this->identities;
        $t['context'] = $this->context;
        $t['date'] = $date->format("Y-m-d H:i:s");

        $file = fopen($this->file_access_path, "w+");
        fwrite($file, serialize($t));
        fclose($file);
        chmod($this->file_access_path, 0700);
        $this->log->info('Saving access to file');
    }

    /**
     * @throws \Exception
     */
    private function get_login_info(): string|array
    {
        $action = new GetLoginInfo();
        return $this->run_action_command($action);
    }

    /**
     * @throws \Exception
     */
    private function run_action_command(EdisActionGeneric $action, string $command = null): string|array
    {
        $data = ['message' => '{"actions":[' . $action . ']}'];
        $command = ($command) ?: $action->getCommand();
        return $this->command("other.{command}=1", ['data' => $data]);
    }


    /**
     * @param string $command
     * @param array $options
     * @param bool $recursive
     *
     * @return string|array
     * @throws EdisError
     * @throws \Exception
     */
    private function command(string $command, array $options, bool $recursive = false): string|array
    {
        $default = [
            'data' => null,
            'dashboard' => null,
            'accept' => '*/*',
            'content_type' => null,
            'recursive' => false,
        ];
        $options = array_merge($default, $options);

        $headers = [];
        $options['dashboard'] = ($options['dashboard']) ?: $this->dashboard;


        if ($this->command_index) {
            $command = "r=" . $this->command_index . "&";
            $this->command_index += 1;
        }

        $this->log->debug("Preparing command: " . $command);

        if ($options['data']) {
            $options['data']['aura.context'] = $this->context;
            $options['data']['aura.pageURI'] = self::URL_EXECUTE_COMMAND;
            $options['data']['aura.token'] = $this->token;
            $this->log->debug("POST DATA: " . json_encode($options['data']));
        }

        $this->log->debug("Dashboard: " . $options['dashboard']);

        if ($options['accept']) {
            $this->log->debug("Accept: " . $options['accept']);
            $headers['Accept'] = $options['accept'];
        }

        if ($options['content_type']) {
            $this->log->debug("Content-type: " . $options['content_type']);
            $headers['Content-Type'] = $options['content_type'];
        }

        $response = $this->get_url($options['dashboard'] . $command, [
            "method" => "POST",
            "data" => $options['data'],
            "headers" => $headers,
        ]);

        $rText = $response->getBody()->getContents();
        $rHeaderContent = $response->getHeader("Content-Type");

        if (str_contains($rText, "window.location.href") || str_contains($rText, "clientOutOfSync")) {
            if (!$recursive) {
                $this->log->info("Redirection received. Fetching credentials again");
                $this->jar->clear();
                $this->force_login(true);
                $this->command($command, $options, true);
            } else {
                $this->log->warning("Redirection received twice. Aborting command.");
            }
        }

        if (!str_contains($rHeaderContent[0], "json")) {
            return $rText;
        }

        $jr = json_decode($rText, true);
        if ($jr['actions'][0]['state'] != "SUCCESS") {
            if (!$recursive) {
                $this->log->error("Error: " . $command);
                $this->log->info("Error received. Fetching credentials again");
                $this->force_login(true);
                $this->command($command, $options, true);
            } else {
                $this->log->warning("Error received twice. Aborting command.");
                throw new EdisError("Error processing command.");
            }
        }
        return $jr['actions'][0]['returnValue'];

    }

    public function getTagFromHTML(string $tag, string $html): \DOMNodeList
    {
        $htmlDOM = new \DOMDocument();
        @$htmlDOM->loadHTML($html);
        return $htmlDOM->getElementsByTagName($tag);
    }

    /**
     * @throws \Exception
     */
    public function get_cups(): string|array
    {
        $action = new GetCups(["visSelected" => $this->identities['account_id']]);
        return $this->run_action_command($action);
    }

    /**
     * @throws \Exception
     */
    public function get_cups_info(string $cupsId): string|array
    {
        $action = new GetCupsStatus(["cupsId" => $cupsId]);
        return $this->run_action_command($action);
    }

    /**
     * @throws \Exception
     */
    public function get_meter(string $cupsId): string|array
    {
        $action = new GetMeter(["cupsId" => $cupsId]);
        return $this->run_action_command($action);
    }

    /**
     * @throws \Exception
     */
    public function get_all_cups(): string|array
    {
        $action = new GetAllCups(["visSelected" => $this->identities['account_id']]);
        return $this->run_action_command($action);
    }

    /**
     * @throws \Exception
     */
    public function get_cups_detail(string $cupsId): string|array
    {
        $action = new GetAtrDetail([
            "visSelected" => $this->identities['account_id'],
            "cupsId" => $cupsId
        ]);
        return $this->run_action_command($action);
    }

    /**
     * @throws \Exception
     */
    public function get_cups_status(string $cupsId): string|array
    {
        $action = new GetCupsStatus(["cupsId" => $cupsId]);
        return $this->run_action_command($action);
    }

    /**
     * @throws \Exception
     */
    public function get_atr_detail(string $atrId): string|array
    {
        $action = new GetAtrDetail(["atrId" => $atrId]);
        return $this->run_action_command($action);
    }

    /**
     * @throws \Exception
     */
    public function get_solicitud_atr_detail(string $solId): string|array
    {
        $action = new GetSolicitudAtrDetail(["solId" => $solId]);
        return $this->run_action_command($action);
    }

    /**
     * La orden de  reconexión ha sido enviada con éxito a tu contador.
     * En caso de que habiendo activado el ICP sigas sin tener suministro,
     * llama a Averías 900 850 840
     *
     * @throws \Exception
     */
    public function reconnect_ICP(string $cupsId): string|array
    {
        $action = new ReconnectICPDetail(["cupsId" => $cupsId]);
        $r = $this->run_action_command($action);

        $action = new ReconnectICPModal(["cupsId" => $cupsId]);
        $r = $this->run_action_command($action);

        return $r;
    }

    /**
     * @param string $cupsId
     *
     * @return string|array
     * @throws \Exception
     */
    public function get_list_cups(string $cupsId): string|array
    {
        $action = new GetMeasure(["sIdentificador" => $this->identities['account_id']]);
        return $this->run_action_command($action);
    }

    /**
     * @param string $contId
     *
     * @return string|array
     * @throws \Exception
     */
    public function get_list_cycles(string $contId): string|array
    {
        $action = new GetMeas(["contId" => $contId]);
        return $this->run_action_command($action);
    }

    /**
     * @param string $contId
     * @param string $cycleLabel
     * @param string $cycleValue
     *
     * @return string|array
     * @throws \Exception
     */
    public function get_meas(string $contId, string $cycleLabel, string $cycleValue): string|array
    {
        $action = new GetMeas([
            "cupsId" => $contId,
            "dateRange" => $cycleLabel,
            "cfactura" => $cycleValue
        ]);
        return $this->run_action_command($action);
    }

    /**
     * @param string $contId
     *
     * @return string|array
     * @throws \Exception
     */
    public function get_measure(string $contId): string|array
    {
        $yesterday = new DateTime('yesterday');

        $action = new GetMeasure([
            "contId" => $contId,
            "type" => 1,
            "startDate" => $yesterday->format("Y-m-d")
        ]);
        $this->log->debug($action);
        return $this->run_action_command($action);
    }

    /**
     * @param string $contId
     *
     * @return string|array
     * @throws \Exception
     */
    public function get_maximeter(string $contId): string|array
    {
        //TODO: Program this vars playing with tempo
        $action = new GetMaximeter([
            "mapParams" => [
                "startDate" => "2/2021",
                "endDate" => "2/2022",
                "id" => "******",
                "sIdentificador" =>"*****"
            ]
        ]);
        $this->log->debug($action);
        return $this->run_action_command($action);
    }


}

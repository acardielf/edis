<?php

namespace Edistribucion\Traits;

use Edistribucion\Actions as Actions;
use Edistribucion\EdisConfigStatic;
use Edistribucion\EdisError;

trait Login
{

    private \DateTime $access_date;
    private array $identities = [];
    private ?string $token = null;

    private string $username;
    private string $password;


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

        $response = $this->get_url(
            EdisConfigStatic::EDIS_AREAPRIVADA_BASE . EdisConfigStatic::EDIS_AREAPRIVADA_URL
        )->getBody()->getContents();

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
            "startUrl" => EdisConfigStatic::EDIS_AREAPRIVADA_BASE
        ];

        $action = new Actions\DoLogin($params);

        $response = $this->get_url($this->dashboard . 'other.LightningLoginForm.login=1', [
            "method" => "POST",
            "data" => [
                'message' => '{"actions":[' . $action . ']}',
                'aura.context' => $this->context,
                'aura.pageURI' => EdisConfigStatic::EDIS_AREAPRIVADA_URL,
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
        $response = $this->get_url(
            EdisConfigStatic::EDIS_AREAPRIVADA_BASE
        )->getBody()->getContents();

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

    public function check_tokens(): bool
    {
        $this->log->debug("Checking tokens");
        return $this->token && $this->access_date->modify("+10 minutes") > new \DateTime("NOW");
    }


    public function getTagFromHTML(string $tag, string $html): \DOMNodeList
    {
        $htmlDOM = new \DOMDocument();
        @$htmlDOM->loadHTML($html);
        return $htmlDOM->getElementsByTagName($tag);
    }



}
<?php

namespace Edistribucion\Traits;

use Edistribucion\Actions as Actions;
use Edistribucion\EdisConfigStatic;
use Edistribucion\EdisError;
use Exception;
use Peast\Peast;

trait Login
{

    private \DateTime $access_date;
    private array $identities = [];
    private ?string $token = null;

    private string $username;
    private string $password;


    /**
     * @throws Exception
     */
    public function login(): bool
    {
        if (!$this->check_tokens()) {
            $this->log->info("Not exist previous session or it's expired...");
            $this->jar->clear();
            return $this->force_login(recursive: false);
        }
        $this->log->info("You're logged. You should be able to execute actions");
        $this->jar->save();
        return true;
    }

    /**
     * @throws Exception
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

        $this->update_context($response);
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
                $this->force_login(recursive: true);
                return true;
            }
            $this->log->error("Error executing command. Response: ", [$response]);
            throw new EdisError("Unexpected error in loginForm. Cannot continue.");
        }

        $json = json_decode($response, true);

        if (!array_key_exists('events', $json ?? [])) {
            throw new EdisError("Wrong login response. Cannot continue");
        }

        //Accessing to frontdoor
        $this->log->info('Accessing to frontdoor:');
        $this->log->debug("URL: " . $json['events'][0]['attributes']['values']['url']);
        $this->get_url($json['events'][0]['attributes']['values']['url'])->getBody()->getContents();

        //Accessing to landing page
        $this->log->info('Accessing to landing page:');
        $this->token = $this->get_token();
        if (!$this->token) {
            throw new EdisError("Cannot obtain token. Cannot continue");
        }
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
        $this->log->info(
            "Received name: " . $r['Name'] . " (" . $r['visibility']['Visible_Account__r']['Identity_number__c'] . ")"
        );
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

    /**
     * @param $response
     * @return void
     */
    public function update_context($response): void
    {
        $this->log->info("Loading scripts");
        $scripts = $this->getTagFromHTML('script', $response);
        $this->log->debug("Founded " . sizeof($scripts) . " scripts ");


        foreach ($scripts as $tag) {
            $src = $tag->getAttribute('src');
            if (!$src) {
                continue;
            }
            if (strpos($src, "resources.js")) {
                try {
                    $unq = rawurldecode($src);
                    $fo = strpos($unq, "{");
                    $lo = strrpos($unq, "}");
                    $json = json_decode(substr($unq, $fo, $lo - $fo + 1), true);

                    $loadedJson = json_encode($json["loaded"], JSON_UNESCAPED_SLASHES);
                    $loadedJson = str_replace(" ", "", $loadedJson);
                    $context = sprintf(
                        '{"mode":"%s","fwuid":"%s","app":"%s","loaded":%s,"dn":[],"globals":{},"uad":false}',
                        $json["mode"],
                        $json["fwuid"],
                        $json["app"],
                        $loadedJson
                    );

                    $this->context = $context;
                    $this->log->debug("Founded JSON APP Info!", [($this->context)]);
                    $this->appInfo = $this->context;
                } catch (Exception $e) {
                    $this->log->error("Cannot obtain dynamic context from resources.js");
                }
            }
        }
    }

    /**
     * @return string|null
     * @throws EdisError
     */
    public function get_token(): ?string
    {
        $response = $this->get_url(
            EdisConfigStatic::EDIS_AREAPRIVADA_BASE
        )->getBody()->getContents();
        $this->update_context($response);

        $scripts = $this->getTagFromHTML('script', $response);

        foreach ($scripts as $script) {
            $text = $this->innerHTML($script);
            if ($text && str_contains($text, 'auraConfig')) {
                $jsonString = $this->extractJson($text);
                if ($jsonString) {
                    $parsed = Peast::latest($text)->parse();
                    if ($parsed) {
                        foreach ($parsed->getBody() as $b) {
                            $decls = $b
                                ->getExpression()
                                ->getExpression()
                                ->getCallee()
                                ->getBody()
                                ->getBody() ?? [];
                            foreach ($decls as $d) {
                                if ($d->getType() === 'VariableDeclaration') {
                                    foreach ($d->getDeclarations() as $dc) {
                                        if ($dc->getId()?->getName() === 'auraConfig') {
                                            foreach ($dc->getInit()?->getProperties() as $prop) {
                                                if ($prop->getKey()->getValue() === 'eikoocnekot') {
                                                    $cookieVar = $prop->getValue()?->getValue();
                                                    $ret = $this->jar->getCookieByName($cookieVar)->getValue() ?? null;
                                                    return $ret;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return null;
    }

    private function extractJson($scriptContent)
    {
        // Extrae el JSON de la cadena del script utilizando expresiones regulares
        if (preg_match('/({.*})/', $scriptContent, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function innerHTML(\DOMElement $element): string
    {
        $doc = $element->ownerDocument;

        $html = '';

        foreach ($element->childNodes as $node) {
            $html .= $doc->saveHTML($node);
        }

        return $html;
    }


}
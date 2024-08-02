<?php

namespace Edistribucion\Traits;

use Edistribucion\EdisActionGeneric;
use Edistribucion\EdisConfigStatic;
use Edistribucion\EdisError;

trait Command
{

    private int $command_index = 1;
    private string $dashboard;

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


        if ($this->command_index >= 0) {
            $command = "r=" . $this->command_index . "&" . $command;
            $this->command_index++;
        }

        $this->log->debug("Preparing command: " . $command);

        if ($options['data']) {
            $options['data']['aura.context'] = $this->context;
            $options['data']['aura.pageURI'] = EdisConfigStatic::EDIS_AREAPRIVADA_BASE . EdisConfigStatic::URL_EXECUTE_COMMAND;
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
                //$this->jar->clear();
                $this->force_login(recursive: false);
                $this->command($command, $options, true);
            } else {
                $this->log->warning("Redirection received twice. Aborting command.");
            }
        }

        if (str_contains($rHeaderContent[0], "json")) {
            if (str_contains($rText, "Invalid token")) {
                if (!$recursive) {
                    //$this->jar->clear();
                    $this->token = $this->get_token();
                    $this->command($command, $options, true);
                } else {
                    $this->log->warning("Token expired. Cannot refresh");
                    throw new EdisError("Token expired. Cannot refresh");
                }
            }

            $jr = json_decode($rText, true);

            if ($jr['actions'][0]['state'] != "SUCCESS") {
                if (!$recursive) {
                    $this->log->error("Error: " . $command);
                    $this->log->info("Error received. Fetching credentials again");
                    //$this->jar->clear();
                    $this->force_login(recursive: false);
                    $this->command($command, $options, true);
                } else {
                    $this->log->warning("Error received twice. Aborting command.");
                    throw new EdisError("Error processing command.");
                }
            }

            return $jr['actions'][0]['returnValue'];
        }

        return $rText;
    }


    /**
     * @throws \Exception
     */
    private function run_action_command(EdisActionGeneric $action, string $command = null): string|array
    {
        $data = ['message' => '{"actions":[' . $action . ']}'];
        $command = ($command) ?: $action->getCommand();
        return $this->command("other.$command=1", ['data' => $data]);
    }


}
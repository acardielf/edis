<?php

namespace Edistribucion\Traits;

use Edistribucion\EdisError;
use GuzzleHttp\Cookie\SetCookie;

trait Files
{

    private string $file_session_path;
    private string $file_access_path;

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
        $this->access_date = new \DateTime($access['date']);
        $this->log->info("Access details restored");
    }


    private function save_session_file()
    {
        file_put_contents($this->file_session_path, serialize($this->jar->toArray()));
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

        file_put_contents($this->file_access_path, serialize($t));
        chmod($this->file_access_path, 0700);
        $this->log->info('Saving access to file');
    }



}
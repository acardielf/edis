<?php
/*
 * Project: e-distribucion package
 * Author: Ángel Cardiel <angel.cardiel@protonmail.com>
 * Thanks to trocotronic, duhow, polhenarejos and rest for their contribution and for open me the way
 * Date: 30/3/22 0:06
 * Version: 0.1
 *
 * A PHP package (based on https://github.com/trocotronic/edistribucion) for use in PHP projects
 * to access data from e-distribucion private area.
 *
 * Developed and distributed under GPL-3.0 License and exclusively for academic purposes.
 *
 * DISCLAIMER:
 *
 *  Please note: this package is released for use "AS IS" without any warranties of any kind,
 *  including, but not limited to their installation, use, or performance. We disclaim any and
 *  all warranties, either express or implied, including but not limited to any warranty of
 *  non-infringement, merchantability, and/ or fitness for a particular purpose. We do not
 *  warrant that the technology will meet your requirements, that the operation thereof
 *  will be uninterrupted or error-free, or that any errors will be corrected.
 *
 *  Any use of these scripts and tools is at your own risk. There is no guarantee that they
 *  have been through thorough testing in a comparable environment, and we are not
 *  responsible for any damage or data loss incurred with their use.
 *
 *  You are responsible for reviewing and testing any scripts you run thoroughly before use
 *  in any non-testing environment.
 */
namespace Edistribucion;

use DateTime;
use Edistribucion\Traits as Traits;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SessionCookieJar;
use Monolog\Logger;

class EdisClient
{

    use Traits\Files;
    use Traits\Login;
    use Traits\GetUrl;
    use Traits\Command;
    use Traits\Logger;
    use Traits\ActionsDefinition;

    private SessionCookieJar $jar;
    private Client $client;
    private Logger $log;


    /**
     * @throws EdisError
     */
    public function __construct(string $username, string $password)
    {
        $this->createLogger();

        $this->jar = new SessionCookieJar('EdisSession', true);

        $this->client = new Client([
            'base_uri' => EdisConfigStatic::EDIS_BASE_URI,
            'cookies' => $this->jar
        ]);

        $this->username = $username;
        $this->password = $password;

        $this->file_session_path = EdisConfigStatic::STORE_PATH . sprintf("edistribucion.%s.session", $this->username);
        $this->file_access_path = EdisConfigStatic::STORE_PATH . sprintf("edistribucion.%s.access", $this->username);

        $this->dashboard = EdisConfigStatic::EDIS_AREAPRIVADA_BASE . EdisConfigStatic::EDIS_DASHBOARD;
        $this->access_date = new DateTime("now");

        $this->processSessionFile();
        $this->processAccessFile();

    }



}

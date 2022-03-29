<?php
namespace Edistribucion;

class EdisConfigStatic
{

    /**
     * Choose the folder to store login sessions
     */
    const STORE_PATH = 'tmp/';

    /**
     * Choose the output for Logger.
     * You should use a valid path file with write permissions or 'php://stdout'
     * Default: 'php://stdout'
     */
    const LOGGER_OUTPUT = 'logger.log';
    //const LOGGER_OUTPUT = 'php://stdout';

    /**
     * Choose the minimal log detail according the levels of messages. Take a look
     * to the class \Monolog\Logger to see the options. Default: \Monolog\Logger::INFO
     */
    const LOGLEVEL = \Monolog\Logger::INFO;

    /**
     * Choose the User Agent to feed the urls with Guzzle
     * You can use this webpage to generate random User Agent: https://generate-name.net/user-agent
     */
    const USER_AGENT = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:98.0) Gecko/20100101 Firefox/98.0';


    /**
     *  ===================================
     *          e-distribucion urls
     *  ==================================
     */

    const EDIS_BASE_URI = "https://zonaprivada.edistribucion.com";
    const EDIS_AREAPRIVADA_BASE = "/areaprivada/s/";
    const EDIS_AREAPRIVADA_URL = 'login?ec=302&startURL=%2Fareaprivada%2Fs%2F';
    const EDIS_DASHBOARD = "sfsites/aura?";
    const URL_EXECUTE_COMMAND = 'wp-online-access';


}

<?php

namespace Edistribucion;

class EdisConfigStatic
{

    /**
     * Choose the folder to store login sessions
     */
    const string STORE_PATH = 'tmp/';

    /**
     * Choose the output for Logger.
     * You should use a valid path file with write permissions or 'php://stdout'
     * Default: 'php://stdout'
     */
    const string LOGGER_OUTPUT = 'logger.log';
    //const string LOGGER_OUTPUT = 'php://stdout';

    /**
     * Choose the minimal log detail according the levels of messages. Take a look
     * to the class \Monolog\Logger to see the options. Default: \Monolog\Logger::INFO
     */
    const \Monolog\Level LOGLEVEL = \Monolog\Level::Info;

    /**
     * Choose the User Agent to feed the urls with Guzzle
     * You can use this webpage to generate random User Agent: https://generate-name.net/user-agent
     */
    const string USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.14; rv:77.0) Gecko/20100101 Firefox/77.0';


    /**
     *  ===================================
     *          e-distribucion urls
     *  ==================================
     */

    const string EDIS_BASE_URI = "https://zonaprivada.edistribucion.com";
    const string EDIS_AREAPRIVADA_BASE = "/areaprivada/s/";
    const string EDIS_AREAPRIVADA_URL = 'login/?language=es&startURL=%2Fareaprivada%2Fs%2F&ec=302';
    const string EDIS_DASHBOARD = "sfsites/aura?";
    const string URL_EXECUTE_COMMAND = '';


}

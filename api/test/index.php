<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Edistribucion\Edistribucion;


session_start();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$dotenv->required('EDIS_USER');
$dotenv->required('EDIS_PASSWORD');

echo "API PARA USUARIO " . $_ENV['EDIS_USER'] . PHP_EOL;

$api = new Edistribucion($_ENV['EDIS_USER'],$_ENV['EDIS_PASSWORD']);
$api->login();
var_dump($api->get_cups());


//var_dump($api->get_cups_info($homeCups));
//var_dump($api->get_meter($homeCups));
//var_dump($api->get_cups_detail($homeCups));
//var_dump($api->get_cups_status($homeCups));
//var_dump($api->reconnect_ICP($homeCups));
//var_dump($api->get_list_cups($homeCups));
//var_dump($api->get_list_cycles($homeCups));
//var_dump($api->get_meas($homeCups, "24/01/2022 - 20/02/2022", "*****"));
//var_dump($api->get_measure($homeCups));
//var_dump($api->get_maximeter($homeCups));




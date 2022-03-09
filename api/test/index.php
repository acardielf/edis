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
var_dump($api->get_cups());



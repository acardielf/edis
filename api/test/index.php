<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Edistribucion\Edistribucion;

session_start();
echo "API";
$api = new Edistribucion();
$api->login("USUARIO","PASSWORD");

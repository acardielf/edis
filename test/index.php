<?php
/*
 * Project: e-distribucion package
 * Author: Ángel Cardiel <angel.cardiel@protonmail.com>
 * Thanks to trocotronic, duhow, polhenarejos and rest for their contribution and for open me the way
 * Date: 30/3/22 0:06
 * Version: 0.1
 *
 * A PHP package based on https://github.com/trocotronic/edistribucion for use in PHP projects
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

require_once __DIR__ . '/../vendor/autoload.php';

use Edistribucion\EdisClient;
use Edistribucion\EdisError;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$dotenv->required('EDIS_USER');
$dotenv->required('EDIS_PASSWORD');

try {
    $edis = new EdisClient($_ENV['EDIS_USER'], $_ENV['EDIS_PASSWORD']);
    $edis->login();
} catch (EdisError|Exception $e) {
    echo $e->getMessage();
    exit;
}

//var_dump($edis->get_cups());
$cups = $edis->get_cups();
$homeCups = $cups['data']['lstCups'][0]['Id'];
//var_dump($edis->get_cups_info($homeCups));
//var_dump($edis->get_meter($homeCups));
//var_dump($edis->get_cups_detail($homeCups));
//var_dump($edis->get_cups_status($homeCups));
//var_dump($edis->reconnect_ICP($homeCups));
//var_dump($edis->get_list_cups($homeCups));
//var_dump($edis->get_list_cycles($homeCups));
//var_dump(
//    $edis->get_meas(
//        cups: $homeCups,
//        cycleLabel: "24/01/2024 - 20/02/2024",
//        cycleValue: "*****"
//    )
//);
//var_dump(
//    $edis->get_meas_interval(
//        cups: $homeCups,
//        startDate: DateTimeImmutable::createFromFormat("d/m/Y", "20/07/2024"),
//        endDate: DateTimeImmutable::createFromFormat("d/m/Y", "30/07/2024")
//    )
//);
//var_dump($edis->get_measure($homeCups));
//var_dump($edis->get_maximeter($homeCups));
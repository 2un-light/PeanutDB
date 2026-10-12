<?php

use PeanutDB\Engine\PeanutDB;

require_once __DIR__ . '/../vendor/autoload.php';

$db = new PeanutDB(__DIR__ . '/../data/users.db');

//INSERT
$db->put(10, 'Walnut');
$db->put(20, 'Almond');
$db->put(30, 'Pistachio');
$db->put(40, 'Cashew');

//SELECT
echo $db->get(20) . PHP_EOL;

//UPDATE
$db->put(20, 'Peanut');
echo $db->get(20) . PHP_EOL;

//DELETE
$db->delete(30);
var_dump($db->get(30));

//RANGE
print_r($db->range(10, 40));
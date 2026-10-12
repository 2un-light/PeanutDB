<?php

use PeanutDB\Storage\FileStorage;

require_once __DIR__ . '/../vendor/autoload.php';

$db = new FileStorage(__DIR__ . '/../data/users.db');

//INSERT
$db->put('user:1', 'Walnut');
$db->put('user:2', 'Almond');

//SELECT
echo $db->get('user:1') . PHP_EOL;

//UPDATE
$db->put('user:1', 'Pistachio');
echo $db->get('user:1') . PHP_EOL;

//DELETE
$db->delete('user:2');

var_dump($db->get('user:2'));
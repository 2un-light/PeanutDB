<?php

use PeanutDB\Index\BPTree;

require_once __DIR__ . '/../vendor/autoload.php';

$tree = new BPTree(4);

$tree->insert(10, 'Walnut');
$tree->insert(20, 'Almond');
$tree->insert(30, 'Pistachio');
$tree->insert(40, 'Cashew');
$tree->insert(50, 'Peanut');
$tree->insert(60, 'Hazelnut');
$tree->insert(70, 'Macadamia');

$result = $tree->range(20, 60);
print_r($result);
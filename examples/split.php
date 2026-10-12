<?php

use PeanutDB\Index\BPTree;

require_once __DIR__ . '/../vendor/autoload.php';

$tree = new BPTree(4);

$tree->insert(10, 'Walnut');
$tree->insert(20, 'Almond');
$tree->insert(30, 'Pistachio');
$tree->insert(40, 'Cashew');

//첫번째 분할 후 검색
echo $tree->search(10) . PHP_EOL;
echo $tree->search(30) . PHP_EOL;
echo $tree->search(40) . PHP_EOL;
<?php

use PeanutDB\Index\BPTree;

require_once __DIR__ . '/../vendor/autoload.php';

$tree = new BPTree(4);

//여러 키 삽입
for($i = 10; $i <= 200; $i += 10) {
    $tree->insert($i, "Value {$i}");
}

//모든 키가 검색되는지 확인
for($i = 10; $i <= 200; $i += 10) {
    echo "{$i}: " . $tree->search($i) . PHP_EOL;
}

//기존 키 수정
$tree->insert(50, 'Updated Value');

echo $tree->search(50) . PHP_EOL;
<?php

declare(strict_types=1);

use PeanutDB\Engine\PeanutDB;
use PeanutDB\Storage\FileStorage;

require_once __DIR__ . '/../vendor/autoload.php';

// ======== 1. 벤치마크 환경 설정 ========

//데이터 갯수를 1,000, 5,000, 10,000건으로 변경하며 측정
$counts = [1000, 5000, 10000];

$dir = __DIR__ . '/../data/test';

if(!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)){
    throw new RuntimeException('Cannot create test directory');
}

// ======== 2. 실행 시간 측정 함수 ========
//경과 시간 측정
function elapsedMs(callable $operation): float {
    $start = hrtime(true);

    $operation();
    
    //ms 단위로 변환
    return (hrtime(true) - $start) / 1_000_000;
}

//측정 결과 소수점 둘째 자리까지 표시
function fmt(float $n): string {
    return number_format($n, 2);
}

// ======== 3. 결과 표 헤더 출력 ========

printf(
    "%8s | %12s | %12s | %13s | %13s | %12s\n",
    'records',
    'scan write',
    'index write',
    'scan read',
    'index read',
    'index reopen'
);

echo "-------------------------------------------------------------------------------------\n";

// ======== 4. 데이터 개수별 반복 측정 ========
foreach($counts as $count) {
    
    //인덱스 없는 경우 - DB 파일 경로 생성
    $scanFile = $dir . "/scan_{$count}.db";

    //B+Tree 인덱스를 사용하는 DB 파일 경로 생성
    $indexFile = $dir . "/index_{$count}.db";

    //======== 4-1. 이전 테스트 데이터 초기화 ========
    foreach([$scanFile, $indexFile] as $path) {
        if(file_exists($path) && !unlink($path)) {
            throw new RuntimeException("Cannot reset file: {$path}");
        }
    }

    //======== 4-2. 데이터베이스 객체 생성 ========
    $scan = new FileStorage($scanFile);
    $indexed = new PeanutDB($indexFile);

    //======== 4-3. 전체 스캔 방식의 저장 시간 측정 ========
    $scanWrite = elapsedMs(function() use ($scan, $count): void {
        for($key = 1; $key <= $count; $key++) {
            $scan->put((string)$key, 'value_' . $key);
        }
    });


    //======== 4-4. B+Tree 방식의 저장 시간 측정 ========
    $indexWrite = elapsedMs(function() use ($indexed, $count): void {
        for($key = 1; $key <= $count; $key++) {
            $indexed->put($key, 'value_' . $key);
        }
    });

    //======== 4-5. 조회할 키 목록 생성 ========
    $keys = [];
    //100개의 조회 키 생성
    for($i = 0; $i < 100; $i++) {
        //전체 데이터 범위에 조회 키를 고르게 배치
        $keys[] = 1 + intdiv($i * ($count -  1), max(1, 100 - 1));
    }

    //======== 4-6. 전체 스캔 방식의 조회 시간 측정 ========
    $scanRead = elapsedMs(function () use ($scan, $keys): void {
        foreach($keys as $key) {
            $actual = $scan->get((string)$key);
            $expected = 'value_' . $key;

            if($actual !== $expected) {
                throw new RuntimeException('Scan read mismatch');
            }
        }
    });

    //======== 4-7. B+Tree 방식의 조회 시간 측정 ========
    $indexRead = elapsedMs(function () use ($indexed, $keys): void {
        foreach($keys as $key) {
            $actual = $indexed->get($key);

            $expected = 'value_' . $key;

            if($actual !== $expected) {
                throw new RuntimeException('Scan read mismatch');
            }
        }
    });

    //======== 4-8. 인덱스 재구성 시간 측정 ========
    $reopen = elapsedMs(function () use ($indexFile, $count): void {
        $reopened = new PeanutDB($indexFile);

        if($reopened->get($count) !== 'value_' . $count) {
            throw new RuntimeException('Reopen read mismatch');
        }
    });

    //======== 4-9. 측정 결과 출력 ========
    printf(
        "%8d | %10sms | %10sms | %11sms | %11sms | %10sms\n",
        $count,             // 데이터 개수
        fmt($scanWrite),    // 전체 스캔 방식 저장 시간
        fmt($indexWrite),   // B+Tree 방식 저장 시간
        fmt($scanRead),     // 전체 스캔 방식 조회 시간
        fmt($indexRead),    // B+Tree 방식 조회 시간
        fmt($reopen)        // 인덱스 재구성 및 조회 시간
    );

}

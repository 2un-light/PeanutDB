<?php

namespace PeanutDB\Storage;

use RuntimeException;

class FileStorage {
    //$filePath: 데이터 저장 경로
    public function __construct(private string $filePath) {
        $directory = dirname($filePath);

        //상위 디렉터리가 없으면 함께 생성
        if(!is_dir($directory)){
            if(!mkdir($directory, 0775, true) && !is_dir($directory)){
                throw new RuntimeException('Cannot create storage directory');
            }
        }

        if(!file_exists($filePath)) {
            if(touch($filePath) === false) {
                throw new RuntimeException('Cannot create database file');
            }
        }
    }

    //저장하기 (operation, key, value)
    public function put(string $key, string $value): void {
        $this->append([
            'op' => 'put',
            'key' => $key,
            'value' => $value
        ]);
    }

    //조회하기 (키의 최신 값 조회)
    public function get(string $key): ?string {
        $value = null;

        //읽기 전용 바이너리 모드(rb)로 열기
        $handle = fopen($this->filePath, 'rb');

        if($handle === false) {
            throw new RuntimeException('Cannot Open database file');
        }

        try{
            
            while(($line = fgets($handle)) !== false) {
                $record = json_decode($line, true, 512, JSON_THROW_ON_ERROR);

                if($record['key'] !== $key) {
                    continue;
                }

                //해당 키의 마지막 작업이 delete라면
                if($record['op'] === 'delete') {
                    $value = null; //삭제 처리
                }else {
                    //put이면 현재 값을 저장
                    $value = $record['value'];
                }
            }
        }finally {
            fclose($handle);
        }

        return $value;

    }

    //삭제하기
    public function delete(string $key): void {
        $this->append([
            'op' => 'delete',
            'key' => $key,
        ]);
    }

    //파일 쓰기
    private function append(array $record): void {
        $line = json_encode($record, JSON_THROW_ON_ERROR) . "\n";

        // 파일에 데이터를 기록
        // FILE_APPEND: 기존 내용을 지우지 않고 파일 끝에 추가
        // LOCK_EX: 쓰기 작업 중 파일 잠금 요청
        if(file_put_contents(
            $this->filePath,
            $line,
            FILE_APPEND | LOCK_EX
        ) === false) {
            throw new RuntimeException('Failed to write database record');
        }
    }


}


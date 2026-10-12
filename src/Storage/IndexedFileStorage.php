<?php

namespace PeanutDB\Storage;

use RuntimeException;

class IndexedFileStorage {
    public function __construct(private string $filePath) {
        $directory = dirname($filePath);

        if(!is_dir($directory)) {
            if(!mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException('Cannot create directory');
            }
        }

        if(!file_exists($filePath) && touch($filePath) === false) {
            throw new RuntimeException('Cannot create database file');
        }
    }

    //새로운 레코드 저장, 시작 Offset 반환
    public function append(array $record): int {
        $handle = fopen($this->filePath, 'c+b');

        if($handle === false) {
            throw new RuntimeException('Cannot open file');
        }

        try {
            //쓰기 잠금 획득
            if(!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Cannot lock file');
            }

            //파일의 마지막 위치로 이동
            if(fseek($handle, 0, SEEK_END) !== 0) {
                throw new RuntimeException('Cannot seel to end');
            }

            //현재 위치 = 새 레코드의 시작 Offset
            $offset = ftell($handle);

            if($offset === false) {
                throw new RuntimeException('Cannot get offset');
            }

            //배열을 JSON 문자열로 변환
            $data = json_encode($record, JSON_THROW_ON_ERROR) . "\n";

            //데이터 전체 기록
            $written = 0;
            $length = strlen($data);

            while($written < $length) {
                $bytes = fwrite($handle, substr($data, $written));

                if($bytes === false || $bytes === 0) {
                    throw new RuntimeException('Write filed');
                }

                $written += $bytes;
            }

            if(!fflush($handle)) {
                throw new RuntimeException('Flush failed');
            }

            return $offset;
        }finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    //특정 Offset 레코드 읽기
    public function readAt(int $offset): array {
        $handle = fopen($this->filePath, 'rb');

        if($handle === false) {
            throw new RuntimeException('Cannot open files');
        }

        try{
            if(!flock($handle, LOCK_SH)) {
                throw new RuntimeException('Cannot lock file');
            }

            if(fseek($handle, $offset, SEEK_SET) !== 0) {
                throw new RuntimeException('Invalid Offset');
            }

            $line = fgets($handle);

            if($line === false) {
                throw new RuntimeException('Record not found');
            }

            return json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        }finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    //파일 순서대로 읽으며 인덱스 재구성
    public function scan(callable $callback): void {
        $handle = fopen($this->filePath, 'rb');

        if($handle === false) {
            throw new RuntimeException('Cannot open file');
        }

        try{

            if(!flock($handle, LOCK_SH)) {
                throw new RuntimeException('Cannot lock file');
            }

            while(true) {
                $offset = ftell($handle);

                if($offset === false) {
                    throw new RuntimeException('Cannot get offset');
                }

                $line = fgets($handle);

                if($line === false) {
                    break;
                }

                $record = json_decode($line, true, 512, JSON_THROW_ON_ERROR);

                $callback($record, $offset);
            }

        }finally{
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }


    
}
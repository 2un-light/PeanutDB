<?php

namespace PeanutDB\Storage;

use RuntimeException;

//데이터 파일을 읽고 쓰는 저장 클래스
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
    
    //새 레코드를 파일 맨 끝에 저장
    //그 레코드의 시작 offset 반환
    public function append(array $record): int {
        //지우지 않고 열고(c), 읽기/쓰기(+), 바이너리 (b)
        $handle = fopen($this->filePath, 'c+b');

        if($handle === false) {
            throw new RuntimeException('Cannot open file');
        }

        try {
            //쓰기 잠금 (LOCK_EX)
            if(!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Cannot lock file');
            }

            //포인터 맨 끝으로 이동
            if(fseek($handle, 0, SEEK_END) !== 0) {
                throw new RuntimeException('Cannot seek end');
            }

            //현재 포인터 위치 반환
            $offset = ftell($handle);

            if($offset === false) {
                throw new RuntimeException('Cannot get offset');
            }

            //JSON 배열로 변환
            $data = json_encode($record, JSON_THROW_ON_ERROR) . "\n";

           
            $written = 0;  //지금까지 쓴 바이트 수
            $length = strlen($data);

            while($written < $length) {
                $bytes = fwrite($handle, substr($data, $written));

                if($bytes === false || $bytes === 0) {
                    throw new RuntimeException('Write Failed');
                }

                $written += $bytes;
            }

            //PHP 내부 버퍼 운영체제로 내보내기
            if(!fflush($handle)) {
                throw new RuntimeException('Flush Failed');
            }

            return $offset; //포인터 위치 반환 (레코드 시작 위치)

        }finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    //특정 offset에 있는 레코드 한줄 읽어 배열로 반환
    public function readAt(int $offset): array {
        $handle = fopen($this->filePath, 'rb');

        if($handle === false) {
            throw new RuntimeException('Cannot open file');
        }

        try{
            //읽기 잠금
            if(!flock($handle, LOCK_SH)) {
                throw new RuntimeException('Cannot lock file');
            }

            //파일 시작 기준으로 offset 위치로 점프
            if(fseek($handle, $offset, SEEK_SET) !== 0) {
                throw new RuntimeException('Invalid offset');
            }

            //offset 위치부터 줄바꿈까지 한줄 읽기
            $line = fgets($handle);

            if($line === false) {
                throw new RuntimeException('Record not found');
            }

            //JSON 문자열 -> 배열
            return json_decode($line, true, 512, JSON_THROW_ON_ERROR);

        }finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    //파일 처음부터 순서대로 읽으며 레코드,offset을 콜백에 넘김
    public function scan(callable $callback): void {
        $handle = fopen($this->filePath, 'rb');

        if($handle === false) {
            throw new RuntimeException('Cannot open file');
        }

        try{

            //읽기 잠금
            if(!flock($handle, LOCK_SH)) {
                throw new RuntimeException('Cannot lock file');
            }

            //파일 끝까지 반복
            while(true) {
                $offset = ftell($handle);

                if($offset === false) {
                    throw new RuntimeException('Cannot get offset');
                }

                //한줄 읽기
                $line = fgets($handle);

                if($line === false) {
                    break;
                }

                //한줄 -> 배열
                $record = json_decode($line, true, 512, JSON_THROW_ON_ERROR);

                $callback($record, $offset);
            }

        }finally{
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
    
}
<?php

namespace PeanutDB\Engine;

use PeanutDB\Index\BPTree;
use PeanutDB\Storage\IndexedFileStorage;

class PeanutDB {
    private BPTree $index;
    private IndexedFileStorage $storage;

    public function __construct(string $filePath) {
        //B+Tree 인덱스 생성
        $this->index = new BPTree(4);

        $this->storage = new IndexedFileStorage($filePath);

        $this->storage->scan(function (array $record, int $offset) {
            $this->index->insert($record['key'], (string) $offset);
        });

    }

    public function put(int $key, string $value): void {
        $offset = $this->storage->append([
            'op' => 'put',
            'key' => $key,
            'value' => $value
        ]);

        $this->index->insert($key, (string) $offset);
    }

    public function get(int $key): ?string {
        $offset = $this->index->search($key);

        if($offset === null) {
            return null;
        }

        $record = $this->storage->readAt((int) $offset);

        if($record['op'] === 'delete') {
            return null;
        }

        return $record['value'];
    }

    public function delete(int $key): void {
        $offset = $this->storage->append([
            'op' => 'delete',
            'key' => $key
        ]);

        $this->index->insert($key, (string) $offset);
    }

    public function range(int $start, int $end): array {
        $results = [];

        $offsets = $this->index->range($start, $end);

        foreach($offsets as $key => $offset) {
            $record = $this->storage->readAt((int) $offset);

            if($record['op'] !== 'delete') {
                $results[$key] = $record['value'];
            }
        }

        return $results;

    }
}
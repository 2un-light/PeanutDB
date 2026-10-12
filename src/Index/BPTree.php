<?php

namespace PeanutDB\Index;

class BPTree {
    //트리 최상위 노드
    private BPTreeNode $root;

    //order: 한 내부 노드가 가질 수 있는 최대 자식 수
    public function __construct(private int $order = 4) {
        //리프 노드 생성
        $this->root = new BPTreeNode(true);
    }

    //루트에서 삽입
    public function insert(int $key, string $value): void {
        //루트 노드부터 재귀 삽입
        $split = $this->insertRecursive($this->root, $key, $value);

        //루트가 분할되지 않았다면 삽입 완료
        if($split === null) {
            return;
        }

        //루트가 분할됐다면, 새로운 루트 노드 생성
        $newRoot = new BPTreeNode(false);

        //부모의 구분 키
        $newRoot->keys = [$split['key']];

        //왼쪽 노드와 오른쪽 노드를 자식으로 연결
        $newRoot->children = [
            $this->root,
            $split['right']
        ];

        $this->root = $newRoot;

    }

    //키에 해당하는 값 검색
    public function search(int $key): ?string {
        $node = $this->root;

        //리프 노드가 아니면 -> 리프노드 찾기
        while(!$node->isLeaf) {
            $i = 0;

            while(
                $i < count($node->keys) &&
                $key >= $node->keys[$i]
            ) {
                $i++;
            }

            //검색할 키가 속한 자식 노드로 이동
            $node = $node->children[$i];
        }

        foreach($node->keys as $i => $storedKey) {
            if($storedKey === $key) {
                return $node->values[$i];
            }
        }

        return null;
    }

    //범위 검색
    public function range(int $start, int $end): array {
        $results = [];

        if($start > $end) {
            return $results;
        }

        $node = $this->root;

        while(!$node->isLeaf) {
            $i = 0;

            while(
                $i < count($node->keys) &&
                $start >= $node->keys[$i]
            ) {
                $i++;
            }

            $node = $node->children[$i];
        }

        while($node !== null) {
            foreach($node->keys as $i => $key) {
                if($key > $end) {
                    return $results;
                }

                if($key >= $start) {
                    $results[$key] = $node->values[$i];
                }
            }

            $node = $node->next;
        }

        return $results;
    }

    //올바른 리프까지 내려가 데이터 삽입, 분할 결과 부모에게 전달
    private function insertRecursive(
        BPTreeNode $node, 
        int $key,
        string $value
    ): ?array {

        //1. 리프 노드에 도착했다면
        if($node->isLeaf) {
            $i = 0;

            while(
                $i < count($node->keys) &&
                $node->keys[$i] < $key
            ){
                $i++;
            }

            //기존 키라면 값만 수정
            if(
                $i < count($node->keys) &&
                $node->keys[$i] === $key
            ) {
                $node->values[$i] = $value;
                return null;
            }

            //새로운 키와 값 삽입
            array_splice($node->keys, $i, 0, [$key]);
            array_splice($node->values, $i, 0, [$value]);

            //최대 키 개수를 초과하지 않았다면 종료
            if(count($node->keys) < $this->order) {
                return null;
            }

            //리프 노드 분할
            return $this->splitLeaf($node);
        }

        //2. 내부 노드라면 적절한 자식 탐색
        $i = 0;

        while(
            $i < count($node->keys) &&
            $key >= $node->keys[$i]
        ) {
            $i++;
        }

        //선택한 자식 노드에 재귀 삽입
        $split = $this->insertRecursive($node->children[$i], $key, $value);

        //자식 노드가 분할되지 않았다면 종료
        if($split === null) return null;

        //3. 자식이 분할되었다면 부모에 구분 키 추가
        array_splice($node->keys, $i, 0, [$split['key']]);

        //새 오른쪽 자식을 children에 추가
        array_splice($node->children, $i+1, 0, [$split['right']]);

        //부모 노드가 넘치지 않았다면 종료
        if(count($node->keys) < $this->order) {
            return null;
        }

        return $this->splitInternal($node);

    }

    
    //리프 노드를 둘로 분할
    private function splitLeaf(BPTreeNode $node): array {
        //가운데 위치 계산
        $mid = intdiv(count($node->keys), 2);

        //새로운 오른쪽 리프 노드 생성
        $right = new BPTreeNode(true);

        $right->keys = array_splice($node->keys, $mid);
        $right->values = array_splice($node->values, $mid);

        //새 오른쪽 노드가 기존 다음 리프를 가리키도록
        $right->next = $node->next;

        //기존 노드가 새 오른쪽 노드를 가리키도록
        $node->next = $right;

        //부모 노드에 전달할 구분 키와 새로운 오른쪽 노드 반환
        return [
            'key' => $right->keys[0],
            'right' => $right,
        ];

    }

    //내부 노드 둘로 분할
    private function splitInternal(BPTreeNode $node): array {
        //가운데 키 위치
        $mid = intdiv(count($node->keys), 2);

        //부모 노드로 올라갈 키
        $promotedKey = $node->keys[$mid];

        $right = new BPTreeNode(false);

        $right->keys = array_slice($node->keys, $mid + 1);

        $right->children = array_slice($node->children, $mid + 1);

        $node->keys = array_slice($node->keys, 0, $mid);

        $node->children = array_slice($node->children, 0, $mid + 1);

        return [
            'key' => $promotedKey,
            'right' => $right
        ];

    }

    
    

}
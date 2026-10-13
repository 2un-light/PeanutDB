<?php

namespace PeanutDB\Index;

class BPTree {
    //트리 최상위 노드
    private BPTreeNode $root;

    //order: 한 내부 노드가 가질 수 있는 최대 자식 수(키는 최대 order - 1개)
    public function __construct(private int $order = 4) {
        //리프 노드 생성
        $this->root = new BPTreeNode(true);
    }

    //데이터 삽입(외부)
    public function insert(int $key, string $value): void {
        //루트부터 재귀 삽입
        //반환값: 분할이 없으면 null, 있으면 ['key'=>구분키, 'right'=>새 오른쪽 노드]
        $split = $this->insertRecursive($this->root, $key, $value);

        if($split === null) {
            return;
        }

        //루트가 분할된 경우
        $newRoot = new BPTreeNode(false);
        $newRoot->keys = [$split['key']];
        $newRoot->children = [
            $this->root,
            $split['right']
        ];

        $this->root = $newRoot;
    }

    //키로 값 탐색
    public function search(int $key): ?string {
        $node = $this->root;

        //리프에 도달할 때까지
        while(!$node->isLeaf) {
            $i = 0;

            while(
                $i < count($node->keys) &&
                $node->keys[$i] <= $key
            ) {
                $i++;
            }

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
    //반환값 : [키 => 값(offset)]
    public function range(int $start, int $end): array {
        $results = [];

        if($start > $end) {
            return $results;
        }

        $node = $this->root;

        //시작점(start) 리프 찾기
        while(!$node->isLeaf) {
            $i = 0;

            while(
                $i < count($node->keys) &&
                $node->keys[$i] <= $start
            ){
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

    //재귀 삽입 : 리프까지 내려가서 넣고, 분할이 생기면 부모에게 결과 반환
    //반환: null(분할 X), ['key' => 구분키, 'right' => 새 오른쪽 노드](분할 O)
    private function insertRecursive(BPTreeNode $node, int $key, string $value): ?array {

        // ===== 1. 리프 노드에 도착했다면 (재귀의 끝) =====
        if($node->isLeaf) {
            //키가 들어갈 위치 찾기
            $i = 0;
            while(
                $i < count($node->keys) &&
                $node->keys[$i] < $key
            ){
                $i++;
            }

            //이미 같은 키가 있다면 값만 교체
            if(
                $i < count($node->keys) &&
                $node->keys[$i] === $key
            ){
                $node->values[$i] = $value;
                return null;
            }

            //새 키 들어갈 위치에 넣기
            array_splice($node->keys, $i, 0, [$key]);
            array_splice($node->values, $i, 0, [$value]);

            //키 갯수가 order 미만일 경우 정상 종료
            if(count($node->keys) < $this->order) {
                return null;
            }

            //order와 같거나 크면 리프노드 쪼갠 후 부모에게 결과 반환
            return $this->splitLeaf($node);
        }


        // ===== 2. 내부 노드라면 내려갈 자식을 고름 =====
        $i = 0;
        
        while(
            $i < count($node->keys) &&
            $node->keys[$i] <= $key
        ) {
            $i++;
        }

        //고른 자식에게 삽입을 맡기고 결과 받기
        $split = $this->insertRecursive($node->children[$i], $key, $value);

        if($split === null) return null;


        // ===== 3. 자식이 분할됐을 경우 =====
        //구분키를 내 keys의 $i에 추가
        array_splice($node->keys, $i, 0, [$split['key']]);

        //자식 연결
        array_splice($node->children, $i + 1, 0, [$split['right']]);

        if(count($node->keys) < $this->order) {
            return null;
        }

        //부모도 넘쳤을 경우
        return $this->splitInternal($node);

    }

    //리프 노드 분할
    private function splitLeaf(BPTreeNode $node): array {
        //가운데 위치 선정
        $mid = intdiv(count($node->keys), 2);

        //오른쪽 노드 생성 후 값 할당
        $right = new BPTreeNode(true);
        $right->keys = array_splice($node->keys, $mid);
        $right->values = array_splice($node->values, $mid);

        $right->next = $node->next;
        $node->next = $right;

        return [
            'key' => $right->keys[0],
            'right' => $right,
        ];

    }

    //내부 노드 분할
    private function splitInternal(BPTreeNode $node) {
        //가운데 위치 선정
        $mid = intdiv(count($node->keys), 2);

        //새로운 내부 노드
        $promptKey = $node->keys[$mid];

        //새 오른쪽 내부 노드
        $right = new BPTreeNode(false);
        $right->keys = array_slice($node->keys, $mid + 1);
        $right->children = array_slice($node->children, $mid + 1);

        //왼쪽(원본 키)
        $node->keys = array_slice($node->keys, 0, $mid);
        $node->children = array_slice($node->children, 0, $mid + 1);

        return [
            'key' => $promptKey,
            'right' => $right
        ];

    }
    

}
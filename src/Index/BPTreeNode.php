<?php

namespace PeanutDB\Index;

class BPTreeNode {
    //리프 노드인지 여부
    public bool $isLeaf;

    //노드가 가진 키 목록
    public array $keys = [];

    //내부 노드인 경우 자식 노드 저장(BPTreeNode)
    public array $children = [];

    //리프 노드인 경우 키에 대응하는 값을 저장
    public array $values = [];

    //다음 리프 노드를 가리키는 참조
    public ?BPTreeNode $next = null;

    public function __construct(bool $isLeaf = true) {
        $this->isLeaf = $isLeaf;
    }
    
}
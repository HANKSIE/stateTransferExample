<?php

class State
{
    private $targets = [];
    private $dynamicCallback = null;

    public function addTargets(...$args)
    {
        $this->targets = array_merge($this->targets, $args);
    }

    public function dynamic($callback)
    {
        $this->dynamicCallback = $callback;
    }

    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }
}

class StateTransfer
{

    private $curr = "";
    private $prev = "";
    private $stateTable = "";

    function  __construct($init, $stateTable)
    {
        $this->curr =  $init;
        $this->stateTable = $stateTable;
    }

    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    public function nextStates()
    {
        $dynamicCallback = $this->stateTable[$this->curr]->dynamicCallback;
        if (is_callable($dynamicCallback)) {
            return $dynamicCallback($this->prev);
        }

        return $this->getStates($this->stateTable[$this->curr]->targets);
    }

    private function getStates($states)
    {
        return array_map(function ($state) {
            return array_search($state, $this->stateTable);
        }, $states);
    }

    public function go($next)
    {
        if (in_array($next, $this->nextStates(), true)) {
            $this->prev =  $this->curr;
            $this->curr = $next;
            return true;
        }
        return false;
    }
}

$notShipped = new State();
$shipped = new State();
$returnApply = new State();
$complete = new State();
$cancel = new State();
$failure = new State();

$stateTable = [
    '未出貨' => $notShipped,
    '已出貨' => $shipped,
    '退貨申請中' => $returnApply,
    '訂單完成' => $complete,
    '訂單取消' => $cancel,
    '訂單失效' => $failure,
];

$notShipped->addTargets($returnApply, $shipped);
$shipped->addTargets($complete, $returnApply);
$returnApply->addTargets($notShipped, $shipped, $complete, $cancel);
$complete->addTargets($returnApply);

$returnApply->dynamic(function ($prev) {
    return [$prev, '訂單取消'];
});

$state = $_POST['state'];
$itemTransfer = new StateTransfer(isset($state) ? $state : '未出貨', $stateTable);

/*
狀態對應:

未出貨 => ['退貨申請中', '已出貨']
已出貨 => ['訂單完成', '退貨申請中']
退貨申請中 =>  ['未出貨', '已出貨', '訂單完成', '訂單失效']
訂單完成 => ['退貨申請中']
訂單取消 => []
訂單失效 => []
*/

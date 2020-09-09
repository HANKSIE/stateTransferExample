<?php
session_start();


class State
{
    /**
     * @property {Callback} $destination - 回傳要前往的狀態名稱
     * @return {Array.<String>}
     */
    public $destination;
}

class StateTransfer
{
    private $stateTable;

    function  __construct($stateTable)
    {
        $this->stateTable = $stateTable;
    }

    /**
     * @param {String} $curr - 當前狀態名稱
     */
    public function fetchCanGo($curr)
    {
        $destination = $this->stateTable[$curr]->destination;
        return $destination();
    }

    /**
     * @param {String} $curr - 當前狀態名稱
     * @param {String} $next - 要前往的狀態名稱
     */
    public function canGo($curr, $next)
    {
        return in_array($next, $this->fetchCanGo($curr), true) && $curr != $next;
    }
}

/**
 * 新增狀態
 */
$notShipped = new State();
$shipped = new State();
$returnApply = new State();
$complete = new State();
$cancel = new State();
$failure = new State();

/**
 * 字串常數
 */
const NOT_SHIPPED = '未出貨';
const SHIPPED = '已出貨';
const RETURN_APPLY = '退貨申請中';
const COMPLETE = '訂單完成';
const CANCEL = '訂單取消';
const FAILURE = '訂單失效';

/**
 * 字串=>狀態對應
 */
$stateTable = [
    NOT_SHIPPED => $notShipped,
    SHIPPED => $shipped,
    RETURN_APPLY => $returnApply,
    COMPLETE => $complete,
    CANCEL => $cancel,
    FAILURE => $failure,
];

$notShipped->destination = function () {
    return [RETURN_APPLY, SHIPPED];
};
$shipped->destination = function () {
    return [COMPLETE, RETURN_APPLY];
};
$complete->destination = function () {
    return [RETURN_APPLY];
};

$returnApply->destination = function () {
    return [$_SESSION['item']['prev'], CANCEL];
};

$cancel->destination = function () {
    return [];
};

$failure->destination = function () {
    return [];
};

if (empty($_SESSION)) {
    $_SESSION['item'] = [
        'curr' => NOT_SHIPPED,
        'prev' => null,
    ];
}
$itemTransfer = new StateTransfer($stateTable);
if (isset($_POST['next'])) {
    if ($itemTransfer->canGo($_SESSION['item']['curr'], $_POST['next'])) {
        $_SESSION['item']['prev'] = $_SESSION['item']['curr'];
        $_SESSION['item']['curr'] = $_POST['next'];
    }
}

$transfer = $itemTransfer->fetchCanGo($_SESSION['item']['curr']);

/*
狀態對應:

未出貨 => ['退貨申請中', '已出貨']
已出貨 => ['訂單完成', '退貨申請中']
退貨申請中 =>  ['未出貨', '已出貨', '訂單完成', '訂單失效']
訂單完成 => ['退貨申請中']
訂單取消 => []
訂單失效 => []
*/

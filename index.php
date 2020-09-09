<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改商品狀態</title>
</head>

<body>
    <?php require("./fsm.php") ?>
    <form action="./index.php" method="post">

        <select name="next">
            <option selected disabled value="<?= $_SESSION['item']['curr'] ?>"><?= $_SESSION['item']['curr'] ?></option>
            <?php foreach ($transfer as $state) : ?>
                <option value="<?= $state ?>"><?= $state ?></option>
            <?php endforeach; ?>
        </select>
        <button>提交</button>
    </form>
</body>

</html>
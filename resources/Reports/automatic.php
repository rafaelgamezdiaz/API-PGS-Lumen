<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= $title ?></title>
</head>
<style>
    table {
        border-collapse: collapse;
        max-width: 100%;
        min-width: 100%;
        margin-top: 10px;
    }

    th, td {
        text-align: center;
        padding: 3px;
        font-size:12px ;
    }

    tr:nth-child(even){background-color: #f2f2f2}

    th {
        background-color: <?=$colors['primary']?>;
        color: white;
    }
    h1{
        font-family: apple-system;
        font-size: 2em;
    }
</style>
<body>
<div>
    <img  style="width: 80px;float: right;" src="<?= $logo ?>" alt="Logo" ><br>
    <div style="text-align: center;">
        <h2>Reporte <?= $title ?></h2>
    </div>

</div>
<div>
    <table>
        <thead>
        <tr>
            <?php foreach ($index as $title => $value):?>
                <th><?php echo $title ?></th>
            <?php endforeach ?>
        </tr>
        </thead>
        <tbody>
        <?php $total = 0; ?>
        <?php foreach ($data as $key):?>
            <tr>
                <?php foreach ($index as $title):?>
                    <td><?php echo is_array($key) ? $key[$title] ?? null : $key->$title ?? null?></td>
                <?php endforeach ?>
            </tr>
        <?php endforeach ?>
        <tr>
            <td>Total de registros</td>
            <td><?php echo count($data); ?></td>
        </tr>
        </tbody>
    </table>
</div>
</body>
</html>


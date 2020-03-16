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
        margin-top: 30px;
    }
    th, td {
        text-align: center;
        padding: 3px;
        font-size:12px ;
        width: fit-content !important;
    }
    tr:nth-child(even){background-color: #f2f2f2}
    th {
        background-color: goldenrod;
        color: white;
    }
    h1{
        font-family: apple-system;
        font-size: 2em;
    }
    img {
        position: absolute !important;
        right: 30px !important;
        top: 20px !important;
        width: 75px !important;
    }
</style>
<body>
    <div>
        <div>
            <img src="<?= $logo ?>" alt="Logo" ><br>
        </div>
        <div style="text-align: center;">
            <h2>Reporte de <?= $title ?></h2>
        </div>
        <div>
            <p><strong>Fecha de emision:</strong> <?= $date; ?></p>
            <p><strong>Usuario:</strong> <?= $username; ?></p>
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
            </tbody>
        </table>
        <h5 style="color: darkslategrey;">Total de pagos <small><?php echo count($data); ?></small></h5>
    </div>
</body>
</html>

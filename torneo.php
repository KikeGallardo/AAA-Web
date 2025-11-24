<?php require_once "assets/header.php"; ?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Torneo</title>
    <link rel="stylesheet" href="assets/css/torneo.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <div class="subtitulo">
        <h1>TORNEO</h1>
    </div>
    <table border="1" class="cuerpoTabla">
            <thead>
                <tr>
                    <th>Acciones<a href="?sort=nomArchivo&order=asc" class="ordenar">▲</a> <a href="?sort=nomArchivo&order=desc" class="ordenar">▼</a></th>
                    <th>NOº<a href="?sort=fecha&order=asc" class="ordenar">▲</a> <a href="?sort=fecha&order=desc" class="ordenar">▼</a></th>
                    <th>Nombre<a href="?sort=tamano&order=asc" class="ordenar">▲</a> <a href="?sort=tamano&order=desc" class="ordenar">▼</a></th>
                </tr>
            </thead>
            <tbody id="cuerpoTabla">
                <tr id="row-<?= $archivo['id'] ?>">
                    <td class="botonesfile">
                        <a class="verbtn" href="verArchivo.php?id=<?= $archivo['id']; ?>" target="_blank"><i class="material-icons">import_contacts</i></a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $archivo['id']; ?>">
                            <button class="verelbtn" type="submit" name="eliminar_individual"><i class="material-icons">delete_sweep</i></button>
                        </form>
                    </td>
                </tr>
                <tr>
                    <td class="findocs" colspan="3">Fin de los documentos.</td>
                </tr>
            </tbody>
        </table>
    
    
</body>
</html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Torneo</title>
    <link rel="stylesheet" href="assets/css/torneo.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <header>
        <nav class="nav_bar_upper">
            <ul class="nav_links">
                <li><a href="dashboard.php"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg></a></li>
            </ul>
            <ul class="nav_links">
                <li><a href="calendario.php"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 2.994v2.25m10.5-2.25v2.25m-14.252 13.5V7.491a2.25 2.25 0 0 1 2.25-2.25h13.5a2.25 2.25 0 0 1 2.25 2.25v11.251m-18 0a2.25 2.25 0 0 0 2.25 2.25h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5a2.25 2.25 0 0 1 2.25-2.25h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5m-6.75-6h2.25m-9 2.25h4.5m.002-2.25h.005v.006H12v-.006Zm-.001 4.5h.006v.006h-.006v-.005Zm-2.25.001h.005v.006H9.75v-.006Zm-2.25 0h.005v.005h-.006v-.005Zm6.75-2.247h.005v.005h-.005v-.005Zm0 2.247h.006v.006h-.006v-.006Zm2.25-2.248h.006V15H16.5v-.005Z" /></svg></a></li>
            </ul>
            <ul class="nav_links">
                <li><a href="programar.php"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">  <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 0 1 9 9v.375M10.125 2.25A3.375 3.375 0 0 1 13.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 0 1 3.375 3.375M9 15l2.25 2.25L15 12" /></svg></a></li>
            </ul>
            <ul class="nav_links">
                <li><a href="arbitros.php"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg></a></li>
            </ul>
        </nav>
    </header>
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
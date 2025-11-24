<?php require_once "assets/header.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programar partido</title>
    <link rel="stylesheet" href="assets/css/programar.css">
    <link rel="stylesheet" href="assets/css/subtitulos.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    </head>
<body>
    <div class="subtitulo">
        <h1>PROGRAMAR PARTIDOS</h1>
    </div>
    <main>
        <div class="contenedorPrincipal">
            <div class="campoArchivos" >

                <form class="nuevoar" method="POST" enctype="multipart/form-data">
                    <label for="archivo" class="ver">
                        Elegir archivos
                    </label>
                    <input type="file" id="archivo" name="archivo[]" accept=".pdf" multiple required style="display: none;">
                    <button id="subir" class="subir" type="submit">Subir</button>
                </form>
            </div>
            <div>
                <div id="listaCategorias" class="lista-categorias">
                    <!-- Lista desplegable de categorias con la opción de crear más-->
                    <select id="categoriasSelect" class="categorias-select">
                        <option value="" disabled selected>Selecciona un torneo</option>
                        <option value="">Opción 1</option>
                        <option value="">Opción 2</option>
                        <option value="">Opción 3</option>
                    </select>
                </div>
            </div>
            <div>
                <div id="listaPago" class="lista-categorias">
                    <!-- Lista desplegable de categorias con la opción de pago-->
                    <select id="pagoSelect" class="categorias-select">
                        <option value="" disabled selected>Programación de Pago</option>
                        <option value="">Opción 1</option>
                        <option value="">Opción 2</option>
                        <option value="">Opción 3</option>
                    </select>
                </div>
            </div>
        </div>
        <script src="assets/js/programar.js"></script>
    </main>
    
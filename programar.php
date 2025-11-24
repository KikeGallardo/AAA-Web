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
         <div class="container">
            <div class="upload-area" id="uploadArea">
                <p style="font-size: 18px; color: #666; margin-bottom: 20px;">
                    Arrastra tu archivo de programación XLSX aquí o haz clic para seleccionar
                </p>
                <input type="file" id="fileInput" accept=".xlsx, .xls">
                <button class="btn" onclick="document.getElementById('fileInput').click()">
                    Cargar Programación
                </button>
            </div>
            
            <div class="loading" id="loading">⏳ Procesando archivo...</div>
            <div class="error" id="error"></div>
            <div class="success" id="success"></div>
            
            <div class="table-container" id="tableContainer" style="display: none;">
                <table id="dataTable" class="cuadricula"></table>
            </div>
        </div>

        <script src="assets/js/xlsx.full.min.js"></script>
        <script src="assets/js/programar.js"></script>
    </main>
    
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
    </main>
    <div class="container">
        <div class="upload-section">
            <h1>📋 Sistema de Programación de Partidos</h1>
            <p class="subtitle">Carga tu archivo Excel con la programación de partidos</p>
            
            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">📁</div>
                <div class="upload-text">Arrastra tu archivo Excel aquí</div>
                <div class="upload-hint">o haz clic para seleccionar</div>
                <input type="file" id="fileInput" accept=".xlsx, .xls">
                <button class="btn" onclick="document.getElementById('fileInput').click()">
                    Seleccionar Archivo
                </button>
            </div>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Procesando archivo...</p>
            </div>
        </div>

        <div class="data-section" id="dataSection">
            <h2>📊 Datos Cargados</h2>
            
            <div class="stats" id="stats"></div>

            <div class="filters">
                <input type="text" id="searchInput" placeholder="🔍 Buscar...">
                <select id="categoriaFilter">
                    <option value="">Todas las categorías</option>
                </select>
                <select id="escenarioFilter">
                    <option value="">Todos los escenarios</option>
                </select>
                <button class="btn" onclick="exportData()">📥 Exportar a JSON</button>
            </div>

            <div style="overflow-x: auto;">
                <table id="dataTable">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th>Equipo A</th>
                            <th>Equipo B</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Escenario</th>
                            <th>Árbitro 1</th>
                            <th>Árbitro 2</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="8" class="no-data">No hay datos cargados</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="assets/js/programar.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</body>
</html>
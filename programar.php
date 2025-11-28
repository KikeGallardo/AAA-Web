<?php require_once "assets/header.php"; ?>
<?php require_once "assets/footer.php"; ?>
<?php include "basedatos.php";?>

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

                        <?php
                        // Consulta correcta (id + nombre)
                        $stmt = $conn->prepare("SELECT idTorneo, nombreTorneo FROM torneo ORDER BY idTorneo DESC");
                        $stmt->execute();
                        $result = $stmt->get_result();

                        // Recorrer todos los torneos
                        while ($torneo = $result->fetch_assoc()): ?>
                            <option value="<?php echo $torneo['idTorneo']; ?>">
                                <?php echo htmlspecialchars($torneo['nombreTorneo']); ?>
                            </option>
                        <?php endwhile;

                        $stmt->close();
                        ?>
                    </select>
                </div>
            </div>
            <div>
                <div id="listaPago" class="lista-categorias">
                    <!-- Lista desplegable de categorias con la opción de pago-->
                    <select id="pagoSelect" class="categorias-select">
                        <option value="" disabled selected>Programación de Pago</option>
                        <option value="">Pago inmediato</option>
                        <option value="">pago a quincena</option>
                        <option value="">Pago a plazos</option>
                    </select>
                </div>
            </div>
        </div>
         <div class="container">
        <h1>Programación de Partidos - Academia Antioqueña</h1>
        
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
        
        <div class="action-buttons" id="actionButtons" style="display: none;">
            <button class="btn btn-success" id="saveBtn" onclick="guardarPartidos()">
                Guardar Programación en Base de Datos
            </button>
            <button class="btn btn-secondary" id="exportBtn" onclick="exportarExcel()">
                Exportar Excel Editado
            </button>
        </div>
        
        <div class="table-container" id="tableContainer" style="display: none;">
            <div class="info-edit">
                <span>Haz clic en cualquier celda para editarla</span>
            </div>
                <table id="dataTable"></table>
            </div>
        </div>

        <script src="assets/js/xlsx.full.min.js"></script>
        <script src="assets/js/programar.js"></script>
    </main>
    
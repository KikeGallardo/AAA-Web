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
                    <select id="torneoSelect" class="categorias-select" required>
                        <option value="" disabled selected>Selecciona un torneo</option>

                        <?php
                        $stmt = $conn->prepare("SELECT idTorneo, nombreTorneo FROM torneo ORDER BY idTorneo DESC");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($torneo = $result->fetch_assoc()): ?>
                            <option value="<?php echo $torneo['idTorneo']; ?>" data-nombre="<?php echo htmlspecialchars($torneo['nombreTorneo']); ?>">
                                <?php echo htmlspecialchars($torneo['nombreTorneo']); ?>
                            </option>
                        <?php endwhile;
                        $stmt->close();
                        ?>
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

    <!-- MODAL ÁRBITROS NO ENCONTRADOS -->
    <div id="modalArbFaltantes" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:14px; padding:2rem; width:520px; max-width:95%; max-height:85vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.25);">
            <h3 style="margin:0 0 .4rem; font-size:1.1rem;">⚠️ Árbitros no encontrados</h3>
            <p style="color:#6b7280; font-size:.85rem; margin-bottom:1.2rem;">Selecciona a cuál árbitro corresponde cada nombre del Excel:</p>
            <div id="modalArbBody"></div>
            <div style="display:flex; gap:.75rem; margin-top:1.5rem;">
                <button onclick="confirmarMapeosArbitros()" style="flex:1; padding:.75rem; background:#1a56db; color:#fff; border:none; border-radius:8px; font-size:.95rem; font-weight:600; cursor:pointer;">Guardar con estos árbitros</button>
                <button onclick="document.getElementById('modalArbFaltantes').style.display='none'" style="flex:1; padding:.75rem; background:#e5e7eb; color:#374151; border:none; border-radius:8px; font-size:.95rem; font-weight:600; cursor:pointer;">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- MODAL ÁRBITROS AMBIGUOS -->
    <div id="modalArbAmbiguos" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:14px; padding:2rem; width:520px; max-width:95%; max-height:85vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.25);">
            <h3 style="margin:0 0 .4rem; font-size:1.1rem;">🔀 Nombres ambiguos</h3>
            <p style="color:#6b7280; font-size:.85rem; margin-bottom:1.2rem;">Estos nombres coinciden con más de un árbitro. Selecciona cuál corresponde:</p>
            <div id="modalArbAmbiguosBody"></div>
            <div style="display:flex; gap:.75rem; margin-top:1.5rem;">
                <button onclick="confirmarMapeosAmbiguos()" style="flex:1; padding:.75rem; background:#1a56db; color:#fff; border:none; border-radius:8px; font-size:.95rem; font-weight:600; cursor:pointer;">Continuar</button>
                <button onclick="document.getElementById('modalArbAmbiguos').style.display='none'" style="flex:1; padding:.75rem; background:#e5e7eb; color:#374151; border:none; border-radius:8px; font-size:.95rem; font-weight:600; cursor:pointer;">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- MODAL CATEGORÍAS NO ENCONTRADAS -->
    <div id="modalCatFaltantes" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:14px; padding:2rem; width:520px; max-width:95%; max-height:85vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.25);">
            <h3 style="margin:0 0 .4rem; font-size:1.1rem;">⚠️ Categorías no encontradas</h3>
            <p style="color:#6b7280; font-size:.85rem; margin-bottom:1.2rem;">Selecciona a cuál categoría corresponde cada una del Excel:</p>
            <div id="modalCatBody"></div>
            <div style="display:flex; gap:.75rem; margin-top:1.5rem;">
                <button onclick="confirmarMapeosCategorias()" style="flex:1; padding:.75rem; background:#1a56db; color:#fff; border:none; border-radius:8px; font-size:.95rem; font-weight:600; cursor:pointer;">Guardar con estas categorías</button>
                <button onclick="document.getElementById('modalCatFaltantes').style.display='none'" style="flex:1; padding:.75rem; background:#e5e7eb; color:#374151; border:none; border-radius:8px; font-size:.95rem; font-weight:600; cursor:pointer;">Cancelar</button>
            </div>
        </div>
    </div>
    </main>
// Obtener elementos del DOM
const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('fileInput');
const loading = document.getElementById('loading');
const errorDiv = document.getElementById('error');
const successDiv = document.getElementById('success');
const tableContainer = document.getElementById('tableContainer');
const dataTable = document.getElementById('dataTable');
const actionButtons = document.getElementById('actionButtons');

// Variables globales para los datos y filtros
let allData = [];
let headers = [];
let editedCells = new Set();

// Eventos de drag and drop
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('drag-over');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('drag-over');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        if (!torneoSeleccionado()) return;
        handleFile(files[0]);
    }
});

// Evento de selección de archivo
fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        if (!torneoSeleccionado()) return;
        handleFile(e.target.files[0]);
    }
});

function torneoSeleccionado() {
    const select = document.getElementById('torneoSelect');
    if (!select.value) {
        showError('Debes seleccionar un torneo antes de cargar el archivo.');
        select.focus();
        return false;
    }
    return true;
}

/**
 * Procesa el archivo Excel seleccionado
 */
function handleFile(file) {
    const validTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel'
    ];
    
    if (!validTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls)$/i)) {
        showError('Por favor selecciona un archivo Excel válido (.xlsx o .xls)');
        return;
    }

    loading.style.display = 'block';
    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';
    tableContainer.style.display = 'none';

    const reader = new FileReader();
    
    reader.onload = (e) => {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            const jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1, defval: '' });
            
            if (jsonData.length === 0) {
                showError('El archivo está vacío');
                return;
            }
            
            // Buscar la fila de encabezados
            let headerRowIndex = -1;
            for (let i = 0; i < jsonData.length; i++) {
                const row = jsonData[i];
                if (row.some(cell => {
                    const cellStr = cell ? cell.toString().toUpperCase() : '';
                    return cellStr.includes('CATEGORIA') || 
                           cellStr.includes('FECHA') || 
                           cellStr.includes('EQUIPO') ||
                           cellStr.includes('ARBITRO');
                })) {
                    headerRowIndex = i;
                    break;
                }
            }
            
            if (headerRowIndex === -1) {
                showError('No se encontraron los encabezados esperados en el archivo. Asegúrate de que la primera fila contenga: FECHA, CATEGORIA, EQUIPO A, etc.');
                return;
            }

            headers = jsonData[headerRowIndex];
            allData = jsonData.slice(headerRowIndex + 1).filter(row => row.some(cell => cell !== ''));
            
            console.log('Headers encontrados:', headers);
            console.log('Total de partidos:', allData.length);
            console.log('Primer partido:', allData[0]);
            
            createTable(allData);
            
            loading.style.display = 'none';
            successDiv.textContent = `✓ Archivo cargado: ${file.name} (${allData.length} partidos)`;
            successDiv.style.display = 'block';
            tableContainer.style.display = 'block';
            actionButtons.style.display = 'block';
            
        } catch (error) {
            console.error('Error al procesar:', error);
            showError('Error al procesar el archivo: ' + error.message);
        }
    };
    
    reader.onerror = () => {
        showError('Error al leer el archivo');
    };
    
    reader.readAsArrayBuffer(file);
}

/**
 * Convierte el número serial de Excel a fecha
 */
function excelDateToJSDate(serial) {
    const utc_days = Math.floor(serial - 25569);
    const utc_value = utc_days * 86400;
    const date_info = new Date(utc_value * 1000);
    
    const day = date_info.getUTCDate().toString().padStart(2, '0');
    const month = (date_info.getUTCMonth() + 1).toString().padStart(2, '0');
    const year = date_info.getUTCFullYear();
    
    return `${day}/${month}/${year}`;
}

/**
 * Convierte el número decimal de Excel a hora
 */
function excelTimeToJSTime(serial) {
    const hours = Math.floor(serial * 24);
    const minutes = Math.floor((serial * 24 * 60) % 60);
    
    const period = hours >= 12 ? 'PM' : 'AM';
    const hours12 = hours % 12 || 12;
    
    return `${hours12}:${minutes.toString().padStart(2, '0')} ${period}`;
}

/**
 * Crea una tabla HTML a partir de los datos del Excel
 */
function createTable(data) {
    let html = '<thead><tr>';
    
    const fechaIndex = headers.findIndex(h => h && h.toString().toUpperCase().includes('FECHA'));
    const horaIndex = headers.findIndex(h => h && h.toString().toUpperCase().includes('HORA'));
    
    headers.forEach(header => {
        html += `<th>${header || ''}</th>`;
    });
    html += '</tr></thead><tbody>';
    
    data.forEach((row, rowIndex) => {
        html += '<tr>';
        row.forEach((cell, colIndex) => {
            let value = cell;
            
            if (colIndex === fechaIndex && typeof cell === 'number' && cell > 1000) {
                value = excelDateToJSDate(cell);
            }
            
            if (colIndex === horaIndex && typeof cell === 'number' && cell < 1) {
                value = excelTimeToJSTime(cell);
            }
            
            html += `<td contenteditable="true" data-row="${rowIndex}" data-col="${colIndex}" onblur="updateCell(this)">${value !== undefined && value !== null ? value : ''}</td>`;
        });
        html += '</tr>';
    });
    
    html += '</tbody>';
    dataTable.innerHTML = html;
}

/**
 * Actualiza el valor de una celda cuando se edita
 */
function updateCell(cell) {
    const row = parseInt(cell.dataset.row);
    const col = parseInt(cell.dataset.col);
    const newValue = cell.textContent.trim();
    
    allData[row][col] = newValue;
    cell.classList.add('edited-cell');
    editedCells.add(`${row}-${col}`);
}

/**
 * Guarda los partidos en la base de datos
 */
async function guardarPartidos() {
    if (allData.length === 0) {
        showError('No hay datos para guardar');
        return;
    }
    
    if (!confirm(`¿Estás seguro de guardar ${allData.length} partidos en la base de datos?`)) {
        return;
    }
    
    loading.style.display = 'block';
    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';
    
    const torneoSelect = document.getElementById('torneoSelect');
    const idTorneo = torneoSelect.value;
    const nombreTorneo = torneoSelect.options[torneoSelect.selectedIndex].dataset.nombre;

    const partidosData = allData.map(row => {
        const partido = {};
        headers.forEach((header, index) => {
            partido[header] = row[index];
        });
        return partido;
    });
    
    console.log('Enviando datos:', {
        total: partidosData.length,
        primer_partido: partidosData[0]
    });
    
    try {
        const response = await fetch("guardar_partidos.php", {
            method: "POST",
            credentials: "include",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                idTorneo: idTorneo,
                nombreTorneo: nombreTorneo,
                partidos: partidosData,
                forzarDuplicados: window._forzarDuplicados || false,
                mapeoCategoriasManual: window._mapeoCategoriasManual || {},
                mapeoArbitrosManual: window._mapeoArbitrosManual || {}
            })
        });
        window._forzarDuplicados = false;
        
        console.log('Response status:', response.status);
        const responseText = await response.text();
        console.log('Response text:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            throw new Error('Respuesta no válida del servidor: ' + responseText.substring(0, 200));
        }
        
        loading.style.display = 'none';
        
        if (data.duplicados && data.duplicados.length > 0) {
            loading.style.display = 'none';
            const lista = data.duplicados.join('\n');
            const confirmar = confirm(`⚠️ PARTIDOS DUPLICADOS (${data.duplicados.length}):\n\n${lista}\n\n¿Deseas guardar de todas formas?`);
            if (confirmar) {
                window._forzarDuplicados = true;
                await guardarPartidos();
            }
            return;
        }

        if (data.error) {
            console.error("Error del servidor:", data.error);

            if (data.error.includes('Sesión expirada')) {
                alert('⚠️ Tu sesión ha expirado. Serás redirigido al login.');
                window.location.href = 'login.php';
                return;
            }

            showError(data.error);

            if (data.arbitros_ambiguos && data.arbitros_ambiguos.length > 0) {
                abrirModalAmbiguos(data.arbitros_ambiguos);
                return;
            }

            if (data.arbitros_faltantes && data.arbitros_faltantes.length > 0) {
                abrirModalArbitros(data.arbitros_faltantes, data.arbitros_disponibles || []);
            }

            if (data.categorias_faltantes && data.categorias_faltantes.length > 0) {
                abrirModalCategorias(data.categorias_faltantes, data.categorias_disponibles || []);
            }

            return;
        }
        
        if (data.success) {
            window._mapeoCategoriasManual = {};
            window._mapeoArbitrosManual   = {};
            successDiv.textContent = `✓ ${data.message} - ${data.guardados} partidos guardados`;
            successDiv.style.display = 'block';
            editedCells.clear();
            
            document.querySelectorAll('.edited-cell').forEach(cell => {
                cell.classList.remove('edited-cell');
            });
            
            if (data.advertencias && data.advertencias.length > 0) {
                alert(`⚠️ HORAS CORREGIDAS AUTOMÁTICAMENTE (${data.advertencias.length}):\n\n` + data.advertencias.join('\n') + '\n\nRevisa estas filas en la programación.');
            }

            if (data.errores && data.errores.length > 0) {
                console.warn("Errores durante el guardado:", data.errores);
                alert("Se guardaron los partidos pero hubo algunos errores:\n\n" + data.errores.join('\n'));
            }
        } else {
            showError(data.message || 'Error al guardar los partidos');
        }
        
    } catch (error) {
        console.error('Error completo:', error);
        loading.style.display = 'none';
        showError('Error de conexión: ' + error.message);
    }
}

function exportarExcel() {
    const wb = XLSX.utils.book_new();
    const wsData = [headers, ...allData];
    const ws = XLSX.utils.aoa_to_sheet(wsData);
    XLSX.utils.book_append_sheet(wb, ws, "Programación");
    XLSX.writeFile(wb, `Programacion_Editada_${new Date().getTime()}.xlsx`);
    
    successDiv.textContent = '✓ Excel exportado correctamente';
    successDiv.style.display = 'block';
}

// ── Modal de árbitros ambiguos ───────────────────────────────
function abrirModalAmbiguos(ambiguos) {
    const filas = ambiguos.map(item => {
        const opciones = item.opciones.map(a =>
            `<option value="${a.id}">${a.nombre}</option>`
        ).join('');
        return `
            <div style="margin-bottom:1rem;">
                <div style="font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.35rem;">
                    🔀 "${item.nombre_excel}" → escoger:
                </div>
                <select data-excel="${item.nombre_excel}"
                        style="width:100%; padding:.5rem .7rem; border:1.5px solid #d1d5db;
                               border-radius:7px; font-size:.9rem; font-family:inherit;">
                    <option value="">— Selecciona un árbitro —</option>
                    ${opciones}
                </select>
            </div>`;
    }).join('');

    document.getElementById('modalArbAmbiguosBody').innerHTML = filas;
    document.getElementById('modalArbAmbiguos').style.display = 'flex';
}

async function confirmarMapeosAmbiguos() {
    const selects = document.querySelectorAll('#modalArbAmbiguosBody select');
    const mapeo = {};
    let incompleto = false;

    selects.forEach(sel => {
        if (!sel.value) { incompleto = true; return; }
        mapeo[sel.dataset.excel] = parseInt(sel.value);
    });

    if (incompleto) {
        alert('Debes seleccionar un árbitro para cada nombre antes de continuar.');
        return;
    }

    document.getElementById('modalArbAmbiguos').style.display = 'none';
    window._mapeoArbitrosManual = { ...(window._mapeoArbitrosManual || {}), ...mapeo };
    await guardarPartidos();
}

// ── Modal de árbitros no encontrados ─────────────────────────
let _arbitrosFaltantes = [];
let _arbitrosDisponibles = [];

function abrirModalArbitros(faltantes, disponibles) {
    _arbitrosFaltantes   = faltantes;
    _arbitrosDisponibles = disponibles;

    const opciones = disponibles.map(a =>
        `<option value="${a.id}">${a.nombre}</option>`
    ).join('');

    const filas = faltantes.map(nombre => `
        <div style="margin-bottom:1rem;">
            <div style="font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.35rem;">
                🧑‍⚖️ "${nombre}" → escoger:
            </div>
            <select data-excel="${nombre}"
                    style="width:100%; padding:.5rem .7rem; border:1.5px solid #d1d5db;
                           border-radius:7px; font-size:.9rem; font-family:inherit;">
                <option value="">— Selecciona un árbitro —</option>
                ${opciones}
            </select>
        </div>
    `).join('');

    document.getElementById('modalArbBody').innerHTML = filas;
    document.getElementById('modalArbFaltantes').style.display = 'flex';
}

async function confirmarMapeosArbitros() {
    const selects = document.querySelectorAll('#modalArbBody select');
    const mapeo = {};
    let incompleto = false;

    selects.forEach(sel => {
        if (!sel.value) { incompleto = true; return; }
        mapeo[sel.dataset.excel] = parseInt(sel.value);
    });

    if (incompleto) {
        alert('Debes seleccionar un árbitro para cada fila antes de continuar.');
        return;
    }

    document.getElementById('modalArbFaltantes').style.display = 'none';
    window._mapeoArbitrosManual = mapeo;
    await guardarPartidos();
}

// ── Modal de categorías no encontradas ───────────────────────
let _categoriasFaltantes = [];
let _categoriasDisponibles = [];

function abrirModalCategorias(faltantes, disponibles) {
    _categoriasFaltantes  = faltantes;
    _categoriasDisponibles = disponibles;

    const opciones = disponibles.map(c =>
        `<option value="${c.id}">${c.nombre}</option>`
    ).join('');

    const filas = faltantes.map(nombre => `
        <div style="margin-bottom:1rem;">
            <div style="font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.35rem;">
                📋 "${nombre}" → escoger:
            </div>
            <select data-excel="${nombre}"
                    style="width:100%; padding:.5rem .7rem; border:1.5px solid #d1d5db;
                           border-radius:7px; font-size:.9rem; font-family:inherit;">
                <option value="">— Selecciona una categoría —</option>
                ${opciones}
            </select>
        </div>
    `).join('');

    document.getElementById('modalCatBody').innerHTML = filas;
    const modal = document.getElementById('modalCatFaltantes');
    modal.style.display = 'flex';
}

async function confirmarMapeosCategorias() {
    const selects = document.querySelectorAll('#modalCatBody select');
    const mapeo = {};
    let incompleto = false;

    selects.forEach(sel => {
        if (!sel.value) { incompleto = true; return; }
        mapeo[sel.dataset.excel] = parseInt(sel.value);
    });

    if (incompleto) {
        alert('Debes seleccionar una categoría para cada fila antes de continuar.');
        return;
    }

    document.getElementById('modalCatFaltantes').style.display = 'none';
    window._mapeoCategoriasManual = mapeo;
    await guardarPartidos();
}

/**
 * Muestra un mensaje de error
 */
function showError(message) {
    loading.style.display = 'none';
    errorDiv.textContent = '❌ ' + message;
    errorDiv.style.display = 'block';
    successDiv.style.display = 'none';
    
    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

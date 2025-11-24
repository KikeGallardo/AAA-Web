// Obtener elementos del DOM
const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('fileInput');
const loading = document.getElementById('loading');
const errorDiv = document.getElementById('error');
const successDiv = document.getElementById('success');
const tableContainer = document.getElementById('tableContainer');
const dataTable = document.getElementById('dataTable');
const filters = document.getElementById('filters');

// Variables globales para los datos y filtros
let allData = [];
let headers = [];

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
        handleFile(files[0]);
    }
});

// Evento de selección de archivo
fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        handleFile(e.target.files[0]);
    }
});

/**
 * Procesa el archivo Excel seleccionado
 * @param {File} file - Archivo a procesar
 */
function handleFile(file) {
    // Validar tipo de archivo
    const validTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel'
    ];
    
    if (!validTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls)$/i)) {
        showError('Por favor selecciona un archivo Excel válido (.xlsx o .xls)');
        return;
    }

    // Mostrar loading
    loading.style.display = 'block';
    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';
    tableContainer.style.display = 'none';

    // Leer archivo
    const reader = new FileReader();
    
    reader.onload = (e) => {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            
            // Obtener la primera hoja
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            
            // Convertir a JSON
            const jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1, defval: '' });
            
            if (jsonData.length === 0) {
                showError('El archivo está vacío');
                return;
            }
            
            // Buscar la fila de encabezados (la que contiene "GRUPO", "CATEGORIA", etc.)
            let headerRowIndex = -1;
            for (let i = 0; i < jsonData.length; i++) {
                const row = jsonData[i];
                if (row.some(cell => cell && cell.toString().toUpperCase().includes('CATEGORÍA'))) {
                    headerRowIndex = i;
                    break;
                }
            }
            
            if (headerRowIndex === -1) {
                showError('No se encontraron los encabezados esperados en el archivo');
                return;
            }
            
            // Guardar encabezados y datos
            headers = jsonData[headerRowIndex];
            allData = jsonData.slice(headerRowIndex + 1).filter(row => {
                // Filtrar filas vacías o que son encabezados repetidos
                return row.some(cell => cell !== '' && cell !== null) && 
                       !row.some(cell => cell && cell.toString().toUpperCase().includes('GRUPO'));
            });
            console.log(headers)
            console.log(allData)
            
            // Crear tabla y filtros
            createTable(allData);
            
            loading.style.display = 'none';
            successDiv.textContent = `✓ Archivo cargado exitosamente: ${file.name} (${allData.length} partidos encontrados)`;
            successDiv.style.display = 'block';
            tableContainer.style.display = 'block';
            
        } catch (error) {
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
 * @param {number} serial - Número serial de Excel
 * @returns {string} - Fecha formateada
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
 * @param {number} serial - Número decimal de Excel (0.75 = 6:00 PM)
 * @returns {string} - Hora formateada
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
 * @param {Array} data - Datos en formato de array de arrays
 */
function createTable(data) {
    let html = '<thead><tr>';
    
    // Identificar índices de fecha y hora
    const fechaIndex = headers.findIndex(h => h && h.toString().toUpperCase().includes('FECHA'));
    const horaIndex = headers.findIndex(h => h && h.toString().toUpperCase().includes('HORA'));
    
    // Crear encabezados
    headers.forEach(header => {
        html += `<th>${header || ''}</th>`;
    });
    html += '</tr></thead><tbody>';
    
    // Crear filas de datos
    data.forEach(row => {
        html += '<tr>';
        row.forEach((cell, index) => {
            let value = cell;
            
            // Convertir fechas
            if (index === fechaIndex && typeof cell === 'number' && cell > 1000) {
                value = excelDateToJSDate(cell);
            }
            
            // Convertir horas
            if (index === horaIndex && typeof cell === 'number' && cell < 1) {
                value = excelTimeToJSTime(cell);
            }
            
            html += `<td>${value !== undefined && value !== null ? value : ''}</td>`;
        });
        html += '</tr>';
    });
    
    html += '</tbody>';
    dataTable.innerHTML = html;
}
/**
 * Aplica los filtros seleccionados
 */
function applyFilters() {
    const grupoFilter = document.getElementById('grupoFilter').value;
    const categoriaFilter = document.getElementById('categoriaFilter').value;
    const fechaFilter = document.getElementById('fechaFilter').value;
    
    const grupoIndex = headers.findIndex(h => h && h.toString().toUpperCase().includes('GRUPO'));
    const categoriaIndex = headers.findIndex(h => h && h.toString().toUpperCase().includes('CATEGORIA'));
    const fechaIndex = headers.findIndex(h => h && h.toString().toUpperCase().includes('FECHA'));
    
    const filteredData = allData.filter(row => {
        const matchGrupo = !grupoFilter || (grupoIndex >= 0 && row[grupoIndex] == grupoFilter);
        const matchCategoria = !categoriaFilter || (categoriaIndex >= 0 && row[categoriaIndex] == categoriaFilter);
        const matchFecha = !fechaFilter || (fechaIndex >= 0 && row[fechaIndex] == fechaFilter);
        
        return matchGrupo && matchCategoria && matchFecha;
    });
    
    createTable(filteredData);
    successDiv.textContent = `✓ Mostrando ${filteredData.length} de ${allData.length} partidos`;
}

/**
 * Muestra un mensaje de error
 * @param {string} message - Mensaje de error a mostrar
 */
function showError(message) {
    loading.style.display = 'none';
    errorDiv.textContent = '❌ ' + message;
    errorDiv.style.display = 'block';
    successDiv.style.display = 'none';
}
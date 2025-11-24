let allData = [];
let filteredData = [];

// Configurar drag and drop
const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('fileInput');

uploadArea.addEventListener('click', () => fileInput.click());

uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        handleFile(files[0]);
    }
});

fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        handleFile(e.target.files[0]);
    }
});

function handleFile(file) {
    if (!file.name.match(/\.(xlsx|xls)$/)) {
        alert('Por favor selecciona un archivo Excel válido (.xlsx o .xls)');
        return;
    }

    document.getElementById('loading').classList.add('active');
    
    const reader = new FileReader();
    
    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            
            // Leer la primera hoja
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            const jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });
            
            processData(jsonData);
            
        } catch (error) {
            alert('Error al procesar el archivo: ' + error.message);
            console.error(error);
        } finally {
            document.getElementById('loading').classList.remove('active');
        }
    };
    
    reader.readAsArrayBuffer(file);
}

function processData(jsonData) {
    allData = [];
    
    // Encontrar la fila de encabezados
    let headerRow = -1;
    for (let i = 0; i < jsonData.length; i++) {
        const row = jsonData[i];
        if (row.includes('CATEGORIA') || row.includes('EQUIPO A')) {
            headerRow = i;
            break;
        }
    }

    if (headerRow === -1) {
        alert('No se encontraron los encabezados esperados');
        return;
    }

    // Procesar cada fila de datos
    for (let i = headerRow + 1; i < jsonData.length; i++) {
        const row = jsonData[i];
        
        // Saltar filas vacías
        if (!row || row.length === 0 || !row.some(cell => cell)) continue;
        
        const partido = {
            categoria: row[1] || '',
            equipoA: row[2] || '',
            vs: row[3] || '',
            equipoB: row[4] || '',
            fecha: row[5] || '',
            hora: row[6] || '',
            escenario: row[7] || '',
            arbitro1: row[8] || '',
            arbitro2: row[9] || ''
        };

        // Solo agregar si tiene información relevante
        if (partido.equipoA || partido.equipoB || partido.categoria) {
            allData.push(partido);
        }
    }

    filteredData = [...allData];
    displayData();
    updateStats();
    populateFilters();
    document.getElementById('dataSection').classList.add('active');
}

function displayData() {
    const tbody = document.getElementById('tableBody');
    
    if (filteredData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="no-data">No se encontraron resultados</td></tr>';
        return;
    }

    tbody.innerHTML = filteredData.map(partido => `
        <tr>
            <td><strong>${partido.categoria}</strong></td>
            <td>${partido.equipoA}</td>
            <td>${partido.equipoB}</td>
            <td>${partido.fecha}</td>
            <td>${partido.hora}</td>
            <td>${partido.escenario}</td>
            <td>${partido.arbitro1}</td>
            <td>${partido.arbitro2}</td>
        </tr>
    `).join('');
}

function updateStats() {
    const stats = document.getElementById('stats');
    const totalPartidos = allData.length;
    const categorias = new Set(allData.map(p => p.categoria).filter(c => c)).size;
    const escenarios = new Set(allData.map(p => p.escenario).filter(e => e)).size;

    stats.innerHTML = `
        <div class="stat-card">
            <div class="stat-number">${totalPartidos}</div>
            <div class="stat-label">Total Partidos</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">${categorias}</div>
            <div class="stat-label">Categorías</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">${escenarios}</div>
            <div class="stat-label">Escenarios</div>
        </div>
    `;
}

function populateFilters() {
    // Categorías
    const categorias = [...new Set(allData.map(p => p.categoria).filter(c => c))].sort();
    const categoriaFilter = document.getElementById('categoriaFilter');
    categoriaFilter.innerHTML = '<option value="">Todas las categorías</option>' +
        categorias.map(c => `<option value="${c}">${c}</option>`).join('');

    // Escenarios
    const escenarios = [...new Set(allData.map(p => p.escenario).filter(e => e))].sort();
    const escenarioFilter = document.getElementById('escenarioFilter');
    escenarioFilter.innerHTML = '<option value="">Todos los escenarios</option>' +
        escenarios.map(e => `<option value="${e}">${e}</option>`).join('');
}

// Filtros
document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('categoriaFilter').addEventListener('change', applyFilters);
document.getElementById('escenarioFilter').addEventListener('change', applyFilters);

function applyFilters() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const categoria = document.getElementById('categoriaFilter').value;
    const escenario = document.getElementById('escenarioFilter').value;

    filteredData = allData.filter(partido => {
        const matchSearch = !searchTerm || 
            Object.values(partido).some(val => 
                String(val).toLowerCase().includes(searchTerm)
            );
        const matchCategoria = !categoria || partido.categoria === categoria;
        const matchEscenario = !escenario || partido.escenario === escenario;

        return matchSearch && matchCategoria && matchEscenario;
    });

    displayData();
}

function exportData() {
    const dataStr = JSON.stringify(filteredData, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'programacion_partidos.json';
    link.click();
    URL.revokeObjectURL(url);
}
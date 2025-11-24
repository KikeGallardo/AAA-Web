function editarArbitro(id, nombre, apellido, cedula, correo, telefono) {
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_nombre").value = nombre;
    document.getElementById("edit_apellido").value = apellido;
    document.getElementById("edit_cedula").value = cedula;
    document.getElementById("edit_correo").value = correo;
    document.getElementById("edit_telefono").value = telefono;
    document.getElementById("modalEditar").style.display = "flex";
}

const input = document.getElementById('buscador');
const tbody = document.querySelector('.cuerpoTabla tbody');
const tablaOriginal = tbody.innerHTML; // Guarda el contenido original

input.addEventListener('keyup', async () => {
    const query = input.value.trim();
    
    // Si está vacío, restaura la tabla original
    if (query.length === 0) {
        tbody.innerHTML = tablaOriginal;
        return;
    }

    console.log("Evento Detectado");

    try {
        const formData = new FormData();
        formData.append('accion', 'buscar_arbitro');
        formData.append('q', query);

        const res = await fetch('consultas.php', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();
        console.log(data);
        tbody.innerHTML = '';

        // Verifica que sea array
        if (!Array.isArray(data)) {
            console.log('Error: respuesta no es array', data);
            return;
        }

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:2rem; color:#6b7280;">No se encontraron árbitros</td></tr>';
            return;
        }

        // Recorre los resultados y crea las filas
        data.forEach(arbitro => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${escapeHtml(arbitro.nombre)}</td>
                <td>${escapeHtml(arbitro.apellido)}</td>
                <td>${escapeHtml(arbitro.cedula)}</td>
                <td>${escapeHtml(arbitro.fechaNacimiento || '')}</td>
                <td>${escapeHtml(arbitro.correo || '')}</td>
                <td>${escapeHtml(arbitro.telefono || '')}</td>
                <td>${escapeHtml(arbitro.categoriaArbitro || '')}</td>
                <td class="botonesfile">
                    <button type="button" class="btn-editar" 
                            onclick="editarArbitro(${arbitro.idArbitro}, '${escapeHtml(arbitro.nombre)}', '${escapeHtml(arbitro.apellido)}', '${escapeHtml(arbitro.cedula)}', '${escapeHtml(arbitro.fechaNacimiento || '')}', '${escapeHtml(arbitro.correo || '')}', '${escapeHtml(arbitro.telefono || '')}', '${escapeHtml(arbitro.categoriaArbitro || '')}')"
                            title="Editar">
                        <i class="material-icons">edit</i>
                    </button>
                    <button type="button" class="btn-eliminar" 
                            onclick="confirmarEliminar(${arbitro.idArbitro}, '${escapeHtml(arbitro.nombre)} ${escapeHtml(arbitro.apellido)}')"
                            title="Eliminar">
                        <i class="material-icons">delete</i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

    } catch (err) {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:2rem; color:#ef4444;">Error al buscar</td></tr>';
    }
});

// Función para escapar HTML y prevenir XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
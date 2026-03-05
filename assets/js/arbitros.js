// editarArbitro debe ser global (se llama desde onclick en el HTML)
function editarArbitro(id, nombre, apellido, cedula, fecha, correo, telefono, categoria) {
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_nombre").value = nombre;
    document.getElementById("edit_apellido").value = apellido;
    document.getElementById("edit_cedula").value = cedula;
    document.getElementById("edit_fechaNacimiento").value = fecha;
    document.getElementById("edit_correo").value = correo;
    document.getElementById("edit_telefono").value = telefono;
    document.getElementById("edit_categoria").value = categoria;
    document.getElementById("modalEditar").style.display = "flex";
}

// Todo lo que toca el DOM espera a que la página esté lista
document.addEventListener('DOMContentLoaded', function () {

    const input      = document.getElementById('buscador');
    const tbody      = document.querySelector('table.cuerpoTabla tbody');
    const paginacion = document.querySelector('.paginacion');

    // Si no existe el buscador en esta página, no hacer nada
    if (!input || !tbody) return;

    const tablaOriginal = tbody.innerHTML;
    let debounceTimer = null;

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(buscarArbitros, 300);
    });

    async function buscarArbitros() {
        const query = input.value.trim();

        if (query.length === 0) {
            tbody.innerHTML = tablaOriginal;
            if (paginacion) paginacion.style.display = '';
            return;
        }

        if (paginacion) paginacion.style.display = 'none';

        try {
            const formData = new FormData();
            formData.append('accion', 'buscar_arbitro');
            formData.append('q', query);

            const res = await fetch('consultas.php', {
                method: 'POST',
                body: formData
            });

            if (!res.ok) throw new Error('Error HTTP: ' + res.status);

            const data = await res.json();

            if (!Array.isArray(data)) {
                console.error('Error: respuesta no es array', data);
                return;
            }

            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:2rem; color:#6b7280;">No se encontraron árbitros</td></tr>';
                return;
            }

            data.forEach(arbitro => {
                const tr       = document.createElement('tr');
                const nombre   = escapeHtml(arbitro.nombre            || '');
                const apellido = escapeHtml(arbitro.apellido          || '');
                const cedula   = escapeHtml(arbitro.cedula            || '');
                const fecha    = escapeHtml(arbitro.fechaNacimiento   || '');
                const correo   = escapeHtml(arbitro.correo            || '');
                const telefono = escapeHtml(arbitro.telefono          || '');
                const categoria= escapeHtml(arbitro.categoriaArbitro || '');

                tr.innerHTML = `
                    <td>${nombre}</td>
                    <td>${apellido}</td>
                    <td>${cedula}</td>
                    <td>${fecha}</td>
                    <td>${correo}</td>
                    <td>${telefono}</td>
                    <td>${categoria}</td>
                    <td class="botonesfile">
                        <button type="button" class="btn-editar"
                                data-id="${arbitro.idArbitro}"
                                data-nombre="${nombre}"
                                data-apellido="${apellido}"
                                data-cedula="${cedula}"
                                data-fecha="${fecha}"
                                data-correo="${correo}"
                                data-telefono="${telefono}"
                                data-categoria="${categoria}"
                                title="Editar">
                            <i class="material-icons">edit</i>
                        </button>
                        <button type="button" class="btn-eliminar"
                                onclick="confirmarEliminar(${arbitro.idArbitro}, '${nombre} ${apellido}')"
                                title="Eliminar">
                            <i class="material-icons">delete</i>
                        </button>
                    </td>
                `;

                tr.querySelector('.btn-editar').addEventListener('click', function () {
                    const d = this.dataset;
                    editarArbitro(d.id, d.nombre, d.apellido, d.cedula, d.fecha, d.correo, d.telefono, d.categoria);
                });

                tbody.appendChild(tr);
            });

        } catch (err) {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:2rem; color:#ef4444;">Error al buscar</td></tr>';
        }
    }

}); // fin DOMContentLoaded

// Función para escapar HTML y prevenir XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}
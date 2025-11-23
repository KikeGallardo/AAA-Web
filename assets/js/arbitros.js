function editarArbitro(id, nombre, apellido, cedula, correo, telefono) {
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_nombre").value = nombre;
    document.getElementById("edit_apellido").value = apellido;
    document.getElementById("edit_cedula").value = cedula;
    document.getElementById("edit_correo").value = correo;
    document.getElementById("edit_telefono").value = telefono;
    document.getElementById("modalEditar").style.display = "flex";
}
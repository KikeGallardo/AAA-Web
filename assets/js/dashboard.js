document.addEventListener("DOMContentLoaded", () => {

    // Capturar los botones correctos
    const botonesEliminar = document.querySelectorAll(".eliminar-btn");

    if (!botonesEliminar) return;

    botonesEliminar.forEach(boton => {
        boton.addEventListener("click", async (e) => {
            const item = e.target.closest(".notificacion-item");
            if (!item) return;

            const id = item.dataset.id;

            // Enviar petición
            const formData = new FormData();
            formData.append("eliminar_noti", "1");
            formData.append("id", id);

            const response = await fetch("consultas.php", {
                method: "POST",
                body: formData
            });

            // Si NO hay JSON, no intentes leer JSON
            if (!response.ok) {
                alert("Error al eliminar");
                return;
            }

            // Eliminar del DOM
            item.remove();
        });
    });

});

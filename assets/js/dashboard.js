document.addEventListener("DOMContentLoaded", () => {
    const botonesEliminar = document.querySelectorAll(".verelbtn");
    const eliminarTodasBtn = document.querySelector(".eliminar-todas-btn");

    if (!botonesEliminar) return;

    botonesEliminar.forEach(boton => {
        boton.addEventListener("click", async (e) => {
            const item = e.target.closest(".notificacion-item");
            if (!item) return;

            const id = item.dataset.id;

            const formData = new FormData();
            formData.append("accion", "eliminar_noti");  // ✅ Cambio aquí
            formData.append("id", id);

            const response = await fetch("consultas.php", {
                method: "POST",
                body: formData
            });

            if (!response.ok) {
                alert("Error al eliminar");
                return;
            }

            const data = await response.json();
            
            if (data.status === "ok") {
                item.remove();
                alert("Notificación eliminada");
            } else {
                alert("Error: " + (data.msg || "Desconocido"));
            }
        });
    });

    if (eliminarTodasBtn) {
        eliminarTodasBtn.addEventListener("click", async () => {
            const formData = new FormData();
            formData.append("accion", "eliminar_todas");
            const response = await fetch("consultas.php", {
                method: "POST",
                body: formData
            });

            if (!response.ok) {
                alert("Error al eliminar todas las notificaciones");
                return;
            };
            const data = await response.json();

            if (data.status === "ok") {
                document.querySelectorAll(".notificacion-item").forEach(item => item.remove());
                alert("Todas las notificaciones eliminadas");
            } else {
                alert("Error: " + (data.msg || "Desconocido"));
            }
        });
    }
});


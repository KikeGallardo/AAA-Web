document.addEventListener("DOMContentLoaded", function() {
  const calendarEl = document.getElementById("calendar");

  

  // Creamos el objeto calendario con todos sus parametros
  const calendar = new FullCalendar.Calendar(calendarEl, {
      titleFormat: {
      month: 'long',
      day: 'numeric',
      year: 'numeric'
    },
      initialView: "dayGridMonth",
      themeSystem: "bootstrap5",
      headerToolbar: {
        left: 'title,dayGridMonth',
        center: 'listMonth',
        right: 'prev,next'
      },
      buttonText: {
        today: 'Hoy',
        month: 'Mes',
        week: 'Semana',
        day: 'Día',
        listMonth: 'Torneos mensuales'
      },
      dateClick: function(info) {
        abrirModal(info.date)
        
      },

      eventClick: function(info) {
        const fechaObj = info.event.start;
        const fecha = fechaObj.toISOString().split("T")[0];
        // Obtener el idTorneo desde extendedProps (lo guardamos en filtrar_fechas)
        const idTorneo = info.event.extendedProps.idTorneo ?? null;

        let formData = new FormData();
        formData.append("accion", "obtener_partidos_por_torneo_dia");
        formData.append("fecha", fecha);
        if (idTorneo) formData.append("idTorneo", idTorneo);

        fetch("consultas.php", { method: "POST", body: formData })
            .then(r => r.json())
            .then(data => {
                console.table(data);
                let html = `
                  <table class="table" border="1" style="width:100%; text-align:left; border-collapse: collapse;">
                    <thead>
                      <tr style="background:#eee;">
                        <th>ID</th><th>Fecha</th><th>Hora</th><th>Cancha</th>
                        <th>Categoría</th><th>Equipo Local</th><th>Equipo Visitante</th>
                        <th>Categoría Pago</th><th>Árbitro Principal</th><th>Pago Principal</th>
                        <th>Asistente 1</th><th>Pago Asistente 1</th>
                        <th>Asistente 2</th><th>Pago Asistente 2</th>
                        <th>Cuarto Árbitro</th><th>Pago Cuarto Árbitro</th>
                        <th>Eliminar</th>
                      </tr>
                    </thead>
                    <tbody>`;
                data.forEach(p => {
                    html += `<tr>
                      <td>${p.idPartido}</td><td>${p.fecha}</td><td>${p.hora}</td>
                      <td>${p.canchaLugar}</td><td>${p.categoriaText}</td>
                      <td>${p.equipoLocal}</td><td>${p.equipoVisitante}</td>
                      <td>${p.categoriaPago}</td><td>${p.arbitroPrincipal}</td>
                      <td class="precio">$${p.pagoPrincipal}</td>
                      <td>${p.arbitroAsistente1}</td><td class="precio">$${p.pagoAsistente1}</td>
                      <td>${p.arbitroAsistente2}</td><td class="precio">$${p.pagoAsistente2}</td>
                      <td>${p.arbitroCuarto}</td><td class="precio">$${p.pagoCuarto}</td>
                      <td>
                        <button onclick="eliminarPartido(${p.idPartido})"
                                style="padding:0.4rem 0.8rem; background:#ef4444; color:white; border:none;
                                      border-radius:6px; font-weight:600; cursor:pointer; font-size:13px;">
                            🗑️
                        </button>
                    </td>
                    </tr>`;
                });
                window.eliminarPartido = function(idPartido) {
                  if (!confirm(`¿Eliminar el partido #${idPartido}?`)) return;

                  const formData = new FormData();
                  formData.append("accion", "eliminar_partido");
                  formData.append("idPartido", idPartido);

                  fetch("consultas.php", { method: "POST", body: formData })
                      .then(r => r.json())
                      .then(data => {
                          if (data.status === "ok") {
                              // Quitar la fila de la tabla sin cerrar el modal
                              document.querySelector(`button[onclick="eliminarPartido(${idPartido})"]`)
                                  .closest("tr").remove();
                              calendar.refetchEvents();
                          } else {
                              alert("❌ Error: " + (data.msg || "No se pudo eliminar"));
                          }
                      });
              };
                html += `</tbody></table>`;
                document.getElementById("modalTitle").textContent =
                    info.event.title + " — " + fecha;
                document.getElementById("cuerpoTabla").innerHTML = html;
                document.getElementById("miModal").style.display = "block";
                
            });
    },
      locale: 'es',
      dayMaxEvents: true,
      
      // Cargar eventos desde PHP según el rango visible
      events: async function(info, successCallback, failureCallback) {
      const formData = new FormData();
      formData.append("accion", "filtrar_fechas");
      formData.append("inicio", info.startStr);
      formData.append("fin", info.endStr);
      
      try {
        const res = await fetch("consultas.php", {
          method: "POST",
          body: formData
        });
        
        const data = await res.json();
        successCallback(data); // FullCalendar muestra los eventos
      } catch (err) {
        failureCallback(err);
        alert(err)
      }
    },
  });

  // Se crea el calendario
  calendar.render();

  function abrirModal(fecha) {
      document.getElementById("anoModal").textContent = "Información: " + fecha ;
      document.getElementById("miModal").style.display = "block";
  }
  
  document.getElementById("cerrarModal").onclick = function() {
      document.getElementById("miModal").style.display = "none";
  };
  
  window.onclick = function(event) {
      if(event.target.id === "miModal") {
          document.getElementById("miModal").style.display = "none";
      }
  };
});


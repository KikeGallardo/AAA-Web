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
        left: 'title',
        center: '',
        right: 'prev,next'
      },
      dateClick: function(info) {
        abrirModal(info.date.getDate())
        
      },
      locale: 'es',
      
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

  async function cargarUsuarios() {
    const res = await fetch("consultas.php?accion=usuarios");
    const data = await res.json();
    console.log("Usuarios:", data);
  }

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


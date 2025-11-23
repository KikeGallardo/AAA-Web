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
      events: [
      ]
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


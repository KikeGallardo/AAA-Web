document.addEventListener("DOMContentLoaded", function() {
  const calendarEl = document.getElementById("calendar");

  // Creamos el objeto calendario con todos sus parametros
  const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: "dayGridMonth",
      themeSystem: "bootstrap5",
      lang: 'es',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,listMonth, dayGridWeek'
      },
      dateClick: function(info) {
        if (info.title == "undefined") {
          info.title = "No hay nada pendiente aqui!"
        } 
        abrirModal(info.date)
        
      },
      events: [
        {
          title: "Partido Final",
          start: "2025-11-22",
          extendedProps: {
          department: 'BioChemistry'
          },
          description: 'Lecture'
        },
        {
          title: "Partido",
          start: "2025-11-22",
          end: "2025-11-26"
        }
      ]
  });

  // Se crea el calendario
  calendar.render();

  

  function abrirModal(fecha) {
      document.getElementById("modalDate").textContent = "Fecha seleccionada: " + fecha;
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


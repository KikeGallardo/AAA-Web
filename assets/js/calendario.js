document.addEventListener("DOMContentLoaded", function () {
  const calendarEl = document.getElementById("calendar");

  // ── Color palette por torneo ──────────────────────────────
  const paleta = [
    "#2563eb","#16a34a","#9333ea","#ea580c","#0891b2",
    "#be123c","#ca8a04","#15803d","#7c3aed","#b45309"
  ];
  const coloresTorneo = {};
  function colorTorneo(id) {
    if (!coloresTorneo[id]) {
      coloresTorneo[id] = paleta[Object.keys(coloresTorneo).length % paleta.length];
    }
    return coloresTorneo[id];
  }

  // ── Lista de árbitros (cargada una vez) ───────────────────
  let listaArbitros = [];
  fetch("consultas.php", {
    method: "POST",
    body: (() => { const f = new FormData(); f.append("accion","listar_arbitros"); return f; })()
  }).then(r => r.json()).then(d => { listaArbitros = d; });

  function selectArbitro(id, valorActual, label) {
    const opts = listaArbitros.map(a =>
      `<option value="${a.idArbitro}" ${a.idArbitro == valorActual ? "selected" : ""}>${a.nombreCompleto}</option>`
    ).join("");
    return `
      <label class="edit-label">${label}
        <select id="${id}" class="edit-input edit-select">
          <option value="">— Sin árbitro —</option>
          ${opts}
        </select>
      </label>`;
  }

  // ── Calendario ────────────────────────────────────────────
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: "dayGridMonth",
    themeSystem: "bootstrap5",
    locale: "es",
    dayMaxEvents: 4,
    headerToolbar: { left: "title", center: "listMonth", right: "prev,today,next" },
    buttonText: { today: "Hoy", listMonth: "Lista mensual" },

    eventContent: function (arg) {
      const props = arg.event.extendedProps;
      const hora  = props.horaInicio || "";
      const count = props.count || 1;
      const label = count > 1 ? `${count} partidos` : "1 partido";
      const wrap  = document.createElement("div");
      wrap.className = "fc-event-custom";
      wrap.innerHTML =
        `<span class="fc-ev-hora">${hora}</span>` +
        `<span class="fc-ev-nombre">${arg.event.title}</span>` +
        `<span class="fc-ev-count">${label}</span>`;
      return { domNodes: [wrap] };
    },

    eventDidMount: function (arg) {
      const color = colorTorneo(arg.event.extendedProps.idTorneo);
      Object.assign(arg.el.style, {
        background: color, borderColor: color,
        color: "#fff", borderRadius: "6px", padding: "2px 5px"
      });
    },

    eventClick: function (info) {
      const fecha    = info.event.start.toISOString().split("T")[0];
      const idTorneo = info.event.extendedProps.idTorneo ?? null;
      const titulo   = info.event.title;
      abrirModalCargando(titulo, fecha);
      const fd = new FormData();
      fd.append("accion","obtener_partidos_por_torneo_dia");
      fd.append("fecha", fecha);
      if (idTorneo) fd.append("idTorneo", idTorneo);
      fetch("consultas.php", { method:"POST", body:fd })
        .then(r => r.json())
        .then(data => renderTablaPartidos(data, titulo, fecha))
        .catch(err => {
          document.getElementById("cuerpoModal").innerHTML =
            `<p class="text-danger">Error: ${err.message}</p>`;
        });
    },

    events: async function (info, ok, fail) {
      const fd = new FormData();
      fd.append("accion","filtrar_fechas");
      fd.append("inicio", info.startStr);
      fd.append("fin",    info.endStr);
      try { ok(await (await fetch("consultas.php",{method:"POST",body:fd})).json()); }
      catch(e) { fail(e); }
    }
  });

  calendar.render();

  // ── Modal helpers ─────────────────────────────────────────
  function abrirModalCargando(titulo, fecha) {
    document.getElementById("modalTitle").textContent = titulo + " — " + formatFecha(fecha);
    document.getElementById("cuerpoModal").innerHTML =
      '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Cargando…</p></div>';
    document.getElementById("miModal").style.display = "flex";
  }

  function cerrarModal() {
    document.getElementById("miModal").style.display = "none";
    document.getElementById("cuerpoModal").innerHTML = "";
  }

  document.getElementById("cerrarModal").onclick = cerrarModal;
  window.addEventListener("click", e => { if (e.target.id === "miModal") cerrarModal(); });
  document.addEventListener("keydown", e => { if (e.key === "Escape") cerrarModal(); });

  // ── Utilidades de formato ─────────────────────────────────
  function formatFecha(iso) {
    const [y,m,d] = iso.split("-");
    const meses = ["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"];
    return `${d} ${meses[+m-1]} ${y}`;
  }

  function formatHora(h) {
    if (!h) return "—";
    const [hh, mm] = h.split(":");
    const n = +hh;
    return `${n%12||12}:${mm} ${n>=12?"PM":"AM"}`;
  }

  function a24(h12) {
    const m = h12.match(/(\d+):(\d+)\s*(AM|PM)/i);
    if (!m) return h12;
    let h = +m[1];
    if (m[3].toUpperCase()==="PM" && h<12) h+=12;
    if (m[3].toUpperCase()==="AM" && h===12) h=0;
    return `${String(h).padStart(2,"0")}:${m[2]}`;
  }

  // ── Render tabla ──────────────────────────────────────────
  function renderTablaPartidos(data, titulo, fecha) {
    if (!data.length) {
      document.getElementById("cuerpoModal").innerHTML =
        '<p class="text-muted text-center py-3">No hay partidos para este torneo en este día.</p>';
      return;
    }

    let html = `<div class="table-responsive">
    <table class="table table-sm table-hover align-middle tabla-partidos">
      <thead class="table-dark"><tr>
        <th>Hora</th><th>Cancha</th><th>Categoría</th>
        <th>Local</th><th>Visitante</th>
        <th>Árbitro</th><th>Asistente 1</th><th>Asistente 2</th><th>Emergente</th>
        <th style="width:100px">Acciones</th>
      </tr></thead><tbody>`;

    data.forEach(p => {
      const a1  = p.arbitroPrincipal  ? `${p.arbitroPrincipal} ${p.apellidoArbitro1||""}`.trim()  : "—";
      const a2  = p.arbitroAsistente1 ? `${p.arbitroAsistente1} ${p.apellidoArbitro2||""}`.trim() : "—";
      const a3  = p.arbitroAsistente2 ? `${p.arbitroAsistente2} ${p.apellidoArbitro3||""}`.trim() : "—";
      const a4  = p.arbitroCuarto     ? `${p.arbitroCuarto} ${p.apellidoArbitro4||""}`.trim()     : "—";
      const obs = p.observaciones || "";

      // Codificar datos del partido en data-* para el editor
      const data_p = encodeURIComponent(JSON.stringify({
        hora:       p.hora,
        cancha:     p.canchaLugar   || "",
        categoria:  p.categoriaText || "",
        idEquipo1:  p.idEquipo1,
        idEquipo2:  p.idEquipo2,
        idArbitro1: p.idArbitro1 || "",
        idArbitro2: p.idArbitro2 || "",
        idArbitro3: p.idArbitro3 || "",
        idArbitro4: p.idArbitro4 || "",
        equipoLocal:     p.equipoLocal,
        equipoVisitante: p.equipoVisitante
      }));

      html += `
      <tr data-id="${p.idPartido}" data-partido="${data_p}">
        <td><strong>${formatHora(p.hora)}</strong></td>
        <td>${p.canchaLugar||"—"}</td>
        <td>${p.categoriaText||"—"}</td>
        <td data-campo="equipoLocal">${p.equipoLocal}</td>
        <td data-campo="equipoVisitante">${p.equipoVisitante}</td>
        <td data-campo="arb1">${a1}</td>
        <td data-campo="arb2">${a2}</td>
        <td data-campo="arb3">${a3}</td>
        <td data-campo="arb4">${a4}</td>
        <td class="acciones-td">
          <button class="btn-acc" title="Editar"       onclick="abrirEdicion(${p.idPartido}, this)">✏️</button>
          <button class="btn-acc btn-obs ${obs?"btn-obs-activa":""}"
                  title="${obs?"Ver/editar observación":"Agregar observación"}"
                  onclick="abrirObservacion(${p.idPartido}, this)">${obs?"📝":"📋"}</button>
          <button class="btn-acc" title="Eliminar"     onclick="eliminarPartido(${p.idPartido}, this)">🗑️</button>
        </td>
      </tr>
      ${obs ? `<tr class="fila-obs" data-obs-id="${p.idPartido}">
        <td colspan="10" class="obs-texto"><span class="obs-badge">📝 Obs:</span> ${obs}</td>
      </tr>` : ""}`;
    });

    html += `</tbody></table></div>`;
    document.getElementById("cuerpoModal").innerHTML = html;
    document.getElementById("modalTitle").textContent = titulo + " — " + formatFecha(fecha);
  }

  // ── Observaciones inline ──────────────────────────────────
  window.abrirObservacion = function (id, btn) {
    const tr  = btn.closest("tr");
    const obs = (() => {
      const sib = tr.nextElementSibling;
      return (sib && sib.classList.contains("fila-obs"))
        ? sib.querySelector(".obs-texto").textContent.replace("📝 Obs: ","").trim()
        : "";
    })();

    const existe = document.getElementById("obs-editor-"+id);
    if (existe) { existe.remove(); return; }

    const row = document.createElement("tr");
    row.id = "obs-editor-"+id;
    row.innerHTML = `
      <td colspan="10" class="obs-editor-td">
        <textarea class="obs-textarea" rows="2" placeholder="Escribe la observación...">${obs}</textarea>
        <div class="edit-actions">
          <button class="btn-save" onclick="guardarObservacion(${id})">💾 Guardar</button>
          <button class="btn-cancel" onclick="document.getElementById('obs-editor-${id}').remove()">Cancelar</button>
        </div>
      </td>`;

    // Insertar después de fila-obs si existe, sino después de la fila principal
    const sibObs = tr.nextElementSibling;
    const insertAfter = (sibObs && sibObs.classList.contains("fila-obs")) ? sibObs : tr;
    insertAfter.insertAdjacentElement("afterend", row);
    row.querySelector("textarea").focus();
  };

  window.guardarObservacion = function (id) {
    const editor = document.getElementById("obs-editor-"+id);
    const texto  = editor.querySelector("textarea").value.trim();
    const fd = new FormData();
    fd.append("accion","guardar_observacion"); fd.append("idPartido",id); fd.append("observacion",texto);
    fetch("consultas.php",{method:"POST",body:fd}).then(r=>r.json()).then(d => {
      if (d.status !== "ok") { alert("Error: "+d.msg); return; }
      editor.remove();
      const tr     = document.querySelector(`tr[data-id="${id}"]`);
      const btnObs = tr.querySelector(".btn-obs");
      const sibObs = tr.nextElementSibling;
      if (sibObs && sibObs.classList.contains("fila-obs")) sibObs.remove();
      if (texto) {
        btnObs.textContent = "📝"; btnObs.classList.add("btn-obs-activa"); btnObs.title = "Ver/editar observación";
        const obsRow = document.createElement("tr");
        obsRow.className = "fila-obs"; obsRow.dataset.obsId = id;
        obsRow.innerHTML = `<td colspan="10" class="obs-texto"><span class="obs-badge">📝 Obs:</span> ${texto}</td>`;
        tr.insertAdjacentElement("afterend", obsRow);
      } else {
        btnObs.textContent = "📋"; btnObs.classList.remove("btn-obs-activa"); btnObs.title = "Agregar observación";
      }
    });
  };

  // ── Edición completa inline ───────────────────────────────
  window.abrirEdicion = function (id, btn) {
    const existe = document.getElementById("edit-editor-"+id);
    if (existe) { existe.remove(); return; }

    const tr = btn.closest("tr");
    const p  = JSON.parse(decodeURIComponent(tr.dataset.partido));

    // Hora a formato HH:MM para el input type=time
    const hora24 = p.hora ? p.hora.substring(0,5) : "";

    const row = document.createElement("tr");
    row.id = "edit-editor-"+id;
    row.innerHTML = `
      <td colspan="10" class="edit-editor-td">
        <div class="edit-section-title">⏱ Horario y lugar</div>
        <div class="edit-grid">
          <label class="edit-label">Hora
            <input type="time" id="eh-hora-${id}"     value="${hora24}"   class="edit-input">
          </label>
          <label class="edit-label">Fecha
            <input type="date" id="eh-fecha-${id}"   value="${p.hora ? p.hora.substring(0,10) : ""}" class="edit-input">
          </label>
          <label class="edit-label">Cancha / Lugar
            <input type="text" id="eh-cancha-${id}"   value="${esc(p.cancha)}"    class="edit-input" placeholder="Cancha">
          </label>
          <label class="edit-label">Categoría
            <input type="text" id="eh-cat-${id}"      value="${esc(p.categoria)}" class="edit-input" placeholder="Categoría">
          </label>
        </div>

        <div class="edit-section-title" style="margin-top:12px">👥 Árbitros</div>
        <div class="edit-grid">
          ${selectArbitro("eh-a1-"+id, p.idArbitro1, "Árbitro Principal")}
          ${selectArbitro("eh-a2-"+id, p.idArbitro2, "Asistente 1")}
          ${selectArbitro("eh-a3-"+id, p.idArbitro3, "Asistente 2")}
          ${selectArbitro("eh-a4-"+id, p.idArbitro4, "Emergente")}
        </div>

        <div class="edit-actions">
          <button class="btn-save"   onclick="guardarEdicion(${id})">💾 Guardar cambios</button>
          <button class="btn-cancel" onclick="document.getElementById('edit-editor-${id}').remove()">Cancelar</button>
        </div>
      </td>`;

    // Insertar después de posibles filas de obs/edit existentes
    let insertRef = tr;
    while (insertRef.nextElementSibling &&
           (insertRef.nextElementSibling.classList.contains("fila-obs") ||
            (insertRef.nextElementSibling.id && insertRef.nextElementSibling.id.startsWith("obs-editor")))) {
      insertRef = insertRef.nextElementSibling;
    }
    insertRef.insertAdjacentElement("afterend", row);
  };

  function esc(s) {
    return String(s||"").replace(/"/g,"&quot;").replace(/'/g,"&#39;");
  }

  window.guardarEdicion = function (id) {
    const get = sid => document.getElementById(sid);
    const hora = get("eh-hora-"+id)?.value;
    if (!hora) { alert("La hora es requerida"); return; }

    const fd = new FormData();
    fd.append("accion",        "actualizar_partido");
    fd.append("idPartido",     id);
    fd.append("hora",          hora+":00");
    fd.append("fecha",         get("eh-fecha-"+id)?.value || "");
    fd.append("canchaLugar",   get("eh-cancha-"+id).value.trim());
    fd.append("categoriaText", get("eh-cat-"+id).value.trim());
    fd.append("idArbitro1",    get("eh-a1-"+id).value);
    fd.append("idArbitro2",    get("eh-a2-"+id).value);
    fd.append("idArbitro3",    get("eh-a3-"+id).value);
    fd.append("idArbitro4",    get("eh-a4-"+id).value);

    fetch("consultas.php",{method:"POST",body:fd}).then(r=>r.json()).then(d => {
      if (d.status !== "ok") { alert("Error: "+d.msg); return; }

      // Cerrar editor
      document.getElementById("edit-editor-"+id).remove();

      // Actualizar celdas de la fila
      const tr      = document.querySelector(`tr[data-id="${id}"]`);
      const nombres = d.nombres || {};
      tr.cells[0].innerHTML  = `<strong>${formatHora(hora+":00")}</strong>`;
      tr.cells[1].dataset.fecha = get("eh-fecha-"+id)?.value || "";
      tr.cells[2].textContent = get("eh-cancha-"+id)?.value.trim() || "—";
      tr.cells[3].textContent = get("eh-cat-"+id)?.value.trim()    || "—";
      tr.cells[4].textContent = nombres.n1 || "—";
      tr.cells[5].textContent = nombres.n2 || "—";
      tr.cells[6].textContent = nombres.n3 || "—";
      tr.cells[7].textContent = nombres.n4 || "—";

      // Actualizar data-partido para próximas ediciones
      const p = JSON.parse(decodeURIComponent(tr.dataset.partido));
      p.hora      = hora+":00";
      p.cancha    = get("eh-cancha-"+id).value.trim();
      p.categoria = get("eh-cat-"+id).value.trim();
      p.idArbitro1 = get("eh-a1-"+id).value;
      p.idArbitro2 = get("eh-a2-"+id).value;
      p.idArbitro3 = get("eh-a3-"+id).value;
      p.idArbitro4 = get("eh-a4-"+id).value;
      tr.dataset.partido = encodeURIComponent(JSON.stringify(p));

      calendar.refetchEvents();
    });
  };

  // ── Eliminar partido ──────────────────────────────────────
  window.eliminarPartido = function (id, btn) {
    if (!confirm(`¿Eliminar este partido? La acción no se puede deshacer.`)) return;
    const fd = new FormData();
    fd.append("accion","eliminar_partido"); fd.append("idPartido",id);
    fetch("consultas.php",{method:"POST",body:fd}).then(r=>r.json()).then(d => {
      if (d.status !== "ok") { alert("❌ "+( d.msg||"No se pudo eliminar")); return; }
      const tr = btn.closest("tr");
      // Eliminar filas extra asociadas
      let sib = tr.nextElementSibling;
      while (sib && (sib.classList.contains("fila-obs") || /^(obs|edit)-editor/.test(sib.id||""))) {
        const next = sib.nextElementSibling;
        sib.remove();
        sib = next;
      }
      tr.remove();
      calendar.refetchEvents();
    });
  };
});
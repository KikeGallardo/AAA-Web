document.addEventListener("DOMContentLoaded", function () {
  const calendarEl = document.getElementById("calendar");

  // ── Paleta de colores por torneo ─────────────────────────
  const paleta = [
    "#2563eb","#16a34a","#9333ea","#ea580c","#0891b2",
    "#be123c","#ca8a04","#15803d","#7c3aed","#b45309"
  ];
  const coloresTorneo = {};
  function colorTorneo(id) {
    if (!coloresTorneo[id])
      coloresTorneo[id] = paleta[Object.keys(coloresTorneo).length % paleta.length];
    return coloresTorneo[id];
  }

  // ── Listas maestras (una sola carga al inicio) ────────────
  let listaArbitros   = [];
  let listaEquipos    = [];
  let listaCategorias = [];

  function postJSON(accion, extra) {
    const fd = new FormData();
    fd.append("accion", accion);
    if (extra) Object.entries(extra).forEach(([k,v]) => fd.append(k, v));
    return fetch("consultas.php", { method:"POST", body:fd }).then(r => r.json());
  }

  Promise.all([
    postJSON("listar_arbitros"),
    postJSON("listar_equipos"),
    postJSON("listar_categorias_pago"),
  ]).then(([arbs, eqs, cats]) => {
    listaArbitros   = arbs;
    listaEquipos    = eqs;
    listaCategorias = cats;
  });

  // ── Helpers para construir <select> ──────────────────────
  function mkSelect(id, lista, valorActual, labelVacio, keyId, keyNombre) {
    const opts = lista.map(item => {
      const sel = String(item[keyId]) === String(valorActual) ? " selected" : "";
      return `<option value="${item[keyId]}"${sel}>${esc(item[keyNombre])}</option>`;
    }).join("");
    return `<select id="${id}" class="edit-input edit-select">
              <option value="">— ${labelVacio} —</option>
              ${opts}
            </select>`;
  }

  function selectArbitro(id, valorActual, label) {
    return `<label class="edit-label">${label}
      ${mkSelect(id, listaArbitros, valorActual, "Sin árbitro", "idArbitro", "nombreCompleto")}
    </label>`;
  }

  function selectEquipo(id, valorActual, label) {
    return `<label class="edit-label">${label} <span style="color:#ef4444">*</span>
      ${mkSelect(id, listaEquipos, valorActual, "Seleccionar equipo", "idEquipo", "nombreEquipo")}
    </label>`;
  }

  function selectCategoria(id, valorActual, label) {
    return `<label class="edit-label">${label}
      ${mkSelect(id, listaCategorias, valorActual, "Sin categoría", "idCategoriaPagoArbitro", "nombreCategoria")}
    </label>`;
  }

  // ── Escape HTML ───────────────────────────────────────────
  function esc(s) {
    return String(s ?? "")
      .replace(/&/g,"&amp;").replace(/"/g,"&quot;")
      .replace(/'/g,"&#39;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
  }

  // ── FullCalendar ──────────────────────────────────────────
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: "dayGridMonth",
    themeSystem: "bootstrap5",
    locale: "es",
    dayMaxEvents: 4,
    headerToolbar: { left:"title", center:"listMonth", right:"prev,today,next" },
    buttonText: { today:"Hoy", listMonth:"Lista mensual" },

    eventContent: function (arg) {
      const p    = arg.event.extendedProps;
      const hora = p.horaInicio || "";
      const cnt  = p.count || 1;
      const wrap = document.createElement("div");
      wrap.className = "fc-event-custom";
      wrap.innerHTML =
        `<span class="fc-ev-hora">${hora}</span>` +
        `<span class="fc-ev-nombre">${arg.event.title}</span>` +
        `<span class="fc-ev-count">${cnt > 1 ? cnt+" partidos" : "1 partido"}</span>`;
      return { domNodes:[wrap] };
    },

    eventDidMount: function (arg) {
      const color = colorTorneo(arg.event.extendedProps.idTorneo);
      Object.assign(arg.el.style, {
        background:color, borderColor:color,
        color:"#fff", borderRadius:"6px", padding:"2px 5px"
      });
    },

    eventClick: function (info) {
      const fecha    = info.event.start.toISOString().split("T")[0];
      const idTorneo = info.event.extendedProps.idTorneo ?? null;
      const titulo   = info.event.title;
      abrirModalCargando(titulo, fecha);
      postJSON("obtener_partidos_por_torneo_dia", Object.assign({fecha}, idTorneo ? {idTorneo} : {}))
        .then(data => renderTablaPartidos(data, titulo, fecha))
        .catch(err => {
          document.getElementById("cuerpoModal").innerHTML =
            `<p class="text-danger">Error: ${err.message}</p>`;
        });
    },

    events: async function (info, ok, fail) {
      try { ok(await postJSON("filtrar_fechas", { inicio:info.startStr, fin:info.endStr })); }
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

  // ── Formateadores ─────────────────────────────────────────
  function formatFecha(iso) {
    const [y,m,d] = iso.split("-");
    const meses = ["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"];
    return `${d} ${meses[+m-1]} ${y}`;
  }

  function formatHora(h) {
    if (!h) return "—";
    const parts = h.split(":");
    const n = +parts[0], mm = parts[1]||"00";
    return `${n%12||12}:${mm} ${n>=12?"PM":"AM"}`;
  }

  // ── Render tabla de partidos ──────────────────────────────
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
        <th style="width:90px">Acciones</th>
      </tr></thead><tbody>`;

    data.forEach(p => {
      const a1  = p.arbitroPrincipal  ? `${p.arbitroPrincipal} ${p.apellidoArbitro1||""}`.trim()  : "—";
      const a2  = p.arbitroAsistente1 ? `${p.arbitroAsistente1} ${p.apellidoArbitro2||""}`.trim() : "—";
      const a3  = p.arbitroAsistente2 ? `${p.arbitroAsistente2} ${p.apellidoArbitro3||""}`.trim() : "—";
      const a4  = p.arbitroCuarto     ? `${p.arbitroCuarto} ${p.apellidoArbitro4||""}`.trim()     : "—";
      const obs = p.observaciones || "";

      const dataPartido = encodeURIComponent(JSON.stringify({
        fecha:           p.fecha        || "",
        hora:            p.hora         || "",
        cancha:          p.canchaLugar  || "",
        categoria:       p.categoriaText || "",
        idEquipo1:       p.idEquipo1    || "",
        idEquipo2:       p.idEquipo2    || "",
        idCategoriaPago: p.idCategoriaPagoArbitro || "",
        idArbitro1:      p.idArbitro1   || "",
        idArbitro2:      p.idArbitro2   || "",
        idArbitro3:      p.idArbitro3   || "",
        idArbitro4:      p.idArbitro4   || "",
      }));

      html += `
      <tr data-id="${p.idPartido}" data-partido="${dataPartido}">
        <td><strong>${formatHora(p.hora)}</strong></td>
        <td>${esc(p.canchaLugar)||"—"}</td>
        <td>${esc(p.categoriaText)||"—"}</td>
        <td data-campo="equipoLocal">${esc(p.equipoLocal)}</td>
        <td data-campo="equipoVisitante">${esc(p.equipoVisitante)}</td>
        <td data-campo="arb1">${esc(a1)}</td>
        <td data-campo="arb2">${esc(a2)}</td>
        <td data-campo="arb3">${esc(a3)}</td>
        <td data-campo="arb4">${esc(a4)}</td>
        <td class="acciones-td">
          <button class="btn-acc" title="Editar"   onclick="abrirEdicion(${p.idPartido}, this)">✏️</button>
          <button class="btn-acc btn-obs ${obs?"btn-obs-activa":""}"
                  title="${obs?"Ver/editar observación":"Agregar observación"}"
                  onclick="abrirObservacion(${p.idPartido}, this)">${obs?"📝":"📋"}</button>
          <button class="btn-acc" title="Eliminar" onclick="eliminarPartido(${p.idPartido}, this)">🗑️</button>
        </td>
      </tr>
      ${obs ? `<tr class="fila-obs" data-obs-id="${p.idPartido}">
        <td colspan="10" class="obs-texto"><span class="obs-badge">📝 Obs:</span> ${esc(obs)}</td>
      </tr>` : ""}`;
    });

    html += `</tbody></table></div>`;
    document.getElementById("cuerpoModal").innerHTML = html;
    document.getElementById("modalTitle").textContent = titulo + " — " + formatFecha(data[0].fecha || fecha);
  }

  // ── Observaciones inline ──────────────────────────────────
  window.abrirObservacion = function (id, btn) {
    const tr  = btn.closest("tr");
    const sib = tr.nextElementSibling;
    const obs = (sib && sib.classList.contains("fila-obs"))
      ? sib.querySelector(".obs-texto").textContent.replace("📝 Obs: ","").trim()
      : "";

    const existe = document.getElementById("obs-editor-"+id);
    if (existe) { existe.remove(); return; }

    const row = document.createElement("tr");
    row.id = "obs-editor-"+id;
    row.innerHTML = `
      <td colspan="10" class="obs-editor-td">
        <textarea class="obs-textarea" rows="2" placeholder="Escribe la observación...">${esc(obs)}</textarea>
        <div class="edit-actions">
          <button class="btn-save"   onclick="guardarObservacion(${id})">💾 Guardar</button>
          <button class="btn-cancel" onclick="document.getElementById('obs-editor-${id}').remove()">Cancelar</button>
        </div>
      </td>`;

    const insertAfter = (sib && sib.classList.contains("fila-obs")) ? sib : tr;
    insertAfter.insertAdjacentElement("afterend", row);
    row.querySelector("textarea").focus();
  };

  window.guardarObservacion = function (id) {
    const editor = document.getElementById("obs-editor-"+id);
    const texto  = editor.querySelector("textarea").value.trim();
    postJSON("guardar_observacion", { idPartido:id, observacion:texto }).then(d => {
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
        obsRow.innerHTML = `<td colspan="10" class="obs-texto"><span class="obs-badge">📝 Obs:</span> ${esc(texto)}</td>`;
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
    const hora24 = p.hora ? p.hora.substring(0,5) : "";

    const row = document.createElement("tr");
    row.id = "edit-editor-"+id;
    row.innerHTML = `
      <td colspan="10" class="edit-editor-td">

        <div class="edit-section-title">📅 Fecha y hora</div>
        <div class="edit-grid">
          <label class="edit-label">Hora
            <input type="time" id="eh-hora-${id}" value="${hora24}" class="edit-input">
          </label>
          <label class="edit-label">Fecha
            <input type="date" id="eh-fecha-${id}" value="${esc(p.fecha)}" class="edit-input">
          </label>
          <label class="edit-label">Cancha / Lugar
            <input type="text" id="eh-cancha-${id}" value="${esc(p.cancha)}" class="edit-input" placeholder="Cancha">
          </label>
          <label class="edit-label">Categoría (texto)
            <input type="text" id="eh-cat-${id}" value="${esc(p.categoria)}" class="edit-input" placeholder="Ej: SUPERIOR">
          </label>
        </div>

        <div class="edit-section-title" style="margin-top:12px">⚽ Equipos</div>
        <div class="edit-grid">
          ${selectEquipo("eh-eq1-"+id, p.idEquipo1, "Equipo Local")}
          ${selectEquipo("eh-eq2-"+id, p.idEquipo2, "Equipo Visitante")}
        </div>

        <div class="edit-section-title" style="margin-top:12px">💰 Categoría de pago</div>
        <div class="edit-grid">
          ${selectCategoria("eh-catpago-"+id, p.idCategoriaPago, "Categoría de pago")}
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

    let ref = tr;
    while (ref.nextElementSibling &&
           (ref.nextElementSibling.classList.contains("fila-obs") ||
            /^(obs|edit)-editor/.test(ref.nextElementSibling.id || ""))) {
      ref = ref.nextElementSibling;
    }
    ref.insertAdjacentElement("afterend", row);

    // ── Feedback en tiempo real: árbitros duplicados ──────
    const arbSelectIds = ["eh-a1-","eh-a2-","eh-a3-","eh-a4-"].map(p => p+id);
    arbSelectIds.forEach(sid => {
      const el = document.getElementById(sid);
      if (!el) return;
      el.addEventListener("change", () => {
        // Limpiar todos primero
        arbSelectIds.forEach(s => {
          const e = document.getElementById(s);
          if (e) { e.style.borderColor = ""; e.style.boxShadow = ""; e.title = ""; }
        });
        // Marcar repetidos
        const vals = arbSelectIds.map(s => document.getElementById(s)?.value).filter(v=>v);
        const dups = vals.filter((v,i) => vals.indexOf(v) !== i);
        if (dups.length) {
          arbSelectIds.forEach(s => {
            const e = document.getElementById(s);
            if (e && dups.includes(e.value)) {
              e.style.borderColor = "#ef4444";
              e.style.boxShadow   = "0 0 0 2px rgba(239,68,68,.2)";
              e.title = "Árbitro duplicado";
            }
          });
        }
      });
    });

    // ── Feedback en tiempo real: equipos iguales ──────────
    const selEq1live = document.getElementById("eh-eq1-"+id);
    const selEq2live = document.getElementById("eh-eq2-"+id);
    function checkEquipos() {
      const same = selEq1live?.value && selEq1live.value === selEq2live?.value;
      [selEq1live, selEq2live].forEach(el => {
        if (!el) return;
        el.style.borderColor = same ? "#ef4444" : "";
        el.style.boxShadow   = same ? "0 0 0 2px rgba(239,68,68,.2)" : "";
        el.title             = same ? "No pueden ser el mismo equipo" : "";
      });
    }
    if (selEq1live) selEq1live.addEventListener("change", checkEquipos);
    if (selEq2live) selEq2live.addEventListener("change", checkEquipos);
  };

  // ── Helpers de validación visual ─────────────────────────
  function setFieldError(el, msg) {
    if (!el) return;
    el.style.borderColor = msg ? "#ef4444" : "";
    el.style.boxShadow   = msg ? "0 0 0 2px rgba(239,68,68,.2)" : "";
    // tooltip nativo
    el.title = msg || "";
  }

  function clearErrors(id) {
    ["eh-hora","eh-fecha","eh-cancha","eh-cat","eh-eq1","eh-eq2",
     "eh-catpago","eh-a1","eh-a2","eh-a3","eh-a4"].forEach(prefix => {
      const el = document.getElementById(prefix+"-"+id);
      if (el) setFieldError(el, "");
    });
    const banner = document.getElementById("eh-banner-"+id);
    if (banner) banner.remove();
  }

  function showBanner(id, msg, tipo) {
    let banner = document.getElementById("eh-banner-"+id);
    if (!banner) {
      banner = document.createElement("div");
      banner.id = "eh-banner-"+id;
      banner.style.cssText = "margin-bottom:10px;padding:8px 12px;border-radius:6px;font-size:12px;font-weight:600;";
      const actions = document.querySelector(`#edit-editor-${id} .edit-actions`);
      actions.insertAdjacentElement("beforebegin", banner);
    }
    const colores = { error:"#fef2f2;color:#dc2626;border:1px solid #fca5a5",
                      warn:"#fffbeb;color:#b45309;border:1px solid #fcd34d",
                      ok:"#f0fdf4;color:#16a34a;border:1px solid #86efac" };
    banner.style.cssText += `background:${colores[tipo]||colores.error}`;
    banner.innerHTML = msg;
  }

  window.guardarEdicion = function (id, forzar) {
    forzar = forzar || false;
    clearErrors(id);

    const get   = sid => document.getElementById(sid);
    const fecha = get("eh-fecha-"+id)?.value;
    const hora  = get("eh-hora-"+id)?.value;
    const idEq1 = get("eh-eq1-"+id)?.value;
    const idEq2 = get("eh-eq2-"+id)?.value;
    const a1    = get("eh-a1-"+id)?.value;
    const a2    = get("eh-a2-"+id)?.value;
    const a3    = get("eh-a3-"+id)?.value;
    const a4    = get("eh-a4-"+id)?.value;

    let hayError = false;

    // ── Campos obligatorios ────────────────────────────────
    if (!fecha) {
      setFieldError(get("eh-fecha-"+id), "La fecha es obligatoria");
      hayError = true;
    }
    if (!hora) {
      setFieldError(get("eh-hora-"+id), "La hora es obligatoria");
      hayError = true;
    }
    if (!idEq1) {
      setFieldError(get("eh-eq1-"+id), "Selecciona el equipo local");
      hayError = true;
    }
    if (!idEq2) {
      setFieldError(get("eh-eq2-"+id), "Selecciona el equipo visitante");
      hayError = true;
    }
    if (hayError) {
      showBanner(id, "⚠️ Corrige los campos marcados en rojo antes de guardar.", "error");
      return;
    }

    // ── Mismo equipo local = visitante ─────────────────────
    if (idEq1 === idEq2) {
      setFieldError(get("eh-eq1-"+id), "No puede ser igual al visitante");
      setFieldError(get("eh-eq2-"+id), "No puede ser igual al local");
      showBanner(id, "❌ El equipo local y visitante no pueden ser el mismo.", "error");
      return;
    }

    // ── Árbitros duplicados ────────────────────────────────
    const arbsIds    = [a1,a2,a3,a4].filter(v => v);
    const arbsUnicos = [...new Set(arbsIds)];
    if (arbsIds.length !== arbsUnicos.length) {
      const campos = ["eh-a1","eh-a2","eh-a3","eh-a4"];
      const vals   = [a1,a2,a3,a4];
      const vistos = {};
      vals.forEach((v,i) => {
        if (!v) return;
        if (vistos[v]) {
          setFieldError(get(campos[i]+"-"+id), "Árbitro repetido");
          setFieldError(get(campos[vistos[v]]+"-"+id), "Árbitro repetido");
        } else { vistos[v] = i; }
      });
      showBanner(id, "❌ El mismo árbitro está asignado a más de un puesto.", "error");
      return;
    }

    // ── Hora fuera de rango (advertencia, no bloquea) ──────
    const horaH = parseInt(hora.split(":")[0], 10);
    if (!forzar && (horaH < 6 || horaH >= 23)) {
      showBanner(id,
        `⚠️ La hora <strong>${hora}</strong> está fuera del rango habitual (06:00–23:00).<br>
         <button class="btn-save" style="margin-top:6px;font-size:11px;padding:4px 10px"
           onclick="guardarEdicion(${id}, true)">Guardar de todas formas</button>
         <button class="btn-cancel" style="margin-top:6px;font-size:11px;padding:4px 10px;margin-left:6px"
           onclick="clearErrors(${id})">Corregir</button>`, "warn");
      return;
    }

    // ── Fecha pasada (advertencia, no bloquea) ─────────────
    if (!forzar) {
      const hoy     = new Date(); hoy.setHours(0,0,0,0);
      const fechaDt = new Date(fecha + "T00:00:00");
      if (fechaDt < hoy) {
        showBanner(id,
          `⚠️ La fecha <strong>${fecha}</strong> ya pasó.<br>
           <button class="btn-save" style="margin-top:6px;font-size:11px;padding:4px 10px"
             onclick="guardarEdicion(${id}, true)">Guardar de todas formas</button>
           <button class="btn-cancel" style="margin-top:6px;font-size:11px;padding:4px 10px;margin-left:6px"
             onclick="clearErrors(${id})">Corregir</button>`, "warn");
        return;
      }
    }

    // ── Enviar al servidor ─────────────────────────────────
    const fd = new FormData();
    fd.append("accion",          "actualizar_partido");
    fd.append("idPartido",       id);
    fd.append("fecha",           fecha);
    fd.append("hora",            hora + ":00");
    fd.append("canchaLugar",     get("eh-cancha-"+id).value.trim());
    fd.append("categoriaText",   get("eh-cat-"+id).value.trim());
    fd.append("idEquipo1",       idEq1);
    fd.append("idEquipo2",       idEq2);
    fd.append("idCategoriaPago", get("eh-catpago-"+id)?.value || "");
    fd.append("idArbitro1",      a1);
    fd.append("idArbitro2",      a2);
    fd.append("idArbitro3",      a3);
    fd.append("idArbitro4",      a4);
    if (forzar) fd.append("forzar", "1");

    const btnGuardar = document.querySelector(`#edit-editor-${id} .btn-save`);
    if (btnGuardar) { btnGuardar.disabled = true; btnGuardar.textContent = "Guardando…"; }

    fetch("consultas.php", { method:"POST", body:fd })
      .then(r => r.json())
      .then(d => {
        if (btnGuardar) { btnGuardar.disabled = false; btnGuardar.textContent = "💾 Guardar cambios"; }

        // ── Conflicto de horario ──────────────────────────
        if (d.status === "conflict") {
          const lista = d.conflictos.map(c => `• ${c}`).join("<br>");
          showBanner(id,
            `⚠️ <strong>Conflicto de horario (±30 min):</strong><br>${lista}<br><br>
             <button class="btn-save" style="margin-top:6px;font-size:11px;padding:4px 10px"
               onclick="guardarEdicion(${id}, true)">Forzar igualmente</button>
             <button class="btn-cancel" style="margin-top:6px;font-size:11px;padding:4px 10px;margin-left:6px"
               onclick="clearErrors(${id})">Cancelar</button>`, "warn");
          return;
        }

        if (d.status !== "ok") {
          showBanner(id, "❌ " + (d.msg || "Error al guardar"), "error");
          return;
        }

        // ── Éxito: actualizar fila ────────────────────────
        document.getElementById("edit-editor-"+id).remove();

        const tr = document.querySelector(`tr[data-id="${id}"]`);
        const n  = d.nombres || {};
        const selEq1 = get("eh-eq1-"+id);
        const selEq2 = get("eh-eq2-"+id);
        const nomEq1 = selEq1?.options[selEq1.selectedIndex]?.text || n.eq1 || "—";
        const nomEq2 = selEq2?.options[selEq2.selectedIndex]?.text || n.eq2 || "—";

        tr.cells[0].innerHTML   = `<strong>${formatHora(hora+":00")}</strong>`;
        tr.cells[1].textContent = get("eh-cancha-"+id)?.value.trim() || "—";
        tr.cells[2].textContent = get("eh-cat-"+id)?.value.trim()    || "—";
        tr.querySelector('[data-campo="equipoLocal"]').textContent     = nomEq1;
        tr.querySelector('[data-campo="equipoVisitante"]').textContent = nomEq2;
        tr.querySelector('[data-campo="arb1"]').textContent = (n.n1||"").trim() || "—";
        tr.querySelector('[data-campo="arb2"]').textContent = (n.n2||"").trim() || "—";
        tr.querySelector('[data-campo="arb3"]').textContent = (n.n3||"").trim() || "—";
        tr.querySelector('[data-campo="arb4"]').textContent = (n.n4||"").trim() || "—";

        // Actualizar data-partido para próximas ediciones
        const p = JSON.parse(decodeURIComponent(tr.dataset.partido));
        p.fecha = fecha; p.hora = hora+":00"; p.cancha = get("eh-cancha-"+id).value.trim();
        p.categoria = get("eh-cat-"+id).value.trim();
        p.idEquipo1 = idEq1; p.idEquipo2 = idEq2;
        p.idCategoriaPago = get("eh-catpago-"+id)?.value || "";
        p.idArbitro1 = a1; p.idArbitro2 = a2; p.idArbitro3 = a3; p.idArbitro4 = a4;
        tr.dataset.partido = encodeURIComponent(JSON.stringify(p));

        calendar.refetchEvents();
      })
      .catch(() => {
        if (btnGuardar) { btnGuardar.disabled = false; btnGuardar.textContent = "💾 Guardar cambios"; }
        showBanner(id, "❌ Error de red. Intenta de nuevo.", "error");
      });
  };

  // Exponer clearErrors globalmente (lo usan los botones inline del banner)
  window.clearErrors = (id) => {
    const banner = document.getElementById("eh-banner-"+id);
    if (banner) banner.remove();
    ["eh-hora","eh-fecha","eh-cancha","eh-cat","eh-eq1","eh-eq2",
     "eh-catpago","eh-a1","eh-a2","eh-a3","eh-a4"].forEach(prefix => {
      const el = document.getElementById(prefix+"-"+id);
      if (el) { el.style.borderColor = ""; el.style.boxShadow = ""; el.title = ""; }
    });
  };

  // ── Eliminar partido ──────────────────────────────────────
  window.eliminarPartido = function (id, btn) {
    if (!confirm("¿Eliminar este partido? La acción no se puede deshacer.")) return;
    postJSON("eliminar_partido", { idPartido:id }).then(d => {
      if (d.status !== "ok") { alert("❌ "+(d.msg||"No se pudo eliminar")); return; }
      const tr = btn.closest("tr");
      let sib  = tr.nextElementSibling;
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
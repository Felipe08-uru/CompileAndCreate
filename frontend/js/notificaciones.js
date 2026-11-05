// "Campanita" de notificaciones. Se agrega al banner, muestra cuántas notificaciones
// no se leyeron y, al presionarla, la lista (por ejemplo: "su incidencia fue resuelta").
// Consulta al servidor al abrir la página y luego cada 30 segundos.
import { getNotificaciones, marcarNotificacionLeida, marcarTodasLeidas } from "./APINotificaciones.js";

const INTERVALO_MS = 30000;

function formatearFecha(texto) {
    // El servidor envía la hora de Uruguay como "AAAA-MM-DD HH:MM:SS".
    const m = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/.exec(texto || "");
    return m ? m[3] + "/" + m[2] + "/" + m[1] + " " + m[4] + ":" + m[5] : "";
}

export function iniciarCampana() {
    const header = document.querySelector(".header");

    if (!header || document.getElementById("campana")) return;

    const campana = document.createElement("div");
    campana.className = "campana";
    campana.id = "campana";
    campana.innerHTML =
        "<button type='button' class='campana-boton' id='campanaBoton' aria-label='Notificaciones' aria-expanded='false'>" +
        "<svg viewBox='0 0 24 24' width='24' height='24' aria-hidden='true'>" +
        "<path fill='currentColor' d='M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22zm7-6v-5a7 7 0 0 0-5.5-6.84V3.5a1.5 1.5 0 0 0-3 0v.66A7 7 0 0 0 5 11v5l-2 2v1h18v-1l-2-2z'/></svg>" +
        "<span class='campana-contador' id='campanaContador' hidden>0</span>" +
        "</button>" +
        "<div class='campana-panel' id='campanaPanel' hidden>" +
        "<div class='campana-cabecera'><strong>Notificaciones</strong>" +
        "<button type='button' class='campana-todas' id='campanaTodas'>Marcar todas como leídas</button></div>" +
        "<ul class='campana-lista' id='campanaLista'></ul></div>";

    header.appendChild(campana);

    const boton = document.getElementById("campanaBoton");
    const contador = document.getElementById("campanaContador");
    const panel = document.getElementById("campanaPanel");
    const lista = document.getElementById("campanaLista");
    const todas = document.getElementById("campanaTodas");

    function dibujar(datos) {
        const noLeidas = datos.no_leidas || 0;

        contador.textContent = noLeidas > 99 ? "99+" : String(noLeidas);
        contador.hidden = noLeidas === 0;
        boton.classList.toggle("con-avisos", noLeidas > 0);
        todas.hidden = noLeidas === 0;

        lista.innerHTML = "";

        if (!datos.notificaciones || datos.notificaciones.length === 0) {
            const vacio = document.createElement("li");
            vacio.className = "campana-vacio";
            vacio.textContent = "No tiene notificaciones.";
            lista.appendChild(vacio);
            return;
        }

        datos.notificaciones.forEach(function (n) {
            const item = document.createElement("li");
            item.className = "campana-item" + (n.Leida ? "" : " sin-leer");

            const mensaje = document.createElement("span");
            mensaje.className = "campana-mensaje";
            mensaje.textContent = n.Mensaje;

            const fecha = document.createElement("small");
            fecha.className = "campana-fecha";
            fecha.textContent = formatearFecha(n.Fecha);

            item.appendChild(mensaje);
            item.appendChild(fecha);

            if (!n.Leida) {
                item.addEventListener("click", async function () {
                    await marcarNotificacionLeida(n.Id_Notificacion);
                    actualizar();
                });
            }

            lista.appendChild(item);
        });
    }

    async function actualizar() {
        try {
            const { ok, data } = await getNotificaciones();

            if (ok && data && !data.error) dibujar(data);
        } catch (error) {
            console.error("Notificaciones:", error);
        }
    }

    boton.addEventListener("click", function (e) {
        e.stopPropagation();
        const abrir = panel.hidden;
        panel.hidden = !abrir;
        boton.setAttribute("aria-expanded", String(abrir));

        if (abrir) actualizar();
    });

    todas.addEventListener("click", async function () {
        await marcarTodasLeidas();
        actualizar();
    });

    document.addEventListener("click", function (e) {
        if (!panel.hidden && !campana.contains(e.target)) {
            panel.hidden = true;
            boton.setAttribute("aria-expanded", "false");
        }
    });

    actualizar();
    setInterval(function () {
        if (!document.hidden) actualizar();
    }, INTERVALO_MS);
}

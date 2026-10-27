import { verificarSesion, cerrarSesion } from "./auth.js";
import { getMiAsignacion } from "./APIAsignaciones.js";
import { getRutaActiva } from "./APIRutas.js";
import { activarFlechaScroll } from "./scrollFlecha.js";

function esc(texto) {
    const div = document.createElement("div");
    div.textContent = texto === null || texto === undefined ? "" : String(texto);
    return div.innerHTML;
}

function formatearFecha(valor) {
    if (!valor) return "-";

    const fecha = new Date(String(valor).replace(" ", "T"));

    if (isNaN(fecha.getTime())) return valor;

    return fecha.toLocaleString("es-UY", { dateStyle: "short", timeStyle: "short" });
}

function etiquetaEstado(estado) {
    const clase = "estado-" + String(estado).toLowerCase().replace(/\s+/g, "-");
    return "<span class='etiqueta-estado " + clase + "'>" + esc(estado) + "</span>";
}

// Misma imagen de contenedor que usan los mapas; el círculo naranja de atrás marca el siguiente.
function miniContenedor(parada) {
    return "<span class='mini-cont'><img src='img/contenedor.png' alt=''>" +
        "<b>" + (parada.Estado === "Recogido" ? "✓" : parada.Orden) + "</b></span>";
}

function tarjeta(titulo, valor, detalle) {
    return "<div class='tarjeta-dato'>" +
        "<div><span>" + esc(titulo) + "</span><strong>" + valor + "</strong>" +
        (detalle ? "<small>" + detalle + "</small>" : "") + "</div></div>";
}

function mostrarTarjetas(info) {
    const contenedor = document.getElementById("tarjetasResumen");

    if (!info.asignado) {
        contenedor.innerHTML =
            "<div class='aviso-vacio' style='flex:1'>Todavía no tiene un camión asignado. " +
            "El administrador debe asignárselo para que pueda recibir rutas.</div>";
        return;
    }

    const ruta = info.ruta_activa;

    contenedor.innerHTML =
        tarjeta("Mi camión", esc(info.camion.Matricula), esc(info.camion.Tipo) + " · " + esc(info.camion.Estado)) +
        tarjeta("Zona de la ruta", ruta ? "Zona " + ruta.Id_Poligono : "Sin ruta", ruta ? esc(ruta.Tipo) : "Esperando asignación") +
        tarjeta("Avance", ruta ? ruta.Paradas_Hechas + " de " + ruta.Paradas_Total : "-", ruta ? "contenedores recogidos" : "") +
        tarjeta("Peso recogido", ruta ? ruta.Peso_Total + " kg" : "-", ruta ? "en la ruta actual" : "");
}

async function mostrarRuta(info) {
    const carta = document.getElementById("cartaRuta");

    if (!info.asignado) {
        carta.innerHTML =
            "<div class='aviso-vacio'>Cuando el administrador le asigne un camión y una ruta, " +
            "los va a ver acá.</div>";
        return;
    }

    if (!info.ruta_activa) {
        carta.innerHTML =
            "<div class='aviso-vacio'>Su camión no tiene ninguna ruta pendiente. " +
            "Las rutas las crea y asigna el administrador.</div>";
        return;
    }

    const { ok, data } = await getRutaActiva();

    if (!ok || !data.ruta) {
        carta.innerHTML = "<p class='mensaje-error'>No se pudo cargar la ruta.</p>";
        return;
    }

    const ruta = data.ruta;
    const total = data.paradas.length;
    const porcentaje = ruta.Paradas_Total > 0
        ? Math.round((ruta.Paradas_Hechas / ruta.Paradas_Total) * 100)
        : 0;

    const siguiente = data.paradas.find(function (p) { return p.Orden === data.siguiente_orden; });

    let pasos = "";

    data.paradas.forEach(function (parada) {
        let clase = "paso";
        let nota = "";

        if (parada.Estado === "Recogido") clase += " hecho";
        if (parada.Orden === data.siguiente_orden) {
            clase += " actual";
            nota = "Siguiente";
        }

        if (parada.Orden === 1) nota = nota ? "Inicio · " + nota : "Inicio";
        if (parada.Orden === total) nota = nota ? nota + " · Fin" : "Fin";

        pasos +=
            "<li class='" + clase + "'>" +
            miniContenedor(parada) +
            "<div class='paso-texto'><strong>Contenedor " + parada.Id_Contenedor + "</strong>" +
            "<small>" + esc(parada.Tipo_Contenedor) +
            (parada.Peso !== null ? " · " + parada.Peso + " kg" : "") + "</small></div>" +
            (nota ? "<span class='paso-nota'>" + esc(nota) + "</span>" : "") +
            "</li>";
    });

    let proximo = "<div class='aviso-vacio'>Todos los contenedores fueron recogidos.</div>";

    if (siguiente) {
        proximo =
            "<div class='proxima-parada'>" +
            "<span>Próxima parada</span>" +
            "<strong>Parada " + siguiente.Orden + " de " + total + " · Contenedor " + siguiente.Id_Contenedor + "</strong>" +
            "</div>";
    }

    carta.innerHTML =
        "<div class='ruta-cabecera'>" +
        "<div><h3>Ruta #" + ruta.Id_Ruta + " · " + esc(ruta.Tipo) + "</h3>" +
        "<p>Zona " + ruta.Id_Poligono + " · " + etiquetaEstado(ruta.Estado) + "</p></div>" +
        "<a class='btn' href='mapaRuta.html' data-menu='Abrir mapa de la ruta' data-menu-grupo='Mi jornada'>Abrir mapa de la ruta</a>" +
        "</div>" +
        "<div class='barra-progreso'><div style='width:" + porcentaje + "%'></div></div>" +
        "<p class='barra-texto'>" + porcentaje + "% completado</p>" +
        proximo +
        "<ol class='pasos'>" + pasos + "</ol>";
}

function mostrarHistorial(historial) {
    const tabla = document.getElementById("tablaHistorial");
    const mensaje = document.getElementById("mensajeHistorial");

    tabla.innerHTML = "";

    if (historial.length === 0) {
        mensaje.textContent = "Todavía no finalizó ninguna ruta.";
        return;
    }

    mensaje.textContent = "";

    historial.forEach(function (ruta) {
        const fila = document.createElement("tr");

        fila.innerHTML =
            "<td>" + ruta.Id_Ruta + "</td>" +
            "<td>" + formatearFecha(ruta.Fecha_Fin || ruta.Fecha_Creacion) + "</td>" +
            "<td>" + esc(ruta.Tipo) + "</td>" +
            "<td>Zona " + ruta.Id_Poligono + "</td>" +
            "<td>" + ruta.Paradas_Total + "</td>" +
            "<td>" + ruta.Peso_Total + "</td>" +
            "<td class='acciones'><a class='btn' href='mapaRuta.html?id=" + ruta.Id_Ruta + "'>Ver</a></td>";

        tabla.appendChild(fila);
    });
}

async function cargarPanel() {
    const { ok, data } = await getMiAsignacion();

    if (!ok) {
        document.getElementById("tarjetasResumen").innerHTML =
            "<p class='mensaje-error'>No se pudo cargar su información.</p>";
        document.getElementById("cartaRuta").innerHTML = "";
        return;
    }

    mostrarTarjetas(data);
    await mostrarRuta(data);
    mostrarHistorial(data.historial || []);
}

window.cerrarSesion = () => cerrarSesion("index.html");

verificarSesion("Cuadrilla", "index.html");
cargarPanel();
activarFlechaScroll();

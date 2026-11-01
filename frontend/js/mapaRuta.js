import { ESTILO_ZONA } from "./zonaEstilo.js";
import { verificarSesion, cerrarSesion } from "./auth.js";
import { getRuta, getRutaActiva, registrarPeso } from "./APIRutas.js";
import { activarFlechaScroll } from "./scrollFlecha.js";

const rol = localStorage.getItem("rol");
const idRutaParam = new URLSearchParams(window.location.search).get("id");

// Solo la cuadrilla (registra pesos) y el administrador (solo lectura) ven esta página.
const esCuadrilla = rol === "Cuadrilla";
const esAdmin = rol === "Administrador";

document.getElementById("enlaceVolver").href = esAdmin ? "administrador.html" : "cuadrilla.html";

const mapa = L.map("mapa").setView([-34.9055, -56.1905], 15);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors"
}).addTo(mapa);

const capaZona = L.layerGroup().addTo(mapa);
const capaParadas = L.layerGroup().addTo(mapa);
const capaFlechas = L.layerGroup().addTo(mapa);
const marcadoresPorOrden = {};
let coordsTrazado = [];
let capaLineaRespaldo = null;
let controlRuta = null;
let detalle = null;
let primeraVez = true;

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

function dato(titulo, valor) {
    return "<div class='dato'><span>" + esc(titulo) + "</span><strong>" + valor + "</strong></div>";
}

function mostrarMensajeMapa(texto, esError) {
    const mensaje = document.getElementById("mensajeMapa");
    mensaje.className = esError ? "mensaje-error" : "";
    mensaje.textContent = texto;
}

// ---------------------------------------------------------------------
// Dibujo del mapa
// ---------------------------------------------------------------------

/**
 * Marcador de una parada: la imagen del contenedor (la misma que usan los otros
 * mapas) con el número de orden pegado a ella. Todo va dentro de una caja de
 * tamaño fijo y sin márgenes ni transformaciones propias, así que imagen y
 * número se mueven juntos al hacer zoom. El pie del marcador es el punto exacto.
 */
function iconoParada(parada, esActual, total) {
    let clase = "pm";
    const etiquetas = [];

    if (parada.Estado === "Recogido") clase += " hecha";
    if (esActual) clase += " actual";

    if (parada.Orden === 1) etiquetas.push("INICIO");
    if (esActual) etiquetas.push("SIGUIENTE");
    if (parada.Orden === total) etiquetas.push("FIN");

    let html = "<div class='" + clase + "'>";

    if (etiquetas.length > 0) {
        html += "<span class='pm-etiqueta'>" + etiquetas.join(" · ") + "</span>";
    }

    html += "<img src='img/contenedor.png' alt='' draggable='false'>" +
        "<span class='pm-numero'>" + (parada.Estado === "Recogido" ? "✓" : parada.Orden) + "</span>" +
        "</div>";

    return L.divIcon({
        className: "pm-icono",
        html: html,
        iconSize: [44, 50],
        iconAnchor: [22, 48],
        popupAnchor: [0, -46]
    });
}

function contenidoPopup(parada, total, esActual) {
    let html =
        "<strong>Parada " + parada.Orden + " de " + total + "</strong><br>" +
        "Contenedor " + parada.Id_Contenedor + "<br>" +
        "Tipo: " + esc(parada.Tipo_Contenedor) + "<br>" +
        "Estado: " + esc(parada.Estado);

    if (parada.Peso !== null) {
        html += "<br>Peso: " + parada.Peso + " kg";
    }

    if (parada.Incidencias && parada.Incidencias.length > 0) {
        html += "<br><br><strong>Incidencias:</strong>";

        parada.Incidencias.forEach(function (incidencia) {
            html += "<br>• " + esc(incidencia.Tipo);

            if (incidencia.Foto) {
                html += " (<a href='../backend/uploads/incidencias/" +
                    encodeURIComponent(incidencia.Foto) + "' target='_blank'>ver foto</a>)";
            }
        });
    }

    if (esActual && esCuadrilla) {
        html += "<br><br><strong>¿Cuánto pesaba este contenedor?</strong><br>" +
            "<button type='button' class='btn-mapa' onclick='enfocarPeso()'>Registrar peso</button>";
    }

    return html;
}

function dibujarZona() {
    capaZona.clearLayers();

    if (!detalle.poligono || detalle.poligono.vertices.length < 3) {
        return null;
    }

    const vertices = detalle.poligono.vertices.map(function (v) {
        return [v.latitud, v.longitud];
    });

    // La ruta pertenece únicamente a esta zona: es el único polígono que se dibuja.
    const poligono = L.polygon(vertices, ESTILO_ZONA).addTo(capaZona);

    return poligono;
}

function dibujarParadas() {
    capaParadas.clearLayers();

    Object.keys(marcadoresPorOrden).forEach(function (clave) { delete marcadoresPorOrden[clave]; });

    let marcadorActual = null;
    const total = detalle.paradas.length;

    detalle.paradas.forEach(function (parada) {
        const esActual = esCuadrilla &&
            detalle.ruta.Estado !== "Finalizada" &&
            parada.Orden === detalle.siguiente_orden;

        const marcador = L.marker([parada.Latitud, parada.Longitud], {
            icon: iconoParada(parada, esActual, total),
            zIndexOffset: esActual ? 1000 : parada.Orden === 1 ? 500 : 0,
            title: "Parada " + parada.Orden + " - Contenedor " + parada.Id_Contenedor
        }).addTo(capaParadas);

        marcador.bindPopup(contenidoPopup(parada, total, esActual));
        marcadoresPorOrden[parada.Orden] = marcador;

        if (esActual) {
            marcadorActual = marcador;
        }
    });

    return marcadorActual;
}

// ---------------------------------------------------------------------
// Flechas de dirección sobre el recorrido
// ---------------------------------------------------------------------

/**
 * Dibuja una flecha cada cierta distancia EN PANTALLA siguiendo el recorrido,
 * apuntando hacia donde avanza el camión. Se recalcula al cambiar el zoom.
 */
function dibujarFlechas() {
    capaFlechas.clearLayers();

    if (coordsTrazado.length < 2) return;

    const zoom = mapa.getZoom();
    const separacion = 110;
    let acumulado = separacion / 2;

    for (let i = 1; i < coordsTrazado.length; i++) {
        const a = mapa.project(coordsTrazado[i - 1], zoom);
        const b = mapa.project(coordsTrazado[i], zoom);
        const dx = b.x - a.x;
        const dy = b.y - a.y;
        const largo = Math.sqrt(dx * dx + dy * dy);

        if (largo === 0) continue;

        const angulo = Math.atan2(dy, dx) * 180 / Math.PI;
        let posicion = separacion - acumulado;

        while (posicion <= largo) {
            const punto = mapa.unproject(L.point(a.x + dx * posicion / largo, a.y + dy * posicion / largo), zoom);

            L.marker(punto, {
                interactive: false,
                keyboard: false,
                icon: L.divIcon({
                    className: "flecha-ruta-icono",
                    html: "<div class='flecha-ruta' style='transform:rotate(" + angulo.toFixed(0) + "deg)'></div>",
                    iconSize: [16, 16],
                    iconAnchor: [8, 8]
                })
            }).addTo(capaFlechas);

            posicion += separacion;
        }

        acumulado = (acumulado + largo) % separacion;
    }
}

mapa.on("zoomend", dibujarFlechas);

function puntosDelTrazado() {
    const puntos = [];

    if (esCuadrilla && detalle.ruta.Estado !== "Finalizada") {
        // Desde la última parada recogida hasta el final de la ruta.
        const hechas = detalle.paradas.filter(function (p) { return p.Estado === "Recogido"; });
        const pendientes = detalle.paradas.filter(function (p) { return p.Estado === "Pendiente"; });

        if (hechas.length > 0) {
            const ultima = hechas[hechas.length - 1];
            puntos.push([ultima.Latitud, ultima.Longitud]);
        }

        pendientes.forEach(function (p) {
            puntos.push([p.Latitud, p.Longitud]);
        });
    } else {
        detalle.paradas.forEach(function (p) {
            puntos.push([p.Latitud, p.Longitud]);
        });
    }

    return puntos;
}

function quitarLineaRespaldo() {
    if (capaLineaRespaldo) {
        mapa.removeLayer(capaLineaRespaldo);
        capaLineaRespaldo = null;
    }
}

function dibujarLineaRecta(puntos) {
    quitarLineaRespaldo();

    capaLineaRespaldo = L.polyline(puntos, {
        color: "#0f4c81",
        weight: 4,
        dashArray: "8 8"
    }).addTo(mapa);

    coordsTrazado = puntos.map(function (p) { return L.latLng(p[0], p[1]); });
    dibujarFlechas();
}

/**
 * Traza la ruta sobre las calles con Leaflet Routing Machine, pasando por
 * las paradas en el orden optimizado que calculó el servidor.
 */
function trazarRuta(puntos) {
    quitarLineaRespaldo();

    const hayRouting = typeof L.Routing !== "undefined" && typeof L.Routing.control === "function";

    if (!hayRouting) {
        if (puntos.length >= 2) {
            dibujarLineaRecta(puntos);
            mostrarMensajeMapa(
                "No se pudo cargar Leaflet Routing Machine: se muestra el recorrido en línea recta.",
                true
            );
        }
        return;
    }

    const waypoints = puntos.map(function (p) {
        return L.latLng(p[0], p[1]);
    });

    if (controlRuta === null) {
        if (puntos.length < 2) {
            coordsTrazado = [];
            dibujarFlechas();
            return;
        }

        const idioma = L.Routing.Localization && L.Routing.Localization.es ? "es" : "en";

        controlRuta = L.Routing.control({
            waypoints: waypoints,
            router: L.Routing.osrmv1({
                serviceUrl: "https://router.project-osrm.org/route/v1",
                profile: "driving"
            }),
            lineOptions: {
                styles: [
                    { color: "white", opacity: 0.9, weight: 9 },
                    { color: "#0f4c81", opacity: 0.95, weight: 5 }
                ],
                extendToWaypoints: true,
                missingRouteTolerance: 0
            },
            createMarker: function () { return null; },
            addWaypoints: false,
            draggableWaypoints: false,
            routeWhileDragging: false,
            fitSelectedRoutes: false,
            showAlternatives: false,
            show: false,
            language: idioma
        }).addTo(mapa);

        controlRuta.on("routesfound", function (e) {
            quitarLineaRespaldo();

            coordsTrazado = e.routes[0].coordinates;
            dibujarFlechas();

            const resumen = e.routes[0].summary;
            const km = (resumen.totalDistance / 1000).toFixed(1);
            const minutos = Math.round(resumen.totalTime / 60);

            mostrarMensajeMapa("Recorrido restante: " + km + " km (aprox. " + minutos + " min en camión).", false);
        });

        controlRuta.on("routingerror", function () {
            const actuales = controlRuta.getWaypoints()
                .filter(function (w) { return w.latLng; })
                .map(function (w) { return [w.latLng.lat, w.latLng.lng]; });

            dibujarLineaRecta(actuales);
            mostrarMensajeMapa(
                "No se pudo calcular el trazado por calles (servicio de rutas no disponible). Se muestra el recorrido en línea recta.",
                true
            );
        });

        return;
    }

    // Ya existe el control: solo se le cambian los puntos (menos de 2 limpia la línea).
    controlRuta.setWaypoints(puntos.length >= 2 ? waypoints : []);

    if (puntos.length < 2) {
        coordsTrazado = [];
        dibujarFlechas();
        mostrarMensajeMapa("", false);
    }
}

// ---------------------------------------------------------------------
// Paneles de información
// ---------------------------------------------------------------------

function mostrarResumen() {
    const ruta = detalle.ruta;

    document.getElementById("tituloRuta").textContent =
        "Ruta #" + ruta.Id_Ruta + " - " + ruta.Tipo;

    document.getElementById("subtituloRuta").textContent =
        "Zona " + ruta.Id_Poligono + " · Solo se recorren los contenedores de esta zona.";

    document.getElementById("resumenRuta").innerHTML =
        dato("Zona", "Zona " + ruta.Id_Poligono) +
        dato("Tipo de ruta", esc(ruta.Tipo)) +
        dato("Camión", ruta.Matricula ? esc(ruta.Matricula) : "-") +
        dato("Cuadrilla", ruta.Nombre_Cuadrilla ? esc(ruta.Nombre_Cuadrilla) : "-") +
        dato("Estado", etiquetaEstado(ruta.Estado)) +
        dato("Avance", ruta.Paradas_Hechas + " de " + ruta.Paradas_Total) +
        dato("Peso recogido", ruta.Peso_Total + " kg");
}

function mostrarListaOrden() {
    const lista = document.getElementById("listaOrden");
    lista.innerHTML = "";

    const total = detalle.paradas.length;

    detalle.paradas.forEach(function (parada) {
        const esActual = esCuadrilla &&
            detalle.ruta.Estado !== "Finalizada" &&
            parada.Orden === detalle.siguiente_orden;

        let clase = "orden-item";
        if (parada.Estado === "Recogido") clase += " hecha";
        if (esActual) clase += " actual";

        let nota = "";
        if (parada.Orden === 1) nota = "Inicio de la ruta";
        else if (parada.Orden === total) nota = "Fin de la ruta";
        if (esActual) nota = nota ? "Siguiente · " + nota : "Siguiente parada";

        const item = document.createElement("li");
        item.className = clase;
        item.innerHTML =
            "<span class='mini-cont'><img src='img/contenedor.png' alt=''><b>" +
            (parada.Estado === "Recogido" ? "✓" : parada.Orden) + "</b></span>" +
            "<span class='orden-texto'><strong>Contenedor " + parada.Id_Contenedor + "</strong>" +
            "<small>" + esc(parada.Tipo_Contenedor) +
            (parada.Peso !== null ? " · " + parada.Peso + " kg" : "") +
            (nota ? " · " + esc(nota) : "") + "</small></span>";

        item.addEventListener("click", function () {
            const marcador = marcadoresPorOrden[parada.Orden];
            if (!marcador) return;

            mapa.setView(marcador.getLatLng(), Math.max(mapa.getZoom(), 17), { animate: true });
            marcador.openPopup();
        });

        lista.appendChild(item);
    });
}

function mostrarTablaParadas() {
    const tabla = document.getElementById("tablaParadas");
    tabla.innerHTML = "";

    detalle.paradas.forEach(function (parada) {
        const fila = document.createElement("tr");

        fila.innerHTML =
            "<td>" + parada.Orden + "</td>" +
            "<td>Contenedor " + parada.Id_Contenedor + "</td>" +
            "<td>" + esc(parada.Tipo_Contenedor) + "</td>" +
            "<td>" + etiquetaEstado(parada.Estado) + "</td>" +
            "<td>" + (parada.Peso !== null ? parada.Peso : "-") + "</td>" +
            "<td>" + formatearFecha(parada.Fecha_Recoleccion) + "</td>";

        tabla.appendChild(fila);
    });
}

function mostrarPanelParada() {
    const panel = document.getElementById("panelParada");
    const ruta = detalle.ruta;

    if (esAdmin) {
        panel.innerHTML =
            "<div class='aviso-vacio'>Vista de administrador (solo lectura). " +
            "Los pesos los registra la cuadrilla al recoger cada contenedor.</div>";
        return;
    }

    if (ruta.Estado === "Finalizada") {
        panel.innerHTML =
            "<div class='panel-parada-actual' style='border-color:#2e8b57;background:#f1fbf4;'>" +
            "<h3>Ruta finalizada</h3>" +
            "<p>Se recogieron " + ruta.Paradas_Total + " contenedores. " +
            "Peso total: <strong>" + ruta.Peso_Total + " kg</strong>.</p>" +
            "<a class='btn' href='cuadrilla.html'>Volver al panel</a>" +
            "</div>";
        return;
    }

    const actual = detalle.paradas.find(function (p) {
        return p.Orden === detalle.siguiente_orden;
    });

    if (!actual) {
        panel.innerHTML = "";
        return;
    }

    let ayuda = "Ingrese el peso del contenedor una vez recogido. El sistema pasará a la siguiente parada de la ruta.";

    if (ruta.Tipo === "Incidencias") {
        ayuda = "Atienda las incidencias de este contenedor. Si no retiró residuos, deje el peso en 0.";
    }

    panel.innerHTML =
        "<div class='panel-parada-actual'>" +
        "<h3>Parada " + actual.Orden + " de " + detalle.paradas.length +
        " · Contenedor " + actual.Id_Contenedor + "</h3>" +
        "<p><strong>¿Cuánto pesaba el contenedor?</strong> " + ayuda + "</p>" +
        "<label for='pesoContenedor'>Peso (kg)</label><br>" +
        "<input type='number' id='pesoContenedor' min='0' max='10000' step='0.01' " +
        "placeholder='Ej: 85.5' style='padding:8px;width:200px;'><br>" +
        "<button type='button' class='btn' id='btnRegistrarPeso'>Registrar peso y continuar</button>" +
        "<p id='mensajePeso'></p>" +
        "</div>";

    document.getElementById("btnRegistrarPeso").addEventListener("click", enviarPeso);

    document.getElementById("pesoContenedor").addEventListener("keydown", function (evento) {
        if (evento.key === "Enter") {
            enviarPeso();
        }
    });
}

function enfocarPeso() {
    const campo = document.getElementById("pesoContenedor");

    if (campo) {
        campo.scrollIntoView({ behavior: "smooth", block: "center" });
        campo.focus();
    }
}

async function enviarPeso() {
    const campo = document.getElementById("pesoContenedor");
    const boton = document.getElementById("btnRegistrarPeso");
    const mensaje = document.getElementById("mensajePeso");

    const texto = campo.value.trim();
    const peso = Number(texto);

    mensaje.className = "mensaje-error";

    if (texto === "" || isNaN(peso)) {
        mensaje.textContent = "Ingrese el peso del contenedor en kilos.";
        return;
    }

    if (peso < 0 || peso > 10000) {
        mensaje.textContent = "El peso debe estar entre 0 y 10000 kg.";
        return;
    }

    boton.disabled = true;
    mensaje.className = "";
    mensaje.textContent = "Registrando...";

    const { ok, data } = await registrarPeso(detalle.ruta.Id_Ruta, detalle.siguiente_orden, peso);

    if (!ok || data.error) {
        mensaje.className = "mensaje-error";
        mensaje.textContent = data.error || "No se pudo registrar el peso.";
        boton.disabled = false;
        return;
    }

    detalle = data.detalle;
    dibujar();

    mostrarMensajeMapa(data.mensaje, false);
}

// ---------------------------------------------------------------------
// Carga y render general
// ---------------------------------------------------------------------

function dibujar() {
    mostrarResumen();
    mostrarTablaParadas();
    mostrarListaOrden();
    mostrarPanelParada();

    const poligono = dibujarZona();
    const marcadorActual = dibujarParadas();

    trazarRuta(puntosDelTrazado());

    if (primeraVez) {
        primeraVez = false;

        if (poligono) {
            mapa.fitBounds(poligono.getBounds(), { padding: [30, 30] });
        } else if (detalle.paradas.length > 0) {
            mapa.fitBounds(
                detalle.paradas.map(function (p) { return [p.Latitud, p.Longitud]; }),
                { padding: [30, 30] }
            );
        }
    }

    // Al abrir el mapa, el contenedor actual muestra la pregunta del peso.
    if (marcadorActual) {
        marcadorActual.openPopup();
    }
}

async function cargar() {
    let respuesta;

    if (idRutaParam) {
        respuesta = await getRuta(idRutaParam);
    } else if (esCuadrilla) {
        respuesta = await getRutaActiva();
    } else {
        mostrarMensajeMapa("Indique la ruta que desea ver.", true);
        return;
    }

    if (!respuesta.ok) {
        mostrarMensajeMapa(respuesta.data.error || "No se pudo cargar la ruta.", true);
        return;
    }

    if (!respuesta.data.ruta) {
        document.getElementById("panelParada").innerHTML =
            "<div class='aviso-vacio'>No tiene una ruta en curso. Cuando el administrador le asigne una ruta aparecerá acá.</div>" +
            "<a class='btn' href='cuadrilla.html'>Ir a mi panel</a>";
        return;
    }

    detalle = respuesta.data;
    dibujar();
}

window.cerrarSesion = () => cerrarSesion("index.html");
window.enfocarPeso = enfocarPeso;

if (!esCuadrilla && !esAdmin) {
    cerrarSesion("index.html");
} else {
    verificarSesion(rol, "index.html");
    cargar();
    activarFlechaScroll();
}

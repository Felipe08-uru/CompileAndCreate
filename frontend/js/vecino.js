import { verificarSesion, cerrarSesion } from "./auth.js";
import { getPoligonos } from "./APIPoligonos.js";
import { getContenedores } from "./APIContenedores.js";
import { crearIncidencia } from "./APIIncidencias.js";

const modal = document.getElementById("modal");
const overlay = document.getElementById("overlay");

function mostrarIncidencia(idContenedor) {
    modal.style.display = "block";
    overlay.style.display = "block";

    document.getElementById("idContenedor").value = idContenedor;
    document.getElementById("tipoIncidencia").value = "";
    document.getElementById("foto").value = "";
    document.getElementById("mensajeIncidencia").textContent = "";
}

function cerrarModal() {
    modal.style.display = "none";
    overlay.style.display = "none";
    document.getElementById("tipoIncidencia").value = "";
    document.getElementById("foto").value = "";
    document.getElementById("mensajeIncidencia").textContent = "";
}

let mapa = L.map("mapa").setView([-34.9055, -56.1905], 16);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors"
}).addTo(mapa);

let iconoContenedor = L.icon({
    iconUrl: "img/contenedor.png",
    iconSize: [30, 30],
    iconAnchor: [15, 20],
    popupAnchor: [0, -20]
});

function calcularCentro(vertices) {
    let sumaLatitud = 0;
    let sumaLongitud = 0;

    vertices.forEach(function (punto) {
        sumaLatitud += punto[0];
        sumaLongitud += punto[1];
    });

    return [sumaLatitud / vertices.length, sumaLongitud / vertices.length];
}

async function cargarZonas() {
    const { ok, data } = await getPoligonos();

    if (!ok) {
        document.getElementById("mensajeMapa").textContent = "No se pudieron cargar las zonas.";
        return;
    }

    data.forEach(function (zona) {
        let vertices = zona.vertices.map(function (v) {
            return [parseFloat(v.latitud), parseFloat(v.longitud)];
        });

        if (vertices.length >= 3) {
            L.polygon(vertices, {
                color: "#0f4c81",
                fillColor: "#0f4c81",
                fillOpacity: 0.2,
                weight: 2
            }).addTo(mapa);

            let centro = calcularCentro(vertices);

            L.marker(centro, {
                icon: L.divIcon({
                    className: "id-zona",
                    html: "<span>" + zona.id_poligono + "</span>",
                    iconSize: null,
                    iconAnchor: [0, 0]
                }),
                interactive: false
            }).addTo(mapa);
        }
    });
}

async function cargarContenedores() {
    const { ok, data } = await getContenedores();

    if (!ok) {
        document.getElementById("mensajeMapa").textContent = "No se pudieron cargar los contenedores.";
        return;
    }

    data.forEach(function (contenedor) {
        if (contenedor.Latitud !== null && contenedor.Longitud !== null) {
            let marcador = L.marker(
                [parseFloat(contenedor.Latitud), parseFloat(contenedor.Longitud)],
                { icon: iconoContenedor }
            ).addTo(mapa);

            marcador.bindPopup(
                "<strong>Contenedor " +
                    contenedor.Id_Contenedor +
                    "</strong><br>" +
                    "Tipo: " +
                    contenedor.Tipo +
                    "<br>" +
                    "Estado: " +
                    contenedor.Estado +
                    "<br><br>" +
                    "<button class='btn-mapa' onclick='mostrarIncidencia(" +
                    contenedor.Id_Contenedor +
                    ")'>" +
                    "Registrar incidencia" +
                    "</button>"
            );
        }
    });
}

async function registrarIncidencia() {
    let idContenedor = document.getElementById("idContenedor").value.trim();
    let tipoIncidencia = document.getElementById("tipoIncidencia").value;
    let foto = document.getElementById("foto").files[0];
    let mensaje = document.getElementById("mensajeIncidencia");
    let boton = document.getElementById("btnIncidencia");

    if (idContenedor === "") {
        mensaje.textContent = "No se seleccionó ningún contenedor.";
        return;
    }

    if (tipoIncidencia === "") {
        mensaje.textContent = "Seleccione el tipo de incidencia.";
        return;
    }

    if (!foto) {
        mensaje.textContent = "Seleccione una fotografía.";
        return;
    }

    let tiposPermitidos = ["image/jpeg", "image/png", "image/gif"];

    if (!tiposPermitidos.includes(foto.type)) {
        mensaje.textContent = "La imagen debe ser JPG, PNG o GIF.";
        return;
    }

    if (foto.size > 5 * 1024 * 1024) {
        mensaje.textContent = "La imagen no puede superar los 5 MB.";
        return;
    }

    boton.disabled = true;
    boton.textContent = "Registrando...";
    mensaje.textContent = "";

    try {
        const { ok, data } = await crearIncidencia(idContenedor, tipoIncidencia, foto);

        if (ok && !data.error) {
            mensaje.textContent = data.mensaje || "La incidencia fue registrada correctamente.";

            document.getElementById("tipoIncidencia").value = "";
            document.getElementById("foto").value = "";

            setTimeout(function () {
                cerrarModal();
            }, 1000);
        } else {
            mensaje.textContent = data.error || "No se pudo registrar la incidencia.";
        }
    } catch (error) {
        console.error("Error:", error);
        mensaje.textContent = "No se pudo conectar con el servidor.";
    } finally {
        boton.disabled = false;
        boton.textContent = "Registrar incidencia";
    }
}

// Se exponen al scope global porque el HTML (y los popups de Leaflet) las llaman con atributos onclick.
window.mostrarIncidencia = mostrarIncidencia;
window.cerrarModal = cerrarModal;
window.registrarIncidencia = registrarIncidencia;
window.cerrarSesion = () => cerrarSesion("index.html");

cargarZonas();
cargarContenedores();
verificarSesion("Vecino", "index.html");

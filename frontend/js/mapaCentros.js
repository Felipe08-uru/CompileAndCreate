import { ESTILO_ZONA } from "./zonaEstilo.js";
import { getPoligonos } from "./APIPoligonos.js";
import { getCentros, crearCentro, eliminarCentro } from "./APICentros.js";

let iconoCentro = L.icon({
    iconUrl: "img/centro.svg",
    iconSize: [38, 38],
    iconAnchor: [19, 19],
    popupAnchor: [0, -18]
});

let mapa = L.map("mapa").setView([-34.9055, -56.1905], 12);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors"
}).addTo(mapa);

let marcadorNuevo = null;
let capaCentros = L.layerGroup().addTo(mapa);

mapa.on("click", function (e) {
    if (marcadorNuevo) {
        mapa.removeLayer(marcadorNuevo);
    }

    marcadorNuevo = L.marker([e.latlng.lat, e.latlng.lng], { icon: iconoCentro, opacity: 0.7 }).addTo(mapa);

    document.getElementById("latitud").value = e.latlng.lat.toFixed(7);
    document.getElementById("longitud").value = e.latlng.lng.toFixed(7);
    document.getElementById("mensajeMapa").textContent = "";
});

function limpiarCentro() {
    if (marcadorNuevo) {
        mapa.removeLayer(marcadorNuevo);
        marcadorNuevo = null;
    }

    document.getElementById("latitud").value = "";
    document.getElementById("longitud").value = "";
    document.getElementById("servicioCentro").value = "";
    document.getElementById("capacidadCentro").value = "";
    document.getElementById("mensajeMapa").textContent = "";
}

async function guardarCentro() {
    const servicio = document.getElementById("servicioCentro").value.trim();
    const capacidad = document.getElementById("capacidadCentro").value;
    const latitud = document.getElementById("latitud").value;
    const longitud = document.getElementById("longitud").value;
    const mensaje = document.getElementById("mensajeMapa");

    if (servicio === "") {
        mensaje.textContent = "Ingrese el servicio que presta el centro.";
        return;
    }

    if (latitud === "" || longitud === "") {
        mensaje.textContent = "Debe seleccionar una ubicación en el mapa.";
        return;
    }

    const { data } = await crearCentro({
        servicio,
        capacidad,
        latitud: parseFloat(latitud),
        longitud: parseFloat(longitud)
    });

    if (data.error) {
        mensaje.textContent = data.error;
    } else {
        limpiarCentro();
        mensaje.textContent = "Centro de acopio " + data.id_centro + " guardado correctamente.";
        cargarCentros();
    }
}

async function cargarZonas() {
    const { data } = await getPoligonos();

    if (!Array.isArray(data)) return;

    data.forEach(function (zona) {
        const vertices = zona.vertices.map(function (v) {
            return [v.latitud, v.longitud];
        });

        if (vertices.length >= 3) {
            L.polygon(vertices, ESTILO_ZONA).addTo(mapa);
        }
    });
}

async function cargarCentros() {
    const { data } = await getCentros();

    if (!Array.isArray(data)) return;

    capaCentros.clearLayers();

    const puntos = [];

    data.forEach(function (centro) {
        if (centro.Latitud === null || centro.Longitud === null) return;

        const punto = [parseFloat(centro.Latitud), parseFloat(centro.Longitud)];
        puntos.push(punto);

        const popup = document.createElement("div");
        const titulo = document.createElement("strong");
        titulo.textContent = "Centro de acopio " + centro.ID;
        popup.appendChild(titulo);
        popup.appendChild(document.createElement("br"));
        popup.appendChild(document.createTextNode("Servicio: " + centro.Servicio));
        popup.appendChild(document.createElement("br"));
        popup.appendChild(document.createTextNode("Capacidad: " + centro.Capacidad));

        L.marker(punto, { icon: iconoCentro }).addTo(capaCentros).bindPopup(popup);
    });

    if (puntos.length > 0 && !cargarCentros.yaEncuadrado) {
        cargarCentros.yaEncuadrado = true;
        mapa.fitBounds(puntos, { padding: [40, 40], maxZoom: 14 });
    }
}

async function eliminarCentroUI() {
    const id = document.getElementById("idEliminar").value;

    if (id === "") {
        document.getElementById("mensajeEliminar").textContent = "Debe ingresar el ID del centro.";
        return;
    }

    const { data } = await eliminarCentro(parseInt(id));

    if (data.error) {
        document.getElementById("mensajeEliminar").textContent = data.error;
    } else {
        location.reload();
    }
}

window.limpiarCentro = limpiarCentro;
window.guardarCentro = guardarCentro;
window.eliminarCentroUI = eliminarCentroUI;

cargarZonas();
cargarCentros();

import { getPoligonos } from "./APIPoligonos.js";
import { getContenedores, crearContenedor, eliminarContenedor } from "./APIContenedores.js";

let iconoContenedor = L.icon({
    iconUrl: "img/contenedor.png",
    iconSize: [30, 30],
    iconAnchor: [15, 20]
});

let mapa = L.map("mapa").setView([-34.9055, -56.1905], 16);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors"
}).addTo(mapa);

let marcadorContenedor = null;

mapa.on("click", function (e) {
    if (marcadorContenedor) {
        mapa.removeLayer(marcadorContenedor);
    }

    marcadorContenedor = L.marker([e.latlng.lat, e.latlng.lng], { icon: iconoContenedor }).addTo(mapa);

    document.getElementById("latitud").value = e.latlng.lat.toFixed(7);
    document.getElementById("longitud").value = e.latlng.lng.toFixed(7);
    document.getElementById("mensajeMapa").textContent = "";
});

function limpiarContenedor() {
    if (marcadorContenedor) {
        mapa.removeLayer(marcadorContenedor);
        marcadorContenedor = null;
    }

    document.getElementById("latitud").value = "";
    document.getElementById("longitud").value = "";
    document.getElementById("tipoContenedor").value = "Residuos mezclados";
    document.getElementById("mensajeMapa").textContent = "";
}

async function guardarContenedor() {
    let latitud = document.getElementById("latitud").value;
    let longitud = document.getElementById("longitud").value;
    let tipo = document.getElementById("tipoContenedor").value;

    if (latitud === "" || longitud === "") {
        document.getElementById("mensajeMapa").textContent = "Debe seleccionar una ubicación en el mapa.";
        return;
    }

    const { data } = await crearContenedor({
        tipo,
        latitud: parseFloat(latitud),
        longitud: parseFloat(longitud)
    });

    if (data.error) {
        document.getElementById("mensajeMapa").textContent = data.error;
    } else {
        limpiarContenedor();
        document.getElementById("mensajeMapa").textContent =
            "Contenedor " + data.id_contenedor + " guardado correctamente.";
        cargarContenedores();
    }
}

async function cargarZonas() {
    const { data } = await getPoligonos();

    if (!Array.isArray(data)) return;

    data.forEach(function (zona) {
        let vertices = zona.vertices.map(function (v) {
            return [v.latitud, v.longitud];
        });

        if (vertices.length >= 3) {
            let poligonoZona = L.polygon(vertices).addTo(mapa);
            let centro = poligonoZona.getBounds().getCenter();

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
    const { data } = await getContenedores();

    if (!Array.isArray(data)) return;

    data.forEach(function (contenedor) {
        if (contenedor.Latitud !== null && contenedor.Longitud !== null) {
            L.marker(
                [parseFloat(contenedor.Latitud), parseFloat(contenedor.Longitud)],
                { icon: iconoContenedor }
            )
                .addTo(mapa)
                .bindPopup(
                    "<strong>Contenedor " +
                        contenedor.Id_Contenedor +
                        "</strong><br>" +
                        "Tipo: " +
                        contenedor.Tipo +
                        "<br>" +
                        "Estado: " +
                        contenedor.Estado
                );
        }
    });
}

async function eliminarContenedorUI() {
    let id = document.getElementById("idEliminar").value;

    if (id === "") {
        document.getElementById("mensajeEliminar").textContent = "Debe ingresar el ID del contenedor.";
        return;
    }

    const { data } = await eliminarContenedor(parseInt(id));

    if (data.error) {
        document.getElementById("mensajeEliminar").textContent = data.error;
    } else {
        location.reload();
    }
}

window.limpiarContenedor = limpiarContenedor;
window.guardarContenedor = guardarContenedor;
window.eliminarContenedorUI = eliminarContenedorUI;

cargarZonas();
cargarContenedores();

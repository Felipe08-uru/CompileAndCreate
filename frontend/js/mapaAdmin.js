import { getPoligonos, crearPoligono, eliminarPoligono } from "./APIPoligonos.js";

let mapa = L.map("mapa").setView([-34.9055, -56.1905], 16);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors"
}).addTo(mapa);

let puntos = [];
let marcadores = [];
let poligono = null;

mapa.on("click", function (e) {
    puntos.push([e.latlng.lat, e.latlng.lng]);

    let marcador = L.marker([e.latlng.lat, e.latlng.lng]).addTo(mapa);
    marcadores.push(marcador);

    if (poligono) {
        mapa.removeLayer(poligono);
    }

    if (puntos.length >= 3) {
        poligono = L.polygon(puntos).addTo(mapa);
    }
});

function limpiarZona() {
    puntos = [];

    marcadores.forEach(function (marcador) {
        mapa.removeLayer(marcador);
    });

    marcadores = [];

    if (poligono) {
        mapa.removeLayer(poligono);
        poligono = null;
    }

    document.getElementById("mensajeMapa").textContent = "";
}

async function guardarZona() {
    if (puntos.length < 3) {
        document.getElementById("mensajeMapa").textContent = "La zona debe tener al menos 3 puntos.";
        return;
    }

    let vertices = puntos.map(function (punto) {
        return { latitud: punto[0], longitud: punto[1] };
    });

    const { data } = await crearPoligono(vertices);

    if (data.error) {
        document.getElementById("mensajeMapa").textContent = data.error;
    } else {
        document.getElementById("mensajeMapa").textContent = "Zona guardada correctamente.";
        limpiarZona();
        cargarZonas();
    }
}

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

function mostrarModalEliminar() {
    document.getElementById("modalEliminar").style.display = "block";
    document.getElementById("overlayEliminar").style.display = "block";
    document.getElementById("idZonaEliminar").value = "";
    document.getElementById("mensajeEliminar").textContent = "";
}

function cerrarModalEliminar() {
    document.getElementById("modalEliminar").style.display = "none";
    document.getElementById("overlayEliminar").style.display = "none";
}

async function eliminarZona() {
    let id = document.getElementById("idZonaEliminar").value.trim();
    let mensaje = document.getElementById("mensajeEliminar");

    if (id === "") {
        mensaje.textContent = "Ingrese el ID de la zona.";
        return;
    }

    const { data } = await eliminarPoligono(id);

    if (data.error) {
        mensaje.textContent = data.error;
    } else {
        mensaje.textContent = "Zona eliminada correctamente.";

        setTimeout(function () {
            cerrarModalEliminar();
            location.reload();
        }, 800);
    }
}

// Se exponen al scope global porque el HTML las llama con atributos onclick.
window.limpiarZona = limpiarZona;
window.guardarZona = guardarZona;
window.mostrarModalEliminar = mostrarModalEliminar;
window.cerrarModalEliminar = cerrarModalEliminar;
window.eliminarZona = eliminarZona;

cargarZonas();

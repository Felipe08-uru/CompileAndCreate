import "./recargarAlVolver.js";
import { verificarSesion, cerrarSesion } from "./auth.js";
import { getDesbordados, getPromedio, getZonasLlenado, getLimpieza } from "./APIEstadisticas.js";
import { getPoligonos } from "./APIPoligonos.js";
import { activarFlechaScroll } from "./scrollFlecha.js";
import { ESTILO_ZONA } from "./zonaEstilo.js";
import {
    colorDe, NOMBRES_LLENADO, NOMBRES_LIMPIEZA,
    textoDias, textoPorcentaje, mesDe, anchoBarra
} from "./estadisticasUtil.js";

function esc(texto) {
    const div = document.createElement("div");
    div.textContent = texto === null || texto === undefined ? "" : String(texto);
    return div.innerHTML;
}

function formatearFecha(valor) {
    if (!valor) return "-";

    const fecha = new Date(String(valor).replace(" ", "T"));

    return isNaN(fecha.getTime()) ? valor : fecha.toLocaleString("es-UY", { dateStyle: "short", timeStyle: "short" });
}

function barra(valor, maximo, color) {
    return "<div class='barra-dato'><div style='width:" + anchoBarra(valor, maximo) +
        "%;background:" + color + "'></div></div>";
}

function tarjeta(titulo, valor, detalle) {
    return "<div class='tarjeta-dato'><div><span>" + esc(titulo) + "</span><strong>" + valor + "</strong>" +
        (detalle ? "<small>" + detalle + "</small>" : "") + "</div></div>";
}

function leyenda(contenedor, nombres) {
    contenedor.innerHTML = Object.keys(nombres).map(function (clave) {
        return "<span><i style='background:" + colorDe(clave) + "'></i>" + esc(nombres[clave]) + "</span>";
    }).join("");
}

// ---------------------------------------------------------------------
// Mapas
// ---------------------------------------------------------------------

function crearMapa(id) {
    const mapa = L.map(id).setView([-34.9055, -56.1905], 14);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors"
    }).addTo(mapa);

    return mapa;
}

const mapaZonas = crearMapa("mapaZonas");
const mapaLimpieza = crearMapa("mapaLimpieza");

let zonasGeometria = [];
let ajustado = false;

function verticesDe(zona) {
    return zona.vertices.map(function (v) {
        return [parseFloat(v.latitud), parseFloat(v.longitud)];
    });
}

function dibujarZonasLlenado(zonas) {
    const porId = {};
    zonas.forEach(function (z) { porId[z.Id_Poligono] = z; });

    const limites = [];

    zonasGeometria.forEach(function (zona) {
        const vertices = verticesDe(zona);

        if (vertices.length < 3) return;

        const dato = porId[zona.id_poligono] || { Categoria: "sin_datos", Promedio: null, Contenedores: 0 };
        const color = colorDe(dato.Categoria);

        const poligono = L.polygon(vertices, {
            color: ESTILO_ZONA.color,
            weight: ESTILO_ZONA.weight,
            fillColor: color,
            fillOpacity: 0.55
        }).addTo(mapaZonas);

        poligono.bindTooltip(
            "Zona " + zona.id_poligono + "<br>" + textoPorcentaje(dato.Promedio),
            { permanent: true, direction: "center", className: "etiqueta-zona" }
        );

        poligono.bindPopup(
            "<strong>Zona " + zona.id_poligono + "</strong><br>" +
            "Llenado promedio: " + textoPorcentaje(dato.Promedio) + "<br>" +
            "Contenedores con recolecciones: " + dato.Contenedores + "<br>" +
            esc(NOMBRES_LLENADO[dato.Categoria])
        );

        limites.push(poligono.getBounds());
    });

    return limites;
}

function iconoLimpieza(categoria) {
    return L.divIcon({
        className: "cl-icono",
        html: "<div class='cl' style='background:" + colorDe(categoria) + "'><img src='img/contenedor.png' alt=''></div>",
        iconSize: [38, 38],
        iconAnchor: [19, 19],
        popupAnchor: [0, -18]
    });
}

function dibujarLimpieza(contenedores) {
    zonasGeometria.forEach(function (zona) {
        const vertices = verticesDe(zona);

        if (vertices.length >= 3) {
            L.polygon(vertices, ESTILO_ZONA).addTo(mapaLimpieza);
        }
    });

    const puntos = [];

    contenedores.forEach(function (c) {
        if (!c.Latitud || !c.Longitud) return;

        L.marker([c.Latitud, c.Longitud], { icon: iconoLimpieza(c.Categoria) })
            .bindPopup(
                "<strong>Contenedor " + c.Id_Contenedor + "</strong><br>" +
                esc(c.Tipo) + "<br>" +
                "Última recolección: " + formatearFecha(c.Fecha_Ultima_Recoleccion) + "<br>" +
                "<strong>" + textoDias(c.Dias) + "</strong>"
            )
            .addTo(mapaLimpieza);

        puntos.push([c.Latitud, c.Longitud]);
    });

    return puntos;
}

function ajustarMapas(limitesZonas, puntos) {
    if (ajustado) return;

    if (limitesZonas.length > 0) {
        const todos = limitesZonas.reduce(function (a, b) { return a.extend(b); });
        mapaZonas.fitBounds(todos, { padding: [20, 20] });
        mapaLimpieza.fitBounds(todos, { padding: [20, 20] });
        ajustado = true;
    } else if (puntos.length > 0) {
        mapaLimpieza.fitBounds(puntos, { padding: [30, 30] });
        ajustado = true;
    }
}

// ---------------------------------------------------------------------
// Tablas y tarjetas
// ---------------------------------------------------------------------

function mostrarDesbordados(datos) {
    const tabla = document.getElementById("tablaDesbordados");
    const mensaje = document.getElementById("mensajeDesbordados");

    tabla.innerHTML = "";
    document.getElementById("notaDesborde").textContent =
        "Se considera desbordado un contenedor con " + datos.umbral + "% de llenado o más (" + datos.umbral_kg + " kg de " + datos.capacidad_kg + " kg).";

    if (datos.contenedores.length === 0) {
        mensaje.textContent = "No hay contenedores desbordados en este mes.";
        return;
    }

    mensaje.textContent = "";

    const maximo = datos.contenedores[0].Veces;

    datos.contenedores.forEach(function (c, i) {
        const fila = document.createElement("tr");

        fila.innerHTML =
            "<td>" + (i + 1) + "</td>" +
            "<td>Contenedor " + c.Id_Contenedor + "</td>" +
            "<td>" + (c.Id_Poligono !== null ? "Zona " + c.Id_Poligono : "Sin zona") + "</td>" +
            "<td class='celda-barra'><strong>" + c.Veces + "</strong>" + barra(c.Veces, maximo, colorDe("critico")) + "</td>" +
            "<td>" + textoPorcentaje(c.Nivel_Maximo) + "</td>" +
            "<td>" + formatearFecha(c.Ultima_Vez) + "</td>";

        tabla.appendChild(fila);
    });
}

function mostrarPromedio(datos) {
    const destacado = document.getElementById("destacadoPromedio");
    const tabla = document.getElementById("tablaPromedio");

    tabla.innerHTML = "";

    if (datos.contenedores.length === 0) {
        destacado.innerHTML = "<div class='aviso-vacio'>Todavía no hay recolecciones registradas en este período.</div>";
        return;
    }

    const primero = datos.contenedores[0];

    destacado.innerHTML =
        "<div class='destacado'>" +
        "<span>Contenedor con más basura en promedio</span>" +
        "<strong>Contenedor " + primero.Id_Contenedor + " · " + textoPorcentaje(primero.Promedio) + "</strong>" +
        "<small>" + (primero.Id_Poligono !== null ? "Zona " + primero.Id_Poligono : "Sin zona") +
        " · " + primero.Peso_Promedio + " kg por recolección · " + primero.Recolecciones + " recolecciones en los últimos " + datos.dias + " días</small></div>";

    datos.contenedores.forEach(function (c, i) {
        const fila = document.createElement("tr");

        fila.innerHTML =
            "<td>" + (i + 1) + "</td>" +
            "<td>Contenedor " + c.Id_Contenedor + "</td>" +
            "<td>" + (c.Id_Poligono !== null ? "Zona " + c.Id_Poligono : "Sin zona") + "</td>" +
            "<td class='celda-barra'><strong>" + textoPorcentaje(c.Promedio) + "</strong>" +
            barra(c.Promedio, 100, colorDe(c.Categoria)) + "</td>" +
            "<td>" + c.Recolecciones + "</td>" +
            "<td>" + c.Peso_Promedio + " kg</td>";

        tabla.appendChild(fila);
    });
}

function mostrarTablaLimpieza(contenedores) {
    const tabla = document.getElementById("tablaLimpieza");
    tabla.innerHTML = "";

    contenedores.forEach(function (c) {
        const fila = document.createElement("tr");

        fila.innerHTML =
            "<td>Contenedor " + c.Id_Contenedor + "</td>" +
            "<td>" + (c.Id_Poligono !== null ? "Zona " + c.Id_Poligono : "Sin zona") + "</td>" +
            "<td>" + formatearFecha(c.Fecha_Ultima_Recoleccion) + "</td>" +
            "<td><span class='punto-color' style='background:" + colorDe(c.Categoria) + "'></span>" +
            esc(textoDias(c.Dias)) + "</td>";

        tabla.appendChild(fila);
    });
}

function mostrarTarjetas(desbordados, promedio, limpieza) {
    const criticos = limpieza.contenedores.filter(function (c) {
        return c.Categoria === "critico" || c.Categoria === "nunca";
    }).length;

    const primero = promedio.contenedores[0];

    document.getElementById("tarjetasEstadisticas").innerHTML =
        tarjeta("Desbordes del mes", desbordados.total_desbordes, "recolecciones con " + desbordados.umbral + "% o más") +
        tarjeta("Contenedor más lleno", primero ? "N.º " + primero.Id_Contenedor : "-", primero ? textoPorcentaje(primero.Promedio) + " en promedio" : "") +
        tarjeta("Llenado promedio", textoPorcentaje(promedio.promedio_general), "de todos los contenedores") +
        tarjeta("Contenedores", limpieza.contenedores.length, "registrados") +
        tarjeta("Sin limpiar 14 días o más", criticos, "incluye los sin registro");
}

// ---------------------------------------------------------------------
// Carga
// ---------------------------------------------------------------------

async function cargarDesbordados() {
    const mes = document.getElementById("selectorMes").value;
    const { ok, data } = await getDesbordados(mes, 10);

    if (!ok) {
        document.getElementById("mensajeDesbordados").textContent = data.error || "No se pudieron cargar los datos.";
        return null;
    }

    mostrarDesbordados(data);

    return data;
}

async function cargarTodo() {
    document.getElementById("selectorMes").value = mesDe(new Date());

    const [desbordados, promedio, zonas, limpieza, poligonos] = await Promise.all([
        getDesbordados("", 10),
        getPromedio(30, 10),
        getZonasLlenado(30),
        getLimpieza(),
        getPoligonos()
    ]);

    if (!desbordados.ok || !promedio.ok || !zonas.ok || !limpieza.ok) {
        document.getElementById("tarjetasEstadisticas").innerHTML =
            "<p class='mensaje-error'>No se pudieron cargar las estadísticas. Verifique que haya iniciado sesión como administrador.</p>";
        return;
    }

    zonasGeometria = poligonos.ok ? poligonos.data : [];

    mostrarDesbordados(desbordados.data);
    mostrarPromedio(promedio.data);
    mostrarTarjetas(desbordados.data, promedio.data, limpieza.data);
    mostrarTablaLimpieza(limpieza.data.contenedores);

    leyenda(document.getElementById("leyendaZonas"), NOMBRES_LLENADO);
    leyenda(document.getElementById("leyendaLimpieza"), NOMBRES_LIMPIEZA);

    const limites = dibujarZonasLlenado(zonas.data.zonas);
    const puntos = dibujarLimpieza(limpieza.data.contenedores);

    ajustarMapas(limites, puntos);
}

document.getElementById("selectorMes").addEventListener("change", cargarDesbordados);

window.cerrarSesion = () => cerrarSesion("index.html");

verificarSesion("Administrador", "index.html");
cargarTodo();
activarFlechaScroll();

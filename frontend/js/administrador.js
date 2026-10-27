import { ESTILO_ZONA } from "./zonaEstilo.js";
import { verificarSesion, cerrarSesion } from "./auth.js";
import { getUsuarios, crearUsuario, eliminarUsuario } from "./APIUsuarios.js";
import { getCamiones, crearCamion, eliminarCamion } from "./APICamiones.js";
import { getContenedores, eliminarContenedor } from "./APIContenedores.js";
import { getIncidencias, eliminarIncidencia } from "./APIIncidencias.js";
import { getPoligonos } from "./APIPoligonos.js";
import { getCentros, crearCentro, eliminarCentro } from "./APICentros.js";
import { getAsignaciones, asignarCamionACuadrilla } from "./APIAsignaciones.js";
import { getRutas, crearRuta, asignarCamionARuta, eliminarRuta } from "./APIRutas.js";
import { activarFlechaScroll } from "./scrollFlecha.js";

const modal = document.getElementById("modal");
const overlay = document.getElementById("overlay");

const FORMULARIOS = ["camionForm", "usuarioForm", "centroForm", "cuadrillaCamionForm", "rutaForm", "rutaCamionForm"];

function abrirFormulario(idFormulario) {
    modal.style.display = "block";
    overlay.style.display = "block";

    FORMULARIOS.forEach(function (id) {
        document.getElementById(id).style.display = id === idFormulario ? "block" : "none";
    });
}

function mostrarCamion() {
    abrirFormulario("camionForm");
    document.getElementById("mensajeCamion").textContent = "";
}

function mostrarUsuario() {
    abrirFormulario("usuarioForm");
    document.getElementById("mensajeUsuario").textContent = "";
}

function mostrarCentro() {
    abrirFormulario("centroForm");
    document.getElementById("mensajeCentro").textContent = "";
}

function cerrarModal() {
    modal.style.display = "none";
    overlay.style.display = "none";

    FORMULARIOS.forEach(function (id) {
        document.getElementById(id).style.display = "none";
    });
}

const TABLAS = {
    usuarios: "tablaUsuariosContainer",
    camiones: "tablaCamionesContainer",
    contenedores: "tablaContenedoresContainer",
    incidencias: "tablaIncidenciasContainer",
    centros: "tablaCentrosContainer",
    asignaciones: "tablaAsignacionesContainer",
    rutas: "tablaRutasContainer"
};

function cambiarTabla() {
    const seleccion = document.getElementById("selectorTabla").value;

    Object.keys(TABLAS).forEach(function (clave) {
        const contenedor = document.getElementById(TABLAS[clave]);
        contenedor.classList.toggle("activa", clave === seleccion);
    });
}

function irATabla(clave) {
    const selector = document.getElementById("selectorTabla");
    selector.value = clave;
    cambiarTabla();
    selector.scrollIntoView({ behavior: "smooth", block: "start" });
}

function setResumen(id, valor) {
    const elemento = document.getElementById(id);
    if (elemento) elemento.textContent = valor;
}

function esc(texto) {
    const div = document.createElement("div");
    div.textContent = texto === null || texto === undefined ? "" : String(texto);
    return div.innerHTML;
}

function etiquetaEstado(estado) {
    const clase = "estado-" + String(estado).toLowerCase().replace(/\s+/g, "-");
    return "<span class='etiqueta-estado " + clase + "'>" + esc(estado) + "</span>";
}

function formatearFecha(valor) {
    if (!valor) return "-";

    const fecha = new Date(String(valor).replace(" ", "T"));

    if (isNaN(fecha.getTime())) return valor;

    return fecha.toLocaleString("es-UY", { dateStyle: "short", timeStyle: "short" });
}

// Datos que se reutilizan en el formulario de asignación.
let zonasCache = [];
let cuadrillasCache = [];
let asignacionesCache = [];
let rutasCache = [];

let mapa = L.map("mapa").setView([-34.9055, -56.1905], 16);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors"
}).addTo(mapa);

let iconoContenedor = L.icon({
    iconUrl: "img/contenedor.png",
    iconSize: [30, 30],
    iconAnchor: [15, 20]
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
        document.getElementById("mensajeMapa").textContent = "Error al cargar las zonas.";
        return;
    }

    zonasCache = data;

    data.forEach(function (zona) {
        let vertices = zona.vertices.map(function (v) {
            return [v.latitud, v.longitud];
        });

        if (vertices.length >= 3) {
            L.polygon(vertices, ESTILO_ZONA).addTo(mapa);

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
        console.error("Error al cargar los contenedores");
        return;
    }

    setResumen("resContenedores", data.length);

    let tabla = document.getElementById("tablaContenedores");
    tabla.innerHTML = "";

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
                        "Zona: " +
                        (contenedor.Id_Poligono !== null ? contenedor.Id_Poligono : "sin zona") +
                        "<br>" +
                        "Estado: " +
                        contenedor.Estado
                );
        }

        let fila = document.createElement("tr");

        fila.innerHTML =
            "<td>" + contenedor.Id_Contenedor + "</td>" +
            "<td>" + contenedor.Latitud + ", " + contenedor.Longitud + "</td>" +
            "<td>" + contenedor.Tipo + "</td>" +
            "<td>" + (contenedor.Id_Poligono !== null ? "Zona " + contenedor.Id_Poligono : "Sin zona") + "</td>" +
            "<td>" + contenedor.Estado + "</td>" +
            "<td class='acciones'>" +
            "<button class='btn-eliminar' onclick=\"eliminarContenedorUI('" + contenedor.Id_Contenedor + "')\">Eliminar</button>" +
            "</td>";

        tabla.appendChild(fila);
    });
}

async function cargarUsuarios() {
    const { ok, status, data } = await getUsuarios();

    if (!ok) {
        console.error(status === 403 ? "No tiene permisos" : "Sesión no válida");
        return;
    }

    setResumen("resUsuarios", data.length);

    cuadrillasCache = data.filter(function (usuario) {
        return usuario.rol === "Cuadrilla";
    });

    mostrarCuadrillasYCamiones();

    let tabla = document.getElementById("tablaUsuarios");
    tabla.innerHTML = "";

    data.forEach(function (usuario) {
        let fila = document.createElement("tr");

        fila.innerHTML =
            "<td>" + usuario.ci + "</td>" +
            "<td>" + usuario.nombre1 + " " + (usuario.nombre2 || "") + "</td>" +
            "<td>" + usuario.apellido1 + " " + (usuario.apellido2 || "") + "</td>" +
            "<td>" + usuario.correo_e + "</td>" +
            "<td>" + usuario.rol + "</td>" +
            "<td class='acciones'>" +
            "<button class='btn-eliminar' onclick=\"eliminarUsuarioUI('" + usuario.ci + "')\">Eliminar</button>" +
            "</td>";

        tabla.appendChild(fila);
    });
}

async function cargarCamiones() {
    const { ok, data } = await getCamiones();

    if (!ok) {
        console.error("Sesión no válida");
        return;
    }

    setResumen("resCamiones", data.length);

    let tabla = document.getElementById("tablaCamiones");
    tabla.innerHTML = "";

    data.forEach(function (camion) {
        let fila = document.createElement("tr");

        fila.innerHTML =
            "<td>" + camion.Matricula + "</td>" +
            "<td>" + camion.Tipo + "</td>" +
            "<td>" + camion.Estado + "</td>" +
            "<td class='acciones'>" +
            "<button class='btn-eliminar' onclick=\"eliminarCamionUI('" + camion.Matricula + "')\">Eliminar</button>" +
            "</td>";

        tabla.appendChild(fila);
    });
}

async function cargarIncidencias() {
    const { ok, data } = await getIncidencias();

    if (!ok) {
        console.error("Sesión no válida");
        return;
    }

    setResumen("resIncidencias", data.filter(function (i) { return i.estado === "Pendiente" || i.Estado === "Pendiente"; }).length);

    let tabla = document.getElementById("tablaIncidencias");
    tabla.innerHTML = "";

    data.forEach(function (incidencia) {
        let fila = document.createElement("tr");

        fila.innerHTML =
            "<td>" + incidencia.Id_Incidencia + "</td>" +
            "<td>" + incidencia.Id_Contenedor + "</td>" +
            "<td>" + incidencia.Tipo + "</td>" +
            "<td>" + incidencia.Estado + "</td>" +
            "<td>" + (incidencia.Foto || "Sin foto") + "</td>" +
            "<td class='acciones'>" +
            "<button class='btn-eliminar' onclick=\"eliminarIncidenciaUI('" + incidencia.Id_Incidencia + "')\">Eliminar</button>" +
            "</td>";

        tabla.appendChild(fila);
    });
}

async function cargarCentros() {
    const { ok, data } = await getCentros();

    if (!ok) {
        console.error("Sesión no válida");
        return;
    }

    let tabla = document.getElementById("tablaCentros");
    tabla.innerHTML = "";

    data.forEach(function (centro) {
        let fila = document.createElement("tr");

        fila.innerHTML =
            "<td>" + centro.ID + "</td>" +
            "<td>" + centro.Servicio + "</td>" +
            "<td>" + centro.Capacidad + "</td>" +
            "<td>" + centro.ContAlmacenados + "</td>" +
            "<td>" + centro.CamAlmacenados + "</td>" +
            "<td class='acciones'>" +
            "<button class='btn-eliminar' onclick=\"eliminarCentroUI('" + centro.ID + "')\">Eliminar</button>" +
            "</td>";

        tabla.appendChild(fila);
    });
}

async function manejarRegistroUsuario() {
    let ci = document.getElementById("ciUsuario").value.trim();
    let nombre1 = document.getElementById("nombre1Usuario").value.trim();
    let nombre2 = document.getElementById("nombre2Usuario").value.trim();
    let apellido1 = document.getElementById("apellido1Usuario").value.trim();
    let apellido2 = document.getElementById("apellido2Usuario").value.trim();
    let correo = document.getElementById("correoUsuario").value.trim();
    let contrasena = document.getElementById("contrasenaUsuario").value;
    let rol = document.getElementById("rolUsuario").value;
    let mensaje = document.getElementById("mensajeUsuario");

    if (ci === "" || nombre1 === "" || apellido1 === "" || correo === "" || contrasena === "") {
        mensaje.textContent = "Complete todos los campos obligatorios.";
        return;
    }

    try {
        const { ok, data } = await crearUsuario({
            ci,
            nombre1,
            nombre2: nombre2 !== "" ? nombre2 : null,
            apellido1,
            apellido2: apellido2 !== "" ? apellido2 : null,
            correo_e: correo,
            contrasena,
            rol
        });

        console.log("Respuesta del servidor:", data);

        if (ok && !data.error) {
            mensaje.textContent = data.success || "Usuario registrado correctamente.";

            document.getElementById("ciUsuario").value = "";
            document.getElementById("nombre1Usuario").value = "";
            document.getElementById("nombre2Usuario").value = "";
            document.getElementById("apellido1Usuario").value = "";
            document.getElementById("apellido2Usuario").value = "";
            document.getElementById("correoUsuario").value = "";
            document.getElementById("contrasenaUsuario").value = "";

            cargarUsuarios();

            setTimeout(function () {
                cerrarModal();
            }, 700);
        } else {
            mensaje.textContent = data.error || "No se pudo registrar el usuario.";
        }
    } catch (error) {
        console.error("Error:", error);
        mensaje.textContent = "No se pudo conectar con el servidor.";
    }
}

async function manejarRegistroCamion() {
    let matricula = document.getElementById("matricula").value.trim();
    let tipo = document.getElementById("tipoCamion").value;
    let estado = document.getElementById("estadoCamion").value;
    let mensaje = document.getElementById("mensajeCamion");

    if (matricula === "") {
        mensaje.textContent = "Ingrese una matrícula.";
        return;
    }

    try {
        const { ok, data } = await crearCamion({ matricula, tipo, estado });

        console.log("Respuesta del servidor:", data);

        if (ok && !data.error) {
            mensaje.textContent = data.mensaje || "Camión registrado correctamente.";
            document.getElementById("matricula").value = "";
            cargarCamiones();
            cargarAsignaciones();

            setTimeout(function () {
                cerrarModal();
            }, 700);
        } else {
            mensaje.textContent = data.error || "No se pudo registrar el camión.";
        }
    } catch (error) {
        console.error("Error:", error);
        mensaje.textContent = "No se pudo conectar con el servidor.";
    }
}

async function manejarRegistroCentro() {
    let servicio = document.getElementById("servicioCentro").value.trim();
    let capacidad = document.getElementById("capacidadCentro").value;
    let contAlmacenados = document.getElementById("contAlmacenadosCentro").value;
    let camAlmacenados = document.getElementById("camAlmacenadosCentro").value;
    let mensaje = document.getElementById("mensajeCentro");

    if (servicio === "") {
        mensaje.textContent = "Ingrese el servicio que presta el centro.";
        return;
    }

    try {
        const { ok, data } = await crearCentro({
            servicio,
            capacidad,
            cont_almacenados: contAlmacenados,
            cam_almacenados: camAlmacenados
        });

        console.log("Respuesta del servidor:", data);

        if (ok && !data.error) {
            mensaje.textContent = data.mensaje || "Centro registrado correctamente.";

            document.getElementById("servicioCentro").value = "";
            document.getElementById("capacidadCentro").value = "";
            document.getElementById("contAlmacenadosCentro").value = "";
            document.getElementById("camAlmacenadosCentro").value = "";

            cargarCentros();

            setTimeout(function () {
                cerrarModal();
            }, 700);
        } else {
            mensaje.textContent = data.error || "No se pudo registrar el centro.";
        }
    } catch (error) {
        console.error("Error:", error);
        mensaje.textContent = "No se pudo conectar con el servidor.";
    }
}

async function eliminarUsuarioUI(ci) {
    if (!confirm("¿Está seguro de que desea eliminar este usuario?")) return;

    const { data } = await eliminarUsuario(ci);

    if (data.error) {
        alert(data.error);
    } else {
        cargarUsuarios();
    }
}

async function eliminarCamionUI(matricula) {
    if (!confirm("¿Está seguro de que desea eliminar este camión?")) return;

    const { data } = await eliminarCamion(matricula);

    if (data.error) {
        alert(data.error);
    } else {
        cargarCamiones();
        cargarAsignaciones();
    }
}

async function eliminarContenedorUI(id) {
    if (!confirm("¿Está seguro de que desea eliminar este contenedor?")) return;

    const { data } = await eliminarContenedor(id);

    if (data.error) {
        alert(data.error);
    } else {
        cargarContenedores();
    }
}

async function eliminarIncidenciaUI(id) {
    if (!confirm("¿Está seguro de que desea eliminar esta incidencia?")) return;

    const { data } = await eliminarIncidencia(id);

    if (data.error) {
        alert(data.error);
    } else {
        cargarIncidencias();
    }
}

async function eliminarCentroUI(id) {
    if (!confirm("¿Está seguro de que desea eliminar este centro?")) return;

    const { data } = await eliminarCentro(id);

    if (data.error) {
        alert(data.error);
    } else {
        cargarCentros();
    }
}

async function cargarAsignaciones() {
    const { ok, data } = await getAsignaciones();

    if (!ok) {
        console.error("No se pudieron cargar las asignaciones");
        return;
    }

    asignacionesCache = data;
    mostrarCuadrillasYCamiones();
}

// Tabla "Cuadrillas y camiones": una fila por cuadrilla con el camión que usa.
function mostrarCuadrillasYCamiones() {
    const tabla = document.getElementById("tablaAsignaciones");
    tabla.innerHTML = "";

    document.getElementById("mensajeAsignaciones").textContent =
        cuadrillasCache.length === 0 ? "No hay cuadrillas registradas." : "";

    cuadrillasCache.forEach(function (cuadrilla) {
        const asignacion = asignacionesCache.find(function (a) {
            return a.CI_Cuadrilla === cuadrilla.ci;
        });

        let rutaActual = "Sin ruta";
        let acciones =
            "<button class='btn-editar' onclick=\"mostrarCuadrillaCamion('" + esc(cuadrilla.ci) + "')\">Asignar camión</button>";

        if (asignacion && asignacion.Id_Ruta_Activa !== null) {
            rutaActual =
                "#" + asignacion.Id_Ruta_Activa + " " + etiquetaEstado(asignacion.Estado_Ruta) +
                "<br>" + asignacion.Paradas_Hechas + " de " + asignacion.Paradas_Total + " contenedores";

            acciones +=
                "<button class='btn-editar' onclick=\"verRutaUI(" + asignacion.Id_Ruta_Activa + ")\">Ver ruta</button>";
        }

        if (asignacion) {
            acciones +=
                "<button class='btn-eliminar' onclick=\"quitarCamionUI('" + esc(cuadrilla.ci) + "')\">Quitar camión</button>";
        }

        const fila = document.createElement("tr");

        fila.innerHTML =
            "<td>" + esc(cuadrilla.nombre1 + " " + cuadrilla.apellido1) + " (" + esc(cuadrilla.ci) + ")</td>" +
            "<td>" + (asignacion ? esc(asignacion.Matricula) : "Sin camión") + "</td>" +
            "<td>" + (asignacion ? esc(asignacion.Tipo) : "-") + "</td>" +
            "<td>" + rutaActual + "</td>" +
            "<td class='acciones'>" + acciones + "</td>";

        tabla.appendChild(fila);
    });
}

async function cargarRutas() {
    const { ok, data } = await getRutas();

    if (!ok) {
        console.error("No se pudieron cargar las rutas");
        return;
    }

    setResumen("resRutas", data.filter(function (r) { return r.Estado !== "Finalizada"; }).length);

    rutasCache = data;

    const tabla = document.getElementById("tablaRutas");
    tabla.innerHTML = "";

    data.forEach(function (ruta) {
        const fila = document.createElement("tr");

        let acciones = "<button class='btn-editar' onclick=\"verRutaUI(" + ruta.Id_Ruta + ")\">Ver</button>";

        if (ruta.Estado === "Pendiente") {
            acciones +=
                "<button class='btn-editar' onclick=\"mostrarRutaCamion(" + ruta.Id_Ruta + ")\">" +
                (ruta.Matricula ? "Cambiar camión" : "Asignar camión") + "</button>";
        }

        acciones +=
            "<button class='btn-eliminar' onclick=\"eliminarRutaUI(" + ruta.Id_Ruta + ")\">Eliminar</button>";

        fila.innerHTML =
            "<td>" + ruta.Id_Ruta + "</td>" +
            "<td>Zona " + ruta.Id_Poligono + "</td>" +
            "<td>" + esc(ruta.Tipo) + "</td>" +
            "<td>" + (ruta.Matricula ? esc(ruta.Matricula) : "<em>Sin camión</em>") + "</td>" +
            "<td>" + (ruta.Nombre_Cuadrilla ? esc(ruta.Nombre_Cuadrilla) : "-") + "</td>" +
            "<td>" + etiquetaEstado(ruta.Estado) + "</td>" +
            "<td>" + ruta.Paradas_Hechas + " de " + ruta.Paradas_Total + "</td>" +
            "<td>" + ruta.Peso_Total + "</td>" +
            "<td>" + formatearFecha(ruta.Fecha_Creacion) + "</td>" +
            "<td class='acciones'>" + acciones + "</td>";

        tabla.appendChild(fila);
    });
}

function llenarSelect(select, opciones, valorSeleccionado) {
    select.innerHTML = "";

    opciones.forEach(function (opcion) {
        const elemento = document.createElement("option");
        elemento.value = opcion.valor;
        elemento.textContent = opcion.texto;
        select.appendChild(elemento);
    });

    if (valorSeleccionado !== undefined && valorSeleccionado !== null) {
        select.value = String(valorSeleccionado);
    }
}

function textoCamion(asignacion) {
    let texto = asignacion.Matricula + " (" + asignacion.Tipo + ")";

    if (asignacion.Nombre_Cuadrilla) {
        texto += " - lo usa " + asignacion.Nombre_Cuadrilla;
    }

    return texto;
}

// ---------------------------------------------------------------------
// Asignar camión a una cuadrilla (solo administrador)
// ---------------------------------------------------------------------

function actualizarCamionActual() {
    const ci = document.getElementById("ccCuadrilla").value;
    const actual = asignacionesCache.find(function (a) { return a.CI_Cuadrilla === ci; });

    document.getElementById("ccCamion").value = actual ? actual.Matricula : "";
}

function mostrarCuadrillaCamion(ci) {
    if (cuadrillasCache.length === 0) {
        alert("Primero debe registrar al menos una cuadrilla.");
        return;
    }

    abrirFormulario("cuadrillaCamionForm");
    document.getElementById("mensajeCuadrillaCamion").textContent = "";

    llenarSelect(
        document.getElementById("ccCuadrilla"),
        cuadrillasCache.map(function (usuario) {
            return {
                valor: usuario.ci,
                texto: usuario.nombre1 + " " + usuario.apellido1 + " (" + usuario.ci + ")"
            };
        }),
        ci
    );

    llenarSelect(
        document.getElementById("ccCamion"),
        [{ valor: "", texto: "Sin camión" }].concat(
            asignacionesCache.map(function (a) {
                return { valor: a.Matricula, texto: textoCamion(a) };
            })
        )
    );

    actualizarCamionActual();
}

async function guardarCuadrillaCamionUI() {
    const mensaje = document.getElementById("mensajeCuadrillaCamion");

    const ci = document.getElementById("ccCuadrilla").value;
    const matricula = document.getElementById("ccCamion").value;

    if (ci === "") {
        mensaje.textContent = "Seleccione una cuadrilla.";
        return;
    }

    const { ok, data } = await asignarCamionACuadrilla(ci, matricula !== "" ? matricula : null);

    if (ok && !data.error) {
        mensaje.textContent = data.mensaje || "Camión asignado correctamente.";
        await cargarAsignaciones();
        cargarRutas();

        setTimeout(function () {
            cerrarModal();
        }, 700);
    } else {
        mensaje.textContent = data.error || "No se pudo asignar el camión.";
    }
}

async function quitarCamionUI(ci) {
    if (!confirm("¿Quitarle el camión a esta cuadrilla?")) return;

    const { data } = await asignarCamionACuadrilla(ci, null);

    if (data.error) {
        alert(data.error);
    } else {
        await cargarAsignaciones();
        cargarRutas();
    }
}

// ---------------------------------------------------------------------
// Crear ruta y asignarle camión (solo administrador)
// ---------------------------------------------------------------------

// Un camión puede hacer rutas de su tipo o de incidencias.
function camionesParaTipo(tipoRuta) {
    return asignacionesCache.filter(function (a) {
        return tipoRuta === "Incidencias" || a.Tipo === tipoRuta;
    });
}

function actualizarCamionesRuta() {
    const tipo = document.getElementById("rutaTipo").value;

    llenarSelect(
        document.getElementById("rutaCamion"),
        [{ valor: "", texto: "Sin camión por ahora" }].concat(
            camionesParaTipo(tipo).map(function (a) {
                return { valor: a.Matricula, texto: textoCamion(a) };
            })
        )
    );
}

function mostrarRuta() {
    if (zonasCache.length === 0) {
        alert("Primero debe registrar al menos una zona.");
        return;
    }

    abrirFormulario("rutaForm");
    document.getElementById("mensajeRuta").textContent = "";

    llenarSelect(
        document.getElementById("rutaZona"),
        zonasCache.map(function (zona) {
            return {
                valor: zona.id_poligono,
                texto: "Zona " + zona.id_poligono + " (" + zona.total_contenedores + " contenedores)"
            };
        })
    );

    actualizarCamionesRuta();
}

async function crearRutaUI() {
    const mensaje = document.getElementById("mensajeRuta");

    const zona = document.getElementById("rutaZona").value;
    const tipo = document.getElementById("rutaTipo").value;
    const matricula = document.getElementById("rutaCamion").value;

    if (zona === "") {
        mensaje.textContent = "Seleccione una zona.";
        return;
    }

    mensaje.textContent = "Creando ruta...";

    const { ok, data } = await crearRuta(zona, tipo, matricula !== "" ? matricula : null);

    if (ok && !data.error) {
        mensaje.textContent =
            "Ruta creada con " + data.total_paradas + " contenedores " +
            "(recorrido estimado en línea recta: " + (data.distancia_estimada_m / 1000).toFixed(2) + " km).";

        await cargarRutas();
        cargarAsignaciones();

        setTimeout(function () {
            cerrarModal();
        }, 1200);
    } else {
        mensaje.textContent = data.error || "No se pudo crear la ruta.";
    }
}

let rutaEnEdicion = null;

function mostrarRutaCamion(idRuta) {
    const ruta = rutasCache.find(function (r) { return r.Id_Ruta === idRuta; });

    if (!ruta) return;

    rutaEnEdicion = ruta;

    abrirFormulario("rutaCamionForm");
    document.getElementById("mensajeRutaCamion").textContent = "";

    document.getElementById("rutaCamionDetalle").textContent =
        "Ruta #" + ruta.Id_Ruta + " · Zona " + ruta.Id_Poligono + " · " + ruta.Tipo;

    llenarSelect(
        document.getElementById("rcCamion"),
        [{ valor: "", texto: "Sin camión" }].concat(
            camionesParaTipo(ruta.Tipo).map(function (a) {
                return { valor: a.Matricula, texto: textoCamion(a) };
            })
        ),
        ruta.Matricula ? ruta.Matricula : ""
    );
}

async function guardarRutaCamionUI() {
    const mensaje = document.getElementById("mensajeRutaCamion");

    if (rutaEnEdicion === null) return;

    const matricula = document.getElementById("rcCamion").value;

    const { ok, data } = await asignarCamionARuta(rutaEnEdicion.Id_Ruta, matricula !== "" ? matricula : null);

    if (ok && !data.error) {
        mensaje.textContent = data.mensaje || "Camión asignado correctamente.";
        await cargarRutas();
        cargarAsignaciones();

        setTimeout(function () {
            cerrarModal();
        }, 700);
    } else {
        mensaje.textContent = data.error || "No se pudo asignar el camión.";
    }
}

function verRutaUI(idRuta) {
    window.location.href = "mapaRuta.html?id=" + idRuta;
}

async function eliminarRutaUI(idRuta) {
    if (!confirm("¿Está seguro de que desea eliminar esta ruta? Se perderán los pesos registrados.")) return;

    const { data } = await eliminarRuta(idRuta);

    if (data.error) {
        alert(data.error);
    } else {
        cargarRutas();
        cargarAsignaciones();
    }
}

window.mostrarCamion = mostrarCamion;
window.mostrarUsuario = mostrarUsuario;
window.mostrarCentro = mostrarCentro;
window.mostrarCuadrillaCamion = mostrarCuadrillaCamion;
window.actualizarCamionActual = actualizarCamionActual;
window.guardarCuadrillaCamionUI = guardarCuadrillaCamionUI;
window.quitarCamionUI = quitarCamionUI;
window.mostrarRuta = mostrarRuta;
window.actualizarCamionesRuta = actualizarCamionesRuta;
window.crearRutaUI = crearRutaUI;
window.mostrarRutaCamion = mostrarRutaCamion;
window.guardarRutaCamionUI = guardarRutaCamionUI;
window.verRutaUI = verRutaUI;
window.eliminarRutaUI = eliminarRutaUI;
window.irATabla = irATabla;
window.cerrarModal = cerrarModal;
window.cambiarTabla = cambiarTabla;
window.registrarUsuario = manejarRegistroUsuario;
window.registrarCamion = manejarRegistroCamion;
window.registrarCentro = manejarRegistroCentro;
window.eliminarUsuarioUI = eliminarUsuarioUI;
window.eliminarCamionUI = eliminarCamionUI;
window.eliminarContenedorUI = eliminarContenedorUI;
window.eliminarIncidenciaUI = eliminarIncidenciaUI;
window.eliminarCentroUI = eliminarCentroUI;
window.cerrarSesion = () => cerrarSesion("index.html");

verificarSesion("Administrador", "index.html");
cargarZonas();
cargarContenedores();
cargarUsuarios();
cargarCamiones();
cargarIncidencias();
cargarCentros();
cargarAsignaciones();
cargarRutas();
cambiarTabla();

activarFlechaScroll();

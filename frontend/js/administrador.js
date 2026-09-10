import { verificarSesion, cerrarSesion } from "./auth.js";
import { getUsuarios, crearUsuario, eliminarUsuario } from "./APIUsuarios.js";
import { getCamiones, crearCamion, eliminarCamion } from "./APICamiones.js";
import { getContenedores, eliminarContenedor } from "./APIContenedores.js";
import { getIncidencias, eliminarIncidencia } from "./APIIncidencias.js";
import { getPoligonos } from "./APIPoligonos.js";

const modal = document.getElementById("modal");
const overlay = document.getElementById("overlay");

function mostrarCamion() {
    modal.style.display = "block";
    overlay.style.display = "block";
    document.getElementById("camionForm").style.display = "block";
    document.getElementById("usuarioForm").style.display = "none";
    document.getElementById("mensajeCamion").textContent = "";
}

function mostrarUsuario() {
    modal.style.display = "block";
    overlay.style.display = "block";
    document.getElementById("camionForm").style.display = "none";
    document.getElementById("usuarioForm").style.display = "block";
    document.getElementById("mensajeUsuario").textContent = "";
}

function cerrarModal() {
    modal.style.display = "none";
    overlay.style.display = "none";
    document.getElementById("camionForm").style.display = "none";
    document.getElementById("usuarioForm").style.display = "none";
}

function cambiarTabla() {
    let seleccion = document.getElementById("selectorTabla").value;
    let usuarios = document.getElementById("tablaUsuariosContainer");
    let camiones = document.getElementById("tablaCamionesContainer");
    let contenedores = document.getElementById("tablaContenedoresContainer");
    let incidencias = document.getElementById("tablaIncidenciasContainer");

    usuarios.classList.remove("activa");
    camiones.classList.remove("activa");
    contenedores.classList.remove("activa");
    incidencias.classList.remove("activa");

    if (seleccion === "usuarios") usuarios.classList.add("activa");
    if (seleccion === "camiones") camiones.classList.add("activa");
    if (seleccion === "contenedores") contenedores.classList.add("activa");
    if (seleccion === "incidencias") incidencias.classList.add("activa");
}

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

    data.forEach(function (zona) {
        let vertices = zona.vertices.map(function (v) {
            return [v.latitud, v.longitud];
        });

        if (vertices.length >= 3) {
            L.polygon(vertices).addTo(mapa);

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
                        "Estado: " +
                        contenedor.Estado
                );
        }

        let fila = document.createElement("tr");

        fila.innerHTML =
            "<td>" + contenedor.Id_Contenedor + "</td>" +
            "<td>" + contenedor.Latitud + ", " + contenedor.Longitud + "</td>" +
            "<td>" + contenedor.Tipo + "</td>" +
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

    let tabla = document.getElementById("tablaCamiones");
    tabla.innerHTML = "";

    data.forEach(function (camion) {
        let fila = document.createElement("tr");

        fila.innerHTML =
            "<td>" + camion.matricula + "</td>" +
            "<td>" + camion.tipo + "</td>" +
            "<td>" + camion.estado + "</td>" +
            "<td class='acciones'>" +
            "<button class='btn-eliminar' onclick=\"eliminarCamionUI('" + camion.matricula + "')\">Eliminar</button>" +
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

// Se exponen al scope global porque el HTML las llama con atributos onclick.
window.mostrarCamion = mostrarCamion;
window.mostrarUsuario = mostrarUsuario;
window.cerrarModal = cerrarModal;
window.cambiarTabla = cambiarTabla;
window.registrarUsuario = manejarRegistroUsuario;
window.registrarCamion = manejarRegistroCamion;
window.eliminarUsuarioUI = eliminarUsuarioUI;
window.eliminarCamionUI = eliminarCamionUI;
window.eliminarContenedorUI = eliminarContenedorUI;
window.eliminarIncidenciaUI = eliminarIncidenciaUI;
window.cerrarSesion = () => cerrarSesion("index.html");

verificarSesion("Administrador", "index.html");
cargarZonas();
cargarContenedores();
cargarUsuarios();
cargarCamiones();
cargarIncidencias();
cambiarTabla();

const flechaScroll = document.getElementById("flechaScroll");

flechaScroll.addEventListener("click", function () {
    window.scrollBy({ top: window.innerHeight, behavior: "smooth" });
});

function actualizarFlecha() {
    const llegoAlFinal =
        window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 10;

    flechaScroll.style.display = llegoAlFinal ? "none" : "flex";
}

window.addEventListener("scroll", actualizarFlecha);
actualizarFlecha();

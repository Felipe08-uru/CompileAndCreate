import { verificarSesion, cerrarSesion } from "./auth.js";
import { getCamiones } from "./APICamiones.js";
import { getCentros } from "./APICentros.js";
import { getMaquinaria, crearMaquinaria, actualizarMaquinaria, eliminarMaquinaria } from "./APIMaquinaria.js";
import { getRegistros, crearRegistro, actualizarRegistro, eliminarRegistro } from "./APIRegistroCamion.js";
import { activarFlechaScroll } from "./scrollFlecha.js";

const modal = document.getElementById("modal");
const overlay = document.getElementById("overlay");

let centros = [];

function escaparParaAtributo(texto) {
    return String(texto).replace(/\\/g, "\\\\").replace(/'/g, "\\'");
}

function mostrarCamion() {
    modal.style.display = "block";
    overlay.style.display = "block";
    document.getElementById("camionForm").style.display = "block";
    document.getElementById("maquinariaForm").style.display = "none";

    document.getElementById("tituloCamionForm").textContent = "Registrar llegada de camión";
    document.getElementById("btnGuardarRegistro").textContent = "Registrar llegada";
    document.getElementById("idRegistro").value = "";
    document.getElementById("tipoCargaCamion").value = "Orgánico";
    document.getElementById("cantidadCargaCamion").value = "";
    document.getElementById("mensajeCamion").textContent = "";
    document.getElementById("matriculaCamion").disabled = false;
    document.getElementById("centroCamion").disabled = false;
}

function mostrarMaquinaria() {
    modal.style.display = "block";
    overlay.style.display = "block";
    document.getElementById("camionForm").style.display = "none";
    document.getElementById("maquinariaForm").style.display = "block";

    document.getElementById("tituloMaquinariaForm").textContent = "Agregar maquinaria";
    document.getElementById("btnGuardarMaquinaria").textContent = "Agregar maquinaria";
    document.getElementById("idMaquinaria").value = "";
    document.getElementById("nombreMaquinaria").value = "";
    document.getElementById("cantidadMaquinaria").value = "1";
    document.getElementById("estadoMaquinaria").value = "Operativa";
    document.getElementById("mensajeMaquinaria").textContent = "";
    document.getElementById("centroMaquinaria").disabled = false;
}

function cerrarModal() {
    modal.style.display = "none";
    overlay.style.display = "none";
    document.getElementById("camionForm").style.display = "none";
    document.getElementById("maquinariaForm").style.display = "none";
}

async function cargarCentros() {
    const { ok, data } = await getCentros();

    if (!ok || !Array.isArray(data)) {
        return;
    }

    centros = data;

    let selectCamion = document.getElementById("centroCamion");
    let selectMaquinaria = document.getElementById("centroMaquinaria");

    selectCamion.innerHTML = "";
    selectMaquinaria.innerHTML = "";

    centros.forEach(function (centro) {
        let etiqueta = "Centro " + centro.ID + " - " + centro.Servicio;

        let opcionCamion = document.createElement("option");
        opcionCamion.value = centro.ID;
        opcionCamion.textContent = etiqueta;
        selectCamion.appendChild(opcionCamion);

        let opcionMaquinaria = document.createElement("option");
        opcionMaquinaria.value = centro.ID;
        opcionMaquinaria.textContent = etiqueta;
        selectMaquinaria.appendChild(opcionMaquinaria);
    });
}

async function cargarCamionesDisponibles() {
    const { ok, data } = await getCamiones();

    let select = document.getElementById("matriculaCamion");
    select.innerHTML = "";

    if (!ok || !Array.isArray(data) || data.length === 0) {
        let opcion = document.createElement("option");
        opcion.value = "";
        opcion.textContent = "No hay camiones registrados";
        select.appendChild(opcion);
        return;
    }

    data.forEach(function (camion) {
        let opcion = document.createElement("option");
        opcion.value = camion.Matricula;
        opcion.textContent = camion.Matricula + " (" + camion.Tipo + ")";
        select.appendChild(opcion);
    });
}

function formatearFecha(fecha) {
    if (!fecha) return "";
    let f = new Date(fecha.replace(" ", "T"));
    if (isNaN(f.getTime())) return fecha;
    return f.toLocaleString("es-UY", { dateStyle: "short", timeStyle: "short" });
}

async function cargarRegistros() {
    const { ok, data } = await getRegistros();

    if (!ok || !Array.isArray(data)) {
        document.getElementById("mensajeRegistros").textContent = "No se pudieron cargar los registros.";
        return;
    }

    let tabla = document.getElementById("tablaRegistros");
    tabla.innerHTML = "";

    if (data.length === 0) {
        document.getElementById("mensajeRegistros").textContent = "Todavía no hay camiones registrados.";
    } else {
        document.getElementById("mensajeRegistros").textContent = "";
    }

    data.forEach(function (registro) {
        let fila = document.createElement("tr");

        fila.innerHTML =
            "<td>" + registro.Id_Registro + "</td>" +
            "<td>" + formatearFecha(registro.Fecha) + "</td>" +
            "<td>" + registro.Matricula + "</td>" +
            "<td>" + (registro.Tipo_Camion || "-") + "</td>" +
            "<td>" + (registro.Servicio_Centro || registro.ID) + "</td>" +
            "<td>" + registro.Tipo_Carga + "</td>" +
            "<td>" + (registro.Cantidad_Carga !== null ? registro.Cantidad_Carga : "-") + "</td>" +
            "<td class='acciones'>" +
            "<button class='btn-eliminar' onclick=\"editarRegistroUI(" +
                registro.Id_Registro + ",'" + escaparParaAtributo(registro.Tipo_Carga) + "'," +
                (registro.Cantidad_Carga !== null ? registro.Cantidad_Carga : "null") +
            ")\">Editar</button>" +
            "<button class='btn-eliminar' onclick=\"eliminarRegistroUI(" + registro.Id_Registro + ")\">Eliminar</button>" +
            "</td>";

        tabla.appendChild(fila);
    });
}

async function cargarMaquinaria() {
    const { ok, data } = await getMaquinaria();

    if (!ok || !Array.isArray(data)) {
        document.getElementById("mensajeMaquinariaTabla").textContent = "No se pudo cargar la maquinaria.";
        return;
    }

    let tabla = document.getElementById("tablaMaquinaria");
    tabla.innerHTML = "";

    if (data.length === 0) {
        document.getElementById("mensajeMaquinariaTabla").textContent = "Todavía no hay maquinaria registrada.";
    } else {
        document.getElementById("mensajeMaquinariaTabla").textContent = "";
    }

    data.forEach(function (maquina) {
        let fila = document.createElement("tr");

        fila.innerHTML =
            "<td>" + maquina.Id_Maquinaria + "</td>" +
            "<td>" + maquina.ID + "</td>" +
            "<td>" + maquina.Nombre + "</td>" +
            "<td>" + maquina.Cantidad + "</td>" +
            "<td>" + maquina.Estado + "</td>" +
            "<td class='acciones'>" +
            "<button class='btn-eliminar' onclick=\"editarMaquinariaUI(" +
                maquina.Id_Maquinaria + "," + maquina.ID + ",'" +
                escaparParaAtributo(maquina.Nombre) + "'," + maquina.Cantidad + ",'" + maquina.Estado +
            "')\">Editar</button>" +
            "<button class='btn-eliminar' onclick=\"eliminarMaquinariaUI(" + maquina.Id_Maquinaria + ")\">Eliminar</button>" +
            "</td>";

        tabla.appendChild(fila);
    });
}

async function guardarRegistro() {
    let idRegistro = document.getElementById("idRegistro").value;
    let mensaje = document.getElementById("mensajeCamion");

    let tipoCarga = document.getElementById("tipoCargaCamion").value;
    let cantidadCarga = document.getElementById("cantidadCargaCamion").value;

    if (idRegistro !== "") {
        const { data } = await actualizarRegistro({
            id_registro: idRegistro,
            tipo_carga: tipoCarga,
            cantidad_carga: cantidadCarga
        });

        if (data.error) {
            mensaje.textContent = data.error;
        } else {
            mensaje.textContent = data.mensaje || "Registro actualizado correctamente.";
            cargarRegistros();
            setTimeout(cerrarModal, 700);
        }

        return;
    }

    let idCentro = document.getElementById("centroCamion").value;
    let matricula = document.getElementById("matriculaCamion").value;

    if (matricula === "") {
        mensaje.textContent = "No hay camiones disponibles para registrar. Pídale a un administrador que registre uno.";
        return;
    }

    const { data } = await crearRegistro({
        id_centro: idCentro,
        matricula,
        tipo_carga: tipoCarga,
        cantidad_carga: cantidadCarga
    });

    if (data.error) {
        mensaje.textContent = data.error;
    } else {
        mensaje.textContent = data.mensaje || "Llegada registrada correctamente.";
        cargarRegistros();
        setTimeout(cerrarModal, 700);
    }
}

function editarRegistroUI(idRegistro, tipoCarga, cantidadCarga) {
    mostrarCamion();

    document.getElementById("tituloCamionForm").textContent = "Editar registro de camión";
    document.getElementById("btnGuardarRegistro").textContent = "Guardar cambios";
    document.getElementById("idRegistro").value = idRegistro;
    document.getElementById("tipoCargaCamion").value = tipoCarga;
    document.getElementById("cantidadCargaCamion").value = cantidadCarga !== null ? cantidadCarga : "";

    document.getElementById("matriculaCamion").disabled = true;
    document.getElementById("centroCamion").disabled = true;
}

async function eliminarRegistroUI(idRegistro) {
    if (!confirm("¿Está seguro de que desea eliminar este registro?")) return;

    const { data } = await eliminarRegistro(idRegistro);

    if (data.error) {
        alert(data.error);
    } else {
        cargarRegistros();
    }
}

async function guardarMaquinaria() {
    let idMaquinaria = document.getElementById("idMaquinaria").value;
    let mensaje = document.getElementById("mensajeMaquinaria");

    let idCentro = document.getElementById("centroMaquinaria").value;
    let nombre = document.getElementById("nombreMaquinaria").value.trim();
    let cantidad = document.getElementById("cantidadMaquinaria").value;
    let estado = document.getElementById("estadoMaquinaria").value;

    if (nombre === "") {
        mensaje.textContent = "Ingrese el nombre de la máquina.";
        return;
    }

    if (idMaquinaria !== "") {
        const { data } = await actualizarMaquinaria({
            id_maquinaria: idMaquinaria,
            nombre,
            cantidad,
            estado
        });

        if (data.error) {
            mensaje.textContent = data.error;
        } else {
            mensaje.textContent = data.mensaje || "Máquina actualizada correctamente.";
            cargarMaquinaria();
            setTimeout(cerrarModal, 700);
        }

        return;
    }

    const { data } = await crearMaquinaria({
        id_centro: idCentro,
        nombre,
        cantidad,
        estado
    });

    if (data.error) {
        mensaje.textContent = data.error;
    } else {
        mensaje.textContent = data.mensaje || "Máquina agregada correctamente.";
        cargarMaquinaria();
        setTimeout(cerrarModal, 700);
    }
}

function editarMaquinariaUI(idMaquinaria, idCentro, nombre, cantidad, estado) {
    mostrarMaquinaria();

    document.getElementById("tituloMaquinariaForm").textContent = "Editar maquinaria";
    document.getElementById("btnGuardarMaquinaria").textContent = "Guardar cambios";
    document.getElementById("idMaquinaria").value = idMaquinaria;
    document.getElementById("centroMaquinaria").value = idCentro;
    document.getElementById("nombreMaquinaria").value = nombre;
    document.getElementById("cantidadMaquinaria").value = cantidad;
    document.getElementById("estadoMaquinaria").value = estado;
    document.getElementById("centroMaquinaria").disabled = true;
}

async function eliminarMaquinariaUI(idMaquinaria) {
    if (!confirm("¿Está seguro de que desea eliminar esta máquina?")) return;

    const { data } = await eliminarMaquinaria(idMaquinaria);

    if (data.error) {
        alert(data.error);
    } else {
        cargarMaquinaria();
    }
}

window.mostrarCamion = mostrarCamion;
window.mostrarMaquinaria = mostrarMaquinaria;
window.cerrarModal = cerrarModal;
window.guardarRegistro = guardarRegistro;
window.editarRegistroUI = editarRegistroUI;
window.eliminarRegistroUI = eliminarRegistroUI;
window.guardarMaquinaria = guardarMaquinaria;
window.editarMaquinariaUI = editarMaquinariaUI;
window.eliminarMaquinariaUI = eliminarMaquinariaUI;
window.cerrarSesion = () => cerrarSesion("index.html");

verificarSesion("Operario", "index.html");
cargarCentros().then(cargarCamionesDisponibles);
cargarRegistros();
cargarMaquinaria();
activarFlechaScroll();

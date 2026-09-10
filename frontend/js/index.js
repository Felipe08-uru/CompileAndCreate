import { iniciarSesion, registrarUsuario } from "./APIUsuarios.js";
import { getUsuarios } from "./APIUsuarios.js";
import { getContenedores } from "./APIContenedores.js";

const modal = document.getElementById("modal");
const overlay = document.getElementById("overlay");

function mostrarLogin() {
    verificarSesionExistente();
    modal.style.display = "block";
    overlay.style.display = "block";
    document.getElementById("loginForm").style.display = "block";
    document.getElementById("registroForm").style.display = "none";
    document.getElementById("mensajeLogin").textContent = "";
}

function mostrarRegistro() {
    modal.style.display = "block";
    overlay.style.display = "block";
    document.getElementById("loginForm").style.display = "none";
    document.getElementById("registroForm").style.display = "block";
    document.getElementById("mensajeRegistro").textContent = "";
}

function cerrarModal() {
    modal.style.display = "none";
    overlay.style.display = "none";
}

async function verificarSesionExistente() {
    const token = localStorage.getItem("token");
    const rol = localStorage.getItem("rol");

    if (!token || !rol) {
        return;
    }

    let comprobacion;

    if (rol === "Administrador") {
        comprobacion = await getUsuarios();
    } else if (rol === "Vecino") {
        comprobacion = await getContenedores();
    } else {
        return;
    }

    if (comprobacion.ok) {
        if (rol === "Administrador") {
            window.location.href = "administrador.html";
        } else if (rol === "Vecino") {
            window.location.href = "vecino.html";
        }
    } else {
        localStorage.removeItem("token");
        localStorage.removeItem("rol");
    }
}

async function manejarInicioSesion() {
    const correo_e = document.getElementById("loginCorreo").value.trim();
    const contrasena = document.getElementById("loginContrasena").value;
    const mensaje = document.getElementById("mensajeLogin");

    if (correo_e === "" || contrasena === "") {
        mensaje.textContent = "Completa todos los campos.";
        return;
    }

    try {
        const { ok, data } = await iniciarSesion({ correo_e, contrasena });

        console.log("Respuesta del servidor (LOGIN):", data);

        if (ok && data.success) {
            localStorage.setItem("token", data.token);
            localStorage.setItem("rol", data.rol);

            mensaje.textContent = "Inicio de sesión correcto.";

            setTimeout(function () {
                if (data.rol === "Administrador") {
                    window.location.href = "administrador.html";
                } else if (data.rol === "Vecino") {
                    window.location.href = "vecino.html";
                } else if (data.rol === "Operario") {
                    window.location.href = "operario.html";
                } else if (data.rol === "Cuadrilla") {
                    window.location.href = "cuadrilla.html";
                } else {
                    mensaje.textContent = "El usuario no tiene un rol válido.";
                }
            }, 500);
        } else {
            mensaje.textContent = data.error || "No se pudo iniciar sesión.";
        }
    } catch (error) {
        console.error("Error en el inicio de sesión:", error);
        mensaje.textContent = "Error de conexión con el servidor.";
    }
}

async function manejarRegistro() {
    const ci = document.getElementById("registroCi").value.trim();
    const nombre1 = document.getElementById("registroNombre1").value.trim();
    const nombre2 = document.getElementById("registroNombre2").value.trim();
    const apellido1 = document.getElementById("registroApellido1").value.trim();
    const apellido2 = document.getElementById("registroApellido2").value.trim();
    const correo_e = document.getElementById("registroCorreo").value.trim();
    const telefono = document.getElementById("registroTelefono").value.trim();
    const contrasena = document.getElementById("registroContrasena").value;
    const confirmar = document.getElementById("registroConfirmar").value;
    const mensaje = document.getElementById("mensajeRegistro");

    if (
        ci === "" ||
        nombre1 === "" ||
        apellido1 === "" ||
        correo_e === "" ||
        contrasena === "" ||
        confirmar === ""
    ) {
        mensaje.textContent = "Completa los campos obligatorios.";
        return;
    }

    if (contrasena !== confirmar) {
        mensaje.textContent = "Las contraseñas no coinciden.";
        return;
    }

    try {
        const { ok, data } = await registrarUsuario({
            ci,
            nombre1,
            nombre2,
            apellido1,
            apellido2,
            correo_e,
            telefono,
            contrasena
        });

        if (ok && data.success) {
            mensaje.textContent = "Cuenta creada correctamente.";

            document.getElementById("registroCi").value = "";
            document.getElementById("registroNombre1").value = "";
            document.getElementById("registroNombre2").value = "";
            document.getElementById("registroApellido1").value = "";
            document.getElementById("registroApellido2").value = "";
            document.getElementById("registroCorreo").value = "";
            document.getElementById("registroTelefono").value = "";
            document.getElementById("registroContrasena").value = "";
            document.getElementById("registroConfirmar").value = "";

            setTimeout(function () {
                mostrarLogin();
            }, 1000);
        } else {
            mensaje.textContent = data.error || "No se pudo crear la cuenta.";
        }
    } catch (error) {
        console.error("Error en el registro:", error);
        mensaje.textContent = "Error de conexión con el servidor.";
    }
}

// Se exponen al scope global porque el HTML las llama con atributos onclick.
window.mostrarLogin = mostrarLogin;
window.mostrarRegistro = mostrarRegistro;
window.cerrarModal = cerrarModal;
window.iniciarSesion = manejarInicioSesion;
window.registrarUsuario = manejarRegistro;

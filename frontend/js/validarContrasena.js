// Política de contraseñas: mínimo 10 caracteres, con mayúscula, minúscula y número.
// Devuelve null si es válida, o un mensaje que indica lo que falta.
export function validarContrasena(contrasena) {
    const faltan = [];

    if (contrasena.length < 10) faltan.push("al menos 10 caracteres");
    if (!/\p{Lu}/u.test(contrasena)) faltan.push("una letra mayúscula");
    if (!/\p{Ll}/u.test(contrasena)) faltan.push("una letra minúscula");
    if (!/\d/.test(contrasena)) faltan.push("un número");

    if (faltan.length === 0) return null;

    return "Contraseña no válida. Le falta: " + faltan.join(", ") + ".";
}

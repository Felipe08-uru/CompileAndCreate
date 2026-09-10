<?php
function obtenerToken() {
    $headers = function_exists('getallheaders') ? getallheaders() : [];

    $authorization = null;

    if (isset($headers["Authorization"])) {
        $authorization = $headers["Authorization"];
    } elseif (isset($headers["authorization"])) {
        $authorization = $headers["authorization"];
    } else {
        return null;
    }

    if (strpos($authorization, "Bearer ") === 0) {
        return substr($authorization, 7);
    }

    return null;
}

function autenticarUsuario($tokenObj) {
    $token = obtenerToken();

    if ($token === null || $token === "") {
        http_response_code(401);
        echo json_encode(["error" => "Token requerido"]);
        return false;
    }

    $ci = $tokenObj->validarToken($token);

    if ($ci === false) {
        http_response_code(401);
        echo json_encode(["error" => "Token inválido o vencido"]);
        return false;
    }

    return $ci;
}

function esAdministrador($usuarioObj, $ci) {
    $usuario = $usuarioObj->getUsuarioByCi($ci);

    if (!$usuario) {
        http_response_code(401);
        echo json_encode(["error" => "Usuario no encontrado"]);
        return false;
    }

    if ($usuario["rol"] !== "Administrador") {
        http_response_code(403);
        echo json_encode(["error" => "No tiene permisos"]);
        return false;
    }

    return true;
}
?>

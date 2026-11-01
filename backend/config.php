<?php

// Los avisos/errores de PHP no se imprimen en la respuesta (romperían el JSON
// que espera el frontend); quedan registrados en el log de errores de PHP.
ini_set("display_errors", "0");
ini_set("log_errors", "1");
error_reporting(E_ALL);

ob_start();

// Datos de conexión. Cada uno se puede forzar con una variable de entorno
// (DB_HOST, DB_PORT, DB_USER, DB_PASSWORD, DB_NAME).
//
// Si no se define DB_HOST se prueban, en orden, las ubicaciones habituales:
//   - dentro de un contenedor de docker-compose: "db" (puerto 3306)
//   - instalación local (XAMPP, WAMP, MySQL del sistema): localhost y 127.0.0.1
//     en los puertos 3306 y 3307
//   - base de datos de docker-compose publicada en la máquina: 127.0.0.1 (puerto 3303)
$user = getenv("DB_USER") ?: "root";
$password = getenv("DB_PASSWORD") !== false ? getenv("DB_PASSWORD") : "";
$database = getenv("DB_NAME") ?: "sigeru";

$enContenedor = @file_exists("/.dockerenv") || @file_exists("/run/.containerenv");

if(getenv("DB_HOST")){
    $candidatos = [[getenv("DB_HOST"), getenv("DB_PORT") ? intval(getenv("DB_PORT")) : 3306]];
}elseif($enContenedor){
    $candidatos = [["db", 3306]];
}else{
    $candidatos = [
        ["localhost", 3306],
        ["127.0.0.1", 3306],
        ["127.0.0.1", 3307],
        ["127.0.0.1", 3303]
    ];
}

/**
 * Ante un error inesperado (consulta fallida, tabla inexistente, error fatal...)
 * se responde un JSON en lugar de un "500" vacío. El detalle técnico solo se
 * incluye si la petición viene de la propia máquina o de una red privada
 * (desarrollo); siempre se registra en el log de errores de PHP.
 */
function responderErrorInterno($contexto, $detalle, $mensajePublico = "Error interno del servidor"){
    error_log("[SiGeRU] " . $contexto . ": " . $detalle);

    // Se descarta cualquier salida a medias para responder un JSON limpio.
    while(ob_get_level() > 0){
        ob_end_clean();
    }

    if(!headers_sent()){
        http_response_code(500);
        header("Content-Type: application/json; charset=UTF-8");
    }

    $respuesta = ["error" => $mensajePublico];

    $ip = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "";

    $esRedLocal = filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) === false;

    if($esRedLocal){
        $respuesta["detalle"] = $detalle;
    }

    // Algunos mensajes (por ejemplo los de Windows) no vienen en UTF-8.
    $opciones = defined("JSON_INVALID_UTF8_SUBSTITUTE") ? JSON_INVALID_UTF8_SUBSTITUTE : 0;
    $json = json_encode($respuesta, $opciones);

    echo $json !== false ? $json : '{"error":"' . $mensajePublico . '"}';
}

set_exception_handler(function($e){
    responderErrorInterno(
        "Excepción no controlada",
        $e->getMessage() . " (" . basename($e->getFile()) . ":" . $e->getLine() . ")"
    );
});

register_shutdown_function(function(){
    $error = error_get_last();

    if($error !== null && in_array($error["type"], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)){
        responderErrorInterno(
            "Error fatal",
            $error["message"] . " (" . basename($error["file"]) . ":" . $error["line"] . ")"
        );
    }
});

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Errores que significan "no hay un servidor MySQL escuchando ahí".
$erroresDeConexion = [2002, 2003, 2005, 2006, 2013];

$conn = null;
$intentos = [];
$errorDelServidor = null;

foreach($candidatos as $candidato){
    $hostPrueba = $candidato[0];
    $puertoPrueba = $candidato[1];

    try {
        $conexion = mysqli_init();
        $conexion->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
        $conexion->real_connect($hostPrueba, $user, $password, $database, $puertoPrueba);
        $conexion->set_charset("utf8mb4");

        $conn = $conexion;
        break;
    } catch (mysqli_sql_exception $e) {
        $intentos[] = $hostPrueba . ":" . $puertoPrueba . " -> " . $e->getMessage();

        // El servidor respondió pero hubo un problema (base inexistente, contraseña, etc.).
        if($errorDelServidor === null && !in_array($e->getCode(), $erroresDeConexion, true)){
            $errorDelServidor = $hostPrueba . ":" . $puertoPrueba . " -> " . $e->getMessage();
        }
    }
}

if($conn === null){
    responderErrorInterno(
        "Conexión a la base de datos",
        $errorDelServidor !== null
            ? $errorDelServidor
            : "no hay ningún MySQL escuchando. Se probó: " . implode(" | ", $intentos),
        "No se pudo conectar con la base de datos"
    );

    exit;
}
?>

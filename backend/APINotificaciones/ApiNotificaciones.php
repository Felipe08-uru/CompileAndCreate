<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/Notificacion.php";
require_once __DIR__ . "/../Token.php";
require_once __DIR__ . "/../Auth/Tokens.php";

header("Content-Type: application/json; charset=UTF-8");

$notificacionObj = new Notificacion($conn);
$tokenObj = new Token($conn);

$method = $_SERVER["REQUEST_METHOD"];
$endpoint = isset($_SERVER["PATH_INFO"]) ? $_SERVER["PATH_INFO"] : "/";

// Cualquier usuario con sesión iniciada; solo accede a sus propias notificaciones.
$ci = autenticarUsuario($tokenObj);

if($ci !== false){
    switch($method){
        case "GET":
            if($endpoint === "/notificaciones"){
                echo json_encode($notificacionObj->getDelUsuario($ci));
                break;
            }

            http_response_code(404);
            echo json_encode(["error" => "Endpoint no encontrado"]);
            break;

        case "POST":
            // Marcar todas como leídas
            if($endpoint === "/notificaciones/leidas"){
                $notificacionObj->marcarTodasLeidas($ci);
                echo json_encode(["success" => true]);
                break;
            }

            // Marcar una como leída
            if(preg_match('/^\/notificaciones\/(\d+)\/leida$/', $endpoint, $matches)){
                if($notificacionObj->marcarLeida(intval($matches[1]), $ci)){
                    echo json_encode(["success" => true]);
                }else{
                    http_response_code(404);
                    echo json_encode(["error" => "Notificación no encontrada"]);
                }
                break;
            }

            http_response_code(404);
            echo json_encode(["error" => "Endpoint no encontrado"]);
            break;

        default:
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido"]);
    }
}
?>

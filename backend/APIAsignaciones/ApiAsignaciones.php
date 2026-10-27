<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/Asignacion.php";
require_once __DIR__ . "/../APIUsuarios/Usuario.php";
require_once __DIR__ . "/../Token.php";
require_once __DIR__ . "/../Auth/Tokens.php";

header("Content-Type: application/json; charset=UTF-8");

$asignacionObj = new Asignacion($conn);
$usuarioObj = new Usuario($conn);
$tokenObj = new Token($conn);

$method = $_SERVER["REQUEST_METHOD"];
$endpoint = isset($_SERVER["PATH_INFO"]) ? $_SERVER["PATH_INFO"] : "/";

switch($method){
    case "GET":
        $ci = autenticarUsuario($tokenObj);
        if($ci === false){
            break;
        }

        // Asignación de la cuadrilla que consulta (su camión, zona y rutas).
        if($endpoint === "/asignaciones/mia"){
            if(obtenerUsuarioConRol($usuarioObj, $ci, ["Cuadrilla"]) === false){
                break;
            }

            echo json_encode($asignacionObj->getAsignacionDeCuadrilla($ci));
            break;
        }

        // Todas las asignaciones: solo el administrador.
        if($endpoint === "/asignaciones"){
            if(!esAdministrador($usuarioObj, $ci)){
                break;
            }

            echo json_encode($asignacionObj->getAsignaciones());
            break;
        }

        http_response_code(404);
        echo json_encode(["error" => "Endpoint no encontrado"]);
        break;

    case "POST":
        if($endpoint !== "/asignaciones" && $endpoint !== "/asignaciones/cuadrilla"){
            http_response_code(404);
            echo json_encode(["error" => "Endpoint no encontrado"]);
            break;
        }

        $ci = autenticarUsuario($tokenObj);
        if($ci === false){
            break;
        }

        if(!esAdministrador($usuarioObj, $ci)){
            break;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if(!is_array($data)){
            http_response_code(400);
            echo json_encode(["error" => "JSON inválido"]);
            break;
        }

        // Asignar (o quitar) el camión de una cuadrilla.
        if($endpoint === "/asignaciones/cuadrilla"){
            echo $asignacionObj->asignarCuadrilla($data);
            break;
        }

        echo $asignacionObj->guardarAsignacion($data);
        break;

    case "DELETE":
        if($endpoint !== "/asignaciones"){
            http_response_code(404);
            echo json_encode(["error" => "Endpoint no encontrado"]);
            break;
        }

        $ci = autenticarUsuario($tokenObj);
        if($ci === false){
            break;
        }

        if(!esAdministrador($usuarioObj, $ci)){
            break;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if(!is_array($data)){
            http_response_code(400);
            echo json_encode(["error" => "JSON inválido"]);
            break;
        }

        echo $asignacionObj->eliminarAsignacion($data);
        break;

    default:
        header("Allow: GET, POST, DELETE");
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
        break;
}
?>

<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/Maquinaria.php";
require_once __DIR__ . "/../APIUsuarios/Usuario.php";
require_once __DIR__ . "/../Token.php";
require_once __DIR__ . "/../Auth/Tokens.php";

header("Content-Type: application/json; charset=UTF-8");

$maquinariaObj = new Maquinaria($conn);
$usuarioObj = new Usuario($conn);
$tokenObj = new Token($conn);

$method = $_SERVER["REQUEST_METHOD"];
$endpoint = isset($_SERVER["PATH_INFO"]) ? $_SERVER["PATH_INFO"] : "/";

function esOperarioOAdministrador($usuarioObj, $ci){
    $usuario = $usuarioObj->getUsuarioByCi($ci);

    if(!$usuario){
        http_response_code(401);
        echo json_encode(["error" => "Usuario no encontrado"]);
        return false;
    }

    if($usuario["rol"] !== "Operario" && $usuario["rol"] !== "Administrador"){
        http_response_code(403);
        echo json_encode(["error" => "No tiene permisos"]);
        return false;
    }

    return true;
}

switch($method){
    case "GET":
        $ci = autenticarUsuario($tokenObj);
        if($ci === false){
            break;
        }

        if($endpoint === "/maquinaria"){
            $idCentro = isset($_GET["id_centro"]) ? intval($_GET["id_centro"]) : null;
            echo json_encode($maquinariaObj->getAllMaquinaria($idCentro));
            break;
        }

        if(preg_match('/^\/maquinaria\/(\d+)$/', $endpoint, $matches)){
            echo json_encode($maquinariaObj->getAllMaquinaria(intval($matches[1])));
            break;
        }

        http_response_code(404);
        echo json_encode(["error" => "Endpoint no encontrado"]);
        break;

    case "POST":
        if($endpoint !== "/maquinaria"){
            http_response_code(404);
            echo json_encode(["error" => "Endpoint no encontrado"]);
            break;
        }

        $ci = autenticarUsuario($tokenObj);
        if($ci === false){
            break;
        }

        if(!esOperarioOAdministrador($usuarioObj, $ci)){
            break;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if(!is_array($data)){
            http_response_code(400);
            echo json_encode(["error" => "JSON inválido"]);
            break;
        }

        if(isset($data["accion"]) && $data["accion"] === "editar"){
            echo $maquinariaObj->updateMaquinaria($data);
        }else{
            echo $maquinariaObj->addMaquinaria($data);
        }

        break;

    case "DELETE":
        if($endpoint !== "/maquinaria"){
            http_response_code(404);
            echo json_encode(["error" => "Endpoint no encontrado"]);
            break;
        }

        $ci = autenticarUsuario($tokenObj);
        if($ci === false){
            break;
        }

        if(!esOperarioOAdministrador($usuarioObj, $ci)){
            break;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if(!is_array($data) || !isset($data["id_maquinaria"])){
            http_response_code(400);
            echo json_encode(["error" => "Debe indicar la máquina a eliminar"]);
            break;
        }

        echo $maquinariaObj->deleteMaquinaria(intval($data["id_maquinaria"]));
        break;

    default:
        header("Allow: GET, POST, DELETE");
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
        break;
}
?>

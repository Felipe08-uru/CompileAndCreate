<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/RegistroCamion.php";
require_once __DIR__ . "/../APIUsuarios/Usuario.php";
require_once __DIR__ . "/../Token.php";
require_once __DIR__ . "/../Auth/Tokens.php";

header("Content-Type: application/json; charset=UTF-8");

$registroObj = new RegistroCamion($conn);
$usuarioObj = new Usuario($conn);
$tokenObj = new Token($conn);

$method = $_SERVER["REQUEST_METHOD"];
$endpoint = isset($_SERVER["PATH_INFO"]) ? $_SERVER["PATH_INFO"] : "/";

switch($method){
    case "GET":
        if($endpoint !== "/registros"){
            http_response_code(404);
            echo json_encode(["error" => "Endpoint no encontrado"]);
            break;
        }

        $ci = autenticarUsuario($tokenObj);
        if($ci === false){
            break;
        }

        $usuario = $usuarioObj->getUsuarioByCi($ci);

        if(!$usuario){
            http_response_code(401);
            echo json_encode(["error" => "Usuario no encontrado"]);
            break;
        }

        if($usuario["rol"] === "Administrador"){
            echo json_encode($registroObj->getAllRegistros());
        }elseif($usuario["rol"] === "Operario"){
            echo json_encode($registroObj->getRegistrosPorOperario($ci));
        }else{
            http_response_code(403);
            echo json_encode(["error" => "No tiene permisos"]);
        }

        break;

    case "POST":
        $ci = autenticarUsuario($tokenObj);
        if($ci === false){
            break;
        }

        if(!esOperario($usuarioObj, $ci)){
            break;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if(!is_array($data)){
            http_response_code(400);
            echo json_encode(["error" => "JSON inválido"]);
            break;
        }

        if($endpoint === "/registros"){
            if(isset($data["accion"]) && $data["accion"] === "editar"){
                echo $registroObj->updateRegistro($data, $ci);
            }else{
                echo $registroObj->addRegistro($data, $ci);
            }
            break;
        }

        http_response_code(404);
        echo json_encode(["error" => "Endpoint no encontrado"]);
        break;

    case "DELETE":
        $ci = autenticarUsuario($tokenObj);
        if($ci === false){
            break;
        }

        if($endpoint !== "/registros"){
            http_response_code(404);
            echo json_encode(["error" => "Endpoint no encontrado"]);
            break;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if(!is_array($data) || !isset($data["id_registro"])){
            http_response_code(400);
            echo json_encode(["error" => "Debe indicar el registro a eliminar"]);
            break;
        }

        $usuario = $usuarioObj->getUsuarioByCi($ci);

        if($usuario && $usuario["rol"] === "Administrador"){
            echo $registroObj->deleteRegistro(intval($data["id_registro"]), null);
        }elseif($usuario && $usuario["rol"] === "Operario"){
            echo $registroObj->deleteRegistro(intval($data["id_registro"]), $ci);
        }else{
            http_response_code(403);
            echo json_encode(["error" => "No tiene permisos"]);
        }

        break;

    default:
        header("Allow: GET, POST, DELETE");
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
        break;
}
?>

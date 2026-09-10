<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/Camion.php";
require_once __DIR__ . "/../APIUsuarios/Usuario.php";
require_once __DIR__ . "/../Token.php";
require_once __DIR__ . "/../Auth/Tokens.php";

header("Content-Type: application/json; charset=UTF-8");

$camionObj = new Camion($conn);
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
        if($endpoint === "/camiones"){
            echo json_encode($camionObj->getAllCamiones());
            break;
        }
        if(preg_match('/^\/camiones\/(.+)$/', $endpoint, $matches)){
            echo json_encode($camionObj->getCamionByMatricula($matches[1]));
            break;
        }
        http_response_code(404);
        echo json_encode(["error" => "Endpoint no encontrado"]);
        break;

    case "POST":
        if($endpoint !== "/camiones"){
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
        echo $camionObj->addCamion($data);
        break;

    case "DELETE":
        if($endpoint !== "/camiones"){
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
        echo $camionObj->deleteCamion($data);
        break;

    default:
        header("Allow: GET, POST, DELETE");
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
        break;
}
?>

<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/Centro.php";
require_once __DIR__ . "/../APIUsuarios/Usuario.php";
require_once __DIR__ . "/../Token.php";
require_once __DIR__ . "/../Auth/Tokens.php";

header("Content-Type: application/json; charset=UTF-8");

$centroObj = new Centro($conn);
$usuarioObj = new Usuario($conn);
$tokenObj = new Token($conn);

$method = $_SERVER["REQUEST_METHOD"];
$endpoint = isset($_SERVER["PATH_INFO"]) ? $_SERVER["PATH_INFO"] : "/";

switch($method){
    case "GET":
        if($endpoint !== "/centros"){
            http_response_code(404);
            echo json_encode(["error" => "Endpoint no encontrado"]);
            break;
        }

        $ci = autenticarUsuario($tokenObj);
        if($ci === false){
            break;
        }

        echo json_encode($centroObj->getAllCentros());
        break;

    case "POST":
        if($endpoint !== "/centros"){
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

        echo $centroObj->addCentro($data);
        break;

    case "DELETE":
        if($endpoint !== "/centros"){
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
        if(!is_array($data) || !isset($data["id_centro"])){
            http_response_code(400);
            echo json_encode(["error" => "Debe indicar el centro a eliminar"]);
            break;
        }

        echo $centroObj->deleteCentro(intval($data["id_centro"]));
        break;

    default:
        header("Allow: GET, POST, DELETE");
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
        break;
}
?>

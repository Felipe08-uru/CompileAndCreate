<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/Usuario.php";
require_once __DIR__ . "/../Token.php";
require_once __DIR__ . "/../Auth/Tokens.php";

header("Content-Type: application/json; charset=UTF-8");

$usuarioObj = new Usuario($conn);
$tokenObj = new Token($conn);

$method = $_SERVER["REQUEST_METHOD"];
$endpoint = isset($_SERVER["PATH_INFO"]) ? $_SERVER["PATH_INFO"] : "/";

switch($method){
    case "GET":
        if($endpoint === "/usuarios"){
            $ci = autenticarUsuario($tokenObj);

            if($ci === false){
                break;
            }

            if(!esAdministrador($usuarioObj, $ci)){
                break;
            }

            echo json_encode($usuarioObj->getAllUsuarios());
            break;
        }

        if(preg_match('/^\/usuarios\/(.+)$/', $endpoint, $matches)){
            $ci = autenticarUsuario($tokenObj);

            if($ci === false){
                break;
            }

            if(!esAdministrador($usuarioObj, $ci)){
                break;
            }

            echo json_encode($usuarioObj->getUsuarioByCi($matches[1]));
            break;
        }

        http_response_code(404);
        echo json_encode(["error" => "Endpoint no encontrado"]);
        break;

    case "POST":
        $data = json_decode(file_get_contents("php://input"), true);

        if(!is_array($data)){
            http_response_code(400);
            echo json_encode(["error" => "JSON inválido"]);
            break;
        }

        if($endpoint === "/registro"){
            $data["rol"] = "Vecino";
            echo $usuarioObj->addUsuario($data);
            break;
        }

        if($endpoint === "/login"){
            $resultado = $usuarioObj->login($data);

            if(isset($resultado["success"])){
                $usuario = $resultado["success"];
                $token = $tokenObj->crearToken($usuario["ci"]);

                if($token === false){
                    http_response_code(500);
                    echo json_encode(["error" => "No se pudo generar el token"]);
                    break;
                }

                echo json_encode([
                    "success" => true,
                    "token" => $token,
                    "rol" => $usuario["rol"]
                ]);
            }else{
                http_response_code(401);
                echo json_encode($resultado);
            }

            break;
        }

        if($endpoint === "/usuarios"){
            $ci = autenticarUsuario($tokenObj);

            if($ci === false){
                break;
            }

            if(!esAdministrador($usuarioObj, $ci)){
                break;
            }

            if(isset($data["accion"]) && $data["accion"] === "editar"){
                echo $usuarioObj->updateUsuario($data);
            }else{
                echo $usuarioObj->addUsuario($data);
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

        if(!esAdministrador($usuarioObj, $ci)){
            break;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if(!is_array($data)){
            http_response_code(400);
            echo json_encode(["error" => "JSON inválido"]);
            break;
        }

        if($endpoint === "/usuarios"){
            echo $usuarioObj->deleteUsuario($data);
        }else{
            http_response_code(404);
            echo json_encode(["error" => "Endpoint no encontrado"]);
        }

        break;

    default:
        header("Allow: GET, POST, DELETE");
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
        break;
}
?>

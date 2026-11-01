<?php
require_once "config.php";
require_once "contenedor.php";
require_once "usuario.php";
require_once "token.php";

header("Content-Type: application/json; charset=UTF-8");

$contenedorObj=new Contenedor($conn);
$usuarioObj=new Usuario($conn);
$tokenObj=new Token($conn);
$method=$_SERVER["REQUEST_METHOD"];
$endpoint=isset($_SERVER["PATH_INFO"])?$_SERVER["PATH_INFO"]:"/";

function obtenerToken(){
    $headers=getallheaders();
    if(isset($headers["Authorization"])){
        $authorization=$headers["Authorization"];
    }elseif(isset($headers["authorization"])){
        $authorization=$headers["authorization"];
    }else{
        return null;
    }
    if(strpos($authorization,"Bearer ")===0){
        return substr($authorization,7);
    }
    return null;
}

function autenticarUsuario($tokenObj){
    $token=obtenerToken();
    if($token===null||$token===""){
        http_response_code(401);
        echo json_encode(["error"=>"Token requerido"]);
        return false;
    }
    $ci=$tokenObj->validarToken($token);
    if($ci===false){
        http_response_code(401);
        echo json_encode(["error"=>"Token inválido o vencido"]);
        return false;
    }
    return $ci;
}

function esAdministrador($usuarioObj,$ci){
    $usuario=$usuarioObj->getUsuarioByCi($ci);
    if(!$usuario){
        http_response_code(401);
        echo json_encode(["error"=>"Usuario no encontrado"]);
        return false;
    }
    if($usuario["rol"]!=="Administrador"){
        http_response_code(403);
        echo json_encode(["error"=>"No tiene permisos"]);
        return false;
    }
    return true;
}

switch($method){
    case "GET":
        $ci=autenticarUsuario($tokenObj);
        if($ci===false){
            break;
        }
        if($endpoint==="/contenedores"){
            echo json_encode($contenedorObj->getAllContenedores());
            break;
        }
        if(preg_match('/^\/contenedores\/(.+)$/',$endpoint,$matches)){
            echo json_encode($contenedorObj->getContenedorById($matches[1]));
            break;
        }
        http_response_code(404);
        echo json_encode(["error"=>"Endpoint no encontrado"]);
        break;

    case "POST":
        if($endpoint!=="/contenedores"){
            http_response_code(404);
            echo json_encode(["error"=>"Endpoint no encontrado"]);
            break;
        }
        $ci=autenticarUsuario($tokenObj);
        if($ci===false){
            break;
        }
        if(!esAdministrador($usuarioObj,$ci)){
            break;
        }
        $data=json_decode(file_get_contents("php://input"),true);
        if(!is_array($data)){
            http_response_code(400);
            echo json_encode(["error"=>"JSON inválido"]);
            break;
        }
        echo $contenedorObj->addContenedor($data);
        break;

    case "DELETE":
        if($endpoint!=="/contenedores"){
            http_response_code(404);
            echo json_encode(["error"=>"Endpoint no encontrado"]);
            break;
        }
        $ci=autenticarUsuario($tokenObj);
        if($ci===false){
            break;
        }
        if(!esAdministrador($usuarioObj,$ci)){
            break;
        }
        $data=json_decode(file_get_contents("php://input"),true);
        if(!is_array($data)){
            http_response_code(400);
            echo json_encode(["error"=>"JSON inválido"]);
            break;
        }
        echo $contenedorObj->deleteContenedor($data);
        break;

    default:
        header("Allow: GET, POST, DELETE");
        http_response_code(405);
        echo json_encode(["error"=>"Método no permitido"]);
        break;
}
?>
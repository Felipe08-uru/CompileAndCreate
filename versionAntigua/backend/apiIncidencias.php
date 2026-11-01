<?php
require_once "config.php";
require_once "incidencia.php";
require_once "usuario.php";
require_once "token.php";

header("Content-Type: application/json; charset=UTF-8");

$incidenciaObj=new Incidencia($conn);
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
        if($endpoint==="/incidencias"){
            echo json_encode($incidenciaObj->getAllIncidencias());
            break;
        }
        if(preg_match('/^\/incidencias\/(\d+)$/',$endpoint,$matches)){
            echo json_encode($incidenciaObj->getIncidenciaById($matches[1]));
            break;
        }
        http_response_code(404);
        echo json_encode(["error"=>"Endpoint no encontrado"]);
        break;

    case "POST":
        if($endpoint!=="/incidencias"){
            http_response_code(404);
            echo json_encode(["error"=>"Endpoint no encontrado"]);
            break;
        }

        $ci=autenticarUsuario($tokenObj);
        if($ci===false){
            break;
        }

        $idContenedor=isset($_POST["idContenedor"])?trim($_POST["idContenedor"]):"";
        $tipoIncidencia=isset($_POST["tipoIncidencia"])?trim($_POST["tipoIncidencia"]):"";

        if($idContenedor===""||$tipoIncidencia===""){
            http_response_code(400);
            echo json_encode(["error"=>"Faltan datos de la incidencia"]);
            break;
        }

        if(!isset($_FILES["foto"])||$_FILES["foto"]["error"]!==UPLOAD_ERR_OK){
            http_response_code(400);
            echo json_encode(["error"=>"Debe seleccionar una fotografía"]);
            break;
        }

        $foto=$_FILES["foto"];

        if($foto["size"]>5*1024*1024){
            http_response_code(400);
            echo json_encode(["error"=>"La imagen no puede superar los 5 MB"]);
            break;
        }

        $tiposPermitidos=["image/jpeg","image/png","image/gif"];
        $tipoArchivo=mime_content_type($foto["tmp_name"]);

        if(!in_array($tipoArchivo,$tiposPermitidos)){
            http_response_code(400);
            echo json_encode(["error"=>"La imagen debe ser JPG, PNG o GIF"]);
            break;
        }

        $extension="";
        if($tipoArchivo==="image/jpeg"){
            $extension=".jpg";
        }elseif($tipoArchivo==="image/png"){
            $extension=".png";
        }elseif($tipoArchivo==="image/gif"){
            $extension=".gif";
        }

        $carpeta="uploads/incidencias/";

        if(!is_dir($carpeta)){
            mkdir($carpeta,0777,true);
        }

        $nombreArchivo="incidencia_".uniqid().$extension;
        $ruta=$carpeta.$nombreArchivo;

        if(!move_uploaded_file($foto["tmp_name"],$ruta)){
            http_response_code(500);
            echo json_encode(["error"=>"No se pudo guardar la fotografía"]);
            break;
        }

        $data=[
            "Tipo"=>$tipoIncidencia,
            "Estado"=>"Pendiente",
            "Id_Contenedor"=>$idContenedor,
            "Foto"=>$nombreArchivo
        ];

        $resultado=$incidenciaObj->addIncidencia($data);

        if(!$resultado){
            if(file_exists($ruta)){
                unlink($ruta);
            }
            http_response_code(500);
            echo json_encode(["error"=>"No se pudo registrar la incidencia"]);
            break;
        }

        echo $resultado;
        break;

    case "DELETE":
        if(!preg_match('/^\/incidencias\/(\d+)$/',$endpoint,$matches)){
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

        echo $incidenciaObj->deleteIncidencia($matches[1]);
        break;

    default:
        header("Allow: GET, POST, DELETE");
        http_response_code(405);
        echo json_encode(["error"=>"Método no permitido"]);
        break;
}
?>
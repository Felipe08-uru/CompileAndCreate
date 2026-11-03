<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/Incidencia.php";
require_once __DIR__ . "/../APIUsuarios/Usuario.php";
require_once __DIR__ . "/../Token.php";
require_once __DIR__ . "/../Auth/Tokens.php";

header("Content-Type: application/json; charset=UTF-8"); // la foto lo reemplaza con el tipo de la imagen

$incidenciaObj = new Incidencia($conn);
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
        if($endpoint === "/incidencias"){
            echo json_encode($incidenciaObj->getAllIncidencias());
            break;
        }
        // Foto de la incidencia (solo administrador). Se envía la imagen con el mismo control de token.
        if(preg_match('/^\/incidencias\/(\d+)\/foto$/', $endpoint, $matches)){
            if(!esAdministrador($usuarioObj, $ci)){
                break;
            }

            $incidencia = $incidenciaObj->getIncidenciaById($matches[1]);

            if($incidencia === null || empty($incidencia["Foto"])){
                http_response_code(404);
                echo json_encode(["error" => "La incidencia no tiene foto"]);
                break;
            }

            $archivo = __DIR__ . "/../uploads/incidencias/" . basename($incidencia["Foto"]);

            if(!is_file($archivo)){
                http_response_code(404);
                echo json_encode(["error" => "No se encontró el archivo de la foto"]);
                break;
            }

            header("Content-Type: " . mime_content_type($archivo));
            header("Content-Length: " . filesize($archivo));
            header("X-Content-Type-Options: nosniff");
            readfile($archivo);
            break;
        }
        if(preg_match('/^\/incidencias\/(\d+)$/', $endpoint, $matches)){
            echo json_encode($incidenciaObj->getIncidenciaById($matches[1]));
            break;
        }
        http_response_code(404);
        echo json_encode(["error" => "Endpoint no encontrado"]);
        break;

    case "POST":
        if($endpoint !== "/incidencias"){
            http_response_code(404);
            echo json_encode(["error" => "Endpoint no encontrado"]);
            break;
        }

        $ci = autenticarUsuario($tokenObj);
        if($ci === false){
            break;
        }

        $idContenedor = isset($_POST["idContenedor"]) ? trim($_POST["idContenedor"]) : "";
        $tipoIncidencia = isset($_POST["tipoIncidencia"]) ? trim($_POST["tipoIncidencia"]) : "";

        if($idContenedor === "" || $tipoIncidencia === ""){
            http_response_code(400);
            echo json_encode(["error" => "Faltan datos de la incidencia"]);
            break;
        }

        if(!isset($_FILES["foto"]) || $_FILES["foto"]["error"] !== UPLOAD_ERR_OK){
            http_response_code(400);
            echo json_encode(["error" => "Debe seleccionar una fotografía"]);
            break;
        }

        if(!ctype_digit($idContenedor) || !$incidenciaObj->existeContenedor($idContenedor)){
            http_response_code(404);
            echo json_encode(["error" => "El contenedor no existe"]);
            break;
        }

        if($incidenciaObj->getPendienteDelContenedor($idContenedor) !== null){
            http_response_code(409);
            echo json_encode(["error" => "El contenedor ya tiene una incidencia pendiente. Hay que resolverla antes de registrar otra."]);
            break;
        }

        $foto = $_FILES["foto"];

        if($foto["size"] > 5 * 1024 * 1024){
            http_response_code(400);
            echo json_encode(["error" => "La imagen no puede superar los 5 MB"]);
            break;
        }

        $tiposPermitidos = ["image/jpeg", "image/png", "image/gif"];
        $tipoArchivo = mime_content_type($foto["tmp_name"]);

        if(!in_array($tipoArchivo, $tiposPermitidos)){
            http_response_code(400);
            echo json_encode(["error" => "La imagen debe ser JPG, PNG o GIF"]);
            break;
        }

        $extension = "";
        if($tipoArchivo === "image/jpeg"){
            $extension = ".jpg";
        }elseif($tipoArchivo === "image/png"){
            $extension = ".png";
        }elseif($tipoArchivo === "image/gif"){
            $extension = ".gif";
        }

        $carpeta = __DIR__ . "/../uploads/incidencias/";

        if(!is_dir($carpeta)){
            mkdir($carpeta, 0777, true);
        }

        $nombreArchivo = "incidencia_" . uniqid() . $extension;
        $ruta = $carpeta . $nombreArchivo;

        if(!move_uploaded_file($foto["tmp_name"], $ruta)){
            http_response_code(500);
            echo json_encode(["error" => "No se pudo guardar la fotografía"]);
            break;
        }

        $data = [
            "Tipo" => $tipoIncidencia,
            "Estado" => "Pendiente",
            "Id_Contenedor" => $idContenedor,
            "Foto" => $nombreArchivo
        ];

        $resultado = $incidenciaObj->addIncidencia($data);

        $decodificado = json_decode($resultado, true);

        if(!$resultado || isset($decodificado["error"])){
            if(file_exists($ruta)){
                unlink($ruta);
            }
            if(http_response_code() < 400){
                http_response_code(500);
            }
            echo $resultado ? $resultado : json_encode(["error" => "No se pudo registrar la incidencia"]);
            break;
        }

        echo $resultado;
        break;

    case "DELETE":
        if(!preg_match('/^\/incidencias\/(\d+)$/', $endpoint, $matches)){
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

        echo $incidenciaObj->deleteIncidencia($matches[1]);
        break;

    default:
        header("Allow: GET, POST, DELETE");
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
        break;
}
?>

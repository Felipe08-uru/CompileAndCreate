<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/Ruta.php";
require_once __DIR__ . "/../APIUsuarios/Usuario.php";
require_once __DIR__ . "/../Token.php";
require_once __DIR__ . "/../Auth/Tokens.php";

header("Content-Type: application/json; charset=UTF-8");

$rutaObj = new Ruta($conn);
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

        $usuario = obtenerUsuarioConRol($usuarioObj, $ci, ["Administrador", "Cuadrilla"]);
        if($usuario === false){
            break;
        }

        // Lista de todas las rutas (administrador).
        if($endpoint === "/rutas"){
            if($usuario["rol"] !== "Administrador"){
                http_response_code(403);
                echo json_encode(["error" => "No tiene permisos"]);
                break;
            }

            echo json_encode($rutaObj->getRutas());
            break;
        }

        // Ruta sin finalizar del camión de la cuadrilla.
        if($endpoint === "/rutas/activa"){
            if($usuario["rol"] !== "Cuadrilla"){
                http_response_code(403);
                echo json_encode(["error" => "No tiene permisos"]);
                break;
            }

            $idActiva = $rutaObj->getIdRutaActivaDeCuadrilla($ci);

            if($idActiva === null){
                echo json_encode(["ruta" => null]);
                break;
            }

            echo json_encode($rutaObj->getDetalle($idActiva));
            break;
        }

        // Detalle de una ruta.
        if(preg_match('/^\/rutas\/(\d+)$/', $endpoint, $matches)){
            $idRuta = intval($matches[1]);

            if($usuario["rol"] === "Cuadrilla" && !$rutaObj->cuadrillaPuedeVerRuta($ci, $idRuta)){
                http_response_code(403);
                echo json_encode(["error" => "No tiene permisos para ver esta ruta"]);
                break;
            }

            $detalle = $rutaObj->getDetalle($idRuta);

            if($detalle === null){
                http_response_code(404);
                echo json_encode(["error" => "Ruta no encontrada"]);
                break;
            }

            echo json_encode($detalle);
            break;
        }

        http_response_code(404);
        echo json_encode(["error" => "Endpoint no encontrado"]);
        break;

    case "POST":
        $ci = autenticarUsuario($tokenObj);
        if($ci === false){
            break;
        }

        $usuario = obtenerUsuarioConRol($usuarioObj, $ci, ["Administrador", "Cuadrilla"]);
        if($usuario === false){
            break;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if(!is_array($data)){
            http_response_code(400);
            echo json_encode(["error" => "JSON inválido"]);
            break;
        }

        // Crear una ruta, generar la de un camión o asignarle camión: solo el administrador.
        if($endpoint === "/rutas" || $endpoint === "/rutas/generar" ||
           preg_match('/^\/rutas\/(\d+)\/camion$/', $endpoint, $matchesCamion)){

            if($usuario["rol"] !== "Administrador"){
                http_response_code(403);
                echo json_encode(["error" => "Solo el administrador puede crear rutas y asignar camiones"]);
                break;
            }

            if($endpoint === "/rutas"){
                echo $rutaObj->crearRuta(
                    isset($data["id_poligono"]) ? $data["id_poligono"] : "",
                    isset($data["tipo"]) ? $data["tipo"] : "",
                    isset($data["matricula"]) ? $data["matricula"] : null
                );
                break;
            }

            if($endpoint === "/rutas/generar"){
                echo $rutaObj->generarRuta(isset($data["matricula"]) ? $data["matricula"] : "");
                break;
            }

            echo $rutaObj->asignarCamionARuta(
                intval($matchesCamion[1]),
                isset($data["matricula"]) ? $data["matricula"] : null
            );
            break;
        }

        // Registrar el peso del contenedor de la parada actual.
        if(preg_match('/^\/rutas\/(\d+)\/peso$/', $endpoint, $matches)){

            if($usuario["rol"] !== "Cuadrilla"){
                http_response_code(403);
                echo json_encode(["error" => "Solo la cuadrilla puede registrar el peso"]);
                break;
            }

            $peso = isset($data["peso"]) ? $data["peso"] : null;
            $orden = isset($data["orden"]) ? $data["orden"] : null;

            echo $rutaObj->registrarPeso(intval($matches[1]), $orden, $peso, $ci);
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

        if(preg_match('/^\/rutas\/(\d+)$/', $endpoint, $matches)){
            echo $rutaObj->deleteRuta(intval($matches[1]));
            break;
        }

        http_response_code(404);
        echo json_encode(["error" => "Endpoint no encontrado"]);
        break;

    default:
        header("Allow: GET, POST, DELETE");
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
        break;
}
?>

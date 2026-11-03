<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/Estadistica.php";
require_once __DIR__ . "/../APIUsuarios/Usuario.php";
require_once __DIR__ . "/../Token.php";
require_once __DIR__ . "/../Auth/Tokens.php";

header("Content-Type: application/json; charset=UTF-8");

$estadisticaObj = new Estadistica($conn);
$usuarioObj = new Usuario($conn);
$tokenObj = new Token($conn);

$method = $_SERVER["REQUEST_METHOD"];
$endpoint = isset($_SERVER["PATH_INFO"]) ? $_SERVER["PATH_INFO"] : "/";

function entero_entre($valor, $porDefecto, $min, $max){
    if($valor === null || $valor === "" || !ctype_digit((string)$valor)){
        return $porDefecto;
    }

    return max($min, min($max, intval($valor)));
}

switch($method){
    case "GET":
        $ci = autenticarUsuario($tokenObj);
        if($ci === false){
            break;
        }

        // Las estadísticas son solo para el administrador.
        if(!esAdministrador($usuarioObj, $ci)){
            break;
        }

        if($endpoint === "/estadisticas/desbordados"){
            $resultado = $estadisticaObj->desbordados(
                isset($_GET["mes"]) ? $_GET["mes"] : null,
                entero_entre(isset($_GET["limite"]) ? $_GET["limite"] : null, 10, 1, 100)
            );

            if($resultado === null){
                http_response_code(400);
                echo json_encode(["error" => "El mes debe tener el formato AAAA-MM"]);
                break;
            }

            echo json_encode($resultado);
            break;
        }

        if($endpoint === "/estadisticas/promedio"){
            echo json_encode($estadisticaObj->promedio(
                entero_entre(isset($_GET["dias"]) ? $_GET["dias"] : null, 30, 1, 365),
                entero_entre(isset($_GET["limite"]) ? $_GET["limite"] : null, 10, 1, 100)
            ));
            break;
        }

        if($endpoint === "/estadisticas/zonas"){
            echo json_encode($estadisticaObj->zonas(
                entero_entre(isset($_GET["dias"]) ? $_GET["dias"] : null, 30, 1, 365)
            ));
            break;
        }

        if($endpoint === "/estadisticas/limpieza"){
            echo json_encode($estadisticaObj->limpieza());
            break;
        }

        http_response_code(404);
        echo json_encode(["error" => "Endpoint no encontrado"]);
        break;

    default:
        header("Allow: GET");
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
        break;
}
?>

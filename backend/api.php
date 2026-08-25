<?php
require_once "config.php";
require_once "usuario.php";
require_once "camion.php";
require_once "contenedor.php";
require_once "poligono.php";

session_start();

header("Content-Type: application/json; charset=UTF-8");

$usuarioObj = new Usuario($conn);
$camionObj = new Camion($conn);
$contenedorObj = new Contenedor($conn);
$poligonoObj = new Poligono($conn);

$method = $_SERVER["REQUEST_METHOD"];
$endpoint = isset($_SERVER["PATH_INFO"]) ? $_SERVER["PATH_INFO"] : "/";

switch ($method) {
    case "GET":
        if ($endpoint === "/usuarios") {
            if (!isset($_SESSION["usuario"])) {
                http_response_code(401);
                echo json_encode([
                    "error" => "Debe iniciar sesión"
                ]);
                break;
            }

            if ($_SESSION["usuario"]["rol"] !== "Administrador") {
                http_response_code(403);
                echo json_encode([
                    "error" => "No tiene permisos"
                ]);
                break;
            }

            echo json_encode($usuarioObj->getAllUsuarios());
        } elseif (preg_match('/^\/usuarios\/(.+)$/', $endpoint, $matches)) {
            if (!isset($_SESSION["usuario"])) {
                http_response_code(401);
                echo json_encode([
                    "error" => "Debe iniciar sesión"
                ]);
                break;
            }

            if ($_SESSION["usuario"]["rol"] !== "Administrador") {
                http_response_code(403);
                echo json_encode([
                    "error" => "No tiene permisos"
                ]);
                break;
            }

            echo json_encode(
                $usuarioObj->getUsuarioByCi($matches[1])
            );
        }

        elseif ($endpoint === "/camiones") {
            echo json_encode($camionObj->getAllCamiones());
        }

        elseif (preg_match('/^\/camiones\/(.+)$/', $endpoint, $matches)) {
            echo json_encode(
                $camionObj->getCamionByMatricula($matches[1])
            );
        }

        elseif ($endpoint === "/contenedores") {
            echo json_encode($contenedorObj->getAllContenedores());
        }

        elseif (preg_match('/^\/contenedores\/(\d+)$/', $endpoint, $matches)) {
            echo json_encode(
                $contenedorObj->getContenedorById($matches[1])
            );
        }
        elseif ($endpoint === "/poligonos") {

            if (!isset($_SESSION["usuario"])) {
                http_response_code(401);

                echo json_encode([
                    "error" => "Debe iniciar sesión"
                ]);

                break;
            }

            if ($_SESSION["usuario"]["rol"] !== "Administrador") {
                http_response_code(403);
                echo json_encode(["error" => "No tiene permisos"]);
                break;
            }

            echo json_encode(
                $poligonoObj->getAllPoligonos()
            );
        }

        else {
            http_response_code(404);
            echo json_encode([
                "error" => "Endpoint no encontrado"
            ]);
        }

        break;

    case "POST":
        $data = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode([
                "error" => "JSON inválido"
            ]);

            break;
        }

        if ($endpoint === "/registro") {
            $data["rol"] = "Vecino";
            echo $usuarioObj->addUsuario($data);
            break;
        }

        if ($endpoint === "/login") {
            $resultado = $usuarioObj->login($data);
            if (isset($resultado["success"])) {
                session_regenerate_id(true);
                $_SESSION["usuario"] = $resultado["success"];
            }
            echo json_encode($resultado);
            break;
        }

        if ($endpoint === "/usuarios") {
            if (!isset($_SESSION["usuario"])) {
                http_response_code(401);
                echo json_encode([
                    "error" => "Debe iniciar sesión"
                ]);
                break;
            }

            if ($_SESSION["usuario"]["rol"] !== "Administrador") {
                http_response_code(403);
                echo json_encode([
                    "error" => "No tiene permisos"
                ]);
                break;
            }

            if (isset($data["accion"]) && $data["accion"] === "editar") {
                echo $usuarioObj->updateUsuario($data);
            } else {
                echo $usuarioObj->addUsuario($data);
            }

            break;
        }

        if ($endpoint === "/camiones") {
            if (!isset($_SESSION["usuario"])) {
                http_response_code(401);
                echo json_encode([
                    "error" => "Debe iniciar sesión"
                ]);
                break;
            }
            echo $camionObj->addCamion($data);
            break;
        }

        if ($endpoint === "/contenedores") {
            if (!isset($_SESSION["usuario"])) {
                http_response_code(401);
                echo json_encode([
                    "error" => "Debe iniciar sesión"
                ]);
                break;
            }
            echo $contenedorObj->addContenedor($data);
            break;
        }
        if ($endpoint === "/poligonos") {

            if (!isset($_SESSION["usuario"])) {
                http_response_code(401);

                echo json_encode([
                    "error" => "Debe iniciar sesión"
                ]);

                break;
            }

            if ($_SESSION["usuario"]["rol"] !== "Administrador") {
                http_response_code(403);
                echo json_encode(["error" => "No tiene permisos para crear zonas"]);
                break;
            }
            echo $poligonoObj->addPoligono($data);
            break;
        }

        http_response_code(404);
        echo json_encode([
            "error" => "Endpoint no encontrado"
        ]);

        break;

    case "DELETE":
        if (!isset($_SESSION["usuario"])) {
            http_response_code(401);
            echo json_encode([
                "error" => "Debe iniciar sesión"
            ]);
            break;
        }

        if ($_SESSION["usuario"]["rol"] !== "Administrador") {
            http_response_code(403);
            echo json_encode([
                "error" => "No tiene permisos"
            ]);

            break;
        }
        $data = json_decode(
            file_get_contents("php://input"),
            true
        );
        if ($endpoint === "/usuarios") {
            echo $usuarioObj->deleteUsuario($data);
        } elseif ($endpoint === "/camiones") {
            echo $camionObj->deleteCamion($data);
        } elseif ($endpoint === "/contenedores") {
            echo $contenedorObj->deleteContenedor($data);
        } elseif (preg_match('/^\/poligonos\/(\d+)$/', $endpoint, $matches)) {
            echo $poligonoObj->deletePoligono($matches[1]);
        } else {
            http_response_code(404);

            echo json_encode([
                "error" => "Endpoint no encontrado"
            ]);
        }

        break;

    default:
        header("Allow: GET, POST, DELETE");
        http_response_code(405);

        echo json_encode([
            "error" => "Método no permitido"
        ]);

        break;
}
?>
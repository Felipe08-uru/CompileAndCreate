<?php

require_once 'config.php';
require_once 'usuario.php';
require_once 'camion.php';
require_once 'contenedor.php';

session_start();

header('Content-Type: application/json');

$usuarioObj = new Usuario($conn);
$camionObj = new Camion($conn);
$contenedorObj = new Contenedor($conn);

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/';

switch ($method) {
    case 'GET':
        // usuarios
        if ($endpoint === '/usuarios') {
            echo json_encode($usuarioObj->getAllUsuarios());
        }
        elseif (preg_match('/^\/usuarios\/(.+)$/', $endpoint, $matches)) {
            echo json_encode($usuarioObj->getUsuarioByCi($matches[1]));
        }

        // camiones
        elseif ($endpoint === '/camiones') {
            echo json_encode($camionObj->getAllCamiones());
        }
        elseif (preg_match('/^\/camiones\/(.+)$/', $endpoint, $matches)) {
            echo json_encode($camionObj->getCamionByMatricula($matches[1]));
        }

        // contenedores
        elseif ($endpoint === '/contenedores') {
            echo json_encode($contenedorObj->getAllContenedores());
        }
        elseif (preg_match('/^\/contenedores\/(\d+)$/', $endpoint, $matches)) {
            echo json_encode($contenedorObj->getContenedorById($matches[1]));
        }

        else {
            http_response_code(404);
            echo json_encode([
                "error" => "Endpoint no encontrado"
            ]);
        }

    break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        // Login
        if ($endpoint === '/login') {

            $resultado = $usuarioObj->login($data);

            if (isset($resultado["usuario"])) {
                $_SESSION["usuario"] = $resultado["usuario"];
            }

            echo json_encode($resultado);
        }

        // usuarios
        elseif ($endpoint === '/usuarios') {
            echo $usuarioObj->addUsuario($data);
        }

        // camiones
        elseif ($endpoint === '/camiones') {
            echo $camionObj->addCamion($data);
        }

        // contenedores
        elseif ($endpoint === '/contenedores') {
            echo $contenedorObj->addContenedor($data);
        }

        else {
            http_response_code(404);
            echo json_encode([
                "error" => "Endpoint no encontrado"
            ]);
        }

    break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);

        // Usuarios
        if ($endpoint === '/usuarios') {
            echo $usuarioObj->deleteUsuario($data);
        }

        // Camiones
        elseif ($endpoint === '/camiones') {
            echo $camionObj->deleteCamion($data);
        }

        // Contenedores
        elseif ($endpoint === '/contenedores') {
            echo $contenedorObj->deleteContenedor($data);
        }

        else {
            http_response_code(404);
            echo json_encode([
                "error" => "Endpoint no encontrado"
            ]);
        }
    break;
    default:
        header('Allow: GET, POST, DELETE');
        http_response_code(405);
        echo json_encode([
            "error" => "Método no permitido"
        ]);
    break;
}

?>
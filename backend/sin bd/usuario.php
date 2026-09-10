<?php

class Usuario{

    private $conn;
    private $table = "usuario";

    public function __construct($conn){
        $this->conn = $conn;

        if(session_status() == PHP_SESSION_NONE){
            session_start();
        }

        if(!isset($_SESSION['usuarios'])){
            $_SESSION['usuarios'] = [];
        }
    }

    public function getAllUsuarios(){
        return $_SESSION['usuarios'];
    }

    public function getUsuarioByCi($ci){
        foreach($_SESSION['usuarios'] as $usuario){
            if($usuario['ci'] == $ci){
                return $usuario;
            }
        }

        return null;
    }

    public function addUsuario($data){
        if(!isset($data['ci']) || !isset($data['nombre']) || !isset($data['apellido']) || !isset($data['correo']) || !isset($data['contrasena']) || !isset($data['telefono']) || !isset($data['rol'])){
            http_response_code(400);
            return json_encode([
                "error" => "Datos incompletos"
            ]);
        }else{
            $usuario = [
                "ci" => $data['ci'],
                "nombre" => $data['nombre'],
                "apellido" => $data['apellido'],
                "correo" => $data['correo'],
                "contrasena" => password_hash($data['contrasena'], PASSWORD_DEFAULT),
                "telefono" => $data['telefono'],
                "rol" => $data['rol']
            ];

            $_SESSION['usuarios'][] = $usuario;

            http_response_code(201);
            return json_encode([
                "success" => "Usuario registrado con éxito"
            ]);
        }
    }

    public function deleteUsuario($data){
        $ci = $data['ci'];

        foreach($_SESSION['usuarios'] as $i => $usuario){
            if($usuario['ci'] == $ci){
                unset($_SESSION['usuarios'][$i]);
                $_SESSION['usuarios'] = array_values($_SESSION['usuarios']);

                return json_encode([
                    "mensaje" => "Usuario eliminado"
                ]);
            }
        }

        return json_encode([
            "error" => "Usuario no encontrado"
        ]);
    }

    public function login($data){
        if(!isset($data['correo']) || !isset($data['contrasena'])){
            http_response_code(400);
            return json_encode([
                "error" => "Datos incompletos"
            ]);
        }else{
            foreach($_SESSION['usuarios'] as $usuario){
                if($usuario['correo'] == $data['correo']){
                    if(password_verify($data['contrasena'], $usuario['contrasena'])){

                        $_SESSION['usuario'] = [
                            "ci" => $usuario['ci'],
                            "nombre" => $usuario['nombre'],
                            "apellido" => $usuario['apellido'],
                            "correo" => $usuario['correo'],
                            "telefono" => $usuario['telefono'],
                            "rol" => $usuario['rol']
                        ];

                        http_response_code(200);
                        return json_encode([
                            "success" => $_SESSION['usuario']
                        ]);
                    }else{
                        http_response_code(400);
                        return json_encode([
                            "error" => "Contraseña incorrecta"
                        ]);
                    }
                }
            }

            http_response_code(400);
            return json_encode([
                "error" => "Usuario no encontrado"
            ]);
        }
    }

}

?>
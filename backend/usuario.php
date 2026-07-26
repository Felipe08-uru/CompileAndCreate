<?php

class Usuario{

    private $conn;
    private $table = "usuario";

    public function __construct($conn){
        $this->conn = $conn;
    }

    public function getAllUsuarios(){
        $sql = "SELECT * FROM $this->table";
        $result = mysqli_query($this->conn, $sql);
        $usuarios = mysqli_fetch_all($result, MYSQLI_ASSOC);

        return $usuarios;
    }

    public function getUsuarioByCi($ci){
        $sql = "SELECT * FROM $this->table WHERE ci='$ci'";
        $result = mysqli_query($this->conn, $sql);
        $usuario = mysqli_fetch_assoc($result);

        return $usuario;
    }

    public function addUsuario($data){
        if(!isset($data['ci']) || !isset($data['nombre']) || !isset($data['apellido']) || !isset($data['correo']) || !isset($data['contrasena']) || !isset($data['telefono']) || !isset($data['rol'])){
            http_response_code(400);
            return json_encode([
                "error" => "Datos incompletos"
            ]);
        }else{
            $ci = $data['ci'];
            $nombre = $data['nombre'];
            $apellido = $data['apellido'];
            $correo = $data['correo'];
            $contrasena = password_hash($data['contrasena'], PASSWORD_DEFAULT);
            $telefono = $data['telefono'];
            $rol = $data['rol'];

            try{
                $sql = "INSERT INTO $this->table
                        (ci,nombre,apellido,correo,contrasena,telefono,rol)
                        VALUES
                        ('$ci','$nombre','$apellido','$correo','$contrasena','$telefono','$rol')";
                $result = mysqli_query($this->conn, $sql);
            }catch(mysqli_sql_exception $e){
                http_response_code(500);
                return json_encode([
                    "error" => "Error en la base de datos: " . $e->getMessage()
                ]);
            }
            if($result){
                http_response_code(201);
                return json_encode([
                    "success" => "Usuario registrado con éxito"
                ]);
            }else{
                http_response_code(400);
                return json_encode([
                    "error" => "No se pudo registrar el usuario"
                ]);
            }
        }
    }

    public function deleteUsuario($data){
        $ci = $data['ci'];
        $sql = "DELETE FROM $this->table WHERE ci='$ci'";

        if(mysqli_query($this->conn, $sql)){
            return json_encode([
                "mensaje" => "Usuario eliminado"
            ]);
        }

        return json_encode([
            "error" => mysqli_error($this->conn)
        ]);
    }

public function login($data){
    if(!isset($data['correo']) || !isset($data['contrasena'])){
        http_response_code(400);
        return json_encode([
            "error" => "Datos incompletos"
        ]);
    }else{
        $correo = $data['correo'];
        $contrasena = $data['contrasena'];
        $query = "SELECT * FROM $this->table WHERE correo = '$correo'";
        $result = mysqli_query($this->conn, $query);
        if(mysqli_num_rows($result) > 0){
            $usuario = mysqli_fetch_assoc($result);
            if(password_verify($contrasena, $usuario['contrasena'])){
                http_response_code(200);
                return json_encode([
                    "success" => [
                        "ci" => $usuario['ci'],
                        "nombre" => $usuario['nombre'],
                        "apellido" => $usuario['apellido'],
                        "correo" => $usuario['correo'],
                        "telefono" => $usuario['telefono'],
                        "rol" => $usuario['rol']
                    ]
                ]);
            }else{
                http_response_code(400);
                return json_encode([
                    "error" => "Contraseña incorrecta"
                ]);
            }
        }else{
            http_response_code(400);
            return json_encode([
                "error" => "Usuario no encontrado"
            ]);
        }
    }
}

}

?>
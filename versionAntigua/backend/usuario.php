<?php
class Usuario {
    private $conn;
    private $table = "Usuario";

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getAllUsuarios() {
        $sql = "SELECT ci, nombre1, nombre2, apellido1, apellido2, rol, correo_e FROM $this->table";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getUsuarioByCi($ci) {
        $sql = "SELECT ci, nombre1, nombre2, apellido1, apellido2, rol, correo_e
                FROM $this->table
                WHERE ci = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $ci);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function addUsuario($data) {

        $camposObligatorios = [
            "ci",
            "nombre1",
            "apellido1",
            "correo_e",
            "contrasena",
            "rol"
        ];

        foreach ($camposObligatorios as $campo) {
            if (!isset($data[$campo]) || trim($data[$campo]) === "") {
                http_response_code(400);

                return json_encode([
                    "error" => "Falta el campo: $campo"
                ]);
            }
        }

        $ci = trim($data["ci"]);
        $nombre1 = trim($data["nombre1"]);
        $nombre2 = isset($data["nombre2"]) && $data["nombre2"] !== "" ? trim($data["nombre2"]) : null;
        $apellido1 = trim($data["apellido1"]);
        $apellido2 = isset($data["apellido2"]) && $data["apellido2"] !== "" ? trim($data["apellido2"]) : null;
        $correo_e = trim($data["correo_e"]);
        $contrasena = password_hash($data["contrasena"], PASSWORD_DEFAULT);
        $rol = trim($data["rol"]);

        $telefono = isset($data["telefono"]) && $data["telefono"] !== ""
            ? trim($data["telefono"])
            : null;

        if (!preg_match('/^\d{8}$/', $ci)) {
            http_response_code(400);

            return json_encode([
                "error" => "La cédula debe tener 8 dígitos"
            ]);
        }

        if (!filter_var($correo_e, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);

            return json_encode([
                "error" => "Correo electrónico inválido"
            ]);
        }

        $rolesPermitidos = [
            "Vecino",
            "Operario",
            "Administrador",
            "Cuadrilla"
        ];

        if (!in_array($rol, $rolesPermitidos, true)) {
            http_response_code(400);

            return json_encode([
                "error" => "Rol inválido"
            ]);
        }

        try {
            $this->conn->begin_transaction();

            $sql = "INSERT INTO Usuario
                    (ci, nombre1, nombre2, apellido1, apellido2, contrasena, rol, correo_e)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->conn->prepare($sql);

            $stmt->bind_param(
                "ssssssss",
                $ci,
                $nombre1,
                $nombre2,
                $apellido1,
                $apellido2,
                $contrasena,
                $rol,
                $correo_e
            );

            $stmt->execute();

            if ($rol === "Vecino") {

                $sqlVecino = "INSERT INTO Vecino (CI) VALUES (?)";

                $stmtVecino = $this->conn->prepare($sqlVecino);
                $stmtVecino->bind_param("s", $ci);
                $stmtVecino->execute();

                if ($telefono !== null) {
                    $sqlTelefono = "INSERT INTO Vecino_Tel (CI, Tel) VALUES (?, ?)";

                    $stmtTelefono = $this->conn->prepare($sqlTelefono);
                    $stmtTelefono->bind_param("ss", $ci, $telefono);
                    $stmtTelefono->execute();
                }
            }

            elseif ($rol === "Operario") {

                $sqlOperario = "INSERT INTO Operario (CI) VALUES (?)";

                $stmtOperario = $this->conn->prepare($sqlOperario);
                $stmtOperario->bind_param("s", $ci);
                $stmtOperario->execute();
            }

            elseif ($rol === "Administrador") {

                $sqlAdministrador = "INSERT INTO Administrador (CI) VALUES (?)";

                $stmtAdministrador = $this->conn->prepare($sqlAdministrador);
                $stmtAdministrador->bind_param("s", $ci);
                $stmtAdministrador->execute();
            }

            elseif ($rol === "Cuadrilla") {

                $sqlCuadrilla = "INSERT INTO Cuadrilla (CI) VALUES (?)";

                $stmtCuadrilla = $this->conn->prepare($sqlCuadrilla);
                $stmtCuadrilla->bind_param("s", $ci);
                $stmtCuadrilla->execute();
            }

            $this->conn->commit();

            http_response_code(201);

            return json_encode([
                "success" => "Usuario registrado con éxito"
            ]);

        } catch (mysqli_sql_exception $e) {
            $this->conn->rollback();

            http_response_code(400);

            return json_encode([
                "error" => "Error en la base de datos",
                "detalle" => $e->getMessage()
            ]);
        }
    }

    public function updateUsuario($data){

        $ci = $data["ci"];

        $nombre1 = $data["nombre1"];
        $nombre2 = $data["nombre2"];
        $apellido1 = $data["apellido1"];
        $apellido2 = $data["apellido2"];
        $correo_e = $data["correo_e"];
        $rol = $data["rol"];

        $sql = "UPDATE Usuario SET
                nombre1='$nombre1',
                nombre2='$nombre2',
                apellido1='$apellido1',
                apellido2='$apellido2',
                correo_e='$correo_e',
                rol='$rol'
                WHERE CI='$ci'";

        if(mysqli_query($this->conn, $sql)){
            return json_encode([
                "mensaje" => "Usuario actualizado correctamente"
            ]);
        }

        return json_encode([
            "error" => mysqli_error($this->conn)
        ]);
    }    

    public function login($data) {
        if (!isset($data["correo_e"]) || !isset($data["contrasena"])) {
            http_response_code(400);

            return [
                "error" => "Datos incompletos"
            ];
        }

        $correo_e = trim($data["correo_e"]);
        $contrasena = $data["contrasena"];

        $sql = "SELECT *
                FROM Usuario
                WHERE correo_e = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $correo_e);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            http_response_code(401);
        
            return [
                "error" => "Correo o contraseña incorrectos"
            ];
        }

        $usuario = $result->fetch_assoc();

        if (!password_verify($contrasena, $usuario["contrasena"])) {
            http_response_code(401);

            return [
                "error" => "Correo o contraseña incorrectos"
            ];
        }

        return [
            "success" => [
                "ci" => $usuario["ci"],
                "nombre1" => $usuario["nombre1"],
                "nombre2" => $usuario["nombre2"],
                "apellido1" => $usuario["apellido1"],
                "apellido2" => $usuario["apellido2"],
                "correo_e" => $usuario["correo_e"],
                "rol" => $usuario["rol"]
            ]
        ];
    }

    public function deleteUsuario($data) {
        if (!isset($data["ci"])) {
            http_response_code(400);

            return json_encode([
                "error" => "Debe proporcionar una cédula"
            ]);
        }

        $ci = $data["ci"];

        try {
            $sql = "DELETE FROM Usuario WHERE ci = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $ci);
            $stmt->execute();

            return json_encode([
                "success" => "Usuario eliminado"
            ]);

        } catch (mysqli_sql_exception $e) {
            http_response_code(400);
            return json_encode([
                "error" => "No se pudo eliminar el usuario"
            ]);
        }
    }
}
?>
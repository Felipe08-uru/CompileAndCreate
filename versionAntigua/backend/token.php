<?php
class Token {
    private $conn;
    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function crearToken($ci) {
        $token = bin2hex(random_bytes(32));
        $fechaCreado = date("Y-m-d H:i:s");
        $fechaVencimiento = date("Y-m-d H:i:s", strtotime("+12 hours"));
        $sql = "INSERT INTO Access_Token
                (token, CI, fecha_creado, fecha_vencimiento)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "ssss",
            $token,
            $ci,
            $fechaCreado,
            $fechaVencimiento
        );
        if ($stmt->execute()) {
            return $token;
        }
        return false;
    }

    public function validarToken($token) {
        $sql = "SELECT CI
                FROM Access_Token
                WHERE token = ?
                AND fecha_vencimiento > NOW()";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($resultado->num_rows === 0) {
            return false;
        }
        return $resultado->fetch_assoc()["CI"];
    }

    public function eliminarToken($token) {
        $sql = "DELETE FROM Access_Token
                WHERE token = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $token);
        return $stmt->execute();
    }
}
?>
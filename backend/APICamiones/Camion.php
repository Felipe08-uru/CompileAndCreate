<?php

class Camion{

    private $conn;
    private $table = "camion";

    // Un camión solo puede ser de uno de estos dos tipos. El tipo define
    // qué rutas puede hacer (ver Asignacion y Ruta).
    const TIPOS_PERMITIDOS = ["Residuos mezclados", "Reciclables"];

    const ESTADOS_PERMITIDOS = ["Disponible", "En servicio", "Fuera de servicio"];

    public function __construct($db){
        $this->conn = $db;
    }

    /**
     * Lee un campo del JSON aceptando tanto "matricula" como "Matricula".
     */
    private function leerCampo($data, $nombre){
        if(isset($data[$nombre])){
            return $data[$nombre];
        }

        $capitalizado = ucfirst($nombre);

        if(isset($data[$capitalizado])){
            return $data[$capitalizado];
        }

        return null;
    }

    public function getAllCamiones(){
        $sql = "SELECT Matricula, Tipo, Estado FROM $this->table ORDER BY Matricula";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getCamionByMatricula($matricula){
        $sql = "SELECT Matricula, Tipo, Estado FROM $this->table WHERE Matricula = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $matricula);
        $stmt->execute();

        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }

    public function existeCamion($matricula){
        $sql = "SELECT Matricula FROM $this->table WHERE Matricula = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $matricula);
        $stmt->execute();

        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }

    public function addCamion($data){
        $matricula = $this->leerCampo($data, "matricula");

        if($matricula === null || trim($matricula) === ""){
            return json_encode([
                "error" => "Debe indicar la matrícula del camión"
            ]);
        }

        $matricula = trim($matricula);

        if(strlen($matricula) > 10){
            return json_encode([
                "error" => "La matrícula no puede tener más de 10 caracteres"
            ]);
        }

        $tipo = $this->leerCampo($data, "tipo");

        if($tipo === null || !in_array(trim($tipo), self::TIPOS_PERMITIDOS, true)){
            return json_encode([
                "error" => "El tipo de camión debe ser: Residuos mezclados o Reciclables"
            ]);
        }

        $tipo = trim($tipo);

        $estado = $this->leerCampo($data, "estado");
        $estado = ($estado === null || trim($estado) === "") ? "Disponible" : trim($estado);

        if(!in_array($estado, self::ESTADOS_PERMITIDOS, true)){
            return json_encode([
                "error" => "El estado del camión es inválido"
            ]);
        }

        if($this->existeCamion($matricula)){
            return json_encode([
                "error" => "Ya existe un camión con esa matrícula"
            ]);
        }

        $sql = "INSERT INTO $this->table (Matricula, Tipo, Estado) VALUES (?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $matricula, $tipo, $estado);

        if($stmt->execute()){
            return json_encode([
                "mensaje" => "Camión agregado"
            ]);
        }

        return json_encode([
            "error" => "No se pudo registrar el camión"
        ]);
    }

    public function updateCamion($data){
        $matricula = $this->leerCampo($data, "matricula");

        if($matricula === null || trim($matricula) === ""){
            http_response_code(400);
            return json_encode(["error" => "Debe indicar la matrícula del camión"]);
        }

        $matricula = trim($matricula);
        $actual = $this->getCamionByMatricula($matricula);

        if(!$actual){
            http_response_code(404);
            return json_encode(["error" => "Camión no encontrado"]);
        }

        $tipo = $this->leerCampo($data, "tipo");
        $tipo = $tipo === null ? $actual["Tipo"] : trim($tipo);

        if(!in_array($tipo, self::TIPOS_PERMITIDOS, true)){
            http_response_code(400);
            return json_encode(["error" => "El tipo de camión debe ser: Residuos mezclados o Reciclables"]);
        }

        $estado = $this->leerCampo($data, "estado");
        $estado = $estado === null || trim($estado) === "" ? $actual["Estado"] : trim($estado);

        if(!in_array($estado, self::ESTADOS_PERMITIDOS, true)){
            http_response_code(400);
            return json_encode(["error" => "El estado del camión es inválido"]);
        }

        // No se cambia el tipo de un camión que tiene una ruta sin finalizar.
        if($tipo !== $actual["Tipo"]){
            $stmtRuta = $this->conn->prepare(
                "SELECT Id_Ruta FROM ruta WHERE Matricula = ? AND Estado <> 'Finalizada' LIMIT 1"
            );
            $stmtRuta->bind_param("s", $matricula);
            $stmtRuta->execute();
            $ruta = $stmtRuta->get_result()->fetch_assoc();

            if($ruta){
                http_response_code(409);
                return json_encode([
                    "error" => "No se puede cambiar el tipo: el camión tiene la ruta #" . $ruta["Id_Ruta"] . " sin finalizar"
                ]);
            }
        }

        $stmt = $this->conn->prepare("UPDATE $this->table SET Tipo = ?, Estado = ? WHERE Matricula = ?");
        $stmt->bind_param("sss", $tipo, $estado, $matricula);

        if(!$stmt->execute()){
            http_response_code(500);
            return json_encode(["error" => "No se pudo actualizar el camión"]);
        }

        return json_encode(["mensaje" => "Camión actualizado"]);
    }

    public function deleteCamion($data){
        $matricula = $this->leerCampo($data, "matricula");

        if($matricula === null || trim($matricula) === ""){
            return json_encode([
                "error" => "Debe indicar la matrícula del camión"
            ]);
        }

        try{
            $sql = "DELETE FROM $this->table WHERE Matricula = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $matricula);
            $stmt->execute();

            if($stmt->affected_rows > 0){
                return json_encode([
                    "mensaje" => "Camión eliminado"
                ]);
            }

            return json_encode([
                "error" => "Camión no encontrado"
            ]);

        }catch(mysqli_sql_exception $e){
            // El camión tiene registros de llegadas a un centro asociados.
            return json_encode([
                "error" => "No se puede eliminar: el camión tiene registros asociados"
            ]);
        }
    }

}

?>

<?php

class Camion{

    private $conn;
    private $table = "Camion";

    public function __construct($db){
        $this->conn = $db;
    }

    public function getAllCamiones(){
        $sql = "SELECT Matricula, Tipo, Estado FROM $this->table";

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
        if(!isset($data["matricula"]) || trim($data["matricula"]) === ""){
            return json_encode([
                "error" => "Debe indicar la matrícula del camión"
            ]);
        }

        $matricula = trim($data["matricula"]);
        $tipo = isset($data["tipo"]) ? $data["tipo"] : "Residuos mezclados";
        $estado = isset($data["estado"]) ? $data["estado"] : "Disponible";

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

    public function deleteCamion($data){
        if(!isset($data["matricula"])){
            return json_encode([
                "error" => "Debe indicar la matrícula del camión"
            ]);
        }

        $matricula = $data["matricula"];

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
    }

}

?>

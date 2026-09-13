<?php

class Centro {

    private $conn;
    private $table = "Centro";

    public function __construct($db){
        $this->conn = $db;
    }

    public function getAllCentros(){
        $sql = "SELECT c.ID, c.Servicio, c.Capacidad, c.ContAlmacenados, c.CamAlmacenados
                FROM $this->table c
                INNER JOIN CentroDeAcopio cda ON cda.ID = c.ID
                ORDER BY c.ID";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function addCentro($data){
        if(!isset($data["servicio"]) || trim($data["servicio"]) === ""){
            return json_encode([
                "error" => "Debe indicar el servicio del centro"
            ]);
        }

        $servicio = trim($data["servicio"]);
        $capacidad = isset($data["capacidad"]) && $data["capacidad"] !== "" ? intval($data["capacidad"]) : 0;
        $contAlmacenados = isset($data["cont_almacenados"]) && $data["cont_almacenados"] !== ""
            ? intval($data["cont_almacenados"])
            : 0;
        $camAlmacenados = isset($data["cam_almacenados"]) && $data["cam_almacenados"] !== ""
            ? intval($data["cam_almacenados"])
            : 0;

        $sqlId = "SELECT COALESCE(MAX(ID), 0) + 1 AS nuevo_id FROM $this->table";
        $resultId = $this->conn->query($sqlId);
        $filaId = $resultId->fetch_assoc();
        $id = intval($filaId["nuevo_id"]);

        $sql = "INSERT INTO $this->table (ID, Servicio, ContAlmacenados, Capacidad, CamAlmacenados)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isiii", $id, $servicio, $contAlmacenados, $capacidad, $camAlmacenados);

        if(!$stmt->execute()){
            return json_encode([
                "error" => "No se pudo registrar el centro"
            ]);
        }

        $sqlSubtipo = "INSERT INTO CentroDeAcopio (ID) VALUES (?)";
        $stmtSubtipo = $this->conn->prepare($sqlSubtipo);
        $stmtSubtipo->bind_param("i", $id);
        $stmtSubtipo->execute();

        return json_encode([
            "mensaje" => "Centro de acopio agregado correctamente",
            "id_centro" => $id
        ]);
    }

    public function deleteCentro($id){
        try{
            $sqlSubtipo = "DELETE FROM CentroDeAcopio WHERE ID = ?";
            $stmtSubtipo = $this->conn->prepare($sqlSubtipo);
            $stmtSubtipo->bind_param("i", $id);
            $stmtSubtipo->execute();

            $sql = "DELETE FROM $this->table WHERE ID = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if($stmt->affected_rows > 0){
                return json_encode([
                    "mensaje" => "Centro eliminado correctamente"
                ]);
            }

            return json_encode([
                "error" => "No se encontró el centro"
            ]);
        }catch(mysqli_sql_exception $e){
            return json_encode([
                "error" => "No se puede eliminar: el centro tiene maquinaria o registros de camiones asociados"
            ]);
        }
    }
}

?>

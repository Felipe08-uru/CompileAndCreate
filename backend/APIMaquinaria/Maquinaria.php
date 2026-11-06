<?php

class Maquinaria {

    private $conn;
    private $table = "maquinaria";

    public function __construct($db){
        $this->conn = $db;
    }


    public function getAllMaquinaria($idCentro = null){
        if($idCentro !== null){
            $sql = "SELECT Id_Maquinaria, ID, Nombre, Cantidad, Estado
                    FROM $this->table
                    WHERE ID = ?
                    ORDER BY Id_Maquinaria DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $idCentro);
        }else{
            $sql = "SELECT Id_Maquinaria, ID, Nombre, Cantidad, Estado
                    FROM $this->table
                    ORDER BY Id_Maquinaria DESC";

            $stmt = $this->conn->prepare($sql);
        }

        $stmt->execute();

        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function addMaquinaria($data){
        if(
            !isset($data["id_centro"]) ||
            !isset($data["nombre"]) ||
            trim($data["nombre"]) === ""
        ){
            return json_encode([
                "error" => "Debe indicar el centro y el nombre de la máquina"
            ]);
        }

        $idCentro = intval($data["id_centro"]);
        $nombre = trim($data["nombre"]);
        $cantidad = isset($data["cantidad"]) ? intval($data["cantidad"]) : 1;
        $estado = isset($data["estado"]) && $data["estado"] !== "" ? $data["estado"] : "Operativa";

        if($cantidad < 1){
            $cantidad = 1;
        }

        $sql = "INSERT INTO $this->table (ID, Nombre, Cantidad, Estado) VALUES (?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isis", $idCentro, $nombre, $cantidad, $estado);

        if($stmt->execute()){
            return json_encode([
                "mensaje" => "Máquina agregada correctamente",
                "id_maquinaria" => $this->conn->insert_id
            ]);
        }

        return json_encode([
            "error" => "No se pudo agregar la máquina"
        ]);
    }

    public function updateMaquinaria($data){
        if(
            !isset($data["id_maquinaria"]) ||
            !isset($data["nombre"]) ||
            trim($data["nombre"]) === ""
        ){
            return json_encode([
                "error" => "Datos incompletos para actualizar la máquina"
            ]);
        }

        $idMaquinaria = intval($data["id_maquinaria"]);
        $nombre = trim($data["nombre"]);
        $cantidad = isset($data["cantidad"]) ? intval($data["cantidad"]) : 1;
        $estado = isset($data["estado"]) && $data["estado"] !== "" ? $data["estado"] : "Operativa";

        if($cantidad < 1){
            $cantidad = 1;
        }

        $sql = "UPDATE $this->table
                SET Nombre = ?, Cantidad = ?, Estado = ?
                WHERE Id_Maquinaria = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sisi", $nombre, $cantidad, $estado, $idMaquinaria);
        $stmt->execute();

        if($stmt->affected_rows > 0){
            return json_encode([
                "mensaje" => "Máquina actualizada correctamente"
            ]);
        }

        return json_encode([
            "error" => "No se encontró la máquina o no hubo cambios"
        ]);
    }

    public function deleteMaquinaria($idMaquinaria){
        $sql = "DELETE FROM $this->table WHERE Id_Maquinaria = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $idMaquinaria);
        $stmt->execute();

        if($stmt->affected_rows > 0){
            return json_encode([
                "mensaje" => "Máquina eliminada correctamente"
            ]);
        }

        return json_encode([
            "error" => "No se encontró la máquina"
        ]);
    }
}

?>

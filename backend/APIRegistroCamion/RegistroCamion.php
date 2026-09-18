<?php

class RegistroCamion {

    private $conn;
    private $table = "registro_camion";

    public function __construct($db){
        $this->conn = $db;
    }

    public function getAllRegistros(){
        $sql = "SELECT r.Id_Registro, r.Matricula, r.CI, r.ID, r.Fecha,
                       r.Tipo_Carga, r.Cantidad_Carga,
                       c.Tipo AS Tipo_Camion, ce.Servicio AS Servicio_Centro
                FROM $this->table r
                INNER JOIN Camion c ON c.Matricula = r.Matricula
                INNER JOIN Centro ce ON ce.ID = r.ID
                ORDER BY r.Fecha DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getRegistrosPorOperario($ci){
        $sql = "SELECT r.Id_Registro, r.Matricula, r.CI, r.ID, r.Fecha,
                       r.Tipo_Carga, r.Cantidad_Carga,
                       c.Tipo AS Tipo_Camion, ce.Servicio AS Servicio_Centro
                FROM $this->table r
                INNER JOIN Camion c ON c.Matricula = r.Matricula
                INNER JOIN Centro ce ON ce.ID = r.ID
                WHERE r.CI = ?
                ORDER BY r.Fecha DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $ci);
        $stmt->execute();

        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function addRegistro($data, $ci){
        if(
            !isset($data["matricula"]) || trim($data["matricula"]) === "" ||
            !isset($data["id_centro"]) ||
            !isset($data["tipo_carga"]) || trim($data["tipo_carga"]) === ""
        ){
            return json_encode([
                "error" => "Debe indicar la matrícula, el centro y el tipo de carga"
            ]);
        }

        $matricula = trim($data["matricula"]);
        $idCentro = intval($data["id_centro"]);
        $tipoCarga = trim($data["tipo_carga"]);
        $cantidadCarga = isset($data["cantidad_carga"]) && $data["cantidad_carga"] !== ""
            ? floatval($data["cantidad_carga"])
            : null;

        $sqlCamion = "SELECT Matricula FROM Camion WHERE Matricula = ?";
        $stmtCamion = $this->conn->prepare($sqlCamion);
        $stmtCamion->bind_param("s", $matricula);
        $stmtCamion->execute();

        if($stmtCamion->get_result()->num_rows === 0){
            return json_encode([
                "error" => "No existe un camión registrado con esa matrícula"
            ]);
        }

        $sql = "INSERT INTO $this->table (Matricula, CI, ID, Tipo_Carga, Cantidad_Carga)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssisd", $matricula, $ci, $idCentro, $tipoCarga, $cantidadCarga);

        if($stmt->execute()){
            return json_encode([
                "mensaje" => "Llegada del camión registrada correctamente",
                "id_registro" => $this->conn->insert_id
            ]);
        }

        return json_encode([
            "error" => "No se pudo registrar la llegada del camión"
        ]);
    }

    public function updateRegistro($data, $ci = null){
        if(!isset($data["id_registro"])){
            return json_encode([
                "error" => "Debe indicar el registro a modificar"
            ]);
        }

        $idRegistro = intval($data["id_registro"]);
        $tipoCarga = isset($data["tipo_carga"]) ? trim($data["tipo_carga"]) : "";
        $cantidadCarga = isset($data["cantidad_carga"]) && $data["cantidad_carga"] !== ""
            ? floatval($data["cantidad_carga"])
            : null;

        if($tipoCarga === ""){
            return json_encode([
                "error" => "Debe indicar el tipo de carga"
            ]);
        }

        if($ci !== null){
            $sql = "UPDATE $this->table
                    SET Tipo_Carga = ?, Cantidad_Carga = ?
                    WHERE Id_Registro = ? AND CI = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sdis", $tipoCarga, $cantidadCarga, $idRegistro, $ci);
        }else{
            $sql = "UPDATE $this->table
                    SET Tipo_Carga = ?, Cantidad_Carga = ?
                    WHERE Id_Registro = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sdi", $tipoCarga, $cantidadCarga, $idRegistro);
        }

        $stmt->execute();

        if($stmt->affected_rows > 0){
            return json_encode([
                "mensaje" => "Registro actualizado correctamente"
            ]);
        }

        return json_encode([
            "error" => "No se encontró el registro o no le pertenece"
        ]);
    }

    public function deleteRegistro($idRegistro, $ci = null){
        if($ci !== null){
            $sql = "DELETE FROM $this->table WHERE Id_Registro = ? AND CI = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("is", $idRegistro, $ci);
        }else{
            $sql = "DELETE FROM $this->table WHERE Id_Registro = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $idRegistro);
        }

        $stmt->execute();

        if($stmt->affected_rows > 0){
            return json_encode([
                "mensaje" => "Registro eliminado correctamente"
            ]);
        }

        return json_encode([
            "error" => "No se encontró el registro o no le pertenece"
        ]);
    }
}

?>

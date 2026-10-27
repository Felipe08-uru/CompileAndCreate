<?php
require_once __DIR__ . "/../APIPoligonos/Poligono.php";

class Contenedor{

    private $conn;
    private $table = "contenedor";

    // Tipos de residuo que puede tener un contenedor (coinciden con los tipos de camión/ruta).
    const TIPOS_PERMITIDOS = ["Residuos mezclados", "Reciclables"];

    public function __construct($db){
        $this->conn = $db;
    }

    public function getAllContenedores(){
        $sql = "SELECT * FROM $this->table ORDER BY Id_Contenedor";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getContenedorById($id){
        $sql = "SELECT * FROM $this->table WHERE Id_Contenedor = ?";

        $id = intval($id);

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function addContenedor($data){

        if(
            !isset($data["tipo"]) ||
            !isset($data["latitud"]) ||
            !isset($data["longitud"]) ||
            !is_numeric($data["latitud"]) ||
            !is_numeric($data["longitud"])
        ){
            return json_encode([
                "error" => "Faltan datos del contenedor"
            ]);
        }

        $tipo = trim($data["tipo"]);

        if(!in_array($tipo, self::TIPOS_PERMITIDOS, true)){
            return json_encode([
                "error" => "El tipo de contenedor debe ser: Residuos mezclados o Reciclables"
            ]);
        }

        $latitud = floatval($data["latitud"]);
        $longitud = floatval($data["longitud"]);

        if($latitud < -90 || $latitud > 90 || $longitud < -180 || $longitud > 180){
            return json_encode([
                "error" => "La ubicación del contenedor es inválida"
            ]);
        }

        $sqlId = "SELECT COALESCE(MAX(Id_Contenedor), 0) + 1 AS nuevo_id FROM $this->table";

        $resultId = $this->conn->query($sqlId);
        $fila = $resultId->fetch_assoc();

        $id = intval($fila["nuevo_id"]);
        $estado = "Nuevo";

        // El contenedor pertenece a la zona en la que está parado (si hay una).
        $poligonoObj = new Poligono($this->conn);
        $idPoligono = $poligonoObj->buscarPoligonoQueContiene($latitud, $longitud);

        $sql = "INSERT INTO $this->table
                (Id_Contenedor, Tipo, Estado, Latitud, Longitud, Id_Poligono)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issddi", $id, $tipo, $estado, $latitud, $longitud, $idPoligono);

        try{
            $stmt->execute();
        }catch(mysqli_sql_exception $e){
            return json_encode([
                "error" => "No se pudo registrar el contenedor"
            ]);
        }

        return json_encode([
            "mensaje" => "Contenedor agregado",
            "id_contenedor" => $id,
            "id_poligono" => $idPoligono
        ]);
    }

    public function deleteContenedor($data){

        if(!isset($data["id_contenedor"])){
            return json_encode([
                "error" => "Debe indicar el contenedor a eliminar"
            ]);
        }

        $id = intval($data["id_contenedor"]);

        try{
            $sql = "DELETE FROM $this->table WHERE Id_Contenedor = ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if($stmt->affected_rows === 0){
                return json_encode([
                    "error" => "No se encontró el contenedor"
                ]);
            }

            return json_encode([
                "mensaje" => "Contenedor eliminado"
            ]);

        }catch(mysqli_sql_exception $e){
            // Tiene incidencias asociadas u otros registros que lo referencian.
            return json_encode([
                "error" => "No se puede eliminar: el contenedor tiene incidencias u otros registros asociados"
            ]);
        }
    }
}

?>

<?php
require_once __DIR__ . "/../Geo/Geometria.php";

class Centro {

    private $conn;
    private $table = "centro";

    public function __construct($db){
        $this->conn = $db;
    }

    public function getAllCentros(){
        $sql = "SELECT c.ID, c.Servicio, c.Capacidad, c.ContAlmacenados, c.CamAlmacenados, c.Latitud, c.Longitud
                FROM $this->table c
                INNER JOIN centrodeacopio cda ON cda.ID = c.ID
                ORDER BY c.ID";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Centro de acopio con ubicación más cercano a un punto (distancia en línea recta).
     * Devuelve null si ningún centro tiene ubicación.
     */
    public function getMasCercano($lat, $lng){
        $masCercano = null;
        $mejor = null;

        foreach($this->getAllCentros() as $c){
            if($c["Latitud"] === null || $c["Longitud"] === null){
                continue;
            }

            $d = Geometria::distanciaMetros($lat, $lng, floatval($c["Latitud"]), floatval($c["Longitud"]));

            if($mejor === null || $d < $mejor){
                $mejor = $d;
                $masCercano = [
                    "ID" => intval($c["ID"]),
                    "Servicio" => $c["Servicio"],
                    "Latitud" => floatval($c["Latitud"]),
                    "Longitud" => floatval($c["Longitud"]),
                    "Distancia_m" => round($d)
                ];
            }
        }

        return $masCercano;
    }

    /**
     * Operarios con el centro de acopio al que están asociados (null si no tienen).
     */
    public function getOperarios(){
        $sql = "SELECT u.ci AS CI, u.nombre1 AS Nombre1, u.apellido1 AS Apellido1,
                       oc.ID AS Id_Centro, c.Servicio AS Servicio_Centro
                FROM operario o
                INNER JOIN usuario u ON u.ci = o.CI
                LEFT JOIN operario_centro oc ON oc.CI = o.CI
                LEFT JOIN centro c ON c.ID = oc.ID
                ORDER BY u.apellido1, u.nombre1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Centro asociado a un operario, o null si no tiene.
     */
    public function getCentroDeOperario($ci){
        $sql = "SELECT c.ID, c.Servicio, c.Latitud, c.Longitud
                FROM operario_centro oc
                INNER JOIN centro c ON c.ID = oc.ID
                WHERE oc.CI = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $ci);
        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();

        return $fila ? $fila : null;
    }

    /**
     * Asocia un operario con un centro. Con id_centro vacío, quita la asociación.
     */
    public function asociarOperario($data){
        if(!isset($data["ci"]) || trim($data["ci"]) === ""){
            http_response_code(400);
            return json_encode(["error" => "Debe indicar el operario"]);
        }

        $ci = trim($data["ci"]);

        $stmt = $this->conn->prepare("SELECT CI FROM operario WHERE CI = ?");
        $stmt->bind_param("s", $ci);
        $stmt->execute();

        if($stmt->get_result()->num_rows === 0){
            http_response_code(404);
            return json_encode(["error" => "El operario no existe"]);
        }

        if(!isset($data["id_centro"]) || $data["id_centro"] === "" || $data["id_centro"] === null){
            $stmt = $this->conn->prepare("DELETE FROM operario_centro WHERE CI = ?");
            $stmt->bind_param("s", $ci);
            $stmt->execute();

            return json_encode(["mensaje" => "Se quitó el centro del operario"]);
        }

        $idCentro = intval($data["id_centro"]);

        $stmt = $this->conn->prepare("SELECT ID FROM centrodeacopio WHERE ID = ?");
        $stmt->bind_param("i", $idCentro);
        $stmt->execute();

        if($stmt->get_result()->num_rows === 0){
            http_response_code(404);
            return json_encode(["error" => "El centro de acopio no existe"]);
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO operario_centro (CI, ID) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE ID = VALUES(ID)"
        );
        $stmt->bind_param("si", $ci, $idCentro);

        if(!$stmt->execute()){
            http_response_code(500);
            return json_encode(["error" => "No se pudo asociar el operario"]);
        }

        return json_encode(["mensaje" => "Operario asociado al centro correctamente"]);
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

        $latitud = null;
        $longitud = null;
        $tieneLat = isset($data["latitud"]) && $data["latitud"] !== "";
        $tieneLng = isset($data["longitud"]) && $data["longitud"] !== "";

        if($tieneLat !== $tieneLng){
            return json_encode([
                "error" => "Debe indicar la latitud y la longitud del centro"
            ]);
        }

        if($tieneLat){
            if(!is_numeric($data["latitud"]) || !is_numeric($data["longitud"]) ||
               $data["latitud"] < -90 || $data["latitud"] > 90 ||
               $data["longitud"] < -180 || $data["longitud"] > 180){
                return json_encode([
                    "error" => "La ubicación del centro no es válida"
                ]);
            }

            $latitud = floatval($data["latitud"]);
            $longitud = floatval($data["longitud"]);
        }

        $sqlId = "SELECT COALESCE(MAX(ID), 0) + 1 AS nuevo_id FROM $this->table";
        $resultId = $this->conn->query($sqlId);
        $filaId = $resultId->fetch_assoc();
        $id = intval($filaId["nuevo_id"]);

        $sql = "INSERT INTO $this->table (ID, Servicio, ContAlmacenados, Capacidad, CamAlmacenados, Latitud, Longitud)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isiiidd", $id, $servicio, $contAlmacenados, $capacidad, $camAlmacenados, $latitud, $longitud);

        if(!$stmt->execute()){
            return json_encode([
                "error" => "No se pudo registrar el centro"
            ]);
        }

        $sqlSubtipo = "INSERT INTO centrodeacopio (ID) VALUES (?)";
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
            $sqlSubtipo = "DELETE FROM centrodeacopio WHERE ID = ?";
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

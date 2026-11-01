<?php
require_once __DIR__ . "/../Geo/Geometria.php";

class Poligono{

    private $conn;

    public function __construct($db){
        $this->conn = $db;
    }

    /**
     * Devuelve todos los vértices agrupados por polígono: [id => [vertices...]].
     */
    private function cargarVertices(){
        $sql = "SELECT Id_Poligono, Orden, Latitud, Longitud
                FROM poligono_vertices
                ORDER BY Id_Poligono, Orden";

        $result = $this->conn->query($sql);

        $mapa = [];

        while($fila = $result->fetch_assoc()){
            $mapa[intval($fila["Id_Poligono"])][] = [
                "orden" => intval($fila["Orden"]),
                "latitud" => floatval($fila["Latitud"]),
                "longitud" => floatval($fila["Longitud"])
            ];
        }

        return $mapa;
    }

    public function getAllPoligonos(){

        $vertices = $this->cargarVertices();

        $sql = "SELECT p.Id_Poligono, COUNT(c.Id_Contenedor) AS total_contenedores
                FROM poligono p
                LEFT JOIN contenedor c ON c.Id_Poligono = p.Id_Poligono
                GROUP BY p.Id_Poligono
                ORDER BY p.Id_Poligono";

        $result = $this->conn->query($sql);

        $poligonos = [];

        while($poligono = $result->fetch_assoc()){

            $id = intval($poligono["Id_Poligono"]);

            $poligonos[] = [
                "id_poligono" => $id,
                "total_contenedores" => intval($poligono["total_contenedores"]),
                "vertices" => isset($vertices[$id]) ? $vertices[$id] : []
            ];
        }

        return $poligonos;
    }

    public function getPoligonoPorId($id){

        $id = intval($id);
        $vertices = $this->cargarVertices();

        if(!isset($vertices[$id])){
            return null;
        }

        return [
            "id_poligono" => $id,
            "vertices" => $vertices[$id]
        ];
    }

    /**
     * Busca la zona que contiene un punto. Si lo contienen varias gana la de
     * menor ID. Devuelve null si el punto no está dentro de ninguna zona.
     */
    public function buscarPoligonoQueContiene($latitud, $longitud){

        $vertices = $this->cargarVertices();

        foreach($vertices as $idPoligono => $lista){
            if(Geometria::puntoEnPoligono($latitud, $longitud, $lista)){
                return intval($idPoligono);
            }
        }

        return null;
    }

    /**
     * Relaciona con una zona a todos los contenedores que todavía no tienen
     * una y que caen dentro de algún polígono. Devuelve cuántos se relacionaron.
     */
    public function asignarContenedoresSinZona(){

        $vertices = $this->cargarVertices();

        if(count($vertices) === 0){
            return 0;
        }

        $sql = "SELECT Id_Contenedor, Latitud, Longitud
                FROM contenedor
                WHERE Id_Poligono IS NULL
                AND Latitud IS NOT NULL
                AND Longitud IS NOT NULL";

        $result = $this->conn->query($sql);
        $pendientes = $result->fetch_all(MYSQLI_ASSOC);

        if(count($pendientes) === 0){
            return 0;
        }

        $stmt = $this->conn->prepare(
            "UPDATE contenedor SET Id_Poligono = ? WHERE Id_Contenedor = ?"
        );

        $asignados = 0;

        foreach($pendientes as $contenedor){

            $latitud = floatval($contenedor["Latitud"]);
            $longitud = floatval($contenedor["Longitud"]);

            foreach($vertices as $idPoligono => $lista){

                if(Geometria::puntoEnPoligono($latitud, $longitud, $lista)){

                    $idZona = intval($idPoligono);
                    $idContenedor = intval($contenedor["Id_Contenedor"]);

                    $stmt->bind_param("ii", $idZona, $idContenedor);
                    $stmt->execute();

                    $asignados++;
                    break;
                }
            }
        }

        return $asignados;
    }

    public function addPoligono($data){

        if(
            !isset($data["vertices"]) ||
            !is_array($data["vertices"]) ||
            count($data["vertices"]) < 3
        ){

            return json_encode([
                "error" => "El polígono debe tener al menos 3 puntos"
            ]);
        }

        foreach($data["vertices"] as $vertice){
            if(
                !is_array($vertice) ||
                !isset($vertice["latitud"]) ||
                !isset($vertice["longitud"]) ||
                !is_numeric($vertice["latitud"]) ||
                !is_numeric($vertice["longitud"])
            ){
                return json_encode([
                    "error" => "Los vértices del polígono son inválidos"
                ]);
            }
        }

        $sqlId = "SELECT COALESCE(MAX(Id_Poligono), 0) + 1 AS nuevo_id FROM poligono";

        $resultId = $this->conn->query($sqlId);
        $fila = $resultId->fetch_assoc();

        $id = intval($fila["nuevo_id"]);

        $this->conn->begin_transaction();

        try{
            $stmt = $this->conn->prepare("INSERT INTO poligono (Id_Poligono) VALUES (?)");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmtVertice = $this->conn->prepare(
                "INSERT INTO poligono_vertices (Id_Poligono, Orden, Latitud, Longitud)
                 VALUES (?, ?, ?, ?)"
            );

            $orden = 1;

            foreach($data["vertices"] as $vertice){

                $latitud = floatval($vertice["latitud"]);
                $longitud = floatval($vertice["longitud"]);

                $stmtVertice->bind_param("iidd", $id, $orden, $latitud, $longitud);
                $stmtVertice->execute();

                $orden++;
            }

            // Los contenedores que estaban sin zona y quedan dentro del nuevo
            // polígono pasan a pertenecer a él.
            $asignados = $this->asignarContenedoresSinZona();

            $this->conn->commit();

            return json_encode([
                "mensaje" => "Zona guardada correctamente",
                "id_poligono" => $id,
                "contenedores_asignados" => $asignados
            ]);

        }catch(Exception $e){

            $this->conn->rollback();

            return json_encode([
                "error" => $e->getMessage()
            ]);
        }
    }

    public function deletePoligono($id){

        $id = intval($id);

        $this->conn->begin_transaction();

        try{

            $stmtExiste = $this->conn->prepare("SELECT Id_Poligono FROM poligono WHERE Id_Poligono = ?");
            $stmtExiste->bind_param("i", $id);
            $stmtExiste->execute();

            if($stmtExiste->get_result()->num_rows === 0){
                throw new Exception("No existe una zona con ese ID");
            }

            // Los contenedores quedan sin zona.
            $stmt = $this->conn->prepare("UPDATE contenedor SET Id_Poligono = NULL WHERE Id_Poligono = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // Los camiones dejan de estar relacionados con la zona y se
            // eliminan las rutas que le pertenecían (con sus paradas).
            $stmt = $this->conn->prepare("DELETE FROM recoge WHERE Id_Poligono = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $this->conn->prepare("DELETE FROM ruta WHERE Id_Poligono = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $this->conn->prepare("DELETE FROM poligono_vertices WHERE Id_Poligono = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $this->conn->prepare("DELETE FROM poligono WHERE Id_Poligono = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // Si otra zona también abarca a esos contenedores, pasan a ella.
            $this->asignarContenedoresSinZona();

            $this->conn->commit();

            return json_encode([
                "mensaje" => "Zona eliminada correctamente"
            ]);

        }catch(Exception $e){

            $this->conn->rollback();

            return json_encode([
                "error" => $e->getMessage()
            ]);
        }
    }
}

?>

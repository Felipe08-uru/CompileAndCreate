<?php

class Poligono{

    private $conn;

    public function __construct($db){
        $this->conn = $db;
    }

    public function getAllPoligonos(){

        $poligonos = [];

        $sql = "SELECT * FROM Poligono";
        $result = mysqli_query($this->conn, $sql);

        while($poligono = mysqli_fetch_assoc($result)){

            $id = $poligono["Id_Poligono"];

            $sqlVertices = "
                SELECT Orden, Latitud, Longitud
                FROM Poligono_Vertices
                WHERE Id_Poligono = $id
                ORDER BY Orden
            ";

            $resultVertices = mysqli_query(
                $this->conn,
                $sqlVertices
            );

            $vertices = [];

            while($vertice = mysqli_fetch_assoc($resultVertices)){

                $vertices[] = [
                    "orden" => intval($vertice["Orden"]),
                    "latitud" => floatval($vertice["Latitud"]),
                    "longitud" => floatval($vertice["Longitud"])
                ];
            }

            $poligonos[] = [
                "id_poligono" => intval($id),
                "vertices" => $vertices
            ];
        }

        return $poligonos;
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

        $sqlId = "
            SELECT COALESCE(MAX(Id_Poligono), 0) + 1 AS nuevo_id
            FROM Poligono
        ";

        $resultId = mysqli_query($this->conn, $sqlId);
        $fila = mysqli_fetch_assoc($resultId);

        $id = intval($fila["nuevo_id"]);

        mysqli_begin_transaction($this->conn);

        try{
            $sql = "
                INSERT INTO Poligono (Id_Poligono)
                VALUES ($id)
            ";

            if(!mysqli_query($this->conn, $sql)){
                throw new Exception(mysqli_error($this->conn));
            }

            $orden = 1;

            foreach($data["vertices"] as $vertice){

                $latitud = floatval($vertice["latitud"]);
                $longitud = floatval($vertice["longitud"]);

                $sqlVertice = "
                    INSERT INTO Poligono_Vertices
                    (
                        Id_Poligono,
                        Orden,
                        Latitud,
                        Longitud
                    )
                    VALUES
                    (
                        $id,
                        $orden,
                        $latitud,
                        $longitud
                    )
                ";

                if(!mysqli_query($this->conn, $sqlVertice)){
                    throw new Exception(mysqli_error($this->conn));
                }

                $orden++;
            }


            mysqli_commit($this->conn);

            return json_encode([
                "mensaje" => "Zona guardada correctamente",
                "id_poligono" => $id
            ]);

        }catch(Exception $e){

            mysqli_rollback($this->conn);

            return json_encode([
                "error" => $e->getMessage()
            ]);
        }
    }
}

?>
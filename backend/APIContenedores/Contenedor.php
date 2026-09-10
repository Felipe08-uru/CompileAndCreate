<?php

class Contenedor{

    private $conn;
    private $table = "Contenedor";

    public function __construct($db){
        $this->conn = $db;
    }

    public function getAllContenedores(){
        $sql = "SELECT * FROM $this->table";
        $result = mysqli_query($this->conn, $sql);

        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    public function getContenedorById($id){
        $sql = "SELECT * FROM $this->table WHERE Id_Contenedor=$id";
        $result = mysqli_query($this->conn, $sql);

        return mysqli_fetch_assoc($result);
    }

    public function addContenedor($data){

        if(
            !isset($data["tipo"]) ||
            !isset($data["latitud"]) ||
            !isset($data["longitud"])
        ){
            return json_encode([
                "error" => "Faltan datos del contenedor"
            ]);
        }

        $sqlId = "
            SELECT COALESCE(MAX(Id_Contenedor), 0) + 1 AS nuevo_id
            FROM $this->table
        ";

        $resultId = mysqli_query($this->conn, $sqlId);
        $fila = mysqli_fetch_assoc($resultId);

        $id = intval($fila["nuevo_id"]);

        $tipo = $data["tipo"];
        $estado = "Nuevo";
        $latitud = floatval($data["latitud"]);
        $longitud = floatval($data["longitud"]);

        $sql = "INSERT INTO $this->table
                (Id_Contenedor, Tipo, Estado, Latitud, Longitud)
                VALUES
                ('$id', '$tipo', '$estado', '$latitud', '$longitud')";

        if(mysqli_query($this->conn, $sql)){
            return json_encode([
                "mensaje" => "Contenedor agregado",
                "id_contenedor" => $id
            ]);
        }

        return json_encode([
            "error" => mysqli_error($this->conn)
        ]);
    }

    public function deleteContenedor($data){

        $id = $data['id_contenedor'];

        $sql = "DELETE FROM $this->table
                WHERE Id_Contenedor='$id'";

        if(mysqli_query($this->conn, $sql)){
            return json_encode([
                "mensaje" => "Contenedor eliminado"
            ]);
        }

        return json_encode([
            "error" => mysqli_error($this->conn)
        ]);
    }
}

?>
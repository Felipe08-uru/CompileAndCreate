<?php

class Contenedor{

    private $conn;
    private $table = "contenedor";

    public function __construct($db){
        $this->conn = $db;
    }

    public function getAllContenedores(){
        $sql = "SELECT * FROM $this->table";
        $result = mysqli_query($this->conn,$sql);

        return mysqli_fetch_all($result,MYSQLI_ASSOC);
    }

    public function getContenedorById($id){
        $sql = "SELECT * FROM $this->table WHERE id_contenedor=$id";
        $result = mysqli_query($this->conn,$sql);

        return mysqli_fetch_assoc($result);
    }

    public function addContenedor($data){
        $id = $data['id_contenedor'];
        $tipo = $data['tipo'];
        $estado = $data['estado'];
        $ubicacion = $data['ubicacion'];
        $sql = "INSERT INTO $this->table(id_contenedor,tipo,estado,ubicacion)
                VALUES('$id','$tipo','$estado','$ubicacion')";

        if(mysqli_query($this->conn,$sql)){
            return json_encode(["mensaje"=>"Contenedor agregado"]);
        }

        return json_encode(["error"=>mysqli_error($this->conn)]);
    }

    public function deleteContenedor($data){
        $id = $data['id_contenedor'];
        $sql = "DELETE FROM $this->table WHERE id_contenedor='$id'";
        
        if(mysqli_query($this->conn,$sql)){
            return json_encode(["mensaje"=>"Contenedor eliminado"]);
        }
        return json_encode(["error"=>mysqli_error($this->conn)]);
    }

}

?>
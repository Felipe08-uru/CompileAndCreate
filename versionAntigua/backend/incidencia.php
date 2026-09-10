<?php
class Incidencia{
    private $conn;

    public function __construct($conn){
        $this->conn=$conn;
    }

    public function getAllIncidencias(){
        $sql="SELECT * FROM Incidencia";
        $resultado=$this->conn->query($sql);
        $incidencias=[];

        while($fila=$resultado->fetch_assoc()){
            $incidencias[]=$fila;
        }

        return $incidencias;
    }

    public function getIncidenciaById($id){
        $stmt=$this->conn->prepare("SELECT * FROM Incidencia WHERE Id_Incidencia=?");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $resultado=$stmt->get_result();

        if($resultado->num_rows>0){
            return $resultado->fetch_assoc();
        }

        return null;
    }

    public function addIncidencia($data){
        $stmt=$this->conn->prepare("INSERT INTO Incidencia(Tipo,Estado,Id_Contenedor,Foto) VALUES(?,?,?,?)");
        $stmt->bind_param(
            "ssis",
            $data["Tipo"],
            $data["Estado"],
            $data["Id_Contenedor"],
            $data["Foto"]
        );

        if($stmt->execute()){
            return json_encode([
                "success"=>true,
                "mensaje"=>"La incidencia fue registrada correctamente.",
                "Id_Incidencia"=>$this->conn->insert_id
            ]);
        }

        return json_encode([
            "error"=>"No se pudo registrar la incidencia."
        ]);
    }

    public function deleteIncidencia($id){
        $stmt=$this->conn->prepare("DELETE FROM Incidencia WHERE Id_Incidencia=?");
        $stmt->bind_param("i",$id);

        if($stmt->execute()){
            if($stmt->affected_rows>0){
                return json_encode([
                    "success"=>true,
                    "mensaje"=>"La incidencia fue eliminada correctamente."
                ]);
            }

            return json_encode([
                "error"=>"Incidencia no encontrada."
            ]);
        }

        return json_encode([
            "error"=>"No se pudo eliminar la incidencia."
        ]);
    }
}
?>
<?php
class Incidencia{
    private $conn;

    public function __construct($conn){
        $this->conn=$conn;
    }

    public function getAllIncidencias(){
        $sql="SELECT * FROM incidencia";
        $resultado=$this->conn->query($sql);
        $incidencias=[];

        while($fila=$resultado->fetch_assoc()){
            $incidencias[]=$fila;
        }

        return $incidencias;
    }

    public function getIncidenciaById($id){
        $stmt=$this->conn->prepare("SELECT * FROM incidencia WHERE Id_Incidencia=?");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $resultado=$stmt->get_result();

        if($resultado->num_rows>0){
            return $resultado->fetch_assoc();
        }

        return null;
    }

    public function existeContenedor($idContenedor){
        $stmt=$this->conn->prepare("SELECT 1 FROM contenedor WHERE Id_Contenedor=?");
        $stmt->bind_param("i",$idContenedor);
        $stmt->execute();

        return $stmt->get_result()->num_rows>0;
    }

    /**
     * Registra una incidencia. Un contenedor puede tener varias incidencias a la vez
     * (de distintas personas o de distintos tipos). Si se indica la cédula de quien
     * reporta ($data["CI"]) se guarda en la tabla "reporta", que es lo que permite
     * avisarle después, con una notificación, cuando la incidencia se resuelva.
     */
    public function addIncidencia($data){
        $ci=isset($data["CI"]) ? $data["CI"] : null;

        $this->conn->begin_transaction();

        try{
            $stmt=$this->conn->prepare("INSERT INTO incidencia(Tipo,Estado,Id_Contenedor,Foto) VALUES(?,?,?,?)");
            $stmt->bind_param(
                "ssis",
                $data["Tipo"],
                $data["Estado"],
                $data["Id_Contenedor"],
                $data["Foto"]
            );
            $stmt->execute();

            $idIncidencia=$this->conn->insert_id;

            if($ci!==null){
                $stmtReporta=$this->conn->prepare("INSERT INTO reporta(CI,Id_Incidencia) VALUES(?,?)");
                $stmtReporta->bind_param("si",$ci,$idIncidencia);
                $stmtReporta->execute();
            }

            $this->conn->commit();
        }catch(mysqli_sql_exception $e){
            $this->conn->rollback();
            error_log("[SiGeRU] No se pudo registrar la incidencia: ".$e->getMessage());

            return json_encode([
                "error"=>"No se pudo registrar la incidencia."
            ]);
        }

        return json_encode([
            "success"=>true,
            "mensaje"=>"La incidencia fue registrada correctamente.",
            "Id_Incidencia"=>$idIncidencia
        ]);
    }

    public function deleteIncidencia($id){
        $stmt=$this->conn->prepare("DELETE FROM incidencia WHERE Id_Incidencia=?");
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

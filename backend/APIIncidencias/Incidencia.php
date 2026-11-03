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

    /** Incidencia pendiente (sin resolver) del contenedor, o null si no tiene. */
    public function getPendienteDelContenedor($idContenedor){
        $stmt=$this->conn->prepare("SELECT * FROM incidencia WHERE Id_Contenedor=? AND Estado='Pendiente' LIMIT 1");
        $stmt->bind_param("i",$idContenedor);
        $stmt->execute();
        $resultado=$stmt->get_result();

        return $resultado->num_rows>0 ? $resultado->fetch_assoc() : null;
    }

    public function existeContenedor($idContenedor){
        $stmt=$this->conn->prepare("SELECT 1 FROM contenedor WHERE Id_Contenedor=?");
        $stmt->bind_param("i",$idContenedor);
        $stmt->execute();

        return $stmt->get_result()->num_rows>0;
    }

    public function addIncidencia($data){
        // Un contenedor no puede tener dos incidencias pendientes a la vez.
        if($this->getPendienteDelContenedor($data["Id_Contenedor"]) !== null){
            http_response_code(409);

            return json_encode([
                "error"=>"El contenedor ya tiene una incidencia pendiente. Hay que resolverla antes de registrar otra."
            ]);
        }

        $stmt=$this->conn->prepare("INSERT INTO incidencia(Tipo,Estado,Id_Contenedor,Foto) VALUES(?,?,?,?)");
        $stmt->bind_param(
            "ssis",
            $data["Tipo"],
            $data["Estado"],
            $data["Id_Contenedor"],
            $data["Foto"]
        );

        try{
            $ok=$stmt->execute();
        }catch(mysqli_sql_exception $e){
            // 1062: el índice único detectó otra incidencia pendiente (dos pedidos a la vez).
            if($e->getCode()==1062){
                http_response_code(409);

                return json_encode([
                    "error"=>"El contenedor ya tiene una incidencia pendiente. Hay que resolverla antes de registrar otra."
                ]);
            }

            $ok=false;
        }

        if($ok){
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

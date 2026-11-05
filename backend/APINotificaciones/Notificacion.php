<?php
/**
 * Notificaciones de la "campanita". Cada persona solo ve y modifica las suyas.
 */
class Notificacion{
    private $conn;

    const LIMITE = 30;

    public function __construct($conn){
        $this->conn=$conn;
    }

    /** Últimas notificaciones del usuario (las más nuevas primero) y cuántas no leyó. */
    public function getDelUsuario($ci){
        $limite=self::LIMITE;

        $stmt=$this->conn->prepare(
            "SELECT Id_Notificacion, Tipo, Mensaje, Id_Incidencia, Leida, Fecha
             FROM notificacion
             WHERE CI=?
             ORDER BY Fecha DESC, Id_Notificacion DESC
             LIMIT ?"
        );
        $stmt->bind_param("si",$ci,$limite);
        $stmt->execute();
        $resultado=$stmt->get_result();

        $lista=[];

        while($fila=$resultado->fetch_assoc()){
            $fila["Id_Notificacion"]=intval($fila["Id_Notificacion"]);
            $fila["Leida"]=intval($fila["Leida"])===1;
            $lista[]=$fila;
        }

        $stmtCuenta=$this->conn->prepare("SELECT COUNT(*) AS total FROM notificacion WHERE CI=? AND Leida=0");
        $stmtCuenta->bind_param("s",$ci);
        $stmtCuenta->execute();
        $noLeidas=intval($stmtCuenta->get_result()->fetch_assoc()["total"]);

        return [
            "no_leidas"=>$noLeidas,
            "notificaciones"=>$lista
        ];
    }

    /** Marca una notificación como leída. Devuelve false si no existe o no es del usuario. */
    public function marcarLeida($id,$ci){
        $stmt=$this->conn->prepare("UPDATE notificacion SET Leida=1 WHERE Id_Notificacion=? AND CI=?");
        $stmt->bind_param("is",$id,$ci);
        $stmt->execute();

        if($stmt->affected_rows>0){
            return true;
        }

        // Ya estaba leída: se considera correcto si existe y es suya.
        $stmtExiste=$this->conn->prepare("SELECT 1 FROM notificacion WHERE Id_Notificacion=? AND CI=?");
        $stmtExiste->bind_param("is",$id,$ci);
        $stmtExiste->execute();

        return $stmtExiste->get_result()->num_rows>0;
    }

    public function marcarTodasLeidas($ci){
        $stmt=$this->conn->prepare("UPDATE notificacion SET Leida=1 WHERE CI=? AND Leida=0");
        $stmt->bind_param("s",$ci);
        $stmt->execute();

        return $stmt->affected_rows;
    }
}
?>

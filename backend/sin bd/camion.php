<?php

class Camion{

    private $conn;
    private $table = "camion";

    public function __construct($db){
        $this->conn = $db;

        if(session_status() == PHP_SESSION_NONE){
            session_start();
        }

        if(!isset($_SESSION['camiones'])){
            $_SESSION['camiones'] = [];
        }
    }

    public function getAllCamiones(){
        return $_SESSION['camiones'];
    }

    public function getCamionByMatricula($matricula){
        foreach($_SESSION['camiones'] as $camion){
            if($camion['matricula'] == $matricula){
                return $camion;
            }
        }

        return null;
    }

    public function addCamion($data){
        $camion = [
            "tipo" => $data['tipo'],
            "estado" => $data['estado'],
            "matricula" => $data['matricula']
        ];

        $_SESSION['camiones'][] = $camion;

        return json_encode([
            "mensaje"=>"Camión agregado"
        ]);
    }

    public function deleteCamion($data){
        $matricula = $data['matricula'];

        foreach($_SESSION['camiones'] as $i => $camion){
            if($camion['matricula'] == $matricula){
                unset($_SESSION['camiones'][$i]);
                $_SESSION['camiones'] = array_values($_SESSION['camiones']);

                return json_encode([
                    "mensaje"=>"Camión eliminado"
                ]);
            }
        }

        return json_encode([
            "error"=>"Camión no encontrado"
        ]);
    }

}

?>
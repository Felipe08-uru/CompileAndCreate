<?php

class Contenedor{

    private $conn;
    private $table = "contenedor";

    public function __construct($db){
        $this->conn = $db;

        if(session_status() == PHP_SESSION_NONE){
            session_start();
        }

        if(!isset($_SESSION['contenedores'])){
            $_SESSION['contenedores'] = [];
        }
    }

    public function getAllContenedores(){
        return $_SESSION['contenedores'];
    }

    public function getContenedorById($id){
        foreach($_SESSION['contenedores'] as $contenedor){
            if($contenedor['id_contenedor'] == $id){
                return $contenedor;
            }
        }

        return null;
    }

    public function addContenedor($data){
        $contenedor = [
            "id_contenedor" => $data['id_contenedor'],
            "tipo" => $data['tipo'],
            "estado" => $data['estado'],
            "ubicacion" => $data['ubicacion']
        ];

        $_SESSION['contenedores'][] = $contenedor;

        return json_encode([
            "mensaje"=>"Contenedor agregado"
        ]);
    }

    public function deleteContenedor($data){
        $id = $data['id_contenedor'];

        foreach($_SESSION['contenedores'] as $i => $contenedor){
            if($contenedor['id_contenedor'] == $id){
                unset($_SESSION['contenedores'][$i]);
                $_SESSION['contenedores'] = array_values($_SESSION['contenedores']);

                return json_encode([
                    "mensaje"=>"Contenedor eliminado"
                ]);
            }
        }

        return json_encode([
            "error"=>"Contenedor no encontrado"
        ]);
    }

}

?>
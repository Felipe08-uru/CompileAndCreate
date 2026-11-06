<?php
require_once __DIR__ . "/../APIRutas/Ruta.php";
require_once __DIR__ . "/../APIPoligonos/Poligono.php";

/**
 * Asignaciones que hace el administrador:
 *   - camión  -> zona (polígono) + tipo de ruta   (tabla recoge)
 *   - cuadrilla -> camión                          (tabla usa)
 *
 * Reglas:
 *   - Un camión trabaja en una sola zona y con un solo tipo de ruta.
 *   - Un camión de tipo "Residuos mezclados" hace rutas de Residuos mezclados
 *     o de Incidencias; uno de "Reciclables" hace rutas de Reciclables o de
 *     Incidencias. (Incidencias la puede hacer cualquiera de los dos tipos.)
 *   - Una cuadrilla usa un camión y un camión lo usa una cuadrilla.
 */
class Asignacion {

    private $conn;

    public function __construct($db){
        $this->conn = $db;
    }

    private function error($mensaje, $codigo = 400){
        http_response_code($codigo);

        return json_encode([
            "error" => $mensaje
        ]);
    }

    /**
     * Todos los camiones con su zona, tipo de ruta, cuadrilla y ruta activa.
     */
    public function getAsignaciones(){

        $sql = "SELECT c.Matricula, c.Tipo, c.Estado,
                       r.Id_Poligono, r.Tipo_Ruta,
                       u.CI AS CI_Cuadrilla,
                       CONCAT(us.nombre1, ' ', us.apellido1) AS Nombre_Cuadrilla
                FROM camion c
                LEFT JOIN recoge r ON r.Matricula = c.Matricula
                LEFT JOIN usa u ON u.Matricula = c.Matricula
                LEFT JOIN usuario us ON us.ci = u.CI
                ORDER BY c.Matricula";

        $result = $this->conn->query($sql);

        $rutaObj = new Ruta($this->conn);

        $asignaciones = [];

        while($fila = $result->fetch_assoc()){

            $activa = $rutaObj->getResumenActivaPorMatricula($fila["Matricula"]);

            $asignaciones[] = [
                "Matricula" => $fila["Matricula"],
                "Tipo" => $fila["Tipo"],
                "Estado" => $fila["Estado"],
                "Id_Poligono" => $fila["Id_Poligono"] !== null ? intval($fila["Id_Poligono"]) : null,
                "Tipo_Ruta" => $fila["Tipo_Ruta"],
                "CI_Cuadrilla" => $fila["CI_Cuadrilla"],
                "Nombre_Cuadrilla" => $fila["Nombre_Cuadrilla"],
                "Id_Ruta_Activa" => $activa ? $activa["Id_Ruta"] : null,
                "Estado_Ruta" => $activa ? $activa["Estado"] : null,
                "Paradas_Total" => $activa ? $activa["Paradas_Total"] : 0,
                "Paradas_Hechas" => $activa ? $activa["Paradas_Hechas"] : 0
            ];
        }

        return $asignaciones;
    }

    /**
     * Lo que ve una cuadrilla: su camión, su zona, su ruta actual y su historial.
     */
    public function getAsignacionDeCuadrilla($ci){

        $sql = "SELECT c.Matricula, c.Tipo, c.Estado, r.Id_Poligono, r.Tipo_Ruta
                FROM usa u
                INNER JOIN camion c ON c.Matricula = u.Matricula
                LEFT JOIN recoge r ON r.Matricula = c.Matricula
                WHERE u.CI = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $ci);
        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();

        $rutaObj = new Ruta($this->conn);
        $historial = $rutaObj->getHistorialCuadrilla($ci);

        if(!$fila){
            return [
                "asignado" => false,
                "camion" => null,
                "id_poligono" => null,
                "tipo_ruta" => null,
                "poligono" => null,
                "ruta_activa" => null,
                "historial" => $historial
            ];
        }

        $poligono = null;

        if($fila["Id_Poligono"] !== null){
            $poligonoObj = new Poligono($this->conn);
            $poligono = $poligonoObj->getPoligonoPorId($fila["Id_Poligono"]);
        }

        return [
            "asignado" => true,
            "camion" => [
                "Matricula" => $fila["Matricula"],
                "Tipo" => $fila["Tipo"],
                "Estado" => $fila["Estado"]
            ],
            "id_poligono" => $fila["Id_Poligono"] !== null ? intval($fila["Id_Poligono"]) : null,
            "tipo_ruta" => $fila["Tipo_Ruta"],
            "poligono" => $poligono,
            "ruta_activa" => $rutaObj->getResumenActivaPorMatricula($fila["Matricula"]),
            "historial" => $historial
        ];
    }

    private function valorOpcional($data, $clave){
        if(!isset($data[$clave])){
            return null;
        }

        $valor = trim((string)$data[$clave]);

        return $valor === "" ? null : $valor;
    }

    /**
     * Crea o reemplaza la asignación de un camión.
     * Si no se indica zona se quita la relación camión-zona; si no se indica
     * cuadrilla se quita la relación cuadrilla-camión.
     */
    public function guardarAsignacion($data){

        $matricula = $this->valorOpcional($data, "matricula");
        $idPoligono = $this->valorOpcional($data, "id_poligono");
        $tipoRuta = $this->valorOpcional($data, "tipo_ruta");
        $ciCuadrilla = $this->valorOpcional($data, "ci_cuadrilla");

        if($matricula === null){
            return $this->error("Debe indicar el camión");
        }

        $stmt = $this->conn->prepare("SELECT Tipo FROM camion WHERE Matricula = ?");
        $stmt->bind_param("s", $matricula);
        $stmt->execute();

        $camion = $stmt->get_result()->fetch_assoc();

        if(!$camion){
            return $this->error("El camión no existe", 404);
        }

        if($idPoligono !== null){

            if(!ctype_digit($idPoligono)){
                return $this->error("La zona indicada es inválida");
            }

            $idPoligono = intval($idPoligono);

            $stmt = $this->conn->prepare("SELECT Id_Poligono FROM poligono WHERE Id_Poligono = ?");
            $stmt->bind_param("i", $idPoligono);
            $stmt->execute();

            if($stmt->get_result()->num_rows === 0){
                return $this->error("La zona indicada no existe", 404);
            }

            if($tipoRuta === null || !in_array($tipoRuta, Ruta::TIPOS, true)){
                return $this->error("Debe indicar el tipo de ruta: Residuos mezclados, Reciclables o Incidencias");
            }

            if($tipoRuta !== "Incidencias" && $tipoRuta !== $camion["Tipo"]){
                return $this->error(
                    "Un camión de tipo " . $camion["Tipo"] .
                    " solo puede hacer rutas de " . $camion["Tipo"] . " o de Incidencias"
                );
            }
        }else{
            $tipoRuta = null;
        }

        if($ciCuadrilla !== null){
            $stmt = $this->conn->prepare("SELECT CI FROM cuadrilla WHERE CI = ?");
            $stmt->bind_param("s", $ciCuadrilla);
            $stmt->execute();

            if($stmt->get_result()->num_rows === 0){
                return $this->error("La cuadrilla indicada no existe", 404);
            }
        }

        // Si el camión tiene una ruta sin finalizar no se le puede cambiar la zona
        // ni el tipo de ruta (la ruta ya fue armada para esa zona).
        $rutaObj = new Ruta($this->conn);
        $activa = $rutaObj->getResumenActivaPorMatricula($matricula);

        if($activa !== null){
            $stmt = $this->conn->prepare("SELECT Id_Poligono, Tipo_Ruta FROM recoge WHERE Matricula = ?");
            $stmt->bind_param("s", $matricula);
            $stmt->execute();

            $actual = $stmt->get_result()->fetch_assoc();

            $mismaZona = $actual &&
                $idPoligono !== null &&
                intval($actual["Id_Poligono"]) === $idPoligono &&
                $actual["Tipo_Ruta"] === $tipoRuta;

            if(!$mismaZona){
                return $this->error(
                    "El camión tiene una ruta sin finalizar. Elimine la ruta antes de cambiar su zona o su tipo de ruta.",
                    409
                );
            }
        }

        $this->conn->begin_transaction();

        try{
            // camión -> zona
            $stmt = $this->conn->prepare("DELETE FROM recoge WHERE Matricula = ?");
            $stmt->bind_param("s", $matricula);
            $stmt->execute();

            if($idPoligono !== null){
                $stmt = $this->conn->prepare(
                    "INSERT INTO recoge (Matricula, Id_Poligono, Tipo_Ruta) VALUES (?, ?, ?)"
                );
                $stmt->bind_param("sis", $matricula, $idPoligono, $tipoRuta);
                $stmt->execute();
            }

            // cuadrilla -> camión (uno a uno)
            $stmt = $this->conn->prepare("DELETE FROM usa WHERE Matricula = ?");
            $stmt->bind_param("s", $matricula);
            $stmt->execute();

            if($ciCuadrilla !== null){
                $stmt = $this->conn->prepare("DELETE FROM usa WHERE CI = ?");
                $stmt->bind_param("s", $ciCuadrilla);
                $stmt->execute();

                $stmt = $this->conn->prepare("INSERT INTO usa (CI, Matricula) VALUES (?, ?)");
                $stmt->bind_param("ss", $ciCuadrilla, $matricula);
                $stmt->execute();

                // Si había una ruta en curso, queda registrada la nueva cuadrilla.
                if($activa !== null){
                    $idRuta = $activa["Id_Ruta"];

                    $stmt = $this->conn->prepare("DELETE FROM circula WHERE Id_Ruta = ?");
                    $stmt->bind_param("i", $idRuta);
                    $stmt->execute();

                    $stmt = $this->conn->prepare("INSERT INTO circula (CI, Id_Ruta) VALUES (?, ?)");
                    $stmt->bind_param("si", $ciCuadrilla, $idRuta);
                    $stmt->execute();
                }
            }

            $this->conn->commit();

            return json_encode([
                "mensaje" => "Asignación guardada correctamente"
            ]);

        }catch(Exception $e){

            $this->conn->rollback();

            return $this->error("No se pudo guardar la asignación", 500);
        }
    }

    /**
     * Asigna un camión a una cuadrilla (o se lo quita si no se indica camión).
     * Uno a uno: una cuadrilla usa un solo camión y un camión lo usa una sola cuadrilla.
     * Si la cuadrilla ya tenía otro camión, deja de figurar en las rutas sin
     * finalizar de ese camión.
     */
    public function asignarCuadrilla($data){

        $ciCuadrilla = $this->valorOpcional($data, "ci_cuadrilla");
        $matricula = $this->valorOpcional($data, "matricula");

        if($ciCuadrilla === null){
            return $this->error("Debe indicar la cuadrilla");
        }

        $stmt = $this->conn->prepare("SELECT CI FROM cuadrilla WHERE CI = ?");
        $stmt->bind_param("s", $ciCuadrilla);
        $stmt->execute();

        if($stmt->get_result()->num_rows === 0){
            return $this->error("La cuadrilla indicada no existe", 404);
        }

        if($matricula !== null){
            $stmt = $this->conn->prepare("SELECT Matricula FROM camion WHERE Matricula = ?");
            $stmt->bind_param("s", $matricula);
            $stmt->execute();

            if($stmt->get_result()->num_rows === 0){
                return $this->error("El camión no existe", 404);
            }
        }

        $rutaObj = new Ruta($this->conn);

        // Camión que la cuadrilla usaba hasta ahora.
        $stmt = $this->conn->prepare("SELECT Matricula FROM usa WHERE CI = ?");
        $stmt->bind_param("s", $ciCuadrilla);
        $stmt->execute();

        $anterior = $stmt->get_result()->fetch_assoc();
        $matriculaAnterior = $anterior ? $anterior["Matricula"] : null;

        $this->conn->begin_transaction();

        try{
            if($matriculaAnterior !== null){
                $activaAnterior = $rutaObj->getResumenActivaPorMatricula($matriculaAnterior);

                if($activaAnterior !== null){
                    $idRutaAnterior = $activaAnterior["Id_Ruta"];

                    $stmt = $this->conn->prepare("DELETE FROM circula WHERE Id_Ruta = ? AND CI = ?");
                    $stmt->bind_param("is", $idRutaAnterior, $ciCuadrilla);
                    $stmt->execute();
                }
            }

            $stmt = $this->conn->prepare("DELETE FROM usa WHERE CI = ?");
            $stmt->bind_param("s", $ciCuadrilla);
            $stmt->execute();

            if($matricula !== null){
                // Si otra cuadrilla tenía ese camión, lo pierde.
                $stmt = $this->conn->prepare("DELETE FROM usa WHERE Matricula = ?");
                $stmt->bind_param("s", $matricula);
                $stmt->execute();

                $stmt = $this->conn->prepare("INSERT INTO usa (CI, Matricula) VALUES (?, ?)");
                $stmt->bind_param("ss", $ciCuadrilla, $matricula);
                $stmt->execute();

                $activa = $rutaObj->getResumenActivaPorMatricula($matricula);

                if($activa !== null){
                    $idRuta = $activa["Id_Ruta"];

                    $stmt = $this->conn->prepare("DELETE FROM circula WHERE Id_Ruta = ?");
                    $stmt->bind_param("i", $idRuta);
                    $stmt->execute();

                    $stmt = $this->conn->prepare("INSERT INTO circula (CI, Id_Ruta) VALUES (?, ?)");
                    $stmt->bind_param("si", $ciCuadrilla, $idRuta);
                    $stmt->execute();
                }
            }

            $this->conn->commit();

            return json_encode([
                "mensaje" => $matricula === null
                    ? "Se quitó el camión de la cuadrilla"
                    : "Camión asignado a la cuadrilla correctamente"
            ]);

        }catch(Exception $e){

            $this->conn->rollback();

            return $this->error("No se pudo asignar el camión a la cuadrilla", 500);
        }
    }

    /**
     * Quita las relaciones del camión con la zona y con la cuadrilla.
     */
    public function eliminarAsignacion($data){

        $matricula = $this->valorOpcional($data, "matricula");

        if($matricula === null){
            return $this->error("Debe indicar el camión");
        }

        $rutaObj = new Ruta($this->conn);

        if($rutaObj->getResumenActivaPorMatricula($matricula) !== null){
            return $this->error(
                "El camión tiene una ruta sin finalizar. Elimine la ruta antes de quitar su asignación.",
                409
            );
        }

        $this->conn->begin_transaction();

        try{
            $stmt = $this->conn->prepare("DELETE FROM recoge WHERE Matricula = ?");
            $stmt->bind_param("s", $matricula);
            $stmt->execute();

            $stmt = $this->conn->prepare("DELETE FROM usa WHERE Matricula = ?");
            $stmt->bind_param("s", $matricula);
            $stmt->execute();

            $this->conn->commit();

            return json_encode([
                "mensaje" => "Asignación eliminada correctamente"
            ]);

        }catch(Exception $e){

            $this->conn->rollback();

            return $this->error("No se pudo eliminar la asignación", 500);
        }
    }
}

?>

<?php
require_once __DIR__ . "/../Geo/Geometria.php";
require_once __DIR__ . "/../APIPoligonos/Poligono.php";

/**
 * Rutas de recolección.
 *
 * Una ruta pertenece a UNA zona (polígono) y solo recorre los contenedores
 * que están en esa zona. Hay tres tipos de ruta:
 *   - Residuos mezclados: contenedores de ese tipo de la zona.
 *   - Reciclables:        contenedores de ese tipo de la zona.
 *   - Incidencias:        contenedores de la zona con incidencias pendientes.
 *
 * Las paradas se guardan ya ordenadas con el recorrido más corto posible
 * (ver Geometria::optimizarOrden). El trazado sobre las calles lo dibuja el
 * navegador con Leaflet Routing Machine.
 */
class Ruta {

    private $conn;

    const TIPOS = ["Residuos mezclados", "Reciclables", "Incidencias"];

    const PESO_MAXIMO = 10000;

    public function __construct($db){
        $this->conn = $db;
    }

    private function error($mensaje, $codigo = 400){
        http_response_code($codigo);

        return json_encode([
            "error" => $mensaje
        ]);
    }

    // ------------------------------------------------------------------
    // Consultas
    // ------------------------------------------------------------------

    private function normalizarResumen($fila){
        return [
            "Id_Ruta" => intval($fila["Id_Ruta"]),
            "Id_Poligono" => intval($fila["Id_Poligono"]),
            "Tipo" => $fila["Tipo"],
            "Matricula" => $fila["Matricula"],
            "Estado" => $fila["Estado"],
            "Fecha_Creacion" => $fila["Fecha_Creacion"],
            "Fecha_Fin" => $fila["Fecha_Fin"],
            "CI_Cuadrilla" => isset($fila["CI_Cuadrilla"]) ? $fila["CI_Cuadrilla"] : null,
            "Nombre_Cuadrilla" => isset($fila["Nombre_Cuadrilla"]) ? $fila["Nombre_Cuadrilla"] : null,
            "Paradas_Total" => intval($fila["Paradas_Total"]),
            "Paradas_Hechas" => intval($fila["Paradas_Hechas"]),
            "Peso_Total" => round(floatval($fila["Peso_Total"]), 2)
        ];
    }

    private function consultaResumen($where){
        return "SELECT r.Id_Ruta, r.Id_Poligono, r.Tipo, r.Matricula, r.Estado,
                       r.Fecha_Creacion, r.Fecha_Fin,
                       (SELECT ci.CI FROM circula ci
                         WHERE ci.Id_Ruta = r.Id_Ruta LIMIT 1) AS CI_Cuadrilla,
                       (SELECT CONCAT(u.nombre1, ' ', u.apellido1)
                          FROM circula ci
                          INNER JOIN usuario u ON u.ci = ci.CI
                         WHERE ci.Id_Ruta = r.Id_Ruta LIMIT 1) AS Nombre_Cuadrilla,
                       (SELECT COUNT(*) FROM ruta_parada p
                         WHERE p.Id_Ruta = r.Id_Ruta) AS Paradas_Total,
                       (SELECT COUNT(*) FROM ruta_parada p
                         WHERE p.Id_Ruta = r.Id_Ruta AND p.Estado = 'Recogido') AS Paradas_Hechas,
                       (SELECT COALESCE(SUM(p.Peso), 0) FROM ruta_parada p
                         WHERE p.Id_Ruta = r.Id_Ruta) AS Peso_Total
                FROM ruta r
                $where";
    }

    /**
     * Todas las rutas (para el administrador), de la más nueva a la más vieja.
     */
    public function getRutas(){
        $sql = $this->consultaResumen("ORDER BY r.Id_Ruta DESC");

        $result = $this->conn->query($sql);

        $rutas = [];

        while($fila = $result->fetch_assoc()){
            $rutas[] = $this->normalizarResumen($fila);
        }

        return $rutas;
    }

    /**
     * Resumen de la ruta sin finalizar de un camión (o null si no tiene).
     */
    public function getResumenActivaPorMatricula($matricula){
        $sql = $this->consultaResumen(
            "WHERE r.Matricula = ? AND r.Estado <> 'Finalizada'
             ORDER BY r.Id_Ruta DESC LIMIT 1"
        );

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $matricula);
        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();

        return $fila ? $this->normalizarResumen($fila) : null;
    }

    /**
     * Últimas rutas finalizadas por una cuadrilla.
     */
    public function getHistorialCuadrilla($ci, $limite = 10){
        $sql = $this->consultaResumen(
            "INNER JOIN circula cir ON cir.Id_Ruta = r.Id_Ruta AND cir.CI = ?
             WHERE r.Estado = 'Finalizada'
             ORDER BY r.Id_Ruta DESC LIMIT ?"
        );

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $ci, $limite);
        $stmt->execute();

        $result = $stmt->get_result();

        $rutas = [];

        while($fila = $result->fetch_assoc()){
            $rutas[] = $this->normalizarResumen($fila);
        }

        return $rutas;
    }

    /**
     * ID de la ruta sin finalizar del camión que usa la cuadrilla.
     */
    public function getIdRutaActivaDeCuadrilla($ci){
        $sql = "SELECT r.Id_Ruta
                FROM ruta r
                INNER JOIN usa u ON u.Matricula = r.Matricula
                WHERE u.CI = ? AND r.Estado <> 'Finalizada'
                ORDER BY r.Id_Ruta DESC
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $ci);
        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();

        return $fila ? intval($fila["Id_Ruta"]) : null;
    }

    /**
     * Matrícula del camión que usa una cuadrilla (o null si no tiene uno asignado).
     */
    public function getMatriculaDeCuadrilla($ci){
        $stmt = $this->conn->prepare("SELECT Matricula FROM usa WHERE CI = ?");
        $stmt->bind_param("s", $ci);
        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();

        return $fila ? $fila["Matricula"] : null;
    }

    /**
     * Una cuadrilla solo puede ver las rutas de su camión o las que ya hizo.
     */
    public function cuadrillaPuedeVerRuta($ci, $idRuta){
        $sql = "SELECT r.Id_Ruta
                FROM ruta r
                WHERE r.Id_Ruta = ?
                AND (
                    r.Matricula IN (SELECT u.Matricula FROM usa u WHERE u.CI = ?)
                    OR EXISTS (
                        SELECT 1 FROM circula c
                        WHERE c.Id_Ruta = r.Id_Ruta AND c.CI = ?
                    )
                )";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $idRuta, $ci, $ci);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    /**
     * Ruta completa con sus paradas ordenadas y el polígono al que pertenece.
     */
    public function getDetalle($idRuta){
        $idRuta = intval($idRuta);

        $sql = $this->consultaResumen("WHERE r.Id_Ruta = ?");

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $idRuta);
        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();

        if(!$fila){
            return null;
        }

        $ruta = $this->normalizarResumen($fila);

        $sqlParadas = "SELECT p.Orden, p.Id_Contenedor, p.Estado, p.Peso, p.Fecha_Recoleccion,
                              c.Latitud, c.Longitud, c.Tipo AS Tipo_Contenedor
                       FROM ruta_parada p
                       INNER JOIN contenedor c ON c.Id_Contenedor = p.Id_Contenedor
                       WHERE p.Id_Ruta = ?
                       ORDER BY p.Orden";

        $stmtParadas = $this->conn->prepare($sqlParadas);
        $stmtParadas->bind_param("i", $idRuta);
        $stmtParadas->execute();

        $resultParadas = $stmtParadas->get_result();

        $sqlIncidencias = "SELECT Id_Incidencia, Tipo, Foto
                           FROM incidencia
                           WHERE Id_Contenedor = ? AND Estado = 'Pendiente'";
        $stmtIncidencias = $this->conn->prepare($sqlIncidencias);

        $paradas = [];
        $siguienteOrden = null;

        while($p = $resultParadas->fetch_assoc()){

            $parada = [
                "Orden" => intval($p["Orden"]),
                "Id_Contenedor" => intval($p["Id_Contenedor"]),
                "Estado" => $p["Estado"],
                "Peso" => $p["Peso"] !== null ? floatval($p["Peso"]) : null,
                "Fecha_Recoleccion" => $p["Fecha_Recoleccion"],
                "Latitud" => floatval($p["Latitud"]),
                "Longitud" => floatval($p["Longitud"]),
                "Tipo_Contenedor" => $p["Tipo_Contenedor"],
                "Incidencias" => []
            ];

            if($parada["Estado"] === "Pendiente"){

                if($siguienteOrden === null){
                    $siguienteOrden = $parada["Orden"];
                }

                if($ruta["Tipo"] === "Incidencias"){
                    $idContenedor = $parada["Id_Contenedor"];
                    $stmtIncidencias->bind_param("i", $idContenedor);
                    $stmtIncidencias->execute();

                    $parada["Incidencias"] = $stmtIncidencias->get_result()->fetch_all(MYSQLI_ASSOC);
                }
            }

            $paradas[] = $parada;
        }

        $poligonoObj = new Poligono($this->conn);
        $poligono = $poligonoObj->getPoligonoPorId($ruta["Id_Poligono"]);

        return [
            "ruta" => $ruta,
            "poligono" => $poligono,
            "paradas" => $paradas,
            "siguiente_orden" => $siguienteOrden
        ];
    }

    // ------------------------------------------------------------------
    // Acciones
    // ------------------------------------------------------------------

    /**
     * Genera la ruta de un camión según la zona y el tipo de ruta que le
     * asignó el administrador.
     */
    public function generarRuta($matricula){

        $matricula = trim($matricula);

        if($matricula === ""){
            return $this->error("Debe indicar el camión");
        }

        $sql = "SELECT c.Tipo AS Tipo_Camion, r.Id_Poligono, r.Tipo_Ruta
                FROM camion c
                LEFT JOIN recoge r ON r.Matricula = c.Matricula
                WHERE c.Matricula = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $matricula);
        $stmt->execute();

        $asignacion = $stmt->get_result()->fetch_assoc();

        if(!$asignacion){
            return $this->error("El camión no existe", 404);
        }

        if($asignacion["Id_Poligono"] === null){
            return $this->error("El camión no tiene una zona asignada. Debe asignarla el administrador.");
        }

        if($this->getResumenActivaPorMatricula($matricula) !== null){
            return $this->error("El camión ya tiene una ruta sin finalizar", 409);
        }

        $idPoligono = intval($asignacion["Id_Poligono"]);
        $tipoRuta = $asignacion["Tipo_Ruta"];

        return $this->construirRuta($idPoligono, $tipoRuta, $matricula);
    }

    /**
     * Crea una ruta para una zona (la crea el administrador). El camión es
     * opcional: se puede asignar después con asignarCamionARuta().
     */
    public function crearRuta($idPoligono, $tipoRuta, $matricula = null){

        $idPoligono = trim((string)$idPoligono);
        $tipoRuta = trim((string)$tipoRuta);
        $matricula = $matricula === null ? "" : trim((string)$matricula);

        if($idPoligono === "" || !ctype_digit($idPoligono)){
            return $this->error("Debe indicar la zona de la ruta");
        }

        $idPoligono = intval($idPoligono);

        $stmt = $this->conn->prepare("SELECT Id_Poligono FROM poligono WHERE Id_Poligono = ?");
        $stmt->bind_param("i", $idPoligono);
        $stmt->execute();

        if($stmt->get_result()->num_rows === 0){
            return $this->error("La zona indicada no existe", 404);
        }

        if(!in_array($tipoRuta, self::TIPOS, true)){
            return $this->error("Debe indicar el tipo de ruta: Residuos mezclados, Reciclables o Incidencias");
        }

        if($matricula !== ""){
            $problema = $this->validarCamionParaRuta($matricula, $tipoRuta, null);

            if($problema !== null){
                return $problema;
            }
        }else{
            $matricula = null;
        }

        return $this->construirRuta($idPoligono, $tipoRuta, $matricula);
    }

    /**
     * Devuelve null si el camión puede hacer una ruta de ese tipo, o el JSON de error.
     */
    private function validarCamionParaRuta($matricula, $tipoRuta, $exceptoIdRuta){

        $stmt = $this->conn->prepare("SELECT Tipo FROM camion WHERE Matricula = ?");
        $stmt->bind_param("s", $matricula);
        $stmt->execute();

        $camion = $stmt->get_result()->fetch_assoc();

        if(!$camion){
            return $this->error("El camión no existe", 404);
        }

        if($tipoRuta !== "Incidencias" && $tipoRuta !== $camion["Tipo"]){
            return $this->error(
                "Un camión de tipo " . $camion["Tipo"] .
                " solo puede hacer rutas de " . $camion["Tipo"] . " o de Incidencias"
            );
        }

        $activa = $this->getResumenActivaPorMatricula($matricula);

        if($activa !== null && ($exceptoIdRuta === null || $activa["Id_Ruta"] !== intval($exceptoIdRuta))){
            return $this->error("El camión ya tiene la ruta #" . $activa["Id_Ruta"] . " sin finalizar", 409);
        }

        return null;
    }

    /**
     * Asigna (o quita, con matrícula vacía) el camión de una ruta que todavía
     * no empezó. La cuadrilla de la ruta pasa a ser la que usa ese camión.
     */
    public function asignarCamionARuta($idRuta, $matricula){

        $idRuta = intval($idRuta);
        $matricula = $matricula === null ? "" : trim((string)$matricula);

        $stmt = $this->conn->prepare("SELECT Id_Poligono, Tipo, Estado FROM ruta WHERE Id_Ruta = ?");
        $stmt->bind_param("i", $idRuta);
        $stmt->execute();

        $ruta = $stmt->get_result()->fetch_assoc();

        if(!$ruta){
            return $this->error("La ruta no existe", 404);
        }

        if($ruta["Estado"] !== "Pendiente"){
            return $this->error("Solo se puede cambiar el camión de una ruta que todavía no empezó", 409);
        }

        if($matricula !== ""){
            $problema = $this->validarCamionParaRuta($matricula, $ruta["Tipo"], $idRuta);

            if($problema !== null){
                return $problema;
            }
        }

        $this->conn->begin_transaction();

        try{
            $matriculaFinal = $matricula === "" ? null : $matricula;

            $stmt = $this->conn->prepare("UPDATE ruta SET Matricula = ? WHERE Id_Ruta = ?");
            $stmt->bind_param("si", $matriculaFinal, $idRuta);
            $stmt->execute();

            $stmt = $this->conn->prepare("DELETE FROM circula WHERE Id_Ruta = ?");
            $stmt->bind_param("i", $idRuta);
            $stmt->execute();

            if($matriculaFinal !== null){
                // El camión pasa a trabajar en la zona y con el tipo de esta ruta.
                $idPoligono = intval($ruta["Id_Poligono"]);
                $tipo = $ruta["Tipo"];

                $stmt = $this->conn->prepare(
                    "INSERT INTO recoge (Matricula, Id_Poligono, Tipo_Ruta) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE Id_Poligono = VALUES(Id_Poligono), Tipo_Ruta = VALUES(Tipo_Ruta)"
                );
                $stmt->bind_param("sis", $matriculaFinal, $idPoligono, $tipo);
                $stmt->execute();

                $stmt = $this->conn->prepare("SELECT CI FROM usa WHERE Matricula = ?");
                $stmt->bind_param("s", $matriculaFinal);
                $stmt->execute();

                $cuadrilla = $stmt->get_result()->fetch_assoc();

                if($cuadrilla){
                    $ci = $cuadrilla["CI"];

                    $stmt = $this->conn->prepare("INSERT INTO circula (CI, Id_Ruta) VALUES (?, ?)");
                    $stmt->bind_param("si", $ci, $idRuta);
                    $stmt->execute();
                }
            }

            $this->conn->commit();

            return json_encode([
                "mensaje" => $matriculaFinal === null
                    ? "Se quitó el camión de la ruta"
                    : "Camión asignado a la ruta correctamente"
            ]);

        }catch(Exception $e){

            $this->conn->rollback();

            return $this->error("No se pudo asignar el camión a la ruta", 500);
        }
    }

    /**
     * Arma la ruta: elige los contenedores de la zona, los ordena para el
     * recorrido más corto y los guarda como paradas.
     */
    private function construirRuta($idPoligono, $tipoRuta, $matricula){

        // Solo los contenedores que están en la zona de la ruta.
        if($tipoRuta === "Incidencias"){
            $sqlPuntos = "SELECT DISTINCT c.Id_Contenedor, c.Latitud, c.Longitud
                          FROM contenedor c
                          INNER JOIN incidencia i ON i.Id_Contenedor = c.Id_Contenedor
                          WHERE c.Id_Poligono = ?
                          AND i.Estado = 'Pendiente'
                          AND c.Latitud IS NOT NULL
                          AND c.Longitud IS NOT NULL";

            $stmtPuntos = $this->conn->prepare($sqlPuntos);
            $stmtPuntos->bind_param("i", $idPoligono);
        }else{
            $sqlPuntos = "SELECT Id_Contenedor, Latitud, Longitud
                          FROM contenedor
                          WHERE Id_Poligono = ?
                          AND Tipo = ?
                          AND Latitud IS NOT NULL
                          AND Longitud IS NOT NULL";

            $stmtPuntos = $this->conn->prepare($sqlPuntos);
            $stmtPuntos->bind_param("is", $idPoligono, $tipoRuta);
        }

        $stmtPuntos->execute();
        $filas = $stmtPuntos->get_result()->fetch_all(MYSQLI_ASSOC);

        if(count($filas) === 0){
            if($tipoRuta === "Incidencias"){
                return $this->error("La zona no tiene incidencias pendientes que atender");
            }

            return $this->error("La zona no tiene contenedores de tipo $tipoRuta");
        }

        $puntos = [];

        foreach($filas as $fila){
            $puntos[] = [
                "id" => intval($fila["Id_Contenedor"]),
                "lat" => floatval($fila["Latitud"]),
                "lng" => floatval($fila["Longitud"])
            ];
        }

        $ordenados = Geometria::optimizarOrden($puntos);

        $this->conn->begin_transaction();

        try{
            $sqlRuta = "INSERT INTO ruta (Id_Poligono, Tipo, Matricula, Estado)
                        VALUES (?, ?, ?, 'Pendiente')";

            $stmtRuta = $this->conn->prepare($sqlRuta);
            $stmtRuta->bind_param("iss", $idPoligono, $tipoRuta, $matricula);
            $stmtRuta->execute();

            $idRuta = $this->conn->insert_id;

            $stmtParada = $this->conn->prepare(
                "INSERT INTO ruta_parada (Id_Ruta, Orden, Id_Contenedor, Estado)
                 VALUES (?, ?, ?, 'Pendiente')"
            );

            $orden = 1;

            foreach($ordenados as $punto){
                $idContenedor = $punto["id"];
                $stmtParada->bind_param("iii", $idRuta, $orden, $idContenedor);
                $stmtParada->execute();
                $orden++;
            }

            // Se registra qué cuadrilla hace la ruta (la que usa el camión).
            $cuadrilla = false;

            if($matricula !== null){
                $stmtCuadrilla = $this->conn->prepare("SELECT CI FROM usa WHERE Matricula = ?");
                $stmtCuadrilla->bind_param("s", $matricula);
                $stmtCuadrilla->execute();

                $cuadrilla = $stmtCuadrilla->get_result()->fetch_assoc();
            }

            if($cuadrilla){
                $ciCuadrilla = $cuadrilla["CI"];

                $stmtCircula = $this->conn->prepare(
                    "INSERT INTO circula (CI, Id_Ruta) VALUES (?, ?)"
                );
                $stmtCircula->bind_param("si", $ciCuadrilla, $idRuta);
                $stmtCircula->execute();
            }

            $this->conn->commit();

            http_response_code(201);

            return json_encode([
                "mensaje" => "Ruta generada correctamente",
                "id_ruta" => $idRuta,
                "total_paradas" => count($ordenados),
                "distancia_estimada_m" => round(Geometria::largoRecorrido($ordenados))
            ]);

        }catch(Exception $e){

            $this->conn->rollback();

            return $this->error("No se pudo generar la ruta", 500);
        }
    }

    /**
     * La cuadrilla informa el peso del contenedor de la parada actual.
     * La parada se marca como recogida y la ruta avanza a la siguiente.
     *
     * @param int $idRuta
     * @param int|null $orden Parada que el cliente cree estar cerrando (para evitar duplicados).
     * @param mixed $peso Kilos.
     * @param string $ci Cédula de la cuadrilla.
     */
    public function registrarPeso($idRuta, $orden, $peso, $ci){

        $idRuta = intval($idRuta);

        if($peso === null || $peso === "" || !is_numeric($peso)){
            return $this->error("Debe indicar el peso del contenedor");
        }

        $peso = floatval($peso);

        if($peso < 0 || $peso > self::PESO_MAXIMO){
            return $this->error("El peso debe estar entre 0 y " . self::PESO_MAXIMO . " kg");
        }

        $peso = round($peso, 2);

        $this->conn->begin_transaction();

        try{
            $stmtRuta = $this->conn->prepare(
                "SELECT Id_Ruta, Tipo, Estado, Matricula
                 FROM ruta WHERE Id_Ruta = ? FOR UPDATE"
            );
            $stmtRuta->bind_param("i", $idRuta);
            $stmtRuta->execute();

            $ruta = $stmtRuta->get_result()->fetch_assoc();

            if(!$ruta){
                $this->conn->rollback();
                return $this->error("Ruta no encontrada", 404);
            }

            // Solo la cuadrilla que usa el camión de la ruta puede registrar pesos.
            $stmtUsa = $this->conn->prepare("SELECT CI FROM usa WHERE CI = ? AND Matricula = ?");
            $stmtUsa->bind_param("ss", $ci, $ruta["Matricula"]);
            $stmtUsa->execute();

            if($ruta["Matricula"] === null || $stmtUsa->get_result()->num_rows === 0){
                $this->conn->rollback();
                return $this->error("Esta ruta no le corresponde", 403);
            }

            if($ruta["Estado"] === "Finalizada"){
                $this->conn->rollback();
                return $this->error("La ruta ya está finalizada", 409);
            }

            $stmtSiguiente = $this->conn->prepare(
                "SELECT Orden, Id_Contenedor
                 FROM ruta_parada
                 WHERE Id_Ruta = ? AND Estado = 'Pendiente'
                 ORDER BY Orden
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmtSiguiente->bind_param("i", $idRuta);
            $stmtSiguiente->execute();

            $siguiente = $stmtSiguiente->get_result()->fetch_assoc();

            if(!$siguiente){
                $this->conn->rollback();
                return $this->error("La ruta no tiene paradas pendientes", 409);
            }

            $ordenActual = intval($siguiente["Orden"]);
            $idContenedor = intval($siguiente["Id_Contenedor"]);

            if($orden !== null && intval($orden) !== $ordenActual){
                $this->conn->rollback();
                return $this->error("La parada indicada no es la siguiente de la ruta", 409);
            }

            $stmtParada = $this->conn->prepare(
                "UPDATE ruta_parada
                 SET Estado = 'Recogido', Peso = ?, Fecha_Recoleccion = NOW()
                 WHERE Id_Ruta = ? AND Orden = ?"
            );
            $stmtParada->bind_param("dii", $peso, $idRuta, $ordenActual);
            $stmtParada->execute();

            // En una ruta de incidencias, al atender el contenedor se resuelven sus incidencias.
            if($ruta["Tipo"] === "Incidencias"){
                $stmtIncidencia = $this->conn->prepare(
                    "UPDATE incidencia SET Estado = 'Resuelta'
                     WHERE Id_Contenedor = ? AND Estado = 'Pendiente'"
                );
                $stmtIncidencia->bind_param("i", $idContenedor);
                $stmtIncidencia->execute();
            }

            $stmtPendientes = $this->conn->prepare(
                "SELECT COUNT(*) AS pendientes
                 FROM ruta_parada
                 WHERE Id_Ruta = ? AND Estado = 'Pendiente'"
            );
            $stmtPendientes->bind_param("i", $idRuta);
            $stmtPendientes->execute();

            $pendientes = intval($stmtPendientes->get_result()->fetch_assoc()["pendientes"]);

            if($pendientes === 0){
                $stmtFin = $this->conn->prepare(
                    "UPDATE ruta SET Estado = 'Finalizada', Fecha_Fin = NOW() WHERE Id_Ruta = ?"
                );
            }else{
                $stmtFin = $this->conn->prepare(
                    "UPDATE ruta SET Estado = 'En curso' WHERE Id_Ruta = ?"
                );
            }

            $stmtFin->bind_param("i", $idRuta);
            $stmtFin->execute();

            $this->conn->commit();

            return json_encode([
                "mensaje" => $pendientes === 0
                    ? "Ruta finalizada. ¡Buen trabajo!"
                    : "Peso registrado. Continúe con la siguiente parada.",
                "finalizada" => $pendientes === 0,
                "detalle" => $this->getDetalle($idRuta)
            ]);

        }catch(Exception $e){

            $this->conn->rollback();

            return $this->error("No se pudo registrar el peso", 500);
        }
    }

    public function deleteRuta($idRuta){

        $idRuta = intval($idRuta);

        $stmt = $this->conn->prepare("DELETE FROM ruta WHERE Id_Ruta = ?");
        $stmt->bind_param("i", $idRuta);
        $stmt->execute();

        if($stmt->affected_rows > 0){
            return json_encode([
                "mensaje" => "Ruta eliminada correctamente"
            ]);
        }

        return $this->error("No se encontró la ruta", 404);
    }
}

?>

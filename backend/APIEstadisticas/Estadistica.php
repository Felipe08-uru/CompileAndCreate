<?php
require_once __DIR__ . "/../Estadisticas/Calculo.php";

/**
 * Acceso a datos del panel de estadísticas.
 * El llenado sale del peso retirado en cada recolección (ruta_parada): 300 kg = 100 %.
 * Las consultas son simples; los cálculos los hace EstadisticasCalculo.
 */
class Estadistica {

    private $conn;

    public function __construct($db){
        $this->conn = $db;
    }

    private function error($mensaje, $codigo = 400){
        http_response_code($codigo);

        return json_encode(["error" => $mensaje]);
    }

    /** Fecha y hora actual según el servidor de base de datos. */
    public function ahora(){
        $fila = $this->conn->query("SELECT NOW() AS ahora")->fetch_assoc();

        return $fila["ahora"];
    }

    /**
     * Recolecciones hechas entre dos fechas ([desde, hasta)), con la zona del contenedor
     * y su porcentaje de llenado (peso retirado / 300 kg).
     */
    public function getRecolecciones($desde, $hasta){
        $sql = "SELECT p.Id_Contenedor, c.Id_Poligono, p.Fecha_Recoleccion AS Fecha, p.Peso
                FROM ruta_parada p
                INNER JOIN contenedor c ON c.Id_Contenedor = p.Id_Contenedor
                WHERE p.Estado = 'Recogido'
                AND p.Peso IS NOT NULL
                AND p.Fecha_Recoleccion >= ? AND p.Fecha_Recoleccion < ?
                ORDER BY p.Fecha_Recoleccion";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();

        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach($filas as $i => $fila){
            $filas[$i]["Nivel_Llenado"] = EstadisticasCalculo::porcentajeDeLlenado($fila["Peso"]);
        }

        return $filas;
    }

    public function getContenedores(){
        // La última recolección es la parada recogida más reciente de cada contenedor.
        $sql = "SELECT c.Id_Contenedor, c.Id_Poligono, c.Tipo, c.Latitud, c.Longitud,
                       (SELECT MAX(p.Fecha_Recoleccion)
                        FROM ruta_parada p
                        WHERE p.Id_Contenedor = c.Id_Contenedor AND p.Estado = 'Recogido') AS Fecha_Ultima_Recoleccion
                FROM contenedor c
                ORDER BY c.Id_Contenedor";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getIdsZonas(){
        $stmt = $this->conn->prepare("SELECT Id_Poligono FROM poligono ORDER BY Id_Poligono");
        $stmt->execute();

        $ids = [];

        foreach($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $fila){
            $ids[] = intval($fila["Id_Poligono"]);
        }

        return $ids;
    }

    // ------------------------------------------------------------------
    // Estadísticas (cada una devuelve un arreglo listo para json_encode)
    // ------------------------------------------------------------------

    public function desbordados($mes, $limite){
        $ahora = $this->ahora();
        $rango = EstadisticasCalculo::rangoDelMes($mes, $ahora);

        if($rango === null){
            return null;
        }

        $recolecciones = $this->getRecolecciones($rango["desde"], $rango["hasta"]);

        return [
            "mes" => $rango["mes"],
            "umbral" => EstadisticasCalculo::UMBRAL_DESBORDE,
            "umbral_kg" => EstadisticasCalculo::UMBRAL_DESBORDE * EstadisticasCalculo::CAPACIDAD_KG / 100,
            "capacidad_kg" => EstadisticasCalculo::CAPACIDAD_KG,
            "total_desbordes" => count(array_filter($recolecciones, function($l){
                return EstadisticasCalculo::esDesbordado($l["Nivel_Llenado"]);
            })),
            "contenedores" => EstadisticasCalculo::topDesbordados($recolecciones, $rango["desde"], $rango["hasta"], $limite)
        ];
    }

    public function promedio($dias, $limite){
        $ahora = $this->ahora();
        $desde = date("Y-m-d H:i:s", strtotime($ahora . " -" . intval($dias) . " days"));

        $recolecciones = $this->getRecolecciones($desde, date("Y-m-d H:i:s", strtotime($ahora . " +1 day")));
        $lista = EstadisticasCalculo::promedioPorContenedor($recolecciones);
        $general = count($lista) > 0
            ? round(array_sum(array_column($lista, "Promedio")) / count($lista), 1)
            : null;

        return [
            "dias" => intval($dias),
            "capacidad_kg" => EstadisticasCalculo::CAPACIDAD_KG,
            "promedio_general" => $general,
            "contenedores" => array_slice($lista, 0, max(0, intval($limite)))
        ];
    }

    public function zonas($dias){
        $ahora = $this->ahora();
        $desde = date("Y-m-d H:i:s", strtotime($ahora . " -" . intval($dias) . " days"));

        $recolecciones = $this->getRecolecciones($desde, date("Y-m-d H:i:s", strtotime($ahora . " +1 day")));

        return [
            "dias" => intval($dias),
            "zonas" => EstadisticasCalculo::promedioPorZona($recolecciones, $this->getIdsZonas())
        ];
    }

    public function limpieza(){
        $ahora = $this->ahora();

        return [
            "ahora" => $ahora,
            "contenedores" => EstadisticasCalculo::limpiezaPorContenedor($this->getContenedores(), $ahora)
        ];
    }
}
?>

<?php
/**
 * Utilidades geométricas compartidas por Poligono, Contenedor y Ruta.
 */
class Geometria {

    /**
     * Distancia en metros entre dos coordenadas (fórmula de Haversine).
     */
    public static function distanciaMetros($lat1, $lng1, $lat2, $lng2){
        $radioTierra = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        return $radioTierra * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Indica si un punto está dentro de un polígono (algoritmo de ray casting).
     *
     * @param float $lat
     * @param float $lng
     * @param array $vertices Lista de ["latitud" => x, "longitud" => y] en orden.
     */
    public static function puntoEnPoligono($lat, $lng, $vertices){
        $n = count($vertices);

        if($n < 3){
            return false;
        }

        $dentro = false;
        $j = $n - 1;

        for($i = 0; $i < $n; $i++){
            $latI = floatval($vertices[$i]["latitud"]);
            $lngI = floatval($vertices[$i]["longitud"]);
            $latJ = floatval($vertices[$j]["latitud"]);
            $lngJ = floatval($vertices[$j]["longitud"]);

            $cruza = (($lngI > $lng) !== ($lngJ > $lng)) &&
                     ($lat < ($latJ - $latI) * ($lng - $lngI) / ($lngJ - $lngI) + $latI);

            if($cruza){
                $dentro = !$dentro;
            }

            $j = $i;
        }

        return $dentro;
    }

    /**
     * Ordena los puntos para recorrerlos con la menor distancia posible.
     *
     * Es un problema de "viajante de comercio" (camino abierto). Se resuelve con:
     *   1) vecino más cercano partiendo de cada punto posible, y
     *   2) mejora local 2-opt sobre cada recorrido obtenido.
     * Se conserva el recorrido más corto. Las distancias son en línea recta
     * (Haversine); el trazado sobre calles lo calcula luego Leaflet Routing Machine.
     *
     * @param array $puntos Lista de elementos con claves "lat" y "lng" (y los datos que se quieran).
     * @return array Los mismos elementos, reordenados.
     */
    public static function optimizarOrden($puntos){
        $n = count($puntos);

        if($n <= 2){
            return array_values($puntos);
        }

        $puntos = array_values($puntos);

        // Matriz de distancias.
        $d = [];
        for($i = 0; $i < $n; $i++){
            for($j = 0; $j < $n; $j++){
                $d[$i][$j] = ($i === $j) ? 0 : self::distanciaMetros(
                    $puntos[$i]["lat"], $puntos[$i]["lng"],
                    $puntos[$j]["lat"], $puntos[$j]["lng"]
                );
            }
        }

        // Si hay muchos puntos se limitan los puntos de partida a probar.
        $inicios = range(0, $n - 1);
        if($n > 60){
            $inicios = self::puntosExtremos($puntos);
        }

        $mejorCamino = null;
        $mejorLargo = INF;

        foreach($inicios as $inicio){
            $camino = self::vecinoMasCercano($d, $n, $inicio);
            $camino = self::dosOpt($d, $camino);
            $largo = self::largoCamino($d, $camino);

            if($largo < $mejorLargo - 1e-9){
                $mejorLargo = $largo;
                $mejorCamino = $camino;
            }
        }

        // Los dos extremos son equivalentes en distancia: se empieza por el más
        // al norte para que el resultado sea siempre el mismo.
        if($puntos[$mejorCamino[0]]["lat"] < $puntos[$mejorCamino[$n - 1]]["lat"]){
            $mejorCamino = array_reverse($mejorCamino);
        }

        $resultado = [];
        foreach($mejorCamino as $indice){
            $resultado[] = $puntos[$indice];
        }

        return $resultado;
    }

    /**
     * Largo total (en metros) de un recorrido ya ordenado.
     */
    public static function largoRecorrido($puntos){
        $total = 0;

        for($i = 0; $i < count($puntos) - 1; $i++){
            $total += self::distanciaMetros(
                $puntos[$i]["lat"], $puntos[$i]["lng"],
                $puntos[$i + 1]["lat"], $puntos[$i + 1]["lng"]
            );
        }

        return $total;
    }

    private static function vecinoMasCercano($d, $n, $inicio){
        $visitado = array_fill(0, $n, false);
        $camino = [$inicio];
        $visitado[$inicio] = true;
        $actual = $inicio;

        for($paso = 1; $paso < $n; $paso++){
            $mejor = -1;
            $mejorDist = INF;

            for($j = 0; $j < $n; $j++){
                if(!$visitado[$j] && $d[$actual][$j] < $mejorDist){
                    $mejorDist = $d[$actual][$j];
                    $mejor = $j;
                }
            }

            $camino[] = $mejor;
            $visitado[$mejor] = true;
            $actual = $mejor;
        }

        return $camino;
    }

    /**
     * 2-opt para camino abierto: invierte tramos mientras eso acorte el recorrido.
     */
    private static function dosOpt($d, $camino){
        $n = count($camino);
        $mejoro = true;
        $iteraciones = 0;

        while($mejoro && $iteraciones < 200){
            $mejoro = false;
            $iteraciones++;

            for($i = 0; $i < $n - 1; $i++){
                for($j = $i + 1; $j < $n; $j++){
                    $b = $camino[$i];
                    $c = $camino[$j];

                    $delta = 0;

                    if($i > 0){
                        $a = $camino[$i - 1];
                        $delta += $d[$a][$c] - $d[$a][$b];
                    }

                    if($j < $n - 1){
                        $e = $camino[$j + 1];
                        $delta += $d[$b][$e] - $d[$c][$e];
                    }

                    if($delta < -1e-9){
                        $tramo = array_reverse(array_slice($camino, $i, $j - $i + 1));
                        array_splice($camino, $i, $j - $i + 1, $tramo);
                        $mejoro = true;
                    }
                }
            }
        }

        return $camino;
    }

    private static function largoCamino($d, $camino){
        $total = 0;

        for($i = 0; $i < count($camino) - 1; $i++){
            $total += $d[$camino[$i]][$camino[$i + 1]];
        }

        return $total;
    }

    private static function puntosExtremos($puntos){
        $extremos = [];
        $claves = ["lat", "lng"];

        foreach($claves as $clave){
            $min = 0;
            $max = 0;

            foreach($puntos as $i => $p){
                if($p[$clave] < $puntos[$min][$clave]){
                    $min = $i;
                }
                if($p[$clave] > $puntos[$max][$clave]){
                    $max = $i;
                }
            }

            $extremos[] = $min;
            $extremos[] = $max;
        }

        return array_values(array_unique($extremos));
    }
}

?>

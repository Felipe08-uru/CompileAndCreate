<?php

/**
 * Cálculos del panel de estadísticas.
 *
 * Son funciones puras (no tocan la base de datos): reciben filas ya leídas y
 * devuelven los resultados, así se pueden probar con tests unitarios.
 *
 * El llenado de un contenedor sale del peso que la cuadrilla retiró al recogerlo:
 * CAPACIDAD_KG (300 kg) equivalen a 100 %.
 *
 * Una "recolección" es un arreglo con:
 *   Id_Contenedor, Id_Poligono (puede ser null), Fecha ("Y-m-d H:i:s"),
 *   Peso (kg, opcional) y Nivel_Llenado (0 a 100, ver porcentajeDeLlenado)
 */
class EstadisticasCalculo {

    // Kilos que equivalen a un contenedor lleno (100 %).
    const CAPACIDAD_KG = 300;

    // Un contenedor con este nivel de llenado (o más) se considera desbordado (270 kg).
    const UMBRAL_DESBORDE = 90;

    // Categorías de llenado promedio (en %): bajo < 40, medio < 65, alto < 85, crítico >= 85.
    const LIMITES_LLENADO = [40, 65, 85];

    // Categorías de días sin limpiar: reciente <= 2, normal <= 6, atrasado <= 13, crítico >= 14.
    const LIMITES_LIMPIEZA = [2, 6, 13];

    /**
     * Porcentaje de llenado (0 a 100) a partir de los kilos retirados.
     * Más de CAPACIDAD_KG se considera 100 %. Devuelve null si el peso no es un número.
     */
    public static function porcentajeDeLlenado($peso){
        if($peso === null || $peso === "" || !is_numeric($peso)){
            return null;
        }

        $porcentaje = floatval($peso) / self::CAPACIDAD_KG * 100;

        return round(max(0, min(100, $porcentaje)), 2);
    }

    public static function esDesbordado($nivel){
        return floatval($nivel) >= self::UMBRAL_DESBORDE;
    }

    /**
     * Devuelve [desde, hasta) de un mes "YYYY-MM" (o del mes de $ahora si es null).
     * Devuelve null si el formato es inválido.
     */
    public static function rangoDelMes($mes, $ahora){
        if($mes === null || $mes === ""){
            $mes = date("Y-m", strtotime($ahora));
        }

        if(!preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $mes)){
            return null;
        }

        $desde = $mes . "-01 00:00:00";
        $hasta = date("Y-m-d H:i:s", strtotime($desde . " +1 month"));

        return ["mes" => $mes, "desde" => $desde, "hasta" => $hasta];
    }

    /**
     * Contenedores con más desbordes en el rango [desde, hasta).
     * Ordena por cantidad de desbordes y, a igualdad, por nivel máximo.
     */
    public static function topDesbordados($recolecciones, $desde, $hasta, $limite = 10){
        $porContenedor = [];

        foreach($recolecciones as $l){
            if($l["Fecha"] < $desde || $l["Fecha"] >= $hasta){
                continue;
            }

            if(!self::esDesbordado($l["Nivel_Llenado"])){
                continue;
            }

            $id = intval($l["Id_Contenedor"]);

            if(!isset($porContenedor[$id])){
                $porContenedor[$id] = [
                    "Id_Contenedor" => $id,
                    "Id_Poligono" => $l["Id_Poligono"] !== null ? intval($l["Id_Poligono"]) : null,
                    "Veces" => 0,
                    "Nivel_Maximo" => 0.0,
                    "Ultima_Vez" => $l["Fecha"]
                ];
            }

            $porContenedor[$id]["Veces"]++;
            $porContenedor[$id]["Nivel_Maximo"] = max($porContenedor[$id]["Nivel_Maximo"], floatval($l["Nivel_Llenado"]));

            if($l["Fecha"] > $porContenedor[$id]["Ultima_Vez"]){
                $porContenedor[$id]["Ultima_Vez"] = $l["Fecha"];
            }
        }

        $lista = array_values($porContenedor);

        usort($lista, function($a, $b){
            if($a["Veces"] !== $b["Veces"]){
                return $b["Veces"] - $a["Veces"];
            }

            if($a["Nivel_Maximo"] !== $b["Nivel_Maximo"]){
                return $b["Nivel_Maximo"] <=> $a["Nivel_Maximo"];
            }

            return $a["Id_Contenedor"] - $b["Id_Contenedor"];
        });

        return array_slice($lista, 0, max(0, intval($limite)));
    }

    /**
     * Nivel de llenado promedio de cada contenedor, del más lleno al menos lleno.
     */
    public static function promedioPorContenedor($recolecciones, $limite = null){
        $acum = [];

        foreach($recolecciones as $l){
            $id = intval($l["Id_Contenedor"]);

            if(!isset($acum[$id])){
                $acum[$id] = [
                    "Id_Contenedor" => $id,
                    "Id_Poligono" => $l["Id_Poligono"] !== null ? intval($l["Id_Poligono"]) : null,
                    "suma" => 0.0,
                    "kilos" => 0.0,
                    "Recolecciones" => 0
                ];
            }

            $acum[$id]["suma"] += floatval($l["Nivel_Llenado"]);
            $acum[$id]["kilos"] += isset($l["Peso"]) ? floatval($l["Peso"]) : 0.0;
            $acum[$id]["Recolecciones"]++;
        }

        $lista = [];

        foreach($acum as $a){
            $promedio = round($a["suma"] / $a["Recolecciones"], 1);

            $lista[] = [
                "Id_Contenedor" => $a["Id_Contenedor"],
                "Id_Poligono" => $a["Id_Poligono"],
                "Promedio" => $promedio,
                "Peso_Promedio" => round($a["kilos"] / $a["Recolecciones"], 1),
                "Recolecciones" => $a["Recolecciones"],
                "Categoria" => self::categoriaLlenado($promedio)
            ];
        }

        usort($lista, function($a, $b){
            if($a["Promedio"] !== $b["Promedio"]){
                return $b["Promedio"] <=> $a["Promedio"];
            }

            return $a["Id_Contenedor"] - $b["Id_Contenedor"];
        });

        return $limite === null ? $lista : array_slice($lista, 0, max(0, intval($limite)));
    }

    /**
     * Llenado promedio de cada zona. Las zonas de $idsZonas sin recolecciones
     * aparecen con Promedio null y categoría "sin_datos".
     */
    public static function promedioPorZona($recolecciones, $idsZonas = []){
        $acum = [];

        foreach($recolecciones as $l){
            if($l["Id_Poligono"] === null){
                continue;
            }

            $z = intval($l["Id_Poligono"]);

            if(!isset($acum[$z])){
                $acum[$z] = ["suma" => 0.0, "n" => 0, "contenedores" => []];
            }

            $acum[$z]["suma"] += floatval($l["Nivel_Llenado"]);
            $acum[$z]["n"]++;
            $acum[$z]["contenedores"][intval($l["Id_Contenedor"])] = true;
        }

        foreach($idsZonas as $idZona){
            if(!isset($acum[intval($idZona)])){
                $acum[intval($idZona)] = ["suma" => 0.0, "n" => 0, "contenedores" => []];
            }
        }

        $lista = [];

        foreach($acum as $z => $a){
            $promedio = $a["n"] > 0 ? round($a["suma"] / $a["n"], 1) : null;

            $lista[] = [
                "Id_Poligono" => $z,
                "Promedio" => $promedio,
                "Recolecciones" => $a["n"],
                "Contenedores" => count($a["contenedores"]),
                "Categoria" => $promedio === null ? "sin_datos" : self::categoriaLlenado($promedio)
            ];
        }

        usort($lista, function($a, $b){
            if($a["Promedio"] === $b["Promedio"]){
                return $a["Id_Poligono"] - $b["Id_Poligono"];
            }

            if($a["Promedio"] === null) return 1;
            if($b["Promedio"] === null) return -1;

            return $b["Promedio"] <=> $a["Promedio"];
        });

        return $lista;
    }

    public static function categoriaLlenado($promedio){
        if($promedio === null){
            return "sin_datos";
        }

        if($promedio < self::LIMITES_LLENADO[0]) return "bajo";
        if($promedio < self::LIMITES_LLENADO[1]) return "medio";
        if($promedio < self::LIMITES_LLENADO[2]) return "alto";

        return "critico";
    }

    /**
     * Días enteros transcurridos desde la última recolección (null si nunca se recogió).
     */
    public static function diasDesdeLimpieza($fecha, $ahora){
        if($fecha === null || $fecha === ""){
            return null;
        }

        $segundos = strtotime($ahora) - strtotime($fecha);

        if($segundos < 0){
            return 0;
        }

        return intval(floor($segundos / 86400));
    }

    public static function categoriaLimpieza($dias){
        if($dias === null) return "nunca";

        if($dias <= self::LIMITES_LIMPIEZA[0]) return "reciente";
        if($dias <= self::LIMITES_LIMPIEZA[1]) return "normal";
        if($dias <= self::LIMITES_LIMPIEZA[2]) return "atrasado";

        return "critico";
    }

    /**
     * Contenedores con su antigüedad de limpieza, del que lleva más tiempo sin
     * limpiarse al que menos. Los que nunca se recogieron van primero.
     *
     * @param array $contenedores Filas con Id_Contenedor, Id_Poligono, Tipo, Latitud, Longitud, Fecha_Ultima_Recoleccion
     */
    public static function limpiezaPorContenedor($contenedores, $ahora){
        $lista = [];

        foreach($contenedores as $c){
            $dias = self::diasDesdeLimpieza($c["Fecha_Ultima_Recoleccion"], $ahora);

            $lista[] = [
                "Id_Contenedor" => intval($c["Id_Contenedor"]),
                "Id_Poligono" => $c["Id_Poligono"] !== null ? intval($c["Id_Poligono"]) : null,
                "Tipo" => $c["Tipo"],
                "Latitud" => floatval($c["Latitud"]),
                "Longitud" => floatval($c["Longitud"]),
                "Fecha_Ultima_Recoleccion" => $c["Fecha_Ultima_Recoleccion"],
                "Dias" => $dias,
                "Categoria" => self::categoriaLimpieza($dias)
            ];
        }

        usort($lista, function($a, $b){
            if($a["Dias"] === $b["Dias"]){
                return $a["Id_Contenedor"] - $b["Id_Contenedor"];
            }

            if($a["Dias"] === null) return -1;
            if($b["Dias"] === null) return 1;

            return $b["Dias"] - $a["Dias"];
        });

        return $lista;
    }
}
?>

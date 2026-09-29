<?php

namespace App\Services\Recepcion;

use Smalot\PdfParser\Parser as PdfParser;

/**
 * Port a PHP del detector de extensiones de estancia que antes vivía como
 * app de escritorio independiente de Recepción (script Python: "Salidas vs
 * Llegadas" — motor V4 línea por línea). Compara la lista de huéspedes que
 * salen (departures) contra la de huéspedes que llegan (arrivals) y detecta
 * coincidencias de nombre tolerantes a typos y variaciones de formato, para
 * encontrar quién probablemente extendió su estancia en vez de irse.
 *
 * Sin estado ni dependencia de Eloquent a propósito: recibe rutas de PDF,
 * regresa arrays. Quien llama (ExtensionDeteccionController) decide qué
 * persistir.
 *
 * Nota sobre el algoritmo de similitud: el script original en Python usa
 * difflib.SequenceMatcher.ratio(). PHP no trae un equivalente exacto; se usa
 * similar_text() (nativo de PHP) como sustituto pragmático — mismo
 * propósito (tolerar errores de captura/typos en nombres), umbrales
 * idénticos a los del script original (0.70 por palabra, 0.55 para aceptar
 * el match completo), aunque el número exacto que arroja para un par de
 * cadenas puede diferir ligeramente del original.
 */
class ExtensionDetectorService
{
    private const AGENCIAS = '/\b(t-expedia|t-roiback|t-booking|despegar|agoda|hotelbeds|lan)\b/u';
    private const TITULOS   = '/\b(sr|sra|srita|mr|mrs|miss)\b\.?/u';

    /** Similitud mínima (0–1) para que una palabra individual cuente como coincidencia. */
    private const UMBRAL_PALABRA = 0.70;

    /** Similitud mínima (0–1) de nombre completo para aceptar el match como posible extensión. */
    private const UMBRAL_MATCH_COMPLETO = 0.55;

    /**
     * Tope de huéspedes por lista para una sola comparación.
     * compararListasHuespedes() es O(salidas × llegadas) — con más de un
     * puñado de cientos de nombres por lado, el número de pares a comparar
     * (y de llamadas a similar_text() dentro de cada par) crece rápido y
     * puede tardar varios segundos, arriesgando el límite de tiempo de
     * ejecución de PHP. El controlador valida este tope ANTES de llamar a
     * compararListasHuespedes() y le pide al usuario dividir el PDF si lo
     * excede, en vez de dejar que la petición se cuelgue o truene a medias.
     */
    public const MAX_HUESPEDES_POR_LISTA = 500;

    /**
     * Extrae del PDF un diccionario de nombres normalizados => datos, igual
     * que el script original: agrupa por nombre normalizado y cuenta
     * cuántas reservas aparecen bajo ese mismo nombre.
     *
     * @return array<string, array{parts: string[], count: int, original: string}>
     */
    public function extraerNombresPdf(string $rutaLocal): array
    {
        $parser = new PdfParser();
        $pdf    = $parser->parseFile($rutaLocal);

        $lineasSucias = [];
        foreach ($pdf->getPages() as $page) {
            $texto = $page->getText();
            if ($texto !== '') {
                $lineasSucias = array_merge($lineasSucias, preg_split('/\r\n|\r|\n/', $texto));
            }
        }

        $lineas = [];
        foreach ($lineasSucias as $l) {
            $l = trim($l);
            if ($l !== '') {
                $lineas[] = $l;
            }
        }

        // Combina líneas huérfanas (ej: apellido arriba, nombre abajo en
        // columnas angostas): si la siguiente línea trae una coma de
        // nombre y la actual es solo texto, se pegan.
        $lineasProcesadas = [];
        $n = count($lineas);
        $i = 0;
        while ($i < $n) {
            $lineaActual = $lineas[$i];
            if ($i + 1 < $n) {
                $siguiente = $lineas[$i + 1];
                if (str_contains($siguiente, ',') && preg_match('/^[\p{L}\s]+$/u', $lineaActual)) {
                    $lineaActual = $lineaActual . ' ' . $siguiente;
                    $i++;
                }
            }
            $lineasProcesadas[] = $lineaActual;
            $i++;
        }

        $todosLosMatches = [];
        foreach ($lineasProcesadas as $line) {
            $lineCleaned = preg_replace('/^[^\p{L}]+/u', '', $line);
            $lineCleaned = preg_replace('/[^\p{L}]+$/u', '', $lineCleaned);

            // Estrategia 1: nombres con coma (Apellido, Nombre), máximo 3
            // palabras de cada lado para no devorar texto de más.
            preg_match_all(
                '/([\p{L}]+(?:\s+[\p{L}]+){0,2}\s*,\s*[\p{L}]+(?:\s+[\p{L}]+){0,2})/u',
                (string) $lineCleaned,
                $matchesComa
            );

            // Estrategia 2: nombres sin coma pegados a un número de reserva.
            preg_match_all(
                '/([\p{L}]+\s+[\p{L}]+(?:\s+[\p{L}]+){0,2})\s+(?:T-)?\d{6,8}\b/u',
                $line,
                $matchesSinComa
            );

            $todosLosMatches = array_merge(
                $todosLosMatches,
                $matchesComa[1] ?? [],
                $matchesSinComa[1] ?? []
            );
        }

        $nombresDict = [];
        foreach ($todosLosMatches as $m) {
            $norm = $this->normalizarNombre($m);
            if (count($norm) >= 2) { // exige al menos nombre y apellido
                $nStr = implode(' ', $norm);
                if (isset($nombresDict[$nStr])) {
                    $nombresDict[$nStr]['count']++;
                } else {
                    $nombresDict[$nStr] = [
                        'parts'    => $norm,
                        'count'    => 1,
                        'original' => mb_convert_case(trim($m), MB_CASE_TITLE, 'UTF-8'),
                    ];
                }
            }
        }

        return $nombresDict;
    }

    /**
     * Limpia la basura extrema del PDF (agencias de viaje y títulos) para
     * dejar solo el nombre real, y regresa las palabras (>=2 letras) que lo
     * componen.
     *
     * @return string[]
     */
    public function normalizarNombre(string $texto): array
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = preg_replace(self::AGENCIAS, '', $texto);
        $texto = preg_replace(self::TITULOS, '', $texto);

        if (str_contains($texto, ',')) {
            $partes = explode(',', $texto, 2);
            if (count($partes) >= 2) {
                $texto = trim($partes[1]) . ' ' . trim($partes[0]);
            }
        }

        // \p{Ll} (letra minúscula Unicode) en vez de enumerar acentos a
        // mano: cubre cualquier vocal acentuada o diéresis (ej. "ü"), no
        // solo las seis que el script original tenía hardcodeadas.
        $texto = preg_replace('/[^\p{Ll}\s]/u', '', $texto);

        $piezas = preg_split('/\s+/u', trim($texto), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($piezas, fn ($p) => mb_strlen($p, 'UTF-8') >= 2));
    }

    /**
     * Compara dos listas de partes de nombre, tolerante a typos. Cada
     * palabra de $n1 busca su mejor pareja en $n2 (similitud >= 0.70); el
     * score final es la SUMA PONDERADA de esas similitudes / peso total del
     * nombre MÁS LARGO (no el promedio, ni un conteo simple — ver por qué
     * abajo).
     *
     * $pesos (opcional) mapea palabra => peso (0–1], típicamente el
     * resultado de calcularPesosPalabras(). Si se omite, toda palabra pesa
     * 1.0 — es decir, sin $pesos este método se comporta EXACTAMENTE igual
     * que antes de que existiera la ponderación (compatibilidad hacia
     * atrás a propósito, para no romper nada que ya dependa de llamarlo con
     * solo dos argumentos).
     *
     * A propósito ya no es un conteo binario de "coincide sí/no": una
     * palabra que hace match justo al límite (ej. "saul"~"raul" al 75%)
     * ahora pesa menos que una que coincide exacta (100%). Con el conteo
     * binario anterior, ambas sumaban lo mismo (+1), lo que dejaba nombres
     * de 2 palabras con una coincidencia exacta de apellido + una parecida
     * "de refilón" en el nombre con la misma confianza que un match
     * perfecto — ej. "Saul Lara" contra "Tienda Lara, Raul" (un nombre
     * con una palabra extra ajena, "Tienda") salía en 80% en vez de un más
     * honesto 70%, viéndose "de alta confianza" cuando en realidad solo un
     * apellido calzó exacto y el nombre fue un match parcial.
     *
     * El divisor cambió de promedio a MÁXIMO el 2026-08-22 (reportado por
     * Abel con datos reales: "Dominguez,Fabian" [saliendo] emparejó con
     * "Dominguez Paz,Omar Fabiano" [llegando] a 64.1% — arriba del umbral
     * de aceptación — pese a que solo el apellido "Dominguez" (muy común)
     * coincide; "Fabian" nunca aparece en el otro nombre, pero
     * similar_text() lo empareja con "Fabiano" a un altísimo 92% por pura
     * coincidencia de letras (son nombres reales distintos, no un typo del
     * mismo nombre — a diferencia de "saul"~"raul", que SÍ es un typo de
     * una letra). Con el promedio como divisor, las 2 palabras SIN pareja
     * del nombre más largo ("Omar" y "Paz") apenas diluían el score. Con
     * el máximo como divisor, cualquier palabra del nombre más largo que
     * se queda sin pareja cuenta con todo su peso en contra — separa
     * limpiamente este caso (64.1% → 48.1%) del caso "Saul Lara" que sí se
     * quiere seguir detectando (70% → 58.3%, sigue arriba del umbral).
     *
     * El PESO POR PALABRA se agregó el mismo día, más tarde: el máximo
     * como divisor ya resolvía el caso reportado, pero seguía siendo
     * vulnerable en general a que un apellido muy común (que por pura
     * coincidencia aparece en varios huéspedes de este mismo cruce)
     * cuente IGUAL que uno raro — el problema de fondo de Fabian/Fabiano
     * no era el conteo de palabras, era que "Dominguez" no debería ser
     * evidencia fuerte de nada, sin importar cuántas palabras sueltas haya
     * alrededor. Verificado con los 14 nombres de salida y 43 de llegada
     * reales de los 3 PDFs que Abel adjuntó: "Dominguez,Fabian" vs
     * "Dominguez Paz,Omar Fabiano" baja de 48.1% a 44.3% (más margen
     * todavía bajo el umbral de 55%, porque "Dominguez" resultó ser el
     * apellido más repetido de todo el cruce), mientras "Saul Lara" vs
     * "Tienda Lara, Raul" en realidad SUBE a 68.2% (era 58.3% con solo el
     * máximo) porque "Saul" es una palabra rara en este cruce y pesa su
     * 1.0 completo. De paso, esto prepara el terreno para cuando aparezcan
     * nombres largos/con doble apellido: un apellido exacto pero muy común
     * ya no basta por sí solo para levantar confianza, sea cual sea la
     * longitud del nombre.
     */
    public function scoreNombres(array $n1, array $n2, array $pesos = []): float
    {
        if (empty($n1) || empty($n2)) {
            return 0.0;
        }

        $peso = fn (string $palabra): float => $pesos[$palabra] ?? 1.0;

        $n2Temp = $n2;
        $sumaSimilitud = 0.0;

        foreach ($n1 as $a) {
            $mejorSimilitud = 0.0;
            $mejorIdx = -1;

            foreach ($n2Temp as $idx => $b) {
                $sim = $this->similitudTexto($a, $b);
                if ($sim > $mejorSimilitud) {
                    $mejorSimilitud = $sim;
                    $mejorIdx = $idx;
                }
            }

            if ($mejorSimilitud >= self::UMBRAL_PALABRA && $mejorIdx !== -1) {
                $sumaSimilitud += $mejorSimilitud * $peso($a);
                unset($n2Temp[$mejorIdx]);
            }
        }

        $pesoTotal = max(
            array_sum(array_map($peso, $n1)),
            array_sum(array_map($peso, $n2))
        );

        return $pesoTotal > 0 ? $sumaSimilitud / $pesoTotal : 0.0;
    }

    /**
     * Calcula, para cada palabra que aparece en cualquiera de las dos
     * listas (salidas y llegadas), un peso inversamente proporcional a en
     * cuántos huéspedes DISTINTOS de este mismo cruce aparece esa palabra
     * — no por reservación, para que una persona con varias reservas bajo
     * el mismo nombre (ej. "Park,Minjun" con 4 cuartos) no infle
     * artificialmente qué tan "común" parece su apellido. Una palabra que
     * solo aparece en un huésped pesa 1.0 (evidencia fuerte de que es la
     * misma persona si coincide); una que se repite entre varios huéspedes
     * pesa menos (coincidir en ella, por sí sola, dice poco).
     *
     * Se calcula UNA VEZ por comparación completa (no por par) y se pasa a
     * cada llamada de scoreNombres() — recorrer ambos diccionarios es
     * O(salidas + llegadas), insignificante comparado con el O(salidas ×
     * llegadas) de la comparación en sí.
     *
     * @return array<string, float>
     */
    public function calcularPesosPalabras(array $depDict, array $arrDict): array
    {
        $frecuencia = [];

        foreach ([$depDict, $arrDict] as $dict) {
            foreach ($dict as $data) {
                foreach (array_unique($data['parts']) as $palabra) {
                    $frecuencia[$palabra] = ($frecuencia[$palabra] ?? 0) + 1;
                }
            }
        }

        return array_map(fn ($frec) => 1.0 / $frec, $frecuencia);
    }

    private function similitudTexto(string $a, string $b): float
    {
        similar_text($a, $b, $porcentaje);
        return $porcentaje / 100;
    }

    /**
     * Cruza el diccionario de salidas contra el de llegadas y regresa los
     * posibles casos de extensión de estancia, ordenados por confianza
     * descendente. Umbral de aceptación: 55% de similitud de nombre
     * completo (igual que el script original).
     *
     * @return array<int, array{salida: string, llegada: string, similitud: float}>
     */
    public function compararListasHuespedes(array $depDict, array $arrDict): array
    {
        $pesos = $this->calcularPesosPalabras($depDict, $arrDict);

        $resultados = [];

        foreach ($depDict as $dData) {
            $mejorAKey = null;
            $mejorScore = 0.0;

            foreach ($arrDict as $aKey => $aData) {
                $score = $this->scoreNombres($dData['parts'], $aData['parts'], $pesos);
                if ($score > $mejorScore) {
                    $mejorScore = $score;
                    $mejorAKey = $aKey;
                }
            }

            if ($mejorScore >= self::UMBRAL_MATCH_COMPLETO && $mejorAKey !== null) {
                $aData = $arrDict[$mejorAKey];

                $dResStr = $dData['count'] > 1 ? " [{$dData['count']} Reservas]" : '';
                $aResStr = $aData['count'] > 1 ? " [{$aData['count']} Reservas]" : '';

                $nomSalida  = trim((string) preg_replace('/(?i)(t-expedia|t-roiback)/', '', $dData['original']), ', ');
                $nomLlegada = trim((string) preg_replace('/(?i)(t-expedia|t-roiback)/', '', $aData['original']), ', ');

                $resultados[] = [
                    'salida'    => $nomSalida . $dResStr,
                    'llegada'   => $nomLlegada . $aResStr,
                    'similitud' => round($mejorScore * 100, 1),
                ];
            }
        }

        usort($resultados, fn ($a, $b) => $b['similitud'] <=> $a['similitud']);

        return $resultados;
    }
}

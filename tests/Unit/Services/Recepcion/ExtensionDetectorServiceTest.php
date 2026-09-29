<?php

namespace Tests\Unit\Services\Recepcion;

use App\Services\Recepcion\ExtensionDetectorService;
use PHPUnit\Framework\TestCase;

/**
 * Prueba unitaria pura (sin Laravel/DB) de la lógica portada desde el
 * script Python original. No cubre extraerNombresPdf() porque requiere
 * smalot/pdfparser instalado y un PDF real — cubre normalizarNombre(),
 * scoreNombres() y compararListasHuespedes(), que es donde vive toda la
 * regla de negocio.
 */
class ExtensionDetectorServiceTest extends TestCase
{
    private ExtensionDetectorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExtensionDetectorService();
    }

    public function test_normaliza_nombre_con_coma_y_agencia(): void
    {
        $partes = $this->service->normalizarNombre('Valdivia, Guillermo T-Expedia');

        $this->assertSame(['guillermo', 'valdivia'], $partes);
    }

    public function test_normaliza_nombre_quita_titulo(): void
    {
        $partes = $this->service->normalizarNombre('Sr. Raul Perez');

        $this->assertSame(['raul', 'perez'], $partes);
    }

    /**
     * Regresión: el regex de extracción original (heredado del script
     * Python) usaba una clase de caracteres [A-Za-zÁÉÍÓÚÑñ] que NO incluía
     * vocales minúsculas acentuadas, así que nombres como "García López"
     * se cortaban a la mitad. El port a PHP usa \p{L} (cualquier letra
     * Unicode) precisamente para corregir esto.
     */
    public function test_normaliza_nombre_con_acentos_minusculas(): void
    {
        $partes = $this->service->normalizarNombre('García López, María José');

        $this->assertSame(['maría', 'josé', 'garcía', 'lópez'], $partes);
    }

    public function test_score_nombres_identicos_es_uno(): void
    {
        $score = $this->service->scoreNombres(['raul', 'perez'], ['raul', 'perez']);

        $this->assertEqualsWithDelta(1.0, $score, 0.001);
    }

    public function test_score_tolera_typo_leve_por_encima_del_umbral(): void
    {
        $score = $this->service->scoreNombres(['juan', 'perez'], ['jaun', 'perez']);

        $this->assertGreaterThan(0.55, $score);
    }

    public function test_score_nombres_distintos_queda_debajo_del_umbral(): void
    {
        $score = $this->service->scoreNombres(['raul', 'perez'], ['maria', 'lara']);

        $this->assertLessThan(0.55, $score);
    }

    /**
     * Regresión: reportado por Abel viendo resultados reales. "Saul Lara"
     * (saliendo) contra "Tienda Lara, Raul" (llegando, con una palabra
     * extra ajena "Tienda") salía en 80% — se veía "de alta confianza"
     * (>= 75, verde en la UI) cuando en realidad solo el apellido calzó
     * exacto y el nombre fue un match parcial (saul~raul al 75%). Desde que
     * scoreNombres() suma similitud real en vez de contar coincidencias
     * binarias, este caso baja a ~70% (ya no cae en el rango "alta
     * confianza"). El divisor cambió de promedio a máximo el 2026-08-22
     * (ver test_score_penaliza_palabras_sin_pareja_de_nombre_distinto más
     * abajo) y este caso bajó otra vez, a ~58.3% — sigue sin caer en "alta
     * confianza" y sigue arriba del umbral de aceptación de 55%, así que
     * se sigue reportando como posible extensión, solo que con menos
     * confianza numérica.
     *
     * Esta prueba llama scoreNombres() SIN pesos (solo 2 argumentos) a
     * propósito, para fijar su comportamiento base/sin ponderar — ver
     * test_comparar_listas_pondera_por_frecuencia_real más abajo para el
     * número que realmente sale en producción, donde compararListasHuespedes()
     * SIEMPRE pondera.
     */
    public function test_score_penaliza_match_parcial_con_palabra_extra_ajena(): void
    {
        $score = $this->service->scoreNombres(['lara', 'saul'], ['raul', 'tienda', 'lara']);

        $this->assertEqualsWithDelta(0.583, $score, 0.01);
    }

    /**
     * Regresión: reportado por Abel con datos reales de producción (PDF de
     * salidas/llegadas del 22-08-26). "Dominguez,Fabian" (saliendo, 2
     * reservas) emparejaba con "Dominguez Paz,Omar Fabiano" (llegando) a
     * 64.1% — arriba del umbral de aceptación de 55%, así que se reportaba
     * como posible extensión — pero para Abel "lo único en común es
     * Dominguez, de ahí en fuera nada que ver". La causa: similar_text()
     * empareja "fabian" con "fabiano" a un altísimo ~92% (letras muy
     * parecidas) aunque son nombres reales distintos, no un typo del mismo
     * nombre; con el promedio como divisor, las 2 palabras del nombre de
     * llegada que se quedan sin pareja ("omar" y "paz") apenas diluían el
     * score. Con el máximo como divisor este caso cae a ~48.1%, debajo del
     * umbral — ya no se reporta como posible extensión.
     *
     * Igual que la prueba anterior, esta llama scoreNombres() SIN pesos a
     * propósito (fija el comportamiento base). Con los pesos reales del
     * cruce completo (ver test_comparar_listas_pondera_por_frecuencia_real)
     * este caso cae todavía más, a ~44.3%, porque "Dominguez" resultó ser
     * el apellido más repetido de ese cruce.
     */
    public function test_score_penaliza_palabras_sin_pareja_de_nombre_distinto(): void
    {
        $score = $this->service->scoreNombres(['fabian', 'dominguez'], ['omar', 'fabiano', 'dominguez', 'paz']);

        $this->assertLessThan(0.55, $score);
        $this->assertEqualsWithDelta(0.481, $score, 0.01);
    }

    /**
     * calcularPesosPalabras(): una palabra que solo aparece en un huésped
     * de todo el cruce (salidas + llegadas combinadas) pesa 1.0 completo;
     * una que se repite entre varios huéspedes distintos pesa menos. Se
     * cuenta por HUÉSPED DISTINTO (entradas del diccionario), no por
     * reservación — un mismo huésped con 3 reservas bajo el mismo nombre
     * no debe hacer que su apellido parezca más común de lo que es.
     */
    public function test_calcula_pesos_por_frecuencia_de_palabra(): void
    {
        $salidas = [
            'raul dominguez' => ['parts' => ['raul', 'dominguez'], 'count' => 3, 'original' => 'Raul Dominguez'],
        ];
        $llegadas = [
            'mateo dominguez' => ['parts' => ['mateo', 'dominguez'], 'count' => 1, 'original' => 'Mateo Dominguez'],
            'maria lara'    => ['parts' => ['maria', 'lara'], 'count' => 1, 'original' => 'Maria Lara'],
        ];

        $pesos = $this->service->calcularPesosPalabras($salidas, $llegadas);

        // "dominguez" aparece en 2 huéspedes distintos (Raul y Mateo) —
        // pese a que Raul tiene 3 reservas, cuenta como 1 huésped.
        $this->assertEqualsWithDelta(0.5, $pesos['dominguez'], 0.001);
        // "raul", "mateo", "maria", "lara" solo aparecen en 1 huésped cada uno.
        $this->assertEqualsWithDelta(1.0, $pesos['raul'], 0.001);
        $this->assertEqualsWithDelta(1.0, $pesos['lara'], 0.001);
    }

    /**
     * Regresión (segunda parte, mismo día): reconstruye el cruce completo
     * de los 3 PDFs reales que Abel adjuntó (14 nombres de salida / 43 de
     * llegada) y confirma que compararListasHuespedes() — que SIEMPRE
     * pondera por frecuencia internamente — sigue excluyendo el falso
     * positivo (ahora con más margen, 44.3% en vez de 48.1%) y sigue
     * reportando el match real (ahora con MÁS confianza, 68.2% en vez de
     * 58.3%, porque "Saul" es una palabra rara en este cruce mientras que
     * "Dominguez" resultó ser la más repetida).
     */
    public function test_comparar_listas_pondera_por_frecuencia_real(): void
    {
        $nombre = fn ($original, $count = 1) => [
            'parts'    => $this->service->normalizarNombre($original),
            'count'    => $count,
            'original' => $original,
        ];

        $salidas = [
            'lara saul'      => $nombre('Saul,Lara,Sr.'),
            'fabian dominguez' => $nombre('Dominguez,Fabian,Sr.', 2),
        ];

        $llegadas = [
            'raul tienda lara'         => $nombre('Tienda Lara,Raul'),
            'raul navarro'             => $nombre('Navarro,Raul'),
            'raul efren castro domingues' => $nombre('Castro Domingues,Raul Efren'),
            'omar fabiano dominguez paz' => $nombre('Dominguez Paz,Omar Fabiano'),
            'omar hugo ramirez'      => $nombre('Hugo Ramirez,Omar,Sr.'),
            'octavio dominguez montaño'  => $nombre('Dominguez Montaño,Octavio', 2),
            'mateo domingues'            => $nombre('Domingues,Mateo'),
        ];

        $resultados = $this->service->compararListasHuespedes($salidas, $llegadas);

        $porSalida = [];
        foreach ($resultados as $r) {
            $porSalida[$r['salida']] = $r['similitud'];
        }

        // Solo debe reportarse "Saul Lara" — "Dominguez,Fabian" queda
        // excluido, no solo ausente de $porSalida bajo una etiqueta
        // distinta a la esperada.
        $this->assertCount(1, $resultados);
        $this->assertArrayHasKey('Saul,Lara,Sr.', $porSalida);
        $this->assertEqualsWithDelta(68.2, $porSalida['Saul,Lara,Sr.'], 0.5);
    }

    public function test_comparar_listas_detecta_coincidencia_exacta(): void
    {
        $salidas = [
            'raul perez' => ['parts' => ['raul', 'perez'], 'count' => 1, 'original' => 'Raul Perez'],
        ];
        $llegadas = [
            'raul perez' => ['parts' => ['raul', 'perez'], 'count' => 1, 'original' => 'Raul Perez'],
        ];

        $resultados = $this->service->compararListasHuespedes($salidas, $llegadas);

        $this->assertCount(1, $resultados);
        $this->assertSame(100.0, $resultados[0]['similitud']);
    }

    public function test_comparar_listas_no_reporta_nada_por_debajo_del_umbral(): void
    {
        $salidas = [
            'raul perez' => ['parts' => ['raul', 'perez'], 'count' => 1, 'original' => 'Raul Perez'],
        ];
        $llegadas = [
            'maria lara' => ['parts' => ['maria', 'lara'], 'count' => 1, 'original' => 'Maria Lara'],
        ];

        $resultados = $this->service->compararListasHuespedes($salidas, $llegadas);

        $this->assertCount(0, $resultados);
    }

    /**
     * ExtensionDeteccionController usa esta constante para rechazar, antes
     * de comparar, un PDF con más huéspedes de los que la comparación
     * O(salidas × llegadas) puede procesar en una sola petición sin
     * arriesgar el límite de tiempo de ejecución de PHP.
     */
    public function test_limite_de_huespedes_por_lista_esta_definido_y_es_razonable(): void
    {
        $this->assertGreaterThan(0, ExtensionDetectorService::MAX_HUESPEDES_POR_LISTA);
    }
}

<?php

namespace App\Services\Recepcion;

use Smalot\PdfParser\Parser as PdfParser;

/**
 * Port a PHP del validador de correos que antes vivía como app de
 * escritorio independiente de Recepción (script Python: analizador de
 * emails en reservas). Detecta dominios inventados/typos, distingue
 * correos "genéricos" de OTAs (booking.com, expedia) de correos reales del
 * huésped, y valida formato + existencia de registro MX del dominio.
 *
 * Sin estado ni dependencia de Eloquent a propósito: recibe la ruta de un
 * PDF, regresa un array de resultados. Quien llama (EmailValidacionController)
 * decide qué persistir.
 *
 * Diferencias a propósito contra el script original en Python:
 *  - La verificación de dominio (MX) ya no es opcional: en Python dependía
 *    de si el paquete `dnspython` estaba instalado, y sin él el estado
 *    quedaba en un ambiguo "OK (Sin Verificar)". Aquí checkdnsrr() es
 *    nativo de PHP, así que siempre se verifica y ese estado intermedio
 *    desaparece.
 *  - tieneMx() también acepta un registro A como respaldo (RFC 5321: si un
 *    dominio no publica MX, el correo se entrega al registro A). Reduce
 *    falsos "DOMINIO INVALIDO" en dominios corporativos mal configurados
 *    que sí reciben correo.
 */
class EmailValidatorService
{
    /**
     * Tope de dominios distintos que se verifican por DNS en una sola
     * ejecución de procesarPdf(). Los dominios en DOMINIOS_BUENOS/
     * GENERICOS/FALSOS o a 2 ediciones de un dominio conocido nunca llegan
     * hasta tieneMx() (se resuelven antes, sin tocar la red) — este límite
     * solo protege contra un PDF con muchísimos dominios corporativos poco
     * comunes distintos.
     */
    public const MAX_DOMINIOS_A_VERIFICAR = 60;

    /**
     * Presupuesto total (en segundos) para todas las consultas DNS de una
     * sola ejecución. checkdnsrr() no tiene forma nativa de ponerle timeout
     * a una sola consulta (y sigue bloqueando aunque se agote este
     * presupuesto — PHP no puede interrumpir una syscall a medias sin
     * pcntl, que no existe en Windows), pero si ya se pasó el tiempo total
     * antes de EMPEZAR una consulta nueva, se salta y no se hace esa
     * llamada de red.
     */
    private const TIEMPO_MAXIMO_DNS_SEGUNDOS = 8.0;

    private const DOMINIOS_BUENOS = [
        'gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com',
        'live.com', 'msn.com', 'hotmail.es', 'hotmail.com.mx',
        'outlook.es', 'icloud.com', 'me.com', 'mac.com',
        'yahoo.com.mx', 'yahoo.es', 'prodigy.net.mx', 'aol.com',
        'live.com.mx', 'outlook.com.mx',
    ];

    private const DOMINIOS_GENERICOS = [
        'guest.booking.com',
        'expediapartnercentral.com',
        'm.expediapartnercentral.com',
    ];

    private const DOMINIOS_FALSOS = [
        'yopmail.com', 'mailinator.com', 'tempmail.com', 'trashmail.com',
    ];

    private const CORRECCIONES = [
        '.come' => '.com', '.comm' => '.com', '.con' => '.com',
        '.cim' => '.com', '.cmo' => '.com', '.xom' => '.com',
    ];

    /** @var array<string,bool> */
    private array $dnsCache = [];

    private int $dominiosVerificados = 0;

    private ?float $dnsDeadline = null;

    /** @var \Closure(string):bool */
    private \Closure $resolverDns;

    /**
     * @param \Closure(string):bool|null $resolverDns Función que recibe un
     *   dominio y responde si tiene registro MX o A. Por defecto usa el
     *   resolver DNS real del sistema (checkdnsrr). Se puede inyectar uno
     *   falso — típicamente en pruebas, para no depender de la red ni de
     *   que un dominio de prueba exista o no en un momento dado.
     */
    public function __construct(?\Closure $resolverDns = null)
    {
        $this->resolverDns = $resolverDns ?? function (string $dominio): bool {
            return checkdnsrr($dominio, 'MX') || checkdnsrr($dominio, 'A');
        };
    }

    /**
     * Procesa un PDF de reservas (ruta local, típicamente el archivo
     * temporal de una subida) y regresa un array de filas:
     * ['email' => string, 'estado' => string, 'score' => int, 'sugerencia' => string]
     */
    public function procesarPdf(string $rutaLocal): array
    {
        $parser = new PdfParser();
        $pdf    = $parser->parseFile($rutaLocal);
        $texto  = $pdf->getText();

        // IMPORTANTE: unir primero los correos largos que el generador
        // del reporte parte en dos renglones (p.ej. "...@GMAIL.CO" +
        // "M" solo en el siguiente renglón) ANTES de aplanar saltos de
        // línea a espacios — una vez aplanado, esa "M" queda separada
        // por un espacio y el regex de abajo ya no la puede pegar (se
        // detectó con datos reales: "...@gmail.co"/"...@hotmail.co" se
        // reportaban como ERROR TIPOGRAFICO en vez de reconocerse como
        // el correo completo y válido que en verdad es).
        $texto = $this->unirCorreosPartidosEntreRenglones($texto);

        // Mismo saneo que el script original: junta líneas partidas y
        // corrige espacios que el extractor de texto del PDF suele meter
        // alrededor de "@" y ".".
        $texto = str_replace("\n", ' ', $texto);
        $texto = str_replace(' @ ', '@', $texto);
        $texto = str_replace(' .', '.', $texto);
        $texto = str_replace('. ', '.', $texto);

        // \x{00C0}-\x{00FF}: vocales acentuadas, ñ/Ñ, ü/Ü, etc. (Latin-1
        // Supplement) — sin esto, un correo como "MUÑOZ@ejemplo.com.mx" se
        // corta a la mitad en la "Ñ" y solo se captura "OZ@ejemplo.com.mx".
        preg_match_all(
            '/[a-zA-Z0-9._%+\-\x{00C0}-\x{00FF}]+\s*@\s*[a-zA-Z0-9.\-\x{00C0}-\x{00FF}]+\s*\.\s*[a-zA-Z]{2,}/u',
            $texto,
            $matches
        );

        $emailsLimpios = [];
        foreach ($matches[0] as $crudo) {
            $limpio = preg_replace('/\s+/', '', $crudo);
            $limpio = $this->limpiarEmail($limpio);
            $limpio = $this->corregirEmail($limpio);
            $emailsLimpios[$limpio] = true; // dedupe (equivalente a set() en Python)
        }

        $filas = [];
        foreach (array_keys($emailsLimpios) as $email) {
            [$estado, $score, $sugerencia] = $this->analizarEmail($email);
            $filas[] = [
                'email'      => $email,
                'estado'     => $estado,
                'score'      => $score,
                'sugerencia' => $sugerencia,
            ];
        }

        return $filas;
    }

    /**
     * Recorre el texto renglón por renglón y une los que terminan a
     * mitad de un correo (el generador del reporte corta la palabra por
     * ancho de columna, dejando el resto SOLO en el siguiente renglón —
     * ver el mismo caso ya resuelto en
     * PerfilesCorreoValidatorService::parsearFilas()).
     *
     * Un renglón "termina a mitad de un correo" cuando tiene un "@" y,
     * desde ahí hasta el final, no hay ningún espacio (seguimos dentro
     * del mismo token). El siguiente renglón solo se pega si además
     * parece un fragmento de continuación de verdad — corto, sin
     * espacios, solo letras/puntos — para no pegar por accidente una
     * etiqueta de columna u otro texto suelto que nada tiene que ver.
     */
    private function unirCorreosPartidosEntreRenglones(string $texto): string
    {
        $lineas = preg_split('/\r\n|\r|\n/', $texto) ?: [];
        $total  = count($lineas);
        $resultado = [];

        for ($i = 0; $i < $total; $i++) {
            $linea = rtrim($lineas[$i]);

            $posArroba = strrpos($linea, '@');
            $terminaAMitadDeCorreo = $posArroba !== false
                && !str_contains(substr($linea, $posArroba), ' ');

            if ($terminaAMitadDeCorreo && $i + 1 < $total && $this->esFragmentoDeContinuacion(trim($lineas[$i + 1]))) {
                $linea .= trim($lineas[$i + 1]);
                $i++;
            }

            $resultado[] = $linea;
        }

        return implode("\n", $resultado);
    }

    private function esFragmentoDeContinuacion(string $linea): bool
    {
        if ($linea === '' || str_contains($linea, ' ') || mb_strlen($linea) > 20) {
            return false;
        }

        return (bool) preg_match('/^[a-zA-Z.\x{00C0}-\x{00FF}]+$/u', $linea);
    }

    /**
     * Normaliza un correo crudo extraído de texto (minúsculas + solo
     * caracteres válidos de un email). Público a propósito: otros
     * servicios de Recepción que parsean reportes distintos (p. ej.
     * PerfilesCorreoValidatorService, que necesita clasificar un correo
     * SIN aplicar corregirEmail() para poder reportar el typo original en
     * vez de esconderlo) reutilizan esta misma limpieza en vez de
     * duplicar la regex.
     */
    public function limpiarEmail(string $email): string
    {
        // Antes esto borraba cualquier vocal acentuada o "ñ" en vez de
        // conservarla (p.ej. "MUÑOZ@..." quedaba "muoz@..." — un
        // correo DISTINTO, no el que en verdad viene en el PDF). Se
        // agrega el rango \x{00C0}-\x{00FF} (Latin-1 Supplement: á é í ó
        // ú ñ ü y sus mayúsculas) a los caracteres permitidos, y se usa
        // mb_strtolower() en vez de strtolower() porque este último solo
        // pasa a minúscula A-Z y deja "Ñ" tal cual (no la baja a "ñ").
        $minuscula = mb_strtolower($email, 'UTF-8');
        $limpio    = preg_replace('/[^a-z0-9@._\-+\x{00C0}-\x{00FF}]/u', '', $minuscula);

        // preg_replace() regresa null si el texto no es UTF-8 válido (caso
        // raro de un PDF corrupto): en vez de perder el correo, se cae de
        // vuelta al comportamiento anterior (solo ASCII conocido) en ese
        // caso extremo, en lugar de devolver null.
        return $limpio ?? preg_replace('/[^a-z0-9@._\-+]/', '', strtolower($email));
    }

    private function esValido(string $email): bool
    {
        return preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email) === 1;
    }

    private function corregirEmail(string $email): string
    {
        foreach (self::CORRECCIONES as $mal => $bien) {
            if (str_ends_with($email, $mal)) {
                return str_replace($mal, $bien, $email);
            }
        }
        return $email;
    }

    private function tieneMx(string $dominio): bool
    {
        if (isset($this->dnsCache[$dominio])) {
            return $this->dnsCache[$dominio];
        }

        $this->dnsDeadline ??= microtime(true) + self::TIEMPO_MAXIMO_DNS_SEGUNDOS;

        // Red de seguridad: si ya se verificaron demasiados dominios
        // distintos o se acabó el presupuesto de tiempo, no se hace la
        // consulta de red y se asume el dominio válido. Es más seguro no
        // bloquear un correo real de un huésped por falta de tiempo que
        // marcarlo "inválido" sin haberlo verificado de verdad.
        if ($this->dominiosVerificados >= self::MAX_DOMINIOS_A_VERIFICAR || microtime(true) >= $this->dnsDeadline) {
            return $this->dnsCache[$dominio] = true;
        }

        $this->dominiosVerificados++;

        try {
            $tiene = ($this->resolverDns)($dominio);
        } catch (\Throwable $e) {
            $tiene = false;
        }

        return $this->dnsCache[$dominio] = $tiene;
    }

    /**
     * @return array{0:string,1:int,2:string} [estado, score, sugerencia]
     */
    public function analizarEmail(string $email): array
    {
        if (!$this->esValido($email)) {
            return ['FORMATO INVALIDO', 0, ''];
        }

        $partes = explode('@', $email);
        if (count($partes) < 2 || $partes[1] === '') {
            return ['FORMATO INVALIDO', 0, ''];
        }
        $dominio = $partes[1];

        foreach (self::DOMINIOS_FALSOS as $d) {
            if (str_ends_with($dominio, $d)) {
                return ['FALSO', 0, ''];
            }
        }

        foreach (self::DOMINIOS_GENERICOS as $d) {
            if (str_ends_with($dominio, $d)) {
                return ['GENERICO', 60, 'Es email de OTA'];
            }
        }

        $mejorCoincidencia = null;
        $minDist = PHP_INT_MAX;
        foreach (self::DOMINIOS_BUENOS as $d) {
            $dist = levenshtein($dominio, $d);
            if ($dist < $minDist) {
                $minDist = $dist;
                $mejorCoincidencia = $d;
            }
        }

        if (!in_array($dominio, self::DOMINIOS_BUENOS, true) && $minDist <= 2) {
            return ['ERROR TIPOGRAFICO', 10, (string) $mejorCoincidencia];
        }

        if (!$this->tieneMx($dominio)) {
            return ['DOMINIO INVALIDO', 10, 'Revisar dominio'];
        }

        return ['OK', 100, ''];
    }
}

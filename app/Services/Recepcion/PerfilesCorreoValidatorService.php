<?php

namespace App\Services\Recepcion;

use Smalot\PdfParser\Parser as PdfParser;

/**
 * Valida los correos del reporte "Profiles with e-mails" del sistema de
 * reservas (uno de los reportes que Recepción exporta a diario después de
 * las llegadas). A diferencia de EmailValidatorService (que solo extrae el
 * conjunto de correos únicos de un PDF de reservas, sin ligarlos a una
 * reserva concreta), este servicio conserva la fila completa: número de
 * reserva, huésped y fechas, para que el resumen diga "la reserva X con el
 * correo Y tiene tal problema" — que es justo lo que Calidad necesita para
 * dejar de descubrir los correos malos solo cuando el servidor de correo
 * rebota el envío.
 *
 * Reutiliza EmailValidatorService para la clasificación de dominio/typo/DNS
 * (no se duplica esa lógica), pero llama a analizarEmail() directamente
 * sobre el correo TAL CUAL viene en el PDF (usando limpiarEmail(), sin
 * pasar por corregirEmail()): el objetivo aquí es EXHIBIR el error real de
 * cada reserva en el resumen, no corregirlo en silencio.
 *
 * Agrega dos cosas que EmailValidatorService no hace:
 *  1. Distingue si el typo de dominio está en el "sufijo" (la extensión,
 *     p.ej. .com/.mx) o en el nombre del dominio en sí (p.ej. "gmial" en
 *     vez de "gmail") — es la distinción que pidió Recepción para el
 *     resumen.
 *  2. Intenta, de forma best-effort, verificar por SMTP (RCPT TO) si el
 *     buzón específico existe — pero SOLO en dominios propios/corporativos.
 *     Los proveedores de correo masivo (Gmail, Outlook/Hotmail, Yahoo,
 *     iCloud, etc.) no confirman de forma fiable si un buzón existe por
 *     política anti-enumeración de cuentas, así que ahí ni se intenta: se
 *     dejan tal cual (ya validados por formato + dominio), en vez de
 *     fingir una verificación que no es real.
 */
class PerfilesCorreoValidatorService
{
    /**
     * Mismos proveedores de correo masivo que EmailValidatorService trata
     * como "buenos" (ver esa clase). Se repite aquí, en vez de exponerla
     * desde allá, porque la razón de ser de esta lista es distinta: allá
     * decide si vale la pena buscar un typo; aquí decide si vale la pena
     * intentar un sondeo SMTP (que en estos dominios casi siempre da un
     * falso "aceptado" o se bloquea/limita, sin importar si el buzón en
     * verdad existe).
     */
    public const DOMINIOS_SIN_VERIFICACION_SMTP = [
        'gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com',
        'live.com', 'msn.com', 'hotmail.es', 'hotmail.com.mx',
        'outlook.es', 'icloud.com', 'me.com', 'mac.com',
        'yahoo.com.mx', 'yahoo.es', 'prodigy.net.mx', 'aol.com',
        'live.com.mx', 'outlook.com.mx',
    ];

    /** Tope de buzones distintos verificados por SMTP en una sola ejecución. */
    public const MAX_VERIFICACIONES_SMTP = 40;

    /** Presupuesto total en segundos para todos los sondeos SMTP de una ejecución (mismo criterio que EmailValidatorService::TIEMPO_MAXIMO_DNS_SEGUNDOS). */
    private const TIEMPO_MAXIMO_SMTP_SEGUNDOS = 20.0;

    /** Timeout de conexión/lectura por intento de conexión SMTP individual. */
    private const TIMEOUT_CONEXION_SMTP = 4;

    private EmailValidatorService $emailValidator;

    /** @var \Closure(string,string):string Recibe (email, dominio), regresa 'ACEPTADO'|'RECHAZADO'|'NO_CONCLUYENTE'. */
    private \Closure $smtpProbe;

    /** @var array<string,string> */
    private array $smtpCache = [];

    private int $smtpVerificados = 0;

    private ?float $smtpDeadline = null;

    /**
     * @param EmailValidatorService|null $emailValidator Reutiliza una
     *   instancia existente (p.ej. para compartir su resolver DNS
     *   inyectado en pruebas) o crea una nueva por defecto.
     * @param \Closure(string,string):string|null $smtpProbe Función que
     *   intenta confirmar si un buzón existe. Por defecto hace un sondeo
     *   SMTP real (conectar al MX, RCPT TO). Se puede inyectar una falsa
     *   en pruebas para no depender de la red ni de servidores de correo
     *   reales.
     */
    public function __construct(?EmailValidatorService $emailValidator = null, ?\Closure $smtpProbe = null)
    {
        $this->emailValidator = $emailValidator ?? new EmailValidatorService();
        $this->smtpProbe = $smtpProbe ?? \Closure::fromCallable([$this, 'sondaSmtpReal']);
    }

    /**
     * Procesa el PDF "Profiles with e-mails" (ruta local del archivo
     * subido) y regresa un array de filas clasificadas. El PDF nunca se
     * guarda en disco, igual que EmailValidatorService::procesarPdf().
     *
     * IMPORTANTE: aquí NO se puede usar $pdf->getText() plano. Se
     * comprobó con el reporte real que smalot/pdfparser, a diferencia de
     * herramientas como pdftotext o pdfplumber, entrega el texto en el
     * orden LITERAL del content-stream del PDF — y este reporte en
     * particular está generado por columnas: primero salen los 36
     * números de confirmación seguidos, luego los 36 nombres, luego las
     * 36 fechas de llegada, etc., y no fila por fila como se ve
     * visualmente. Con getText() plano, parsearFilas() nunca encuentra
     * "número + nombre + 2 fechas + correo" juntos y la subida falla con
     * "No se encontraron reservas en este PDF" aunque el archivo sea el
     * correcto (bug real, reportado en producción).
     *
     * La solución es reconstruir el orden visual (fila por fila, columna
     * por columna) a partir de las coordenadas reales de cada fragmento
     * de texto — ver extraerTextoPorPosicion().
     */
    public function procesarPdf(string $rutaLocal): array
    {
        $parser = new PdfParser();
        $pdf    = $parser->parseFile($rutaLocal);

        $texto = implode("\n", array_map(
            fn ($pagina) => $this->extraerTextoPorPosicion($pagina),
            $pdf->getPages()
        ));

        return $this->procesarTexto($texto);
    }

    /**
     * Reconstruye el texto de una página en orden de lectura visual
     * (arriba hacia abajo, izquierda a derecha) usando la posición real
     * de cada fragmento de texto en la página, en vez de confiar en el
     * orden en que el generador del PDF escribió los comandos en el
     * content-stream (que en este reporte es por columnas, no por fila
     * — ver procesarPdf()).
     *
     * Page::getDataTm() de smalot/pdfparser regresa, por cada operador
     * de texto (Tj/TJ/'/"), un par [$Tm, $texto] donde $Tm es la matriz
     * de texto vigente en ese momento: $Tm[4] es la coordenada X y
     * $Tm[5] la Y (en unidades de usuario del PDF, con Y creciendo hacia
     * ARRIBA, al revés que en una imagen).
     *
     * El algoritmo agrupa los fragmentos cuya Y difiere por menos de
     * $tolerancia (mismo renglón visual, aunque el content-stream los
     * haya escrito en cualquier orden), y dentro de cada grupo los
     * ordena por X para reconstruir el orden real izquierda-a-derecha.
     * Los renglones se ordenan de mayor a menor Y (arriba hacia abajo).
     *
     * Se validó este mismo algoritmo (tolerancia, agrupado, orden) contra
     * el PDF real reportado por Recepción: reconstruye exactamente las
     * 36 reservas, incluyendo la fila sin correo ("Cortes, Alma") y
     * los correos largos partidos en dos renglones por el generador del
     * reporte (p.ej. "...@GMAIL.CO" + "M" en el renglón siguiente, que
     * parsearFilas()/esFragmentoDeContinuacion() ya sabían unir).
     */
    private function extraerTextoPorPosicion(\Smalot\PdfParser\Page $pagina): string
    {
        $tolerancia = 2.5;

        $filas = [];
        foreach ($pagina->getDataTm() as [$tm, $texto]) {
            if ($texto === null || $texto === '') {
                continue;
            }

            $x = (float) ($tm[4] ?? 0);
            $y = (float) ($tm[5] ?? 0);

            $indice = null;
            foreach ($filas as $i => $fila) {
                if (abs($fila['y'] - $y) <= $tolerancia) {
                    $indice = $i;
                    break;
                }
            }

            if ($indice === null) {
                $filas[] = ['y' => $y, 'items' => [['x' => $x, 'texto' => $texto]]];
                continue;
            }

            // Promedio corrido de Y para no ir arrastrando el error de
            // redondeo del primer fragmento del renglón hacia los demás.
            $n = count($filas[$indice]['items']) + 1;
            $filas[$indice]['y'] = (($filas[$indice]['y'] * ($n - 1)) + $y) / $n;
            $filas[$indice]['items'][] = ['x' => $x, 'texto' => $texto];
        }

        usort($filas, fn ($a, $b) => $b['y'] <=> $a['y']);

        $lineas = [];
        foreach ($filas as $fila) {
            $items = $fila['items'];
            usort($items, fn ($a, $b) => $a['x'] <=> $b['x']);
            $lineas[] = implode(' ', array_map(fn ($it) => $it['texto'], $items));
        }

        return implode("\n", $lineas);
    }

    /**
     * Separado de procesarPdf() para poder probar el parseo y la
     * clasificación con texto real capturado de un PDF, sin depender de
     * smalot/pdfparser ni de un archivo PDF en las pruebas.
     */
    public function procesarTexto(string $texto): array
    {
        $filas = $this->parsearFilas($texto);

        return array_map(fn (array $fila) => $this->clasificarFila($fila), $filas);
    }

    /**
     * Extrae, renglón por renglón, cada reserva del reporte: número de
     * confirmación, huésped, fecha de llegada, fecha de salida y correo
     * (puede quedar vacío — el reporte sí trae reservas sin correo
     * registrado, ver "Cortes, Alma" en un caso real).
     *
     * El reporte es texto plano tabular generado por el sistema de
     * reservas (no celdas de PDF con coordenadas): cada reserva es UN
     * renglón, salvo que el correo sea demasiado largo para el ancho de
     * columna — ahí el generador corta la palabra y dEja el resto
     * colgando SOLO en el siguiente renglón (p.ej. "...@GMAIL.C" seguido
     * de "OM" solo). Ese es justamente el caso que Recepción pidió cuidar.
     *
     * @return array<int,array{numero_reserva:string,huesped:string,fecha_llegada:string,fecha_salida:string,email:string}>
     */
    public function parsearFilas(string $texto): array
    {
        $lineas = preg_split('/\r\n|\r|\n/', $texto) ?: [];
        $total  = count($lineas);
        $filas  = [];

        for ($i = 0; $i < $total; $i++) {
            $linea = trim($lineas[$i]);
            if ($linea === '') {
                continue;
            }

            // Una reserva siempre empieza con su número de confirmación
            // (numérico) al inicio del renglón. Cualquier otra línea
            // (encabezados, pie de página, o una línea de continuación que
            // ya fue consumida) se ignora aquí.
            if (!preg_match('/^(\d{5,10})\s+(.*)$/', $linea, $m)) {
                continue;
            }

            $numeroReserva = $m[1];
            $resto         = $m[2];

            if (!preg_match_all('/\d{2}-\d{2}-\d{2}/', $resto, $fechas) || count($fechas[0]) < 2) {
                // No trae las dos fechas esperadas: no es un renglón de
                // reserva real (p.ej. algún número suelto del pie de
                // página que por coincidencia empiece con dígitos).
                continue;
            }

            $fechaLlegada = $fechas[0][0];
            $fechaSalida  = $fechas[0][1];

            $posLlegada = strpos($resto, $fechaLlegada);
            $huesped    = trim(substr($resto, 0, $posLlegada));

            $posSalida     = strpos($resto, $fechaSalida, $posLlegada + strlen($fechaLlegada));
            $despuesSalida = trim(substr($resto, $posSalida + strlen($fechaSalida)));

            $email = $despuesSalida;

            if ($email !== '' && str_contains($email, '@')) {
                $siguiente = ($i + 1 < $total) ? trim($lineas[$i + 1]) : '';
                if ($this->esFragmentoDeContinuacion($siguiente)) {
                    $email .= $siguiente;
                    $i++; // consumir la línea de continuación
                }
            }

            $filas[] = [
                'numero_reserva' => $numeroReserva,
                'huesped'        => $huesped,
                'fecha_llegada'  => $fechaLlegada,
                'fecha_salida'   => $fechaSalida,
                'email'          => $email,
            ];
        }

        return $filas;
    }

    /**
     * Decide si un renglón es la "cola" de un correo cortado en el
     * renglón anterior, en vez de un encabezado/pie de página o una
     * reserva nueva. A propósito NO se decide por qué tan "completo" se ve
     * el correo antes de este renglón (p.ej. terminar en ".co" es tanto un
     * dominio real como un corte de ".com") — eso es ambiguo. Lo que sí es
     * inambiguo: el renglón de continuación real nunca lleva espacios,
     * comas, ni empieza con un número de reserva, y es corto (es nada más
     * la cola de un dominio, nunca una palabra completa nueva).
     */
    private function esFragmentoDeContinuacion(string $linea): bool
    {
        if ($linea === '') {
            return false;
        }
        if (preg_match('/^\d{5,10}\s+/', $linea)) {
            return false; // abre una reserva nueva
        }
        if (str_contains($linea, ' ')) {
            return false; // encabezados/pie de página siempre llevan espacios
        }
        if (strlen($linea) > 20) {
            return false; // una cola de dominio real es corta
        }

        return (bool) preg_match('/^[a-zA-Z.]+$/', $linea);
    }

    /**
     * Clasifica una fila ya parseada. Regresa la fila original más:
     * email (normalizado o null si no había), estado, motivo (texto para
     * el resumen), sugerencia (dominio corregido, si aplica) y
     * verificacion_smtp (null si no se intentó, o 'ACEPTADO'|'RECHAZADO'|
     * 'NO_CONCLUYENTE' si sí).
     */
    public function clasificarFila(array $fila): array
    {
        $emailCrudo = $fila['email'] ?? '';

        if (trim($emailCrudo) === '') {
            return array_merge($fila, [
                'email'             => null,
                'estado'            => 'SIN_CORREO',
                'motivo'            => 'No se registró ningún correo para esta reserva.',
                'sugerencia'        => null,
                'verificacion_smtp' => null,
            ]);
        }

        // Limpieza básica (minúsculas + caracteres válidos), SIN
        // corregirEmail(): aquí queremos exhibir el typo real, no
        // esconderlo antes de clasificar.
        $email = $this->emailValidator->limpiarEmail($emailCrudo);

        [$estadoBase, , $sugerenciaBase] = $this->emailValidator->analizarEmail($email);

        $partes  = explode('@', $email);
        $dominio = $partes[1] ?? '';

        if ($estadoBase === 'FORMATO INVALIDO') {
            return array_merge($fila, [
                'email'             => $email,
                'estado'            => 'FORMATO_INVALIDO',
                'motivo'            => $this->motivoFormatoInvalido($email),
                'sugerencia'        => null,
                'verificacion_smtp' => null,
            ]);
        }

        if ($estadoBase === 'FALSO') {
            return array_merge($fila, [
                'email'             => $email,
                'estado'            => 'FALSO',
                'motivo'            => 'El dominio es de un servicio de correo desechable/temporal: es muy poco probable que reciba avisos reales.',
                'sugerencia'        => null,
                'verificacion_smtp' => null,
            ]);
        }

        if ($estadoBase === 'GENERICO') {
            return array_merge($fila, [
                'email'             => $email,
                'estado'            => 'GENERICO',
                'motivo'            => 'Es un correo genérico de la agencia de viajes (OTA), no del huésped directamente.',
                'sugerencia'        => null,
                'verificacion_smtp' => null,
            ]);
        }

        if ($estadoBase === 'ERROR TIPOGRAFICO') {
            [$estadoFinal, $motivo] = $this->clasificarTipoDeTypo($dominio, $sugerenciaBase);
            return array_merge($fila, [
                'email'             => $email,
                'estado'            => $estadoFinal,
                'motivo'            => $motivo,
                'sugerencia'        => $sugerenciaBase,
                'verificacion_smtp' => null,
            ]);
        }

        if ($estadoBase === 'DOMINIO INVALIDO') {
            return array_merge($fila, [
                'email'             => $email,
                'estado'            => 'DOMINIO_INVALIDO',
                'motivo'            => "El dominio \"{$dominio}\" no existe o no recibe correo (sin registro MX/A en DNS).",
                'sugerencia'        => null,
                'verificacion_smtp' => null,
            ]);
        }

        // OK según formato + dominio: aquí es donde vale la pena intentar
        // el sondeo SMTP best-effort (si el proveedor lo permite).
        $verificacion = $this->intentarVerificarBuzon($email, $dominio);

        if ($verificacion === 'RECHAZADO') {
            return array_merge($fila, [
                'email'             => $email,
                'estado'            => 'RECHAZADO_SMTP',
                'motivo'            => 'El servidor de correo del dominio rechazó este buzón específico: lo más probable es que no exista.',
                'sugerencia'        => null,
                'verificacion_smtp' => $verificacion,
            ]);
        }

        // OJO: "el formato y el dominio son correctos" es una afirmación
        // que SÍ podemos garantizar nosotros mismos (sintaxis + registro
        // MX/A por DNS), sin depender de que el proveedor confirme nada.
        // Lo que NUNCA se puede afirmar aquí es que el buzón específico
        // existe — eso solo lo dice un "ACEPTADO" real. Por eso los 3
        // mensajes de abajo dejan explícito qué se intentó verificar del
        // buzón y qué no, en vez de repetir "correctos" sin aclarar.
        $motivo = match ($verificacion) {
            'ACEPTADO'       => 'El formato y el dominio son correctos, y el servidor de correo confirmó que este buzón específico existe.',
            'NO_CONCLUYENTE' => 'El formato y el dominio son correctos; se intentó confirmar el buzón específico pero no se obtuvo una respuesta concluyente.',
            default          => 'El formato y el dominio son correctos; no se intentó confirmar si el buzón específico existe porque este proveedor (Gmail, Hotmail/Outlook, Yahoo, etc.) no confirma la existencia de una cuenta de forma fiable.',
        };

        return array_merge($fila, [
            'email'             => $email,
            'estado'            => 'OK',
            'motivo'            => $motivo,
            'sugerencia'        => null,
            'verificacion_smtp' => $verificacion,
        ]);
    }

    private function motivoFormatoInvalido(string $email): string
    {
        if (!str_contains($email, '@')) {
            return 'El texto no parece un correo: falta la arroba (@).';
        }

        [$usuario, $dominio] = array_pad(explode('@', $email, 2), 2, '');

        if ($usuario === '') {
            return 'Falta la parte del usuario, antes de la arroba (@).';
        }

        if ($dominio === '' || !str_contains($dominio, '.')) {
            return 'Falta la extensión del dominio (por ejemplo .com, .mx).';
        }

        return 'El formato del correo no es válido.';
    }

    /**
     * Decide si el typo detectado por EmailValidatorService está en el
     * SUFIJO (la extensión final, p.ej. .com/.mx) o en el nombre del
     * dominio en sí (p.ej. "gmial" por "gmail") — separando por el último
     * punto de cada dominio y comparando cada mitad contra su
     * equivalente en el dominio sugerido.
     *
     * @return array{0:string,1:string} [estado, motivo]
     */
    private function clasificarTipoDeTypo(string $dominio, string $mejorCoincidencia): array
    {
        [$nombreDom, $tldDom]     = $this->separarNombreYTld($dominio);
        [$nombreBueno, $tldBueno] = $this->separarNombreYTld($mejorCoincidencia);

        if ($nombreDom === $nombreBueno && $tldDom !== $tldBueno) {
            return ['SUFIJO_INVALIDO', "La extensión \".{$tldDom}\" parece un error de dedo. ¿Quisiste decir \"{$mejorCoincidencia}\"?"];
        }

        return ['DOMINIO_INVALIDO', "El dominio \"{$dominio}\" parece un error de dedo. ¿Quisiste decir \"{$mejorCoincidencia}\"?"];
    }

    /** @return array{0:string,1:string} [nombre, extensión] */
    private function separarNombreYTld(string $dominio): array
    {
        $pos = strrpos($dominio, '.');
        if ($pos === false) {
            return [$dominio, ''];
        }

        return [substr($dominio, 0, $pos), substr($dominio, $pos + 1)];
    }

    public function esProveedorSinVerificacionSmtp(string $dominio): bool
    {
        foreach (self::DOMINIOS_SIN_VERIFICACION_SMTP as $d) {
            if (str_ends_with($dominio, $d)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string|null null si no se intentó (proveedor bloquea el
     *   sondeo, o ya se llegó al tope de intentos/tiempo — en ese caso,
     *   'NO_CONCLUYENTE' en vez de null, para dejar constancia de que sí
     *   se quiso intentar).
     */
    private function intentarVerificarBuzon(string $email, string $dominio): ?string
    {
        if ($this->esProveedorSinVerificacionSmtp($dominio)) {
            return null;
        }

        $clave = strtolower($email);
        if (isset($this->smtpCache[$clave])) {
            return $this->smtpCache[$clave];
        }

        $this->smtpDeadline ??= microtime(true) + self::TIEMPO_MAXIMO_SMTP_SEGUNDOS;

        if ($this->smtpVerificados >= self::MAX_VERIFICACIONES_SMTP || microtime(true) >= $this->smtpDeadline) {
            return 'NO_CONCLUYENTE';
        }

        $this->smtpVerificados++;

        try {
            $resultado = ($this->smtpProbe)($email, $dominio);
        } catch (\Throwable $e) {
            $resultado = 'NO_CONCLUYENTE';
        }

        if (!in_array($resultado, ['ACEPTADO', 'RECHAZADO', 'NO_CONCLUYENTE'], true)) {
            $resultado = 'NO_CONCLUYENTE';
        }

        return $this->smtpCache[$clave] = $resultado;
    }

    /**
     * Sondeo SMTP real: resuelve el/los MX del dominio y, en el primero
     * que responda, hace un HELO + MAIL FROM + RCPT TO para ver si el
     * servidor acepta o rechaza el buzón exacto. Nunca lanza excepción
     * hacia afuera: cualquier problema de red (puerto 25 bloqueado por el
     * proveedor de internet, timeout, servidor caído) se traduce a
     * NO_CONCLUYENTE — es más seguro no marcar un correo real como malo
     * solo porque no se pudo verificar.
     */
    private function sondaSmtpReal(string $email, string $dominio): string
    {
        foreach ($this->resolverMx($dominio) as $host) {
            $resultado = $this->intentarRcptEn($host, $email);
            if ($resultado !== null) {
                return $resultado;
            }
        }

        return 'NO_CONCLUYENTE';
    }

    /** @return array<int,string> */
    private function resolverMx(string $dominio): array
    {
        $hosts = [];
        $pesos = [];

        if (function_exists('getmxrr') && getmxrr($dominio, $hosts, $pesos) && !empty($hosts)) {
            array_multisort($pesos, $hosts);
            return $hosts;
        }

        // Respaldo RFC 5321, igual criterio que EmailValidatorService::tieneMx().
        if (checkdnsrr($dominio, 'A')) {
            return [$dominio];
        }

        return [];
    }

    private function intentarRcptEn(string $host, string $email): ?string
    {
        $conexion = @fsockopen($host, 25, $errno, $errstr, self::TIMEOUT_CONEXION_SMTP);
        if ($conexion === false) {
            return null; // este MX no se pudo contactar; se intenta el siguiente
        }

        stream_set_timeout($conexion, self::TIMEOUT_CONEXION_SMTP);

        try {
            $this->leerRespuestaSmtp($conexion); // banner 220
            $this->enviarComandoSmtp($conexion, "HELO validador-correos.local\r\n");
            $this->enviarComandoSmtp($conexion, "MAIL FROM:<verificacion@validador-correos.local>\r\n");
            $respuestaRcpt = $this->enviarComandoSmtp($conexion, "RCPT TO:<{$email}>\r\n");
            $this->enviarComandoSmtp($conexion, "QUIT\r\n");

            $codigo = (int) substr(trim($respuestaRcpt), 0, 3);

            if ($codigo >= 200 && $codigo < 300) {
                return 'ACEPTADO';
            }
            if (in_array($codigo, [550, 551, 553], true)) {
                return 'RECHAZADO';
            }

            return 'NO_CONCLUYENTE'; // 4xx temporal (greylisting/límite de tasa) u otro código
        } catch (\Throwable $e) {
            return null;
        } finally {
            fclose($conexion);
        }
    }

    private function leerRespuestaSmtp($conexion): string
    {
        $respuesta = '';
        while (($linea = fgets($conexion, 515)) !== false) {
            $respuesta = $linea;
            // Una respuesta SMTP multilínea usa "250-" en todas menos la
            // última, que usa "250 " (espacio, no guion).
            if (strlen($linea) < 4 || $linea[3] !== '-') {
                break;
            }
        }

        return $respuesta;
    }

    private function enviarComandoSmtp($conexion, string $comando): string
    {
        fwrite($conexion, $comando);
        return $this->leerRespuestaSmtp($conexion);
    }
}

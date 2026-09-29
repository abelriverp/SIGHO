<?php

namespace Tests\Unit\Services\Recepcion;

use App\Services\Recepcion\EmailValidatorService;
use App\Services\Recepcion\PerfilesCorreoValidatorService;
use PHPUnit\Framework\TestCase;

/**
 * Prueba unitaria pura (sin Laravel/DB), mismo criterio que
 * EmailValidatorServiceTest: no cubre procesarPdf() porque requiere
 * smalot/pdfparser y un PDF real — procesarTexto()/parsearFilas() sí se
 * cubren a fondo porque ahí vive el parseo (el corte de correos en dos
 * renglones que pidió Recepción) y la clasificación.
 */
class PerfilesCorreoValidatorServiceTest extends TestCase
{
    private PerfilesCorreoValidatorService $service;

    /**
     * Texto real extraído del PDF "Profiles with e-mails" que subió Abel
     * (26-08-26), tal cual lo entrega la extracción de texto plano —
     * incluye los cortes de correo reales ("...@GMAIL.C" / "OM") y una
     * reserva sin correo (Cortes, Alma).
     */
    private const TEXTO_REAL = <<<'TXT'
Hotel Ejemplo 26-08-26
Profiles with e-mails 15:51
Conf Full Arrival Departure Email
Number Name Date Date
1000001 Ramos Pérez Nuñez, Bruno 25-08-26 28-08-26 BRUNORAMOS96@GMAIL.CO
M
1000002 vega estrada, lucia 25-08-26 27-08-26 luvees@yahoo.com.mx
1000003 Barrera Luna, Marta 25-08-26 27-08-26 MARTABARRERA777@GMAIL.COM
1000004 Castillo, Renata 25-08-26 30-08-26 RENATACASTILLO5@GMAIL.C
OM
1000005 Cortes, Alma 25-08-26 26-08-26
1000006 De La, Sara 25-08-26 27-08-26 SARA_DL13@HOTMAIL.COM
1000007 Luis Medina, Jose 25-08-26 29-08-26 MEDINA67MORALES@GMAIL.C
OM
1000008 Ortega Lima, Xavier 25-08-26 28-08-26 XAVIER.ORTEGA@SEGPUB.GOB.
MX
1000009 Salinas Espino, Irma Janeth 25-08-26 28-08-26 IRMA.SALINAS@HOTMAIL.CO
M
1000010 Vargas Rios, Juan Luis 25-08-26 26-08-26 logistica@grupoejemplo.com
Filter From Arrival Date=25-08-26 Page 1 of 1 profileswithe-mails
To Arrival Date=25-08-26
TXT;

    protected function setUp(): void
    {
        parent::setUp();
        // Resolver DNS falso: todo dominio "resuelve" salvo que la prueba
        // diga lo contrario explícitamente, para no depender de la red.
        $emailValidator = new EmailValidatorService(fn (string $dominio): bool => true);
        // Sonda SMTP falsa por defecto: nunca se llama en estas pruebas de
        // parseo/clasificación general porque ningún caso de TEXTO_REAL cae
        // en dominio propio con estado OK que dispare el sondeo... salvo
        // grupoejemplo.com, así que la dejamos "aceptar siempre" por defecto.
        $smtpProbe = fn (string $email, string $dominio): string => 'ACEPTADO';
        $this->service = new PerfilesCorreoValidatorService($emailValidator, $smtpProbe);
    }

    public function test_parsea_todas_las_reservas_del_texto_real(): void
    {
        $filas = $this->service->parsearFilas(self::TEXTO_REAL);

        $this->assertCount(10, $filas);
    }

    public function test_reconstruye_correo_cortado_en_dos_renglones_variante_una_letra(): void
    {
        $filas = $this->service->parsearFilas(self::TEXTO_REAL);
        $fila  = $this->buscarPorReserva($filas, '1000001');

        $this->assertSame('BRUNORAMOS96@GMAIL.COM', strtoupper($fila['email']));
    }

    public function test_reconstruye_correo_cortado_en_dos_renglones_variante_dos_letras(): void
    {
        $filas = $this->service->parsearFilas(self::TEXTO_REAL);
        $fila  = $this->buscarPorReserva($filas, '1000004');

        $this->assertSame('RENATACASTILLO5@GMAIL.COM', strtoupper($fila['email']));
    }

    public function test_reconstruye_correo_cortado_sin_nada_despues_del_punto(): void
    {
        $filas = $this->service->parsearFilas(self::TEXTO_REAL);
        $fila  = $this->buscarPorReserva($filas, '1000008');

        $this->assertSame('XAVIER.ORTEGA@SEGPUB.GOB.MX', strtoupper($fila['email']));
    }

    public function test_reconstruye_correo_cortado_con_tld_ambiguo_de_dos_letras(): void
    {
        // Este es el caso más delicado: "HOTMAIL.CO" ya "parece" un
        // dominio completo (.co es un TLD real) antes de pegar la "M" que
        // sigue. Si el algoritmo decidiera por longitud del TLD en vez de
        // por descarte (siguiente renglón sin espacios/dígitos), fallaría
        // aquí precisamente.
        $filas = $this->service->parsearFilas(self::TEXTO_REAL);
        $fila  = $this->buscarPorReserva($filas, '1000009');

        $this->assertSame('IRMA.SALINAS@HOTMAIL.COM', strtoupper($fila['email']));
    }

    public function test_no_confunde_dos_letras_finales_reales_con_un_corte(): void
    {
        // "luvees@yahoo.com.mx" no está cortado (el siguiente renglón es
        // otra reserva nueva) — no debe pegársele nada.
        $filas = $this->service->parsearFilas(self::TEXTO_REAL);
        $fila  = $this->buscarPorReserva($filas, '1000002');

        $this->assertSame('luvees@yahoo.com.mx', $fila['email']);
    }

    public function test_reserva_sin_correo_queda_con_email_vacio(): void
    {
        $filas = $this->service->parsearFilas(self::TEXTO_REAL);
        $fila  = $this->buscarPorReserva($filas, '1000005');

        $this->assertSame('', $fila['email']);
        $this->assertSame('Cortes, Alma', $fila['huesped']);
    }

    public function test_clasificacion_marca_sin_correo_cuando_falta(): void
    {
        $resultados = $this->service->procesarTexto(self::TEXTO_REAL);
        $fila       = $this->buscarPorReserva($resultados, '1000005');

        $this->assertSame('SIN_CORREO', $fila['estado']);
        $this->assertNull($fila['email']);
    }

    public function test_clasificacion_ok_para_correo_bien_formado_de_dominio_conocido(): void
    {
        $resultados = $this->service->procesarTexto(self::TEXTO_REAL);
        $fila       = $this->buscarPorReserva($resultados, '1000006');

        $this->assertSame('OK', $fila['estado']);
        $this->assertNull($fila['verificacion_smtp']); // hotmail.com está en la lista sin verificación SMTP
    }

    public function test_dominio_corporativo_ok_si_intenta_verificacion_smtp(): void
    {
        $resultados = $this->service->procesarTexto(self::TEXTO_REAL);
        $fila       = $this->buscarPorReserva($resultados, '1000010');

        $this->assertSame('OK', $fila['estado']);
        $this->assertSame('ACEPTADO', $fila['verificacion_smtp']); // grupoejemplo.com no está en la lista de proveedores masivos
    }

    public function test_smtp_rechazado_se_reporta_como_estado_propio(): void
    {
        $emailValidator = new EmailValidatorService(fn (string $dominio): bool => true);
        $smtpProbe      = fn (string $email, string $dominio): string => 'RECHAZADO';
        $service        = new PerfilesCorreoValidatorService($emailValidator, $smtpProbe);

        $resultados = $service->procesarTexto(self::TEXTO_REAL);
        $fila       = $this->buscarPorReserva($resultados, '1000010');

        $this->assertSame('RECHAZADO_SMTP', $fila['estado']);
        $this->assertSame('RECHAZADO', $fila['verificacion_smtp']);
    }

    public function test_sondeo_smtp_nunca_se_invoca_para_proveedores_masivos(): void
    {
        $invocaciones   = 0;
        $emailValidator = new EmailValidatorService(fn (string $dominio): bool => true);
        $smtpProbe      = function () use (&$invocaciones): string {
            $invocaciones++;
            return 'ACEPTADO';
        };
        $service = new PerfilesCorreoValidatorService($emailValidator, $smtpProbe);

        $service->procesarTexto(self::TEXTO_REAL);

        // De las 10 reservas, varias son OK en gmail/hotmail/yahoo (nunca
        // deberían disparar el sondeo) — solo los 2 dominios propios de la
        // muestra (grupoejemplo.com y segpub.gob.mx) sí lo disparan.
        $this->assertSame(2, $invocaciones);
    }

    public function test_typo_de_sufijo_se_distingue_de_typo_de_dominio(): void
    {
        $emailValidator = new EmailValidatorService(fn (string $dominio): bool => true);
        $service        = new PerfilesCorreoValidatorService($emailValidator, fn () => 'ACEPTADO');

        $filaSufijo = $service->clasificarFila([
            'numero_reserva' => '1', 'huesped' => 'X', 'fecha_llegada' => '', 'fecha_salida' => '',
            'email' => 'persona@gmail.con',
        ]);
        $this->assertSame('SUFIJO_INVALIDO', $filaSufijo['estado']);
        $this->assertSame('gmail.com', $filaSufijo['sugerencia']);

        $filaDominio = $service->clasificarFila([
            'numero_reserva' => '2', 'huesped' => 'Y', 'fecha_llegada' => '', 'fecha_salida' => '',
            'email' => 'persona@gmial.com',
        ]);
        $this->assertSame('DOMINIO_INVALIDO', $filaDominio['estado']);
        $this->assertSame('gmail.com', $filaDominio['sugerencia']);
    }

    public function test_formato_invalido_distingue_falta_de_usuario_y_falta_de_extension(): void
    {
        $filaSinUsuario = $this->service->clasificarFila([
            'numero_reserva' => '1', 'huesped' => 'X', 'fecha_llegada' => '', 'fecha_salida' => '',
            'email' => '@gmail.com',
        ]);
        $this->assertSame('FORMATO_INVALIDO', $filaSinUsuario['estado']);
        $this->assertStringContainsString('usuario', $filaSinUsuario['motivo']);

        $filaSinExtension = $this->service->clasificarFila([
            'numero_reserva' => '2', 'huesped' => 'Y', 'fecha_llegada' => '', 'fecha_salida' => '',
            'email' => 'persona@gmailcom',
        ]);
        $this->assertSame('FORMATO_INVALIDO', $filaSinExtension['estado']);
        $this->assertStringContainsString('extensión', $filaSinExtension['motivo']);
    }

    public function test_dominio_desconocido_sin_dns_es_dominio_invalido(): void
    {
        $emailValidator = new EmailValidatorService(fn (string $dominio): bool => false);
        $service        = new PerfilesCorreoValidatorService($emailValidator, fn () => 'ACEPTADO');

        $fila = $service->clasificarFila([
            'numero_reserva' => '1', 'huesped' => 'X', 'fecha_llegada' => '', 'fecha_salida' => '',
            'email' => 'persona@empresa-que-no-existe-abc123.com',
        ]);

        $this->assertSame('DOMINIO_INVALIDO', $fila['estado']);
    }

    public function test_tope_de_verificaciones_smtp_se_respeta(): void
    {
        $invocaciones   = 0;
        $emailValidator = new EmailValidatorService(fn (string $dominio): bool => true);
        $smtpProbe      = function () use (&$invocaciones): string {
            $invocaciones++;
            return 'ACEPTADO';
        };
        $service = new PerfilesCorreoValidatorService($emailValidator, $smtpProbe);

        $totalCorreos = PerfilesCorreoValidatorService::MAX_VERIFICACIONES_SMTP + 5;
        for ($i = 0; $i < $totalCorreos; $i++) {
            $service->clasificarFila([
                'numero_reserva' => (string) $i, 'huesped' => 'X', 'fecha_llegada' => '', 'fecha_salida' => '',
                'email' => "persona{$i}@dominio-corporativo-{$i}.com",
            ]);
        }

        $this->assertSame(PerfilesCorreoValidatorService::MAX_VERIFICACIONES_SMTP, $invocaciones);
    }

    public function test_correo_repetido_solo_dispara_un_sondeo_smtp(): void
    {
        $invocaciones   = 0;
        $emailValidator = new EmailValidatorService(fn (string $dominio): bool => true);
        $smtpProbe      = function () use (&$invocaciones): string {
            $invocaciones++;
            return 'ACEPTADO';
        };
        $service = new PerfilesCorreoValidatorService($emailValidator, $smtpProbe);

        for ($i = 0; $i < 3; $i++) {
            $service->clasificarFila([
                'numero_reserva' => (string) $i, 'huesped' => 'X', 'fecha_llegada' => '', 'fecha_salida' => '',
                'email' => 'mismapersona@grupoejemplo.com',
            ]);
        }

        $this->assertSame(1, $invocaciones);
    }

    /** @param array<int,array<string,mixed>> $filas */
    private function buscarPorReserva(array $filas, string $numero): array
    {
        foreach ($filas as $fila) {
            if ($fila['numero_reserva'] === $numero) {
                return $fila;
            }
        }
        throw new \RuntimeException("No se encontró la reserva {$numero}");
    }
}

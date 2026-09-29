<?php

namespace Tests\Unit\Services\Recepcion;

use App\Services\Recepcion\EmailValidatorService;
use PHPUnit\Framework\TestCase;

/**
 * Prueba unitaria pura (sin Laravel/DB) de la lógica portada desde el
 * script Python original. No cubre procesarPdf() porque requiere
 * smalot/pdfparser instalado y un PDF real — solo analizarEmail(), que es
 * donde vive toda la regla de negocio.
 */
class EmailValidatorServiceTest extends TestCase
{
    private EmailValidatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EmailValidatorService();
    }

    public function test_correo_valido_de_dominio_conocido_es_ok(): void
    {
        [$estado, $score] = $this->service->analizarEmail('huesped@gmail.com');

        $this->assertSame('OK', $estado);
        $this->assertSame(100, $score);
    }

    public function test_typo_leve_en_dominio_conocido_se_detecta_y_sugiere_correccion(): void
    {
        [$estado, , $sugerencia] = $this->service->analizarEmail('huesped@gmial.com');

        $this->assertSame('ERROR TIPOGRAFICO', $estado);
        $this->assertSame('gmail.com', $sugerencia);
    }

    public function test_correo_de_ota_se_marca_como_generico(): void
    {
        [$estado] = $this->service->analizarEmail('reserva123@guest.booking.com');

        $this->assertSame('GENERICO', $estado);
    }

    public function test_dominio_desechable_se_marca_como_falso(): void
    {
        [$estado] = $this->service->analizarEmail('temporal@mailinator.com');

        $this->assertSame('FALSO', $estado);
    }

    public function test_formato_invalido_se_detecta(): void
    {
        [$estado] = $this->service->analizarEmail('esto-no-es-un-correo');

        $this->assertSame('FORMATO INVALIDO', $estado);
    }

    public function test_dominio_sin_registro_dns_se_marca_invalido(): void
    {
        // Resolver DNS falso que dice que ningún dominio existe. A
        // propósito NO se hace una consulta DNS real aquí: dependía de que
        // "dominio-inventado-que-no-existe-abc123.com" siga sin existir
        // (y de tener red disponible al correr la prueba), lo que la hacía
        // lenta y potencialmente falsa en un sandbox/CI sin salida a
        // internet.
        $service = new EmailValidatorService(fn (string $dominio): bool => false);

        [$estado] = $service->analizarEmail('alguien@dominio-inventado-que-no-existe-abc123.com');

        $this->assertSame('DOMINIO INVALIDO', $estado);
    }

    public function test_dominio_desconocido_es_ok_si_el_resolver_encuentra_registro(): void
    {
        $service = new EmailValidatorService(fn (string $dominio): bool => true);

        [$estado, $score] = $service->analizarEmail('huesped@miempresa-real.com');

        $this->assertSame('OK', $estado);
        $this->assertSame(100, $score);
    }

    /**
     * Red de seguridad de tieneMx(): un PDF con muchos dominios corporativos
     * poco comunes distintos no debe seguir haciendo consultas DNS sin
     * límite. Se verifica contando cuántas veces se invoca el resolver.
     */
    public function test_verificacion_dns_se_detiene_tras_el_limite_de_dominios(): void
    {
        $llamadas = 0;
        $service = new EmailValidatorService(function (string $dominio) use (&$llamadas): bool {
            $llamadas++;
            return false;
        });

        $totalDominios = EmailValidatorService::MAX_DOMINIOS_A_VERIFICAR + 10;
        for ($i = 0; $i < $totalDominios; $i++) {
            $service->analizarEmail("huesped{$i}@empresa-unica-{$i}.com");
        }

        $this->assertSame(EmailValidatorService::MAX_DOMINIOS_A_VERIFICAR, $llamadas);
    }
}

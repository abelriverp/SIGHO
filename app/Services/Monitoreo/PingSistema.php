<?php

namespace App\Services\Monitoreo;

use Symfony\Component\Process\Process;

/**
 * Ping con el comando del sistema operativo, en paralelo (Fase 4).
 *
 * Ojo en Windows: `ping` regresa codigo 0 aunque la respuesta sea
 * "Host de destino inaccesible" (la manda el gateway). Por eso el exito se
 * decide por la presencia de "TTL=" en la salida, no por el codigo.
 */
class PingSistema implements Pinger
{
    public function __construct(private int $timeoutMs = 1000, private int $paralelo = 20)
    {
    }

    public function pingVarias(array $ips): array
    {
        $resultados = [];
        foreach (array_chunk(array_values(array_unique($ips)), $this->paralelo) as $lote) {
            $procesos = [];
            foreach ($lote as $ip) {
                if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $resultados[$ip] = null;
                    continue;
                }
                $cmd = PHP_OS_FAMILY === 'Windows'
                    ? ['ping', '-n', '1', '-w', (string) $this->timeoutMs, $ip]
                    : ['ping', '-c', '1', '-W', (string) max(1, (int) ceil($this->timeoutMs / 1000)), $ip];
                $p = new Process($cmd);
                $p->setTimeout(($this->timeoutMs / 1000) + 5);
                $p->start();
                $procesos[$ip] = $p;
            }
            foreach ($procesos as $ip => $p) {
                try {
                    $p->wait();
                } catch (\Throwable) {
                    $resultados[$ip] = null;
                    continue;
                }
                $salida = $p->getOutput();
                if (stripos($salida, 'ttl=') === false) {
                    $resultados[$ip] = null;
                    continue;
                }
                // "tiempo=3ms", "tiempo<1m" (Windows en español), "time=3ms", "time=0.512 ms"
                $resultados[$ip] = preg_match('/(?:time|tiempo)[=<]\s*([\d.,]+)\s*ms?/i', $salida, $m)
                    ? (int) round((float) str_replace(',', '.', $m[1]))
                    : 0;
            }
        }

        return $resultados;
    }
}

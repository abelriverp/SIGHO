<?php

namespace App\Services\Monitoreo;

/** Hace ping a varias IPs a la vez (Fase 4). Se puede reemplazar en pruebas. */
interface Pinger
{
    /**
     * @param  string[]  $ips
     * @return array<string, int|null>  ip => latencia en ms, o null si no respondió
     */
    public function pingVarias(array $ips): array;
}

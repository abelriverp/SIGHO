<?php

namespace App\Models;

use App\Models\Concerns\BelongsToHotel;
use Illuminate\Database\Eloquent\Model;

/**
 * Segmento de red / VLAN con su rango IPv4 en CIDR (Fase 4, nov 2026).
 * Solo IPv4; el mapa de IPs se limita a /22 (1,022 hosts) para no
 * dibujar rangos enormes.
 */
class SegmentoRed extends Model
{
    use BelongsToHotel;

    protected $table = 'segmentos_red';

    public const PREFIJO_MINIMO_MAPA = 22;

    protected $fillable = ['hotel_code', 'nombre', 'vlan', 'red', 'gateway', 'dns', 'dhcp_inicio', 'dhcp_fin', 'proposito', 'notas'];

    /** [red_long, mascara, prefijo] o null si el CIDR no es válido. */
    public static function parseCidr(string $cidr): ?array
    {
        if (! preg_match('#^(\d{1,3}(?:\.\d{1,3}){3})/(\d{1,2})$#', trim($cidr), $m)) {
            return null;
        }
        $ip = ip2long($m[1]);
        $prefijo = (int) $m[2];
        if ($ip === false || $prefijo < 8 || $prefijo > 30) {
            return null;
        }
        $mascara = $prefijo === 0 ? 0 : (~0 << (32 - $prefijo)) & 0xFFFFFFFF;
        return [$ip & $mascara, $mascara, $prefijo];
    }

    /** Normaliza "10.7.64.15/24" a "10.7.64.0/24". */
    public static function normalizar(string $cidr): ?string
    {
        $p = static::parseCidr($cidr);
        return $p ? long2ip($p[0]) . '/' . $p[2] : null;
    }

    public function contiene(?string $ip): bool
    {
        $p = static::parseCidr($this->red);
        $l = $ip ? ip2long($ip) : false;
        return $p && $l !== false && (($l & $p[1]) === $p[0]);
    }

    /** IPs utilizables (sin red ni broadcast), como enteros. */
    public function hosts(): array
    {
        $p = static::parseCidr($this->red);
        if (! $p || $p[2] < self::PREFIJO_MINIMO_MAPA) {
            return [];
        }
        $broadcast = $p[0] | (~$p[1] & 0xFFFFFFFF);
        return range($p[0] + 1, $broadcast - 1);
    }

    public function totalHosts(): int
    {
        $p = static::parseCidr($this->red);
        return $p ? max(0, (2 ** (32 - $p[2])) - 2) : 0;
    }

    public function enDhcp(int $ipLong): bool
    {
        $i = $this->dhcp_inicio ? ip2long($this->dhcp_inicio) : false;
        $f = $this->dhcp_fin ? ip2long($this->dhcp_fin) : false;
        return $i !== false && $f !== false && $ipLong >= $i && $ipLong <= $f;
    }
}

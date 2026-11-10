<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Log;

/**
 * Valida usuario y contraseña contra Active Directory con un bind LDAP
 * (Fase 5). No sincroniza usuarios ni grupos: el usuario debe existir en
 * SIGHO y tener capturado su "usuario de red" (users.usuario_ad).
 *
 * Requiere la extension ldap de PHP (php.ini: extension=ldap) y en el .env:
 *   LDAP_ENABLED=true
 *   LDAP_HOST=10.7.64.10        (uno o varios separados por espacio)
 *   LDAP_DOMINIO=EMPORIO        (nombre NetBIOS: EMPORIO\usuario)
 *   LDAP_PORT=389 / 636, LDAP_SSL=false, LDAP_TIMEOUT=5
 */
class DirectorioActivo
{
    public const OK = 'ok';
    public const RECHAZADO = 'rechazado';
    public const SIN_CONEXION = 'sin_conexion';

    public function habilitado(): bool
    {
        return (bool) config('services.ldap.enabled') && filled(config('services.ldap.host'));
    }

    /** @return string self::OK | self::RECHAZADO | self::SIN_CONEXION */
    public function autenticar(string $usuarioAd, string $password): string
    {
        if ($password === '' || $usuarioAd === '') {
            return self::RECHAZADO; // un bind con contraseña vacía es "anónimo" y AD lo acepta
        }
        if (! function_exists('ldap_connect')) {
            Log::warning('LDAP habilitado pero la extensión ldap de PHP no está activa.');
            return self::SIN_CONEXION;
        }

        $cfg = config('services.ldap');
        $esquema = ! empty($cfg['ssl']) ? 'ldaps' : 'ldap';
        $uris = collect(preg_split('/[\s,]+/', (string) $cfg['host']))->filter()
            ->map(fn ($h) => "{$esquema}://{$h}:" . ((int) ($cfg['port'] ?? 389)))->implode(' ');

        $conn = @ldap_connect($uris);
        if (! $conn) {
            return self::SIN_CONEXION;
        }
        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, (int) ($cfg['timeout'] ?? 5));

        $login = str_contains($usuarioAd, '@') || str_contains($usuarioAd, '\\')
            ? $usuarioAd
            : ($cfg['dominio'] ? $cfg['dominio'] . '\\' . $usuarioAd : $usuarioAd);

        $ok = @ldap_bind($conn, $login, $password);
        if ($ok) {
            @ldap_unbind($conn);
            return self::OK;
        }

        $errno = ldap_errno($conn);
        @ldap_unbind($conn);
        // 49 = credenciales inválidas; -1/81/91 = no se pudo contactar al servidor
        return $errno === 49 ? self::RECHAZADO : self::SIN_CONEXION;
    }
}

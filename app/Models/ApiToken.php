<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Token de la API REST (Fase 5). Solo se guarda el SHA-256; el token en
 * claro se muestra una sola vez al crearlo.
 */
class ApiToken extends Model
{
    protected $fillable = ['hotel_code', 'nombre', 'token_hash', 'prefijo', 'puede_escribir', 'user_id', 'ultimo_uso_at', 'expira_at'];

    protected $casts = [
        'puede_escribir' => 'boolean',
        'ultimo_uso_at'  => 'datetime',
        'expira_at'      => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** @return array{0: ApiToken, 1: string} el modelo y el token en claro */
    public static function generar(array $atributos): array
    {
        $plano = 'sigho_' . Str::random(40);
        $token = static::create($atributos + [
            'token_hash' => hash('sha256', $plano),
            'prefijo'    => substr($plano, 6, 6),
        ]);

        return [$token, $plano];
    }

    public static function buscar(?string $plano): ?self
    {
        if (! $plano) {
            return null;
        }
        $t = static::where('token_hash', hash('sha256', $plano))->first();
        if (! $t || ($t->expira_at && $t->expira_at->isPast())) {
            return null;
        }
        return $t;
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API REST (Fase 5): "Authorization: Bearer sigho_xxx". El request corre
 * como el usuario dueño del token, así que aplican HotelScope y Policies.
 * Con el parámetro "escritura" exige un token con permiso de escribir.
 */
class AutenticaApiToken
{
    public function handle(Request $request, Closure $next, ?string $modo = null)
    {
        $token = ApiToken::buscar($request->bearerToken());
        if (! $token || ! $token->user) {
            return response()->json(['error' => 'Token inválido o vencido.'], 401);
        }
        if ($modo === 'escritura' && ! $token->puede_escribir) {
            return response()->json(['error' => 'Este token es de solo lectura.'], 403);
        }

        Auth::setUser($token->user);
        $token->forceFill(['ultimo_uso_at' => now()])->saveQuietly();
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}

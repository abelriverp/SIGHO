<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

/** Administración de tokens de la API (Fase 5). Solo admin. */
class ApiTokenController extends Controller
{
    public function index()
    {
        return view('admin.api-tokens.index', [
            'tokens'   => ApiToken::with('user:id,name')->latest()->get(),
            'usuarios' => User::orderBy('name')->get(['id', 'name', 'role']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'         => 'required|string|max:100',
            'user_id'        => 'required|integer|exists:users,id',
            'puede_escribir' => 'nullable|boolean',
            'expira_at'      => 'nullable|date|after:today',
        ]);
        $data['puede_escribir'] = $request->boolean('puede_escribir');
        $data['hotel_code'] = User::find($data['user_id'])->hotel_code;

        [$token, $plano] = ApiToken::generar($data);
        AuditLog::registrar('api_token.creado', "Creó el token de API \"{$token->nombre}\" ({$token->prefijo}…).", $token);

        return back()->with('token_nuevo', $plano)->with('success', 'Token creado. Cópialo ahora: no se vuelve a mostrar.');
    }

    public function destroy(ApiToken $token)
    {
        AuditLog::registrar('api_token.revocado', "Revocó el token de API \"{$token->nombre}\".", $token);
        $token->delete();

        return back()->with('success', 'Token revocado.');
    }
}

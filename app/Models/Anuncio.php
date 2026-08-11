<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Anuncio extends Model
{
    use HasFactory;

    protected $fillable = [
        'modulo_id',
        'titulo',
        'mensaje',
        'enviado_por',
        'total_destinatarios',
    ];

    public function modulo()
    {
        return $this->belongsTo(Modulo::class);
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }

    /**
     * A quién le corresponde este anuncio, para mandarlo por correo (ver
     * AnuncioController::store) y para decidir si se le muestra en la
     * pantalla "Novedades" (ver estaDirigidoA()). Las dos preguntas usan
     * la misma regla para no desincronizarse.
     *
     * - Sin módulo (modulo_id null, o el módulo fue eliminado): todos los
     *   usuarios — es un anuncio general.
     * - Módulo 'gestion_tickets': todos los usuarios, porque ese módulo es
     *   libre para cualquiera (ver User::tieneModulo) y casi nadie tiene
     *   fila explícita en usuario_modulo para él.
     * - Cualquier otro módulo: los usuarios con fila activa en
     *   usuario_modulo (Modulo::usuarios()) + todos los administradores,
     *   que tienen acceso automático a todo sin necesitar esa fila.
     */
    public function destinatarios(): Collection
    {
        $modulo = $this->modulo_id ? $this->modulo : null;

        if (!$modulo || $modulo->clave === 'gestion_tickets') {
            return User::all();
        }

        $admins = User::where('role', 'admin')->get();

        return $modulo->usuarios->merge($admins)->unique('id')->values();
    }

    /**
     * Si a $user le corresponde ver este anuncio en "Novedades". Espejo de
     * destinatarios(), pero evaluado para un solo usuario en vez de traer
     * todos — así "Novedades" no repite la consulta completa por cada
     * anuncio de la lista.
     */
    public function estaDirigidoA(User $user): bool
    {
        $modulo = $this->modulo_id ? $this->modulo : null;

        if (!$modulo || $modulo->clave === 'gestion_tickets') {
            return true;
        }

        return $user->isAdmin() || $user->tieneModulo($modulo->clave);
    }
}

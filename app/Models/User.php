<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Modulo;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'departamento',
        'must_change_password',
        'hotel_code',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    // ==========================================
    // HELPERS DE ROLES
    // ==========================================

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTecnico(): bool
    {
        return $this->role === 'tecnico';
    }

    public function isGerente(): bool
    {
        return $this->role === 'gerente';
    }

    public function isUsuario(): bool
    {
        return $this->role === 'usuario';
    }

    // ==========================================
    // MULTIPROPIEDAD
    // ==========================================

    /**
     * Propiedad (hotel/condominio) a la que pertenece este usuario.
     * El Administrador no depende de este dato para ver información:
     * su rol lo exime del filtro en HotelScope.
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_code', 'hotel_code');
    }

	public function modulos()
{
    return $this->belongsToMany(Modulo::class, 'usuario_modulo')
                ->withPivot('activado_en', 'activado_por')
                ->withTimestamps();
}

	public function tieneModulo(string $clave): bool
{
    // Admin siempre tiene acceso
    if ($this->isAdmin()) return true;

    // Tickets es libre para todos los usuarios autenticados
    if ($clave === 'gestion_tickets') return true;

    if (!$this->relationLoaded('modulos')) {
        $this->load('modulos');
    }

    return $this->modulos->contains('clave', $clave);
}


    // Puede ver y responder tickets (admin o técnico)
    public function puedeResponderTickets(): bool
    {
        return in_array($this->role, ['admin', 'tecnico']);
    }

    // Puede ver todos los tickets (admin o gerente)
    public function puedeVerTodos(): bool
    {
        return in_array($this->role, ['admin', 'gerente']);
    }
}
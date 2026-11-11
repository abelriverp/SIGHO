<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
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
        'area',
        'must_change_password',
        'hotel_code',
        'usuario_ad',
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

    // Un módulo desactivado (obsoleto/renombrado) ya no otorga acceso,
    // aunque el usuario conserve la asignación histórica en la pivote.
    return $this->modulos->contains(fn($modulo) => $modulo->clave === $clave && $modulo->activo);
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

    /**
     * Cierra las sesiones abiertas de este usuario en otras PCs (endurecimiento
     * nov 2026), p. ej. al cambiar su contraseña o su rol. Las sesiones viven
     * en la tabla `sessions` (SESSION_DRIVER=database).
     */
    public function cerrarSesiones(?string $excepto = null): void
    {
        $tabla = config('session.table', 'sessions');
        if (! \Illuminate\Support\Facades\Schema::hasTable($tabla)) {
            return;
        }
        \Illuminate\Support\Facades\DB::table($tabla)->where('user_id', $this->id)
            ->when($excepto, fn ($q) => $q->where('id', '!=', $excepto))->delete();
    }

    // ==========================================
    // PORTAL DE HUÉSPED (QR)
    // ==========================================

    /**
     * Cuenta de sistema que "reporta" los tickets creados desde el portal
     * público de huésped (ver GuestPortalController): Ticket.user_id es
     * obligatorio y no existe un huésped autenticado, así que cada
     * propiedad tiene una única cuenta ficticia dueña de esas solicitudes.
     *
     * A propósito usa el rol 'usuario' que YA existe (no uno nuevo tipo
     * 'huesped'): agregar un rol nuevo obligaría a revisar cada
     * isAdmin/isTecnico/isGerente/isUsuario/puedeVerTodos/tieneModulo del
     * proyecto para asegurar que lo tratan igual que un usuario regular, y
     * no hay forma de verificar eso sin correr la suite completa en un
     * entorno con PHP. Esta cuenta nunca inicia sesión (contraseña
     * aleatoria, must_change_password en false porque jamás llega a esa
     * pantalla), así que comportarse como un 'usuario' normal es
     * exactamente lo que se necesita.
     */
    public static function cuentaHuespedParaPropiedad(string $hotelCode): self
    {
        $hotelCode = strtoupper($hotelCode);
        $email     = 'huesped-qr+' . strtolower($hotelCode) . '@sistema.local';

        return static::firstOrCreate(
            ['email' => $email],
            [
                'name'                 => 'Huésped (QR) — ' . $hotelCode,
                'password'             => bcrypt(Str::random(40)),
                'role'                 => 'usuario',
                'hotel_code'           => $hotelCode,
                'must_change_password' => false,
            ]
        );
    }
}
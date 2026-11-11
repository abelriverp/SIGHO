<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Ticket;
use App\Models\Modulo;
use App\Models\Hotel;
use App\Models\AuditLog;
use App\Notifications\NuevoUsuarioCredencialesNotification;
use App\Notifications\PasswordRestablecidaNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class AdminSettingsController extends Controller
{
    // =====================================
    // MÓDULO DE USUARIOS
    // =====================================
    public function usersIndex() {
        // Nota: User no lleva el scope de propiedad (no usa BelongsToHotel),
        // por eso ->with('hotel') es una simple relación, no un filtro.
        $users = User::with('hotel')->latest()->get();
        return view('admin.users.index', compact('users'));
    }

    public function usersCreate() {
        $hoteles = Hotel::orderBy('nombre')->get();
        return view('admin.users.form', compact('hoteles'));
    }

    public function usersStore(Request $request) {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'usuario_ad'   => 'nullable|string|max:100|unique:users,usuario_ad',
            'password'     => ['required', 'string', \Illuminate\Validation\Rules\Password::defaults()],
            'role'         => 'required|in:admin,usuario,tecnico,gerente',
            'departamento' => 'nullable|string|required_if:role,tecnico|exists:departamentos,nombre',
            'area'         => 'nullable|string|exists:areas,nombre',
            'hotel_code'   => 'nullable|string|required_unless:role,admin|exists:hoteles,hotel_code',
        ]);

        $data['must_change_password'] = true;
        $passwordTemporal = $data['password'];

        $user = User::create($data);

        AuditLog::registrar(
            'usuario.creado',
            "Creó al usuario \"{$user->name}\" ({$user->email}) con rol {$user->role}.",
            $user,
            null,
            $user->only(['name', 'email', 'role', 'hotel_code', 'departamento', 'area'])
        );

        $mensaje = 'Usuario creado correctamente.';

        try {
            $user->notify(new NuevoUsuarioCredencialesNotification($passwordTemporal));
            $mensaje .= ' Se enviaron sus credenciales por correo.';
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el correo de credenciales al nuevo usuario', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            $mensaje .= ' No se pudo enviar el correo de credenciales; compártelas manualmente.';
        }

        return redirect()->route('admin.users.index')->with('success', $mensaje);
    }

    public function usersEdit(User $user) {
        $hoteles = Hotel::orderBy('nombre')->get();
        return view('admin.users.form', compact('user', 'hoteles'));
    }

    public function usersUpdate(Request $request, User $user) {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'usuario_ad'   => ['nullable', 'string', 'max:100', Rule::unique('users', 'usuario_ad')->ignore($user->id)],
            'role'         => 'required|in:admin,usuario,tecnico,gerente',
            'departamento' => 'nullable|string|required_if:role,tecnico|exists:departamentos,nombre',
            'area'         => 'nullable|string|exists:areas,nombre',
            'password'     => ['nullable', 'string', \Illuminate\Validation\Rules\Password::defaults()],
            'hotel_code'   => 'nullable|string|required_unless:role,admin|exists:hoteles,hotel_code',
        ]);

        // Snapshot ANTES de aplicar los cambios, para la bitácora.
        $antes = $user->only(['name', 'email', 'role', 'hotel_code', 'departamento', 'area']);
        $cambioPassword = ! empty($data['password']);
        // Se guarda en claro ANTES de que $user->update() la hashee, para
        // poder mandársela al usuario por correo (igual que en usersStore).
        $passwordTemporal = $data['password'] ?? null;

        if ($data['role'] !== 'tecnico') {
            $data['departamento'] = null;
        }

        // El Administrador tiene acceso a todas las propiedades: no necesita
        // una asignación fija, así que no la forzamos a null si ya tenía una
        // (permite degradar de admin a otro rol sin perder su propiedad previa).
        if ($data['role'] === 'admin' && empty($data['hotel_code'])) {
            unset($data['hotel_code']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            // Si el admin le asigna una nueva contraseña, se le exige
            // cambiarla en su próximo inicio de sesión.
            $data['must_change_password'] = true;
        }

        $user->update($data);

        // Endurecimiento (nov 2026): al cambiar contraseña o rol se cierran
        // las sesiones abiertas de ese usuario, para que el cambio aplique ya.
        if ($cambioPassword || $antes['role'] !== $user->role) {
            $user->cerrarSesiones($request->session()->getId());
        }

        $despues = $user->fresh()->only(['name', 'email', 'role', 'hotel_code', 'departamento', 'area']);

        $notas = [];
        if ($antes['role'] !== $despues['role']) {
            $notas[] = "rol de \"{$antes['role']}\" a \"{$despues['role']}\"";
        }
        if ($antes['hotel_code'] !== $despues['hotel_code']) {
            $notas[] = "propiedad de \"" . ($antes['hotel_code'] ?? 'ninguna') . "\" a \"" . ($despues['hotel_code'] ?? 'ninguna') . "\"";
        }
        if ($antes['area'] !== $despues['area']) {
            $notas[] = "área de \"" . ($antes['area'] ?? 'ninguna') . "\" a \"" . ($despues['area'] ?? 'ninguna') . "\"";
        }
        if ($cambioPassword) {
            $notas[] = 'se le asignó una nueva contraseña';
        }
        $detalle = $notas ? ('Cambió ' . implode(', ', $notas) . '.') : 'Actualizó datos generales.';

        AuditLog::registrar(
            'usuario.actualizado',
            "Editó al usuario \"{$user->name}\" ({$user->email}). {$detalle}",
            $user,
            $antes,
            $despues
        );

        $mensaje = 'Usuario actualizado correctamente.';

        // Antes esto no existía: se le asignaba nueva contraseña al usuario
        // desde Editar y nunca se le avisaba por correo (a diferencia del
        // alta de un usuario nuevo, que sí notifica). El usuario se quedaba
        // sin saber su contraseña temporal.
        if ($cambioPassword && $passwordTemporal) {
            try {
                $user->notify(new PasswordRestablecidaNotification($passwordTemporal));
                $mensaje .= ' Se enviaron sus nuevas credenciales por correo.';
            } catch (\Throwable $e) {
                Log::error('No se pudo enviar el correo de contraseña restablecida', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
                $mensaje .= ' No se pudo enviar el correo con la nueva contraseña; compártela manualmente.';
            }
        }

        return redirect()->route('admin.users.index')->with('success', $mensaje);
    }

    public function usersDestroy(User $user) {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta mientras estés en sesión.');
        }

        // Snapshot antes de borrar: después ya no habrá registro del usuario.
        $datosPrevios = $user->only(['name', 'email', 'role', 'hotel_code', 'departamento', 'area']);

        AuditLog::registrar(
            'usuario.eliminado',
            "Eliminó al usuario \"{$user->name}\" ({$user->email}).",
            $user,
            $datosPrevios,
            null
        );

        $user->delete();
        return back()->with('success', 'Usuario eliminado correctamente.');
    }

    // ✅ NUEVO — Mostrar panel de módulos de un usuario
    public function usersModulos(User $user) {
        $todosLosModulos = Modulo::where('activo', true)->orderBy('orden')->get();
        $modulosActivos  = $user->modulos()->pluck('modulo_id')->toArray();

        return view('admin.users.modulos', compact('user', 'todosLosModulos', 'modulosActivos'));
    }

    // ✅ NUEVO — Guardar los módulos seleccionados
    public function usersModulosUpdate(Request $request, User $user) {
        $modulos = $request->input('modulos', []);

        $antes = $user->modulos()->pluck('clave')->sort()->values()->all();

        // sync reemplaza todo lo que había (quita los desmarcados, agrega los nuevos)
        $user->modulos()->sync(
            collect($modulos)->mapWithKeys(fn($id) => [
                $id => [
                    'activado_en'  => now(),
                    'activado_por' => auth()->id(),
                ]
            ])->toArray()
        );

        $despues = $user->modulos()->pluck('clave')->sort()->values()->all();

        AuditLog::registrar(
            'usuario.modulos_actualizados',
            "Actualizó los módulos habilitados para \"{$user->name}\" ({$user->email}).",
            $user,
            ['modulos' => $antes],
            ['modulos' => $despues]
        );

        return redirect()->route('admin.users.index')
                         ->with('success', "Módulos de {$user->name} actualizados correctamente.");
    }

    // =====================================
    // MÓDULO DE ÁREAS
    // =====================================
    public function areasIndex() {
        $areas = Area::latest()->get();
        return view('admin.areas.index', compact('areas'));
    }

    public function areasCreate() {
        return view('admin.areas.form');
    }

    public function areasStore(Request $request) {
        $request->validate([
            'nombre' => 'required|string|unique:areas,nombre'
        ]);

        Area::create(['nombre' => $request->nombre]);
        return redirect()->route('admin.areas.index')->with('success', 'Área agregada correctamente.');
    }

    public function areasEdit($id) {
        $area = Area::findOrFail($id);
        return view('admin.areas.form', compact('area'));
    }

    public function areasUpdate(Request $request, $id) {
        $area = Area::findOrFail($id);
        
        $request->validate([
            'nombre' => ['required', 'string', Rule::unique('areas')->ignore($area->id)]
        ]);

        $area->update(['nombre' => $request->nombre]);
        return redirect()->route('admin.areas.index')->with('success', 'Área actualizada correctamente.');
    }

    public function areasDestroy($id) {
        $area = Area::findOrFail($id);
        $area->delete();
        return back()->with('success', 'Área eliminada correctamente.');
    }

    // =====================================
    // MÓDULO DE DEPARTAMENTOS (destino de tickets)
    // =====================================
    // Catálogo de departamentos a los que se les puede levantar/asignar un
    // ticket (ej. Sistemas, Mantenimiento, Prevención...). El mismo catálogo
    // se usa para elegir el departamento que atiende cada usuario técnico,
    // así ambos siempre coinciden.
    public function departamentosIndex() {
        // "departamento_destino" (en tickets) y "departamento" (en usuarios) se
        // guardan como texto, no como llave foránea, así que el conteo de uso
        // se calcula agrupando por nombre en vez de con una relación Eloquent.
        $usoTickets = Ticket::select('departamento_destino')
            ->selectRaw('count(*) as total')
            ->groupBy('departamento_destino')
            ->pluck('total', 'departamento_destino');

        $usoUsuarios = User::whereNotNull('departamento')
            ->select('departamento')
            ->selectRaw('count(*) as total')
            ->groupBy('departamento')
            ->pluck('total', 'departamento');

        $departamentos = Departamento::latest()->get()->map(function ($d) use ($usoTickets, $usoUsuarios) {
            $d->tickets_count  = $usoTickets[$d->nombre] ?? 0;
            $d->usuarios_count = $usoUsuarios[$d->nombre] ?? 0;
            return $d;
        });

        return view('admin.departamentos.index', compact('departamentos'));
    }

    public function departamentosCreate() {
        return view('admin.departamentos.form');
    }

    public function departamentosStore(Request $request) {
        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:departamentos,nombre',
            'activo' => 'nullable|boolean',
        ]);
        // Checkbox HTML: si no viene marcado, el navegador no manda el campo
        // (no llega "activo=0"), así que su ausencia significa "desmarcado".
        $data['activo'] = $request->boolean('activo');

        $departamento = Departamento::create($data);

        AuditLog::registrar(
            'departamento.creado',
            "Creó el departamento \"{$departamento->nombre}\".",
            $departamento,
            null,
            $departamento->only(['nombre', 'activo'])
        );

        return redirect()->route('admin.departamentos.index')->with('success', 'Departamento agregado correctamente.');
    }

    public function departamentosEdit($id) {
        $departamento = Departamento::findOrFail($id);
        return view('admin.departamentos.form', compact('departamento'));
    }

    public function departamentosUpdate(Request $request, $id) {
        $departamento = Departamento::findOrFail($id);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('departamentos', 'nombre')->ignore($departamento->id)],
            'activo' => 'nullable|boolean',
        ]);
        $data['activo'] = $request->boolean('activo');

        $antes = $departamento->only(['nombre', 'activo']);
        $nombreAnterior = $departamento->nombre;

        $departamento->update($data);

        // Si el nombre cambió, actualizamos las referencias existentes (tickets
        // y usuarios guardan el nombre como texto, no como llave foránea) para
        // que no queden "huérfanas" apuntando al nombre viejo.
        if ($nombreAnterior !== $departamento->nombre) {
            Ticket::where('departamento_destino', $nombreAnterior)->update(['departamento_destino' => $departamento->nombre]);
            User::where('departamento', $nombreAnterior)->update(['departamento' => $departamento->nombre]);
        }

        AuditLog::registrar(
            'departamento.actualizado',
            "Editó el departamento \"{$nombreAnterior}\".",
            $departamento,
            $antes,
            $departamento->fresh()->only(['nombre', 'activo'])
        );

        return redirect()->route('admin.departamentos.index')->with('success', 'Departamento actualizado correctamente.');
    }

    public function departamentosDestroy($id) {
        $departamento = Departamento::findOrFail($id);

        $enUsoTickets  = Ticket::where('departamento_destino', $departamento->nombre)->count();
        $enUsoUsuarios = User::where('departamento', $departamento->nombre)->count();

        if ($enUsoTickets > 0 || $enUsoUsuarios > 0) {
            return back()->with('error', "No puedes eliminar \"{$departamento->nombre}\": tiene {$enUsoTickets} ticket(s) y {$enUsoUsuarios} usuario(s) asignados. Reasígnalos o desactiva el departamento en vez de eliminarlo.");
        }

        $datosPrevios = $departamento->only(['nombre', 'activo']);

        AuditLog::registrar(
            'departamento.eliminado',
            "Eliminó el departamento \"{$departamento->nombre}\".",
            $departamento,
            $datosPrevios,
            null
        );

        $departamento->delete();
        return back()->with('success', 'Departamento eliminado correctamente.');
    }

    // =====================================
    // MÓDULO DE PROPIEDADES (MULTIPROPIEDAD)
    // =====================================
    public function hotelesIndex() {
        $hoteles = Hotel::withCount('usuarios')->orderBy('nombre')->get();
        return view('admin.hoteles.index', compact('hoteles'));
    }

    public function hotelesCreate() {
        return view('admin.hoteles.form');
    }

    public function hotelesStore(Request $request) {
        $data = $request->validate([
            'hotel_code' => 'required|string|max:10|unique:hoteles,hotel_code|alpha_dash',
            'nombre'     => 'required|string|max:255',
            'tipo'       => 'required|in:hotel,condominio,mixto',
        ]);

        $data['hotel_code'] = strtoupper($data['hotel_code']);

        $hotel = Hotel::create($data);

        AuditLog::registrar(
            'propiedad.creada',
            "Creó la propiedad \"{$hotel->nombre}\" ({$hotel->hotel_code}).",
            $hotel,
            null,
            $hotel->only(['hotel_code', 'nombre', 'tipo'])
        );

        return redirect()->route('admin.hoteles.index')->with('success', 'Propiedad agregada correctamente.');
    }

    public function hotelesEdit($id) {
        $hotel = Hotel::findOrFail($id);
        return view('admin.hoteles.form', compact('hotel'));
    }

    public function hotelesUpdate(Request $request, $id) {
        $hotel = Hotel::findOrFail($id);

        // El código de propiedad no se puede editar: ya está grabado en usuarios
        // y en cada registro (assets, tickets, etc.) de esa propiedad.
        $antes = $hotel->only(['nombre', 'tipo']);

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo'   => 'required|in:hotel,condominio,mixto',
        ]);

        $hotel->update($data);

        AuditLog::registrar(
            'propiedad.actualizada',
            "Editó la propiedad \"{$hotel->hotel_code}\".",
            $hotel,
            $antes,
            $hotel->fresh()->only(['nombre', 'tipo'])
        );

        return redirect()->route('admin.hoteles.index')->with('success', 'Propiedad actualizada correctamente.');
    }

    public function hotelesDestroy($id) {
        $hotel = Hotel::withCount('usuarios')->findOrFail($id);

        if ($hotel->usuarios_count > 0) {
            return back()->with('error', "No puedes eliminar '{$hotel->nombre}': todavía tiene {$hotel->usuarios_count} usuario(s) asignado(s). Reasígnalos primero.");
        }

        $datosPrevios = $hotel->only(['hotel_code', 'nombre', 'tipo']);

        AuditLog::registrar(
            'propiedad.eliminada',
            "Eliminó la propiedad \"{$hotel->nombre}\" ({$hotel->hotel_code}).",
            $hotel,
            $datosPrevios,
            null
        );

        $hotel->delete();
        return back()->with('success', 'Propiedad eliminada correctamente.');
    }

    // =====================================
    // SELECTOR DE PROPIEDAD ACTIVA (solo Administrador)
    // =====================================
    // El Administrador es el único rol que legítimamente supervisa más de
    // una propiedad (gerente/técnico/usuario ya están fijos a la suya vía
    // users.hotel_code). Antes de este cambio no existía forma de que un
    // Admin cambiara de propiedad en Dashboard/Condominios/Inventario/
    // Habitaciones: esas pantallas siempre caían al HOTEL_CODE del .env,
    // sin importar cuántas propiedades hubiera dado de alta en
    // Admin > Propiedades. Ver Hotel::activoParaUsuario() para la regla.
    public function propiedadActivaUpdate(Request $request) {
        // Normalizamos ANTES de validar (no después): hotel_code se guarda
        // siempre en mayúsculas (ver hotelesStore), pero el "exists" de abajo
        // compara el valor tal cual llega. En MySQL (producción) la
        // colación por defecto suele ser insensible a mayúsculas y esto no
        // se nota, pero en SQLite (usado en las pruebas) sí es sensible, y
        // sin este merge un valor en minúsculas fallaría la validación aun
        // existiendo la propiedad.
        $request->merge(['hotel_code' => strtoupper((string) $request->input('hotel_code'))]);

        $data = $request->validate([
            'hotel_code' => 'required|string|exists:hoteles,hotel_code',
        ], [
            'hotel_code.exists' => 'Esa propiedad ya no existe. Recarga la página e intenta de nuevo.',
        ]);

        session(['active_hotel_code' => $data['hotel_code']]);

        return back()->with('success', 'Propiedad activa cambiada correctamente.');
    }

    // =====================================
    // BITÁCORA DE AUDITORÍA
    // =====================================
    public function auditoriaIndex(Request $request) {
        $query = AuditLog::with('usuario')->latest();

        if ($request->filled('accion')) {
            $query->where('accion', $request->accion);
        }

        $logs = $query->paginate(30)->withQueryString();

        // Para el filtro desplegable: acciones distintas ya registradas
        $acciones = AuditLog::select('accion')->distinct()->orderBy('accion')->pluck('accion');

        return view('admin.auditoria.index', compact('logs', 'acciones'));
    }
}

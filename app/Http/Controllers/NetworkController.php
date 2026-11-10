<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\NetworkDevice;
use App\Models\NetworkPort;
use App\Models\Ubicacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NetworkController extends Controller
{
    private const TIPOS = ['Switch', 'Router', 'Patch Panel', 'Firewall'];

    public function index()
    {
        $devices = NetworkDevice::with(['ubicacionCatalogo', 'asset:id,nomenclatura,estado_monitoreo,monitorear'])
            ->withCount(['ports', 'ports as ocupados_count' => fn ($q) => $q->where('status', 'Ocupado')])
            ->orderBy('nombre')->get();

        return view('network.index', compact('devices'));
    }

    private function datosForm(): array
    {
        return [
            'ubicaciones' => Ubicacion::where('activa', true)->orderBy('tipo')->orderBy('codigo')->get(['id', 'tipo', 'codigo', 'nombre']),
            'activosRed'  => Asset::vigentes()->where('categoria', 'Red')->orderBy('nomenclatura')->get(['id', 'nomenclatura', 'nombre', 'tipo_equipo', 'ip_address']),
            'tipos'       => self::TIPOS,
        ];
    }

    private function validar(Request $request, bool $creando): array
    {
        $reglas = [
            'nombre'       => 'required|string|max:255',
            'tipo'         => ['required', 'string', Rule::in(self::TIPOS)],
            'ubicacion'    => 'nullable|string|max:255',
            'ubicacion_id' => 'nullable|integer',
            'asset_id'     => 'nullable|integer',
            'ip_gestion'   => 'nullable|ip',
        ];
        if ($creando) {
            $reglas['total_ports'] = 'required|integer|min:4|max:52';
        }
        $data = $request->validate($reglas);

        $errores = [];
        if (! empty($data['ubicacion_id']) && ! Ubicacion::whereKey($data['ubicacion_id'])->exists()) {
            $errores['ubicacion_id'] = 'Ubicación no válida.';
        }
        if (! empty($data['asset_id']) && ! Asset::whereKey($data['asset_id'])->exists()) {
            $errores['asset_id'] = 'Activo no válido.';
        }
        if ($errores) {
            throw ValidationException::withMessages($errores);
        }
        if (! empty($data['ubicacion_id']) && empty($data['ubicacion'])) {
            $data['ubicacion'] = Ubicacion::find($data['ubicacion_id'])->etiqueta;
        }

        return $data;
    }

    public function create()
    {
        return view('network.create', $this->datosForm());
    }

    public function store(Request $request)
    {
        $data = $this->validar($request, true);
        $device = NetworkDevice::create($data);

        $ports = [];
        $now = now();
        for ($i = 1; $i <= $data['total_ports']; $i++) {
            $ports[] = [
                'network_device_id' => $device->id,
                'port_number'       => $i,
                'status'            => 'Libre',
                'connected_to'      => null,
                'speed'             => 'Gigabit',
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }
        NetworkPort::insert($ports);

        return redirect()->route('network.show', $device->id)->with('success', "Dispositivo '{$device->nombre}' con {$device->total_ports} puertos configurado.");
    }

    public function show($id)
    {
        $device = NetworkDevice::with([
            'ports' => fn ($q) => $q->orderBy('port_number'),
            'ports.asset:id,nomenclatura,nombre,tipo_equipo,ip_address,ubicacion_id,estado_monitoreo',
            'ports.asset.ubicacion',
            'asset', 'ubicacionCatalogo',
        ])->findOrFail($id);

        $activos = Asset::vigentes()->with('ubicacion:id,tipo,codigo,nombre')->orderBy('nomenclatura')
            ->get(['id', 'nomenclatura', 'nombre', 'tipo_equipo', 'ubicacion_id', 'ip_address']);

        return view('network.show', array_merge(compact('device', 'activos'), $this->datosForm(), ['velocidades' => NetworkPort::VELOCIDADES]));
    }

    public function edit($id)
    {
        $device = NetworkDevice::findOrFail($id);
        return view('network.create', array_merge(['device' => $device], $this->datosForm()));
    }

    public function update(Request $request, $id)
    {
        $device = NetworkDevice::findOrFail($id);
        $device->update($this->validar($request, false));

        return redirect()->route('network.show', $device->id)->with('success', 'Dispositivo actualizado.');
    }

    public function updatePort(Request $request, $id)
    {
        $port = NetworkPort::findOrFail($id);
        // El puerto no tiene hotel_code propio: se valida que su dispositivo
        // sea visible para la propiedad del usuario (HotelScope).
        NetworkDevice::findOrFail($port->network_device_id);

        $validated = $request->validate([
            'connected_to' => 'nullable|string|max:255',
            'asset_id'     => 'nullable|integer',
            'vlan'         => 'nullable|string|max:30',
            'notas'        => 'nullable|string|max:255',
            'speed'        => ['nullable', 'string', Rule::in(array_keys(NetworkPort::VELOCIDADES))],
        ]);

        $asset = null;
        if (! empty($validated['asset_id'])) {
            $asset = Asset::find($validated['asset_id']);
            if (! $asset) {
                throw ValidationException::withMessages(['asset_id' => 'Activo no válido.']);
            }
        }

        $texto = trim((string) ($validated['connected_to'] ?? ''));
        $ocupado = $asset || $texto !== '';

        $port->update([
            'connected_to' => $texto !== '' ? $texto : ($asset ? $asset->nomenclatura : null),
            'asset_id'     => $asset?->id,
            'vlan'         => $validated['vlan'] ?? null,
            'notas'        => $validated['notas'] ?? null,
            'speed'        => $validated['speed'] ?? 'Gigabit',
            'status'       => $ocupado ? 'Ocupado' : 'Libre',
        ]);

        return back()->with('success', "Puerto #{$port->port_number} actualizado.");
    }

    public function destroy($id)
    {
        $device = NetworkDevice::findOrFail($id);

        if (! auth()->user()->can('delete', $device)) {
            abort(403, 'Solo un Administrador o Gerente puede eliminar dispositivos de red.');
        }

        $nombre = $device->nombre;
        $device->delete();

        return redirect()->route('network.index')->with('success', "El dispositivo '{$nombre}' y sus puertos fueron removidos.");
    }
}

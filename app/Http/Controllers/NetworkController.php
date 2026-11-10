<?php

namespace App\Http\Controllers;

use App\Models\NetworkDevice;
use App\Models\NetworkPort;
use Illuminate\Http\Request;

class NetworkController extends Controller
{
    public function index()
    {
        // Traemos los dispositivos ordenados y con el conteo de puertos integrado
        $devices = NetworkDevice::withCount('ports')->latest()->get();
        return view('network.index', compact('devices'));
    }

    public function create()
    {
        return view('network.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:255',
            'tipo'        => 'required|string|in:Switch,Router,Patch Panel,Firewall', // Opciones controladas
            'total_ports' => 'required|integer|min:4|max:52', // Límite lógico de puertos comerciales
            'ubicacion'   => 'nullable|string|max:255'
        ]);

        $device = NetworkDevice::create($data);

        // ⚡ OPTIMIZACIÓN DE ALTO RENDIMIENTO (Bulk Insert)
        // Creamos un solo arreglo con todos los puertos y hacemos una ÚNICA consulta a la BD.
        $ports = [];
        $now = now(); // Para estampar los timestamps correctamente en el insert masivo

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

        // Se insertan todos de golpe en una sola consulta SQL
        NetworkPort::insert($ports);

        return redirect()->route('network.index')->with('success', "Dispositivo '{$device->nombre}' con {$device->total_ports} puertos configurado con éxito.");
    }

    public function show($id)
    {
        // Traemos el dispositivo ordenando sus puertos eficientemente
        $device = NetworkDevice::with(['ports' => function($query) {
            $query->orderBy('port_number', 'asc');
        }])->findOrFail($id);

        return view('network.show', compact('device'));
    }

    // Actualizar si un puerto está ocupado o libre
    public function updatePort(Request $request, $id)
    {
        $port = NetworkPort::findOrFail($id);
        // El puerto no tiene hotel_code propio: se valida que su dispositivo
        // sea visible para la propiedad del usuario (HotelScope).
        NetworkDevice::findOrFail($port->network_device_id);
        
        // 🔒 Escudo de validación ante alteraciones del Request
        $validated = $request->validate([
            'connected_to' => 'nullable|string|max:255',
            'speed'        => 'nullable|string|in:FastEthernet,Gigabit,10-Gigabit,PoE' // Forzamos formatos reales de red
        ]);
        
        // Determinamos el estado dinámicamente según el contenido validado
        $status = !empty($validated['connected_to']) ? 'Ocupado' : 'Libre';

        $port->update([
            'connected_to' => $validated['connected_to'],
            'speed'        => $validated['speed'] ?? 'Gigabit', 
            'status'       => $status
        ]);

        return back()->with('success', "Puerto #{$port->port_number} actualizado correctamente.");
    }
    
    public function destroy($id)
    {
        $device = NetworkDevice::findOrFail($id);

        if (!auth()->user()->can('delete', $device)) {
            abort(403, 'Solo un Administrador o Gerente puede eliminar dispositivos de red.');
        }

        $nombre = $device->nombre;

        $device->delete();
        
        // Los puertos asociados se borran solos por la llave foránea "cascade" configurada en DB.
        return redirect()->route('network.index')->with('success', "El dispositivo '{$nombre}' y sus puertos fueron removidos.");
    }
}
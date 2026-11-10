<?php
 
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
 
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
 
// v1.1 — Cierre automático de tickets resueltos sin confirmar tras 72 horas
Schedule::command('tickets:cerrar-vencidos')->hourly();

// Motor de SLA — escala a admin/gerente los tickets abiertos que rebasaron
// su tiempo de resolución (ver App\Services\SlaService y
// App\Console\Commands\EscalarTicketsSla).
Schedule::command('tickets:escalar-sla')->hourly();

// Resumen quincenal para Gerente (días 15 y 30 de cada mes, 8:00 am): UN
// correo por Gerente con todos los tickets pendientes de su propiedad, en
// vez de un correo por cada ticket — a petición explícita del usuario de
// no saturarlo de correos (ver App\Console\Commands\TicketsResumenPendientes
// y App\Notifications\ResumenTicketsPendientesNotification). Cron directo
// porque Schedule::monthlyOn() solo acepta UN día del mes, no dos.
Schedule::command('tickets:resumen-pendientes')->cron('0 8 15,30 * *');
 
// Fase 0 (nov 2026) — Respaldo diario de la base y de storage/app a las
// 02:30 (ver App\Console\Commands\RespaldarSigho). Copia externa opcional
// con BACKUP_PATH en el .env.
Schedule::command('sigho:respaldar')->dailyAt('02:30');

// Fase 2 (nov 2026) — Mantenimiento preventivo: genera a las 06:00 los
// tickets recurrentes que tocan hoy (ver App\Console\Commands\GenerarTicketsRecurrentes).
Schedule::command('tickets:generar-recurrentes')->dailyAt('06:00');

// Fase 3 (nov 2026) — Avisos de contratos, licencias y garantías por vencer
// (solo en los días de aviso; ver App\Console\Commands\AlertasVencimiento).
Schedule::command('sigho:alertas-vencimiento')->dailyAt('07:00');

// Fase 4 (nov 2026) — Monitoreo por ping de los activos marcados
// "monitorear"; abre un ticket tras 3 fallas seguidas (ver
// App\Console\Commands\MonitorearEquipos).
Schedule::command('sigho:monitorear')->everyFiveMinutes()->withoutOverlapping(10);

// Fase 5 (nov 2026) — Reporte mensual de TI en PDF a gerentes y admins,
// el día 1 a las 08:30 (ver App\Console\Commands\ReporteMensualTI).
Schedule::command('sigho:reporte-mensual')->monthlyOn(1, '08:30');

PHP
<?php
 
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
 
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
 
// v1.1 — Cierre automático de tickets resueltos sin confirmar tras 72 horas
Schedule::command('tickets:cerrar-vencidos')->hourly();
 
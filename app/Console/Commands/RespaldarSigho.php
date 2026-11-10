<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Respaldo diario de SIGHO (Fase 0, nov 2026).
 *
 * Genera un ZIP con:
 *  - el volcado completo de la base MySQL (mysqldump), y
 *  - storage/app (evidencias de tickets, PDFs, imagenes).
 *
 * Lo deja en storage/app/respaldos y, si BACKUP_PATH esta definido en el
 * .env (por ejemplo una carpeta compartida en otro equipo), tambien copia
 * ahi el ZIP. Conserva solo los ultimos BACKUP_DIAS respaldos (default 14).
 *
 * Programado en routes/console.php a las 02:30 todos los dias.
 * Prueba manual: php artisan sigho:respaldar
 */
class RespaldarSigho extends Command
{
    protected $signature = 'sigho:respaldar {--sin-archivos : Solo la base de datos, sin storage/app}';

    protected $description = 'Respalda la base de datos y los archivos de SIGHO en un ZIP';

    public function handle(): int
    {
        if (! class_exists(ZipArchive::class)) {
            $this->error('La extension zip de PHP no esta activa (php.ini: extension=zip).');
            return self::FAILURE;
        }

        $conexion = config('database.default');
        $db = config("database.connections.$conexion");
        if (($db['driver'] ?? null) !== 'mysql' && ($db['driver'] ?? null) !== 'mariadb') {
            $this->error("El respaldo solo soporta MySQL/MariaDB (conexion actual: {$conexion}).");
            return self::FAILURE;
        }

        $dir = storage_path('app/respaldos');
        File::ensureDirectoryExists($dir);

        $marca = now()->format('Ymd_His');
        $hotel = config('app.hotel_code', 'SIGHO');
        $sqlPath = "$dir/sigho_{$hotel}_{$marca}.sql";
        $zipPath = "$dir/sigho_{$hotel}_{$marca}.zip";

        // 1) Volcado de la base
        $mysqldump = config('app.mysqldump_path') ?: (PHP_OS_FAMILY === 'Windows' ? 'C:\\xampp\\mysql\\bin\\mysqldump.exe' : 'mysqldump');
        $proceso = new Process([
            $mysqldump,
            '--host=' . ($db['host'] ?? '127.0.0.1'),
            '--port=' . ($db['port'] ?? '3306'),
            '--user=' . ($db['username'] ?? 'root'),
            '--single-transaction',
            '--routines',
            '--default-character-set=utf8mb4',
            '--result-file=' . $sqlPath,
            $db['database'],
        ], null, ['MYSQL_PWD' => (string) ($db['password'] ?? '')]);
        $proceso->setTimeout(600);
        $proceso->run();

        if (! $proceso->isSuccessful() || ! is_file($sqlPath) || filesize($sqlPath) === 0) {
            @unlink($sqlPath);
            $this->error('Fallo mysqldump: ' . trim($proceso->getErrorOutput()));
            return self::FAILURE;
        }

        // 2) ZIP con la base + archivos
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($sqlPath);
            $this->error("No se pudo crear $zipPath");
            return self::FAILURE;
        }
        $zip->addFile($sqlPath, basename($sqlPath));

        if (! $this->option('sin-archivos')) {
            $raiz = storage_path('app');
            foreach (File::allFiles($raiz) as $archivo) {
                $ruta = $archivo->getPathname();
                // No respaldar los respaldos
                if (str_starts_with(str_replace('\\', '/', $ruta), str_replace('\\', '/', $dir))) {
                    continue;
                }
                $zip->addFile($ruta, 'storage/app/' . str_replace('\\', '/', $archivo->getRelativePathname()));
            }
        }
        $zip->close();
        @unlink($sqlPath);

        $mb = round(filesize($zipPath) / 1048576, 2);
        $this->info("Respaldo creado: $zipPath ($mb MB)");

        // 3) Copia externa opcional
        $externo = config('app.backup_path');
        if ($externo) {
            try {
                File::ensureDirectoryExists($externo);
                File::copy($zipPath, rtrim($externo, '\\/') . DIRECTORY_SEPARATOR . basename($zipPath));
                $this->info("Copia externa en: $externo");
                $this->depurar($externo);
            } catch (\Throwable $e) {
                $this->warn('No se pudo copiar a BACKUP_PATH: ' . $e->getMessage());
            }
        }

        $this->depurar($dir);

        return self::SUCCESS;
    }

    /** Conserva solo los ultimos BACKUP_DIAS zips de SIGHO en la carpeta. */
    private function depurar(string $carpeta): void
    {
        $conservar = max(1, (int) config('app.backup_dias', 14));
        $zips = collect(glob(rtrim($carpeta, '\\/') . DIRECTORY_SEPARATOR . 'sigho_*.zip') ?: [])
            ->sortDesc()
            ->values();

        $zips->slice($conservar)->each(fn ($z) => @unlink($z));
    }
}

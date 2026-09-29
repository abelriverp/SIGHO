# SIGHO — Instrucciones para Claude

Antes de modificar cualquier módulo, lee `docs/SIGHO_Notas_Desarrollo.md`. Ahí están las decisiones de diseño, los bugs ya corregidos y los pendientes.

Reglas de trabajo:
- Entrega archivos completos de reemplazo con la ruta exacta de Windows (`C:\xampp\htdocs\Proyecto_Estadias\...`), nunca diffs parciales.
- Responde en español, de forma directa y técnica.
- Si no puedes correr `php artisan migrate` / `php artisan test`, dilo explícitamente e indica qué comandos debo correr localmente.
- Cuando se tome una decisión de diseño nueva, agrégala a `docs/SIGHO_Notas_Desarrollo.md`.

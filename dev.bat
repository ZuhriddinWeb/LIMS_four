@echo off
REM Режим разработки: Vite (HMR) в отдельном окне + сервер Laravel.
cd /d "%~dp0"
start "Vite (npm run dev)" cmd /k npm run dev
echo Vite запущен в отдельном окне. Laravel: http://127.0.0.1:8000
php artisan serve --host=127.0.0.1 --port=8000

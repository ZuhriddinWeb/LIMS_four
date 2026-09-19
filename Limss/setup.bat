@echo off
REM Первичная установка ЛИМС (выполнить один раз после клонирования/распаковки).
cd /d "%~dp0"

echo [1/6] PHP-зависимости (Composer)...
if exist "..\composer.phar" (
    php "..\composer.phar" install
) else (
    where composer >nul 2>nul && composer install || echo composer не найден - установите Composer и повторите
)

echo [2/6] JS-зависимости (npm)...
call npm install

echo [3/6] .env (SQLite для локального запуска)...
if not exist ".env" copy ".env.example" ".env"
powershell -NoProfile -Command "$p=((Join-Path (Get-Location) 'database/database.sqlite') -replace '\\','/'); (Get-Content .env) -replace '^DB_CONNECTION=.*','DB_CONNECTION=sqlite' -replace '^DB_DATABASE=.*',('DB_DATABASE=\"'+$p+'\"') | Set-Content .env -Encoding utf8"

echo [4/6] Ключ приложения...
php artisan key:generate

echo [5/6] База данных (SQLite: migrate + seed)...
if not exist "database\database.sqlite" type nul > "database\database.sqlite"
php artisan migrate --seed --force

echo [6/6] Сборка фронтенда...
call npm run build

echo.
echo Готово. Запуск: run.bat  (или dev.bat для разработки)
echo Логин: zzzz1111*  Пароль: zzzz1111*
pause

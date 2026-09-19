@echo off
REM Публичный доступ через интернет (Cloudflare Tunnel).
REM Требует запущенного сервера (run.bat) на порту 8000.
REM Держите это окно открытым, пока нужен доступ.
cd /d "%~dp0"
if not exist "..\cloudflared.exe" (
    echo Не найден ..\cloudflared.exe
    echo Скачайте: https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe
    echo и сохраните как C:\Claud code\LIMS\cloudflared.exe
    pause
    exit /b
)
echo ==========================================================
echo  Поднимаю публичный туннель. Ниже появится ссылка вида
echo    https://XXXX.trycloudflare.com  - её отправляйте заказчикам.
echo  (Ссылка меняется при каждом запуске. Ctrl+C - остановить.)
echo ==========================================================
"..\cloudflared.exe" tunnel --url http://localhost:8000

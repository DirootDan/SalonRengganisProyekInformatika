@echo off
title Salon Rengganis - Server Publik Gratis (Cloudflare Quick Tunnel)
cd /d "C:\laragon\www\SalonRengganisProyekInformatika"

echo [1/3] Memastikan MySQL Laragon aktif...
netstat -ano | findstr ":3306 " >nul
if errorlevel 1 (
    start "MySQL Laragon" /min "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe" --defaults-file="C:\laragon\bin\mysql\mysql-8.4.3-winx64\my.ini"
    timeout /t 4 >nul
)

echo [2/3] Menjalankan server Laravel di http://127.0.0.1:8088 ...
netstat -ano | findstr ":8088 " >nul
if errorlevel 1 (
    start "Laravel Salon Rengganis" /min "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe" artisan serve --host=127.0.0.1 --port=8088 --no-reload
    timeout /t 3 >nul
)

echo [3/3] Membuka Cloudflare Quick Tunnel (HTTPS Gratis)...
echo Salin alamat https://....trycloudflare.com yang muncul di bawah ini.
echo Jangan tutup jendela ini selama website masih dipakai.
echo.
"C:\laragon\bin\cloudflared\cloudflared.exe" tunnel --url http://127.0.0.1:8088

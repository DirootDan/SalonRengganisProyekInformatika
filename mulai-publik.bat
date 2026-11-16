@echo off
setlocal EnableExtensions EnableDelayedExpansion
title Salon Rengganis - Server Publik Gratis (Cloudflare Quick Tunnel)

cd /d "%~dp0"
set "APP_DIR=%CD%"

echo ============================================================
echo   Salon Rengganis - Server Publik Gratis (Quick Tunnel)
echo ============================================================
echo Folder project: "%APP_DIR%"
echo.

if not exist ".env" (
    echo [GAGAL] File .env belum ada.
    echo Jalankan di terminal:
    echo   copy .env.example .env
    echo   php artisan key:generate
    goto :gagal
)

findstr /R "^APP_KEY=." ".env" >nul
if errorlevel 1 (
    echo [GAGAL] APP_KEY di .env masih kosong.
    echo Jalankan di terminal: php artisan key:generate
    goto :gagal
)

if not exist "vendor\autoload.php" (
    echo [GAGAL] Folder vendor belum ada.
    echo Jalankan di terminal: composer install
    goto :gagal
)

if not exist "public\build\manifest.json" (
    echo [GAGAL] Asset frontend belum dibuild ^(public\build\manifest.json tidak ada^).
    echo Jalankan di terminal: npm install ^&^& npm run build
    goto :gagal
)

set "PHP_EXE="
for /f "delims=" %%I in ('where php 2^>nul') do if not defined PHP_EXE set "PHP_EXE=%%~fI"
if not defined PHP_EXE for /d %%D in ("C:\laragon\bin\php\php-*" "D:\laragon\bin\php\php-*") do if exist "%%~fD\php.exe" set "PHP_EXE=%%~fD\php.exe"
if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE if exist "D:\xampp\php\php.exe" set "PHP_EXE=D:\xampp\php\php.exe"

if not defined PHP_EXE (
    echo [GAGAL] php.exe tidak ditemukan di PATH maupun Laragon/XAMPP.
    echo Pastikan PHP 8.2+ sudah terinstal atau Laragon terpasang.
    goto :gagal
)
echo [OK] PHP ditemukan: "!PHP_EXE!"

if /i "%~1"=="--cek" goto :cek_saja

echo.
echo [1/3] Memastikan MySQL aktif di port 3306...
netstat -ano | findstr "LISTENING" | findstr ":3306 " >nul
if not errorlevel 1 (
    echo [OK] MySQL sudah aktif di port 3306.
    goto :cek_laravel
)

call :cari_mysql

if not defined MYSQL_EXE (
    echo [GAGAL] MySQL belum berjalan di port 3306 dan mysqld.exe tidak ditemukan.
    echo Nyalakan MySQL terlebih dahulu lewat tombol Start All di Laragon/XAMPP.
    goto :gagal
)

if defined MYSQL_INI (
    start "MySQL Salon Rengganis" /min "!MYSQL_EXE!" --defaults-file="!MYSQL_INI!"
) else (
    start "MySQL Salon Rengganis" /min "!MYSQL_EXE!"
)

for /l %%T in (1,1,10) do (
    timeout /t 1 >nul
    netstat -ano | findstr "LISTENING" | findstr ":3306 " >nul
    if not errorlevel 1 goto :mysql_siap
)
echo [GAGAL] MySQL tidak kunjung aktif di port 3306.
echo Coba nyalakan MySQL manual lewat aplikasi Laragon/XAMPP.
goto :gagal

:mysql_siap
echo [OK] MySQL aktif di port 3306.

:cek_laravel
echo.
echo [2/3] Memastikan server Laravel aktif di http://127.0.0.1:8088 ...
netstat -ano | findstr "LISTENING" | findstr ":8088 " >nul
if not errorlevel 1 (
    echo [OK] Server Laravel sudah berjalan di port 8088.
    goto :cek_cloudflared
)

start "Laravel Salon Rengganis" /min "!PHP_EXE!" artisan serve --host=127.0.0.1 --port=8088 --no-reload
for /l %%T in (1,1,10) do (
    timeout /t 1 >nul
    netstat -ano | findstr "LISTENING" | findstr ":8088 " >nul
    if not errorlevel 1 goto :laravel_siap
)
echo [GAGAL] Server Laravel gagal menyala di port 8088.
echo Coba jalankan manual di terminal untuk melihat error:
echo   "!PHP_EXE!" artisan serve --host=127.0.0.1 --port=8088
goto :gagal

:laravel_siap
echo [OK] Server Laravel aktif di http://127.0.0.1:8088

:cek_cloudflared
echo.
echo [3/3] Membuka Cloudflare Quick Tunnel ^(HTTPS Gratis^)...
set "CLOUDFLARED_EXE="
for /f "delims=" %%I in ('where cloudflared 2^>nul') do if not defined CLOUDFLARED_EXE set "CLOUDFLARED_EXE=%%~fI"
if not defined CLOUDFLARED_EXE if exist "%APP_DIR%\cloudflared.exe" set "CLOUDFLARED_EXE=%APP_DIR%\cloudflared.exe"
if not defined CLOUDFLARED_EXE if exist "C:\laragon\bin\cloudflared\cloudflared.exe" set "CLOUDFLARED_EXE=C:\laragon\bin\cloudflared\cloudflared.exe"
if not defined CLOUDFLARED_EXE if exist "D:\laragon\bin\cloudflared\cloudflared.exe" set "CLOUDFLARED_EXE=D:\laragon\bin\cloudflared\cloudflared.exe"
if not defined CLOUDFLARED_EXE if exist "%ProgramFiles%\cloudflared\cloudflared.exe" set "CLOUDFLARED_EXE=%ProgramFiles%\cloudflared\cloudflared.exe"
if not defined CLOUDFLARED_EXE if exist "%ProgramFiles(x86)%\cloudflared\cloudflared.exe" set "CLOUDFLARED_EXE=%ProgramFiles(x86)%\cloudflared\cloudflared.exe"
if not defined CLOUDFLARED_EXE if exist "%LOCALAPPDATA%\Microsoft\WinGet\Links\cloudflared.exe" set "CLOUDFLARED_EXE=%LOCALAPPDATA%\Microsoft\WinGet\Links\cloudflared.exe"

if not defined CLOUDFLARED_EXE (
    echo [GAGAL] cloudflared.exe belum terinstal.
    echo.
    echo Cara pasang ^(pilih salah satu^):
    echo   1. Jalankan di terminal: winget install Cloudflare.cloudflared
    echo   2. Atau download:
    echo      https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe
    echo      Lalu ubah namanya menjadi cloudflared.exe dan taruh di:
    echo      C:\laragon\bin\cloudflared\cloudflared.exe
    goto :gagal
)

echo [OK] Cloudflared ditemukan: "!CLOUDFLARED_EXE!"
if /i "%~1"=="--cek" (
    echo [OK] Semua syarat siap.
    exit /b 0
)
echo Salin alamat https://....trycloudflare.com yang muncul di dalam kotak di bawah.
echo Cara salin: blok link dengan mouse lalu KLIK KANAN ^(jangan tekan Ctrl+C^).
echo Jangan tutup jendela ini selama website masih dipakai.
echo.
"!CLOUDFLARED_EXE!" tunnel --url http://127.0.0.1:8088 --no-autoupdate

echo.
echo [INFO] Cloudflare Tunnel berhenti.
:gagal
echo.
pause
exit /b 1

:cek_saja
call :cari_mysql
if defined MYSQL_EXE (
    echo [OK] MySQL ditemukan: "!MYSQL_EXE!"
) else (
    echo [INFO] mysqld.exe tidak ketemu otomatis ^(akan cek port 3306 saat dijalankan^).
)
goto :cek_cloudflared

:cari_mysql
set "MYSQL_EXE="
set "MYSQL_INI="
for /d %%D in ("C:\laragon\bin\mysql\mysql-*" "D:\laragon\bin\mysql\mysql-*") do (
    if exist "%%~fD\bin\mysqld.exe" (
        set "MYSQL_EXE=%%~fD\bin\mysqld.exe"
        if exist "%%~fD\my.ini" set "MYSQL_INI=%%~fD\my.ini"
    )
)
if not defined MYSQL_EXE for /f "delims=" %%I in ('where mysqld 2^>nul') do if not defined MYSQL_EXE set "MYSQL_EXE=%%~fI"
if not defined MYSQL_EXE if exist "C:\xampp\mysql\bin\mysqld.exe" set "MYSQL_EXE=C:\xampp\mysql\bin\mysqld.exe"
if not defined MYSQL_EXE if exist "D:\xampp\mysql\bin\mysqld.exe" set "MYSQL_EXE=D:\xampp\mysql\bin\mysqld.exe"
exit /b 0

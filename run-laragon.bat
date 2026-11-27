@echo off
setlocal EnableDelayedExpansion
chcp 65001 >nul 2>&1
title SIMUTASI-GIS - Laragon Launcher
cd /d "%~dp0"

echo ============================================================
echo   SIMUTASI-GIS - Pemetaan Spasial ^& Rekomendasi Mutasi PNS
echo   Auto Setup ^& Runner untuk Windows Laragon
echo ============================================================
echo.

:: ------------------------------------------------------------
:: 1. Deteksi otomatis Laragon (C:\laragon, D:\laragon, E:\laragon)
:: ------------------------------------------------------------
set "LARAGON_DIR="
for %%D in (C D E F) do (
    if not defined LARAGON_DIR (
        if exist "%%D:\laragon\laragon.exe" set "LARAGON_DIR=%%D:\laragon"
    )
)

if defined LARAGON_DIR (
    echo [INFO] Laragon terdeteksi di: !LARAGON_DIR!

    where php >nul 2>&1
    if errorlevel 1 (
        set "PHP_DIR="
        for /f "delims=" %%P in ('dir /b /ad /o-n "!LARAGON_DIR!\bin\php\php-*" 2^>nul') do (
            if not defined PHP_DIR set "PHP_DIR=!LARAGON_DIR!\bin\php\%%P"
        )
        if defined PHP_DIR set "PATH=!PHP_DIR!;!PATH!"
    )

    where composer >nul 2>&1
    if errorlevel 1 (
        if exist "!LARAGON_DIR!\bin\composer" set "PATH=!LARAGON_DIR!\bin\composer;!PATH!"
    )

    where mysql >nul 2>&1
    if errorlevel 1 (
        set "MYSQL_BIN="
        for /f "delims=" %%M in ('dir /b /ad /o-n "!LARAGON_DIR!\bin\mysql\mysql-*" 2^>nul') do (
            if not defined MYSQL_BIN if exist "!LARAGON_DIR!\bin\mysql\%%M\bin\mysql.exe" set "MYSQL_BIN=!LARAGON_DIR!\bin\mysql\%%M\bin"
        )
        for /f "delims=" %%M in ('dir /b /ad /o-n "!LARAGON_DIR!\bin\mariadb\mariadb-*" 2^>nul') do (
            if not defined MYSQL_BIN if exist "!LARAGON_DIR!\bin\mariadb\%%M\bin\mysql.exe" set "MYSQL_BIN=!LARAGON_DIR!\bin\mariadb\%%M\bin"
        )
        if defined MYSQL_BIN set "PATH=!MYSQL_BIN!;!PATH!"
    )
)

:: ------------------------------------------------------------
:: 2. Cek PHP
:: ------------------------------------------------------------
where php >nul 2>&1
if errorlevel 1 (
    echo [ERROR] PHP tidak ditemukan di PATH maupun folder Laragon!
    echo         Pastikan Laragon sudah terinstall dan PHP 8.3+ aktif.
    pause
    exit /b 1
)

for /f "tokens=2 delims= " %%V in ('php -v ^| findstr /b "PHP "') do set "PHP_VER=%%V"
echo [OK]   PHP terdeteksi: v!PHP_VER!

php -r "exit(version_compare(PHP_VERSION, '8.3.0', '>=') ? 0 : 1);"
if errorlevel 1 (
    echo [WARN] Project ini membutuhkan PHP ^>= 8.3 - versi saat ini: v!PHP_VER!
    echo        Di Laragon: klik kanan tray icon ^> PHP ^> pilih versi 8.3 / 8.4.
    echo.
)

:: ------------------------------------------------------------
:: 3. Buat file .env otomatis untuk Laragon MySQL jika belum ada
:: ------------------------------------------------------------
if not exist ".env" (
    echo [INFO] File .env belum ada, membuat konfigurasi default Laragon...
    (
        echo APP_NAME="SIMUTASI-GIS"
        echo APP_ENV=local
        echo APP_KEY=
        echo APP_DEBUG=true
        echo APP_URL=http://127.0.0.1:8000
        echo.
        echo APP_LOCALE=id
        echo APP_FALLBACK_LOCALE=en
        echo APP_FAKER_LOCALE=id_ID
        echo.
        echo LOG_CHANNEL=stack
        echo LOG_STACK=single
        echo LOG_LEVEL=debug
        echo.
        echo DB_CONNECTION=mysql
        echo DB_HOST=127.0.0.1
        echo DB_PORT=3306
        echo DB_DATABASE=simutasi_asn
        echo DB_USERNAME=root
        echo DB_PASSWORD=
        echo.
        echo SESSION_DRIVER=database
        echo SESSION_LIFETIME=120
        echo SESSION_ENCRYPT=false
        echo SESSION_PATH=/
        echo SESSION_DOMAIN=null
        echo.
        echo BROADCAST_CONNECTION=log
        echo FILESYSTEM_DISK=local
        echo QUEUE_CONNECTION=database
        echo CACHE_STORE=database
        echo.
        echo MAIL_MAILER=log
    ) > .env
    echo [OK]   File .env berhasil dibuat - DB: simutasi_asn, user: root, pass: kosong.
) else (
    echo [OK]   File .env sudah tersedia.
)

:: ------------------------------------------------------------
:: 4. Install dependencies Composer jika folder vendor belum ada
:: ------------------------------------------------------------
if not exist "vendor\autoload.php" (
    echo [INFO] Folder vendor belum ada. Menjalankan composer install...
    where composer >nul 2>&1
    if errorlevel 1 (
        echo [ERROR] Composer tidak ditemukan! Jalankan dari Laragon Terminal: composer install
        pause
        exit /b 1
    )
    call composer install --no-interaction --prefer-dist
    if errorlevel 1 (
        echo [ERROR] composer install gagal! Cek koneksi internet atau versi PHP.
        pause
        exit /b 1
    )
) else (
    echo [OK]   Folder vendor sudah tersedia.
)

:: ------------------------------------------------------------
:: 5. Generate APP_KEY jika masih kosong
:: ------------------------------------------------------------
findstr /r "^APP_KEY=base64:" .env >nul 2>&1
if errorlevel 1 (
    echo [INFO] Generating Laravel APP_KEY...
    php artisan key:generate --force
)

:: ------------------------------------------------------------
:: 6. Cek koneksi MySQL Laragon & auto-create / import database
:: ------------------------------------------------------------
echo [INFO] Mengecek koneksi MySQL/MariaDB Laragon di 127.0.0.1:3306...

:CHECK_MYSQL
php -r "$fp=@fsockopen('127.0.0.1', 3306, $e, $s, 2); if($fp){fclose($fp); exit(0);} exit(1);"
if errorlevel 1 (
    echo.
    echo [WARN] MySQL/MariaDB belum berjalan di port 3306!
    if defined LARAGON_DIR (
        echo        Membuka Laragon... Silakan klik tombol "Start All" di Laragon.
        start "" "!LARAGON_DIR!\laragon.exe"
    ) else (
        echo        Silakan buka Laragon dan klik "Start All".
    )
    echo        Setelah MySQL aktif, tekan tombol apapun untuk melanjutkan...
    pause >nul
    goto CHECK_MYSQL
)
echo [OK]   MySQL/MariaDB aktif di 127.0.0.1:3306.

:: Baca konfigurasi DB dari .env, buat database jika belum ada, dan cek apakah masih kosong
php -r "$e=[]; foreach(file('.env',6) as $l){ $l=trim($l); if($l==='' or $l[0]==='#' or strpos($l,'=')===false) continue; list($k,$v)=explode('=',$l,2); $e[trim($k)]=trim($v,' '.chr(34).chr(39)); } $h=$e['DB_HOST']??'127.0.0.1'; $p=$e['DB_PORT']??'3306'; $d=$e['DB_DATABASE']??'simutasi_asn'; $u=$e['DB_USERNAME']??'root'; $pw=$e['DB_PASSWORD']??''; try { $pdo=new PDO('mysql:host='.$h.';port='.$p.';charset=utf8mb4',$u,$pw,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); $pdo->exec('CREATE DATABASE IF NOT EXISTS `'.$d.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'); $pdo->exec('USE `'.$d.'`'); echo count($pdo->query('SHOW TABLES')->fetchAll())===0 ? 'EMPTY_DB' : 'HAS_TABLES'; } catch(Throwable $ex) { fwrite(STDERR, $ex->getMessage()); exit(2); }" > "%TEMP%\simutasi_db_state.tmp"

if errorlevel 1 (
    echo [ERROR] Gagal konek ke MySQL! Cek DB_USERNAME / DB_PASSWORD di file .env.
    del "%TEMP%\simutasi_db_state.tmp" >nul 2>&1
    pause
    exit /b 1
)

set /p DB_STATE=<"%TEMP%\simutasi_db_state.tmp"
del "%TEMP%\simutasi_db_state.tmp" >nul 2>&1

if "!DB_STATE!"=="EMPTY_DB" goto IMPORT_SQL
echo [OK]   Database sudah berisi tabel. Mengecek migrasi terbaru...
php artisan migrate --force
goto CLEAR_CACHE

:IMPORT_SQL
if not exist "database\simutasi_asn_full.sql" (
    echo [INFO] File SQL dump tidak ditemukan, menjalankan migrasi database...
    php artisan migrate --force
    goto CLEAR_CACHE
)

echo [INFO] Database baru/kosong. Mengimpor database\simutasi_asn_full.sql...
where mysql >nul 2>&1
if errorlevel 1 (
    echo [INFO] mysql CLI tidak ditemukan, menjalankan php artisan migrate --force...
    php artisan migrate --force
    goto CLEAR_CACHE
)

php -r "$e=[]; foreach(file('.env',6) as $l){ $l=trim($l); if($l==='' or $l[0]==='#' or strpos($l,'=')===false) continue; list($k,$v)=explode('=',$l,2); $e[trim($k)]=trim($v,' '.chr(34).chr(39)); } $h=$e['DB_HOST']??'127.0.0.1'; $p=$e['DB_PORT']??'3306'; $d=$e['DB_DATABASE']??'simutasi_asn'; $u=$e['DB_USERNAME']??'root'; $pw=$e['DB_PASSWORD']??''; $pwArg=strlen($pw)>0 ? '-p'.escapeshellarg($pw) : ''; passthru('mysql -h '.escapeshellarg($h).' -P '.escapeshellarg($p).' -u '.escapeshellarg($u).' '.$pwArg.' '.escapeshellarg($d).' < database\\simutasi_asn_full.sql', $rc); exit($rc);"
if errorlevel 1 (
    echo [WARN] Import via mysql CLI gagal, fallback ke php artisan migrate...
    php artisan migrate --force
) else (
    echo [OK]   Import SQL selesai!
)

:CLEAR_CACHE
:: ------------------------------------------------------------
:: 7. Bersihkan cache Laravel agar tidak bentrok antar OS
:: ------------------------------------------------------------
echo [INFO] Membersihkan cache konfigurasi ^& view...
php artisan optimize:clear >nul 2>&1

:: ------------------------------------------------------------
:: 8. Jalankan server Laravel & buka browser otomatis
:: ------------------------------------------------------------
echo.
echo ============================================================
echo   SERVER SIAP DIJALANKAN!
echo   URL   : http://127.0.0.1:8000
echo   Admin : admin@bkpsdm.go.id / password123
echo   PNS   : ^<NIP-18-digit^>@bkpsdm.go.id / ^<NIP-18-digit^>
echo.
echo   Tekan CTRL+C pada jendela ini untuk menghentikan server.
echo ============================================================
echo.

:: Buka browser otomatis setelah 2 detik
start "" cmd /c "timeout /t 2 /nobreak >nul && start http://127.0.0.1:8000"

php artisan serve --host=127.0.0.1 --port=8000

echo.
echo Server telah berhenti.
pause
endlocal

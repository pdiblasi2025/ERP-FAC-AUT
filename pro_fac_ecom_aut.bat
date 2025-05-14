@echo off
setlocal


REM Obtener la fecha y hora actual
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set "fecha_hora=%%I"
set "fecha=%fecha_hora:~0,4%-%fecha_hora:~4,2%-%fecha_hora:~6,2%"
set "hora=%fecha_hora:~8,2%-%fecha_hora:~10,2%-%fecha_hora:~12,2%"


REM Ejecutar el script de PHP
php -f C:\PSD_PROCESOS\ERP-FACT-AUT\ERP-FACT-AUT\index.php


REM Verificar si el script se ejecutó correctamente
if %errorlevel% neq 0 (
    echo Error al ejecutar el script de PHP.
    exit /b %errorlevel%
)

REM Crear un archivo de log con la fecha y hora
set "log_file=C:\PSD_PROCESOS\ERP-FACT-AUT\ERP-FACT-AUT\log\ERP-FACT-AUT_%fecha%_%hora%.txt" 

echo Script ejecutado correctamente el %fecha% a las %hora% > "%log_file%"
echo. >> "%log_file%"   

REM Hacer una pausa de 5 segundos.
timeout /t 20 /nobreak

REM terminar todas los procesos cmd.exe que estuvieran en ejecucion o colgados. 
TASKKILL /IM CMD.EXE /F

@echo off
setlocal EnableDelayedExpansion

echo ============================================
echo  Simple Manifest Server Setup
echo ============================================
echo.

:: ============================================
:: Defaults
:: ============================================
set FILE_STORE_URL=http://localhost/iiif_manifests
set API_USERNAME=manifest_api_user
set API_PASSWORD=
set ENVIRONMENT=1
set VALID_SERIES=HSAH,MRAS,ARTB,JMHIS,annotations
set FLEXIBLE_SERIES=

:: Load saved config if available
if exist init.cfg (
    for /f "usebackq eol=# tokens=1,* delims==" %%a in ("init.cfg") do set %%a=%%b
    echo Loaded settings from init.cfg
    echo.
)

:: ============================================
:: Site Settings
:: ============================================
echo ============================================
echo  Site Settings
echo ============================================
echo.

set /p FILE_STORE_URL=External manifest server URL [%FILE_STORE_URL%]:
set /p API_USERNAME=API basic-auth username [%API_USERNAME%]:
set _SEC_DISP=
if not "%API_PASSWORD%"=="" set _SEC_DISP=*set*
set _ANS=
set /p _ANS=API basic-auth password [%_SEC_DISP%]:
if not "!_ANS!"=="" set API_PASSWORD=!_ANS!
set /p VALID_SERIES=Valid series comma-separated [%VALID_SERIES%]:
set /p FLEXIBLE_SERIES=Flexible series comma-separated leave blank for none [%FLEXIBLE_SERIES%]:
echo.

:: ============================================
:: Auto-generate secrets
:: ============================================
for /f "usebackq delims=" %%k in (`powershell -NoProfile -Command "[System.BitConverter]::ToString([Security.Cryptography.RandomNumberGenerator]::GetBytes(16)).Replace('-','')"`) do set DB_PASSWORD=%%k
echo Database password generated.
echo.

:: ============================================
:: Save config for next run
:: ============================================
(
    echo FILE_STORE_URL=%FILE_STORE_URL%
    echo API_USERNAME=%API_USERNAME%
    echo API_PASSWORD=%API_PASSWORD%
    echo ENVIRONMENT=%ENVIRONMENT%
    echo VALID_SERIES=%VALID_SERIES%
    echo FLEXIBLE_SERIES=%FLEXIBLE_SERIES%
) > init.cfg
echo init.cfg saved.
echo.

:: ============================================
:: Write .env for docker compose
:: ============================================
(
    echo DB_HOST=mysql
    echo DB_NAME=manifest_server
    echo DB_USER=manifest_user
    echo DB_PASSWORD=%DB_PASSWORD%
    echo API_USERNAME=%API_USERNAME%
    echo API_PASSWORD=%API_PASSWORD%
    echo API_KEY=
    echo FILE_STORE_URL=%FILE_STORE_URL%
    echo INTERNAL_FILE_STORE_URL=http://web:80/iiif_manifests
    echo IIIF_VALIDATOR=http://validator:8080/validate?version=3.0
    echo PUT_MANIFEST=http://web:80/api/v1/putManifest
    echo REMOVE_MANIFEST=http://web:80/api/v1/removeManifest
    echo ENVIRONMENT=%ENVIRONMENT%
    echo VALID_SERIES=%VALID_SERIES%
    echo FLEXIBLE_SERIES=%FLEXIBLE_SERIES%
) > .env
echo .env written.
echo.

if not exist iiif_manifests mkdir iiif_manifests

docker compose down --rmi all -v --remove-orphans
for /f "tokens=*" %%i in ('docker images -q 2^>nul') do docker rmi %%i
docker compose --verbose up -d

echo.
echo Simple Manifest Server is now installed
endlocal
goto :eof

@echo off
setlocal EnableDelayedExpansion

:: Backup if MySQL is running
docker inspect -f "{{.State.Running}}" mysql 2>nul | findstr /c:"true" >nul 2>&1
if %errorlevel%==0 (
    if not exist db_backup mkdir db_backup
    echo Backing up database...
    docker exec mysql sh -c "exec mysqldump -uroot -p$MYSQL_ROOT_PASSWORD manifest_server" > db_backup\manifest_server.sql
    if %errorlevel%==0 (
        echo Database saved to db_backup\manifest_server.sql
    ) else (
        echo Warning: database backup failed
        del db_backup\manifest_server.sql 2>nul
    )
) else (
    echo MySQL not running - skipping backup
)

docker compose down --rmi all -v --remove-orphans
for /f "tokens=*" %%i in ('docker images -q 2^>nul') do docker rmi %%i
docker compose --verbose up -d

if not exist db_backup\manifest_server.sql goto :eof

echo Waiting for MySQL...
set MAX_TRIES=30
set /a count=0

:wait_loop
set /a count+=1
if %count% geq %MAX_TRIES% (
    echo MySQL not ready - skipping restore
    goto :eof
)
docker exec mysql mysqladmin ping -h 127.0.0.1 --silent >nul 2>&1
if %errorlevel%==0 goto :mysql_ready
echo Attempt %count%/%MAX_TRIES%...
timeout /t 2 /nobreak >nul
goto :wait_loop

:mysql_ready
echo Restoring database from backup...
docker exec -i mysql sh -c "exec mysql -uroot -p$MYSQL_ROOT_PASSWORD manifest_server" < db_backup\manifest_server.sql
if %errorlevel%==0 (
    echo Database restored
) else (
    echo Warning: restore failed
)

endlocal

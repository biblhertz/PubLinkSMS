@echo off
setlocal

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
endlocal

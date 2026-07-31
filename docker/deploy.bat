@echo off
:: Rebuild and restart containers without wiping the database volume.
:: Use this to deploy code changes. Use restart.bat if you need a full teardown.
docker compose down --rmi all --remove-orphans
for /f "tokens=*" %%i in ('docker images -q 2^>nul') do docker rmi %%i
docker compose --verbose up -d

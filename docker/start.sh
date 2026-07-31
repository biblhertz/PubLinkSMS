#!/bin/bash
source "$(dirname "$0")/docker-env.sh"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
BACKUP_DIR="$SCRIPT_DIR/db_backup"
BACKUP_FILE="$BACKUP_DIR/manifest_server.sql"

if docker ps --format '{{.Names}}' | grep -q '^mysql$'; then
    mkdir -p "$BACKUP_DIR"
    echo "Backing up database..."
    docker exec mysql sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" manifest_server' > "$BACKUP_FILE"
    if [ $? -eq 0 ] && [ -s "$BACKUP_FILE" ]; then
        echo "Database saved to $BACKUP_FILE"
    else
        echo "Warning: database backup failed"
        rm -f "$BACKUP_FILE"
    fi
else
    echo "MySQL not running — skipping backup"
fi

docker compose down --rmi all -v --remove-orphans
images=$(docker images -q)
[ -n "$images" ] && docker rmi $images
docker compose --verbose up -d

if [ -f "$BACKUP_FILE" ]; then
    echo "Waiting for MySQL..."
    count=0
    until docker exec mysql mysqladmin ping -h 127.0.0.1 --silent 2>/dev/null; do
        count=$((count + 1))
        if [ "$count" -ge 30 ]; then
            echo "MySQL not ready — skipping restore"
            exit 0
        fi
        echo "Attempt $count/30..."
        sleep 2
    done
    echo "Restoring database from backup..."
    docker exec -i mysql sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" manifest_server' < "$BACKUP_FILE"
    if [ $? -eq 0 ]; then
        echo "Database restored"
    else
        echo "Warning: restore failed"
    fi
fi

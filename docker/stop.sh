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

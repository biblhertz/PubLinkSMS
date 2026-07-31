#!/bin/sh
chown -R www-data:www-data /var/www/html/iiif_manifests
chmod -R 775 /var/www/html/iiif_manifests

# Build valid_series INI lines from comma-separated VALID_SERIES env var
series_config=""
if [ -n "${VALID_SERIES:-}" ]; then
    IFS=','
    for s in ${VALID_SERIES}; do
        series_config="${series_config}valid_series[] = \"${s}\"
"
    done
    unset IFS
fi

flexible_config='flexible_series[] = ""'
if [ -n "${FLEXIBLE_SERIES:-}" ]; then
    flexible_config=""
    IFS=','
    for s in ${FLEXIBLE_SERIES}; do
        flexible_config="${flexible_config}flexible_series[] = \"${s}\"
"
    done
    unset IFS
fi

cat > /var/www/config.ini << EOF
[credentials]
username = "${API_USERNAME:-}"
password = "${API_PASSWORD:-}"
api_key = "${API_KEY:-}"

[path]
file_store_path = "/var/www/html/iiif_manifests"
file_store_url = "${FILE_STORE_URL:-http://localhost/iiif_manifests}"
internal_file_store_url = "${INTERNAL_FILE_STORE_URL:-http://web:80/iiif_manifests}"
iiif_validator = "${IIIF_VALIDATOR:-http://validator:8080/validate?version=3.0}"
put_manifest = "${PUT_MANIFEST:-http://web:80/api/v1/putManifest}"
remove_manifest = "${REMOVE_MANIFEST:-http://web:80/api/v1/removeManifest}"

[series]
${series_config}
[environment]
environment = ${ENVIRONMENT:-1}

[database]
db_host = "${DB_HOST:-mysql}"
db_name = "${DB_NAME:-manifest_server}"
db_user = "${DB_USER:-manifest_user}"
db_password = "${DB_PASSWORD:-}"

[flexible]
${flexible_config}
EOF

echo "Waiting for MySQL..."
count=0
until php -r "new PDO('mysql:host=${DB_HOST:-mysql};dbname=${DB_NAME:-manifest_server}', '${DB_USER:-manifest_user}', '${DB_PASSWORD:-}');" 2>/dev/null; do
    count=$((count + 1))
    if [ "$count" -ge 30 ]; then
        echo "MySQL not ready after 60s, starting anyway"
        break
    fi
    echo "Attempt $count/30..."
    sleep 2
done
echo "MySQL ready"

exec php-fpm

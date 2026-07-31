#!/bin/bash
source "$(dirname "$0")/docker-env.sh"

echo "============================================"
echo " Simple Manifest Server Setup"
echo "============================================"
echo

# ============================================
# Defaults
# ============================================
FILE_STORE_URL=http://localhost/iiif_manifests
API_USERNAME=manifest_api_user
API_PASSWORD=
ENVIRONMENT=1
VALID_SERIES=HSAH,MRAS,ARTB,JMHIS,annotations
FLEXIBLE_SERIES=
HTTP_PORT=80

# Load saved config if available
INIT_CFG="$(dirname "$0")/init.cfg"
if [[ -f "$INIT_CFG" ]]; then
    while IFS='=' read -r key value; do
        [[ "$key" =~ ^#.*$ || -z "$key" ]] && continue
        declare "$key=$value"
    done < "$INIT_CFG"
    echo "Loaded settings from init.cfg"
    echo
fi

prompt_value() {
    local var="$1" label="$2"
    local current="${!var}"
    local input
    read -p "$label [$current]: " input
    [[ -n "$input" ]] && declare -g "$var=$input"
}

prompt_secret() {
    local var="$1" label="$2"
    local current="${!var}"
    local display=""
    [[ -n "$current" ]] && display="*set*"
    local input
    read -p "$label [$display]: " input
    [[ -n "$input" ]] && declare -g "$var=$input"
}

# ============================================
# Site Settings
# ============================================
echo "============================================"
echo " Site Settings"
echo "============================================"
echo

prompt_value FILE_STORE_URL "External manifest server URL"
prompt_value HTTP_PORT      "HTTP port (port 80 requires: sudo sysctl net.ipv4.ip_unprivileged_port_start=80)"
prompt_value API_USERNAME   "API basic-auth username"
prompt_secret API_PASSWORD  "API basic-auth password"
prompt_value VALID_SERIES   "Valid series (comma-separated)"
prompt_value FLEXIBLE_SERIES "Flexible series (comma-separated, leave blank for none)"
echo

# ============================================
# Auto-generate secrets
# ============================================
DB_PASSWORD=$(openssl rand -base64 16)
echo "Database password generated."
echo

# ============================================
# Save config for next run
# ============================================
cat > "$INIT_CFG" << EOF
FILE_STORE_URL=${FILE_STORE_URL}
HTTP_PORT=${HTTP_PORT}
API_USERNAME=${API_USERNAME}
API_PASSWORD=${API_PASSWORD}
ENVIRONMENT=${ENVIRONMENT}
VALID_SERIES=${VALID_SERIES}
FLEXIBLE_SERIES=${FLEXIBLE_SERIES}
EOF
chmod 600 "$INIT_CFG"
echo "init.cfg saved."
echo

# ============================================
# Write .env for docker compose
# ============================================
cat > .env << EOF
DB_HOST=mysql
DB_NAME=manifest_server
DB_USER=manifest_user
DB_PASSWORD=${DB_PASSWORD}
API_USERNAME=${API_USERNAME}
API_PASSWORD=${API_PASSWORD}
API_KEY=
FILE_STORE_URL=${FILE_STORE_URL}
HTTP_PORT=${HTTP_PORT}
INTERNAL_FILE_STORE_URL=http://web:80/iiif_manifests
IIIF_VALIDATOR=http://validator:8080/validate?version=3.0
PUT_MANIFEST=http://web:80/api/v1/putManifest
REMOVE_MANIFEST=http://web:80/api/v1/removeManifest
ENVIRONMENT=${ENVIRONMENT}
VALID_SERIES=${VALID_SERIES}
FLEXIBLE_SERIES=${FLEXIBLE_SERIES}
EOF
chmod 600 .env
echo ".env written."
echo

mkdir -p ./iiif_manifests
chmod +x ./*.sh

docker compose down --rmi all -v --remove-orphans
docker images -q | xargs -r docker rmi
docker compose --verbose up -d

# ============================================
# Systemd user service (rootless Docker)
# ============================================
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SERVICE_TEMPLATE="$SCRIPT_DIR/manifest-server.service"
SYSTEMD_USER_DIR="$HOME/.config/systemd/user"

if [[ -f "$SERVICE_TEMPLATE" ]] && command -v systemctl &>/dev/null; then
    read -p "Install systemd user service for auto-start on boot? [y/N]: " yn
    if [[ "$yn" =~ ^[Yy]$ ]]; then
        mkdir -p "$SYSTEMD_USER_DIR"
        sed "s|INSTALL_DIR|$SCRIPT_DIR|g" "$SERVICE_TEMPLATE" > "$SYSTEMD_USER_DIR/manifest-server.service"
        systemctl --user daemon-reload
        systemctl --user enable manifest-server.service
        # Enable linger so the service starts at boot without an interactive login
        loginctl enable-linger "$(id -un)" 2>/dev/null && \
            echo "Linger enabled — service will start at boot." || \
            echo "Note: run 'sudo loginctl enable-linger $(id -un)' to start at boot without login."
        echo "Systemd user service installed and enabled."
    fi
fi

echo
echo "Simple Manifest Server is now installed"
echo "Access at: http://$(hostname -I | awk '{print $1}'):${HTTP_PORT}/"

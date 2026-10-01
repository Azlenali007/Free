#!/bin/bash
set -e

# 1. Ensure MariaDB is running
if ! /etc/init.d/mariadb status >/dev/null 2>&1; then
    echo "Starting MariaDB service..."
    /etc/init.d/mariadb start || mysqld_safe --datadir=/var/lib/mysql &
    # Wait for MariaDB to accept connections
    for i in {1..15}; do
        if mysqladmin ping >/dev/null 2>&1; then
            echo "MariaDB is ready."
            break
        fi
        sleep 1
    done
fi

# 2. Parse arguments
PORT=3000
HOST="0.0.0.0"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --port|-p)
            PORT="$2"
            shift 2
            ;;
        --host|-h)
            HOST="$2"
            shift 2
            ;;
        *)
            if [[ "$1" =~ ^[0-9]+$ ]]; then
                PORT="$1"
            fi
            shift
            ;;
    esac
done

# 3. Kill any stale process on target port to prevent 'Address already in use'
if command -v fuser >/dev/null 2>&1; then
    fuser -k "${PORT}/tcp" 2>/dev/null || true
fi
pkill -f "php -S.*:${PORT}" 2>/dev/null || true
sleep 0.5

# 4. Ensure required directories and permissions
mkdir -p /app/applet/uploads/products /app/applet/config
chmod -R 777 /app/applet/uploads /app/applet/config 2>/dev/null || true

# 5. Enable multi-worker mode for PHP built-in web server (prevents hanging on concurrent assets)
export PHP_CLI_SERVER_WORKERS=8

cd /app/applet
echo "Starting FireZone Free Fire Store on ${HOST}:${PORT} with 8 workers..."
exec php -d memory_limit=256M -d upload_max_filesize=20M -d post_max_size=25M -S "${HOST}:${PORT}" router.php

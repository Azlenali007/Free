#!/bin/bash
# Start MariaDB if not started
/etc/init.d/mariadb status >/dev/null 2>&1 || /etc/init.d/mariadb start

# Ensure directories and permissions
mkdir -p /app/applet/uploads/products /app/applet/config
chmod -R 777 /app/applet/uploads /app/applet/config 2>/dev/null || true

cd /app/applet
echo "Starting FireZone Free Fire Store on 0.0.0.0:3000..."
exec php -S 0.0.0.0:3000 router.php

#!/usr/bin/env bash
set -euo pipefail

cd /app

if [[ ! -f frontend/web/index.php ]]; then
  echo "Initializing Yii production environment…"
  php init --env=Production --overwrite=All
fi

echo "Preparing demo database…"
php yii install/demo-db

echo "Starting Apache…"
exec apache2-foreground

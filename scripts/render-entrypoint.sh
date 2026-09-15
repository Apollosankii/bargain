#!/usr/bin/env bash
set -euo pipefail

cd /app

if [[ ! -f frontend/web/index.php ]]; then
  echo "Initializing Yii production environment…"
  php init --env=Production --overwrite=All
fi

if [[ "${RUN_DEMO_INSTALL:-true}" == "true" ]]; then
  echo "Preparing demo database…"
  php yii install/demo-db
else
  echo "Skipping demo database bootstrap on this service."
fi

echo "Starting Apache…"
exec apache2-foreground

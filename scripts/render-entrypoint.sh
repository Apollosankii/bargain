#!/usr/bin/env bash
set -uo pipefail

cd /app

if [[ ! -f frontend/web/index.php ]]; then
  echo "Initializing Yii production environment…"
  php init --env=Production --overwrite=a
fi

# Start Apache first so Render health checks pass, then bootstrap DB in background.
echo "Starting Apache…"
apache2-foreground &
APACHE_PID=$!

if [[ "${RUN_DEMO_INSTALL:-true}" == "true" ]]; then
  (
    sleep 3
    echo "Preparing demo database…"
    for attempt in 1 2 3 4 5; do
      if php yii install/demo-db; then
        echo "Demo database ready."
        exit 0
      fi
      echo "Demo install attempt ${attempt} failed — retrying in 10s…"
      sleep 10
    done
    echo "Demo install did not complete — check DATABASE_URL and logs."
  ) &
fi

wait "${APACHE_PID}"

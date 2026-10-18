#!/usr/bin/env bash
# Runs on the server after GitHub Actions has pulled the new code (see .github/workflows/deploy.yml).
# Built assets (public/build) are uploaded by the workflow right after this script.
set -euo pipefail

cd "$(dirname "$0")/.."
PHP="${PHP_BIN:-php}"

"$PHP" "$(command -v composer)" install --no-dev --optimize-autoloader --no-interaction --no-progress
"$PHP" artisan migrate --force
"$PHP" artisan optimize

echo "Deployed $(git rev-parse --short HEAD)"

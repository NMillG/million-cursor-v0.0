#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PORT="${1:-8000}"

if [[ -f "${ROOT_DIR}/.env.local" ]]; then
  # shellcheck disable=SC1091
  source "${ROOT_DIR}/.env.local"
fi

php "${ROOT_DIR}/setup_local_mysql.php"

echo "Starting nMillion at http://localhost:${PORT}/index.php"
php -S "127.0.0.1:${PORT}" -t "${ROOT_DIR}"

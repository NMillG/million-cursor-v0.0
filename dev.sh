#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKEND_PORT="${1:-8000}"
FRONTEND_PORT="${2:-5173}"

if [[ -f "${ROOT_DIR}/.env.local" ]]; then
  # shellcheck disable=SC1091
  source "${ROOT_DIR}/.env.local"
fi

cleanup() {
  if [[ -n "${PHP_PID:-}" ]]; then
    kill "${PHP_PID}" >/dev/null 2>&1 || true
  fi
  if [[ -n "${VITE_PID:-}" ]]; then
    kill "${VITE_PID}" >/dev/null 2>&1 || true
  fi
}

trap cleanup EXIT INT TERM

echo "Preparing local MySQL..."
php "${ROOT_DIR}/setup_local_mysql.php"

echo "Ensuring frontend dependencies are installed..."
npm install --prefix "${ROOT_DIR}/frontend" >/dev/null

echo "Starting PHP backend on http://localhost:${BACKEND_PORT}/index.php"
php -S "127.0.0.1:${BACKEND_PORT}" -t "${ROOT_DIR}" &
PHP_PID=$!

echo "Starting React dev server on http://localhost:${FRONTEND_PORT}"
npm run dev --prefix "${ROOT_DIR}/frontend" -- --host 127.0.0.1 --port "${FRONTEND_PORT}" &
VITE_PID=$!

echo
echo "Development stack is running:"
echo "- Backend:  http://localhost:${BACKEND_PORT}/index.php"
echo "- Frontend: http://localhost:${FRONTEND_PORT}"
echo
echo "Press Ctrl+C to stop both."

wait "${PHP_PID}" "${VITE_PID}"

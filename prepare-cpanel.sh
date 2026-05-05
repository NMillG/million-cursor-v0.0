#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FRONTEND_DIR="${ROOT_DIR}/frontend"
PUBLIC_DIR="${ROOT_DIR}/public_html"

echo "Building React/TypeScript assets..."
cd "${FRONTEND_DIR}"
npm install
npm run build

echo "Copying PHP app files into public_html..."
cd "${ROOT_DIR}"
cp admin_rates.php admin_users.php auth.php autoloan.php config.php dashboard.php db.php forgot_password.php index.php login.php logout.php market_data.php mortgage.php portfolio.php portfolio_api.php portfolio_lib.php portfolio_ui.js rates_loader.php rates.json register.php reset_password.php style.css "${PUBLIC_DIR}/"
mkdir -p "${PUBLIC_DIR}/sql"
cp "${ROOT_DIR}/sql/nmillion_schema.sql" "${PUBLIC_DIR}/sql/"

echo "Prepared cPanel upload folder: ${PUBLIC_DIR}"
echo "Upload the contents of public_html/ to your cPanel public_html folder."

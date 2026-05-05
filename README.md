# nMillion Developer Setup

Tech stack:
- PHP + MySQL (backend)
- CSS + JavaScript (UI)
- React + TypeScript (frontend build pipeline)

## Local development

1. Configure local DB in `.env.local`.
2. Start full local stack (backend + frontend):

```bash
./dev.sh
```

3. Open:
- http://localhost:8000/index.php
- http://localhost:5173

Optional custom ports:

```bash
./dev.sh 8001 5174
```

## Frontend (React + TypeScript)

Run from `frontend/`:

```bash
npm install
npm run dev
```

Production build output is configured to:
- `public_html/assets`

## Prepare upload for cPanel

Run:

```bash
./prepare-cpanel.sh
```

This script:
- installs frontend dependencies
- builds React/TypeScript assets
- copies PHP files into local `public_html/` folder for upload

## cPanel database setup

Use `.env.production.example` as reference and set cPanel credentials:
- `MYSQL_HOST`
- `MYSQL_PORT`
- `MYSQL_DATABASE`
- `MYSQL_USERNAME`
- `MYSQL_PASSWORD`
- `APP_BASE_URL`

On first request, required tables are auto-created:
- `users`
- `password_resets`

## Deployment checklist

See `deploy-checklist.md` for full pre/post deployment validation steps.

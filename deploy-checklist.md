# nMillion cPanel Deployment Checklist

## 1) Local prerequisites
- PHP installed (`php -v`)
- MySQL running locally
- Node.js LTS and npm installed

## 2) Local config
- Update `.env.local` with your local MySQL credentials
- Start local app with `./run-local.sh`
- Confirm login/register/forgot password work locally

## 3) Production config
- Copy `.env.production.example` values into cPanel environment strategy
- Set real DB credentials and site URL
- Replace reCAPTCHA test keys in `config.php` with production keys

## 4) Build and package
- Run `./prepare-cpanel.sh`
- This builds React assets and prepares `public_html/`

## 5) cPanel setup
- Create MySQL database and DB user in cPanel
- Assign user privileges to database
- Upload contents of local `public_html/` into cPanel `public_html/`

## 6) Post-deploy verification
- Open production URL
- Test: register, login, forgot password, reset password, logout
- Confirm DB tables are created: `users`, `password_resets`

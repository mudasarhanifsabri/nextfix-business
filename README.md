# NextFix Business

Laravel 12 foundation for the NextFix Business customer and operations portal described in the project specification.

## Included

- Company-scoped authentication and roles (`company_manager`, `nextfix_admin`)
- Responsive customer dashboard with exactly two primary services
- Company unit registration and editing
- Cleaning and maintenance booking intake
- Booking list, status and payment placeholders
- NextFix operations overview
- Installable PWA shell with safe offline fallback
- Admin-only controlled release updater with reauthentication, locking and deployment history
- SQLite demo database and tenant-isolation tests

Live Nomod calls, production pricing, tax rules, messaging, field-staff workflows and the controlled deployment updater are deliberately not simulated as complete. They require the business decisions and credentials listed in the specification.

## Local setup

```bash
composer install
npm install
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000`.

Demo customer: `manager@demo.test` / `password`  
Demo admin: `admin@nextfix.test` / `password`

## Production notes

- Set `APP_ENV=production`, `APP_DEBUG=false`, a secure `APP_KEY`, and MySQL credentials.
- Serve only `public/` from the web document root.
- Configure HTTPS, mail, scheduler, queue processing and private storage.
- Replace demo accounts and data before deployment.
- Keep Nomod credentials in server-side environment configuration only.

## Controlled updates

The System updates screen never accepts commands, branches or tokens from a browser. It reads one approved server-side manifest and queues a fixed restricted runner configured through `NEXTFIX_DEPLOY_SCRIPT`. Copy `deploy/approved.json.example` outside the public document root, replace its values with an approved release, and configure a reviewed server runner. The runner must stop when its database backup or health check fails.

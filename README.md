# ComTech Repair Booking

Laravel-based repair booking and ticket management for customers, technicians, and admins.

## Features

- Customer repair bookings with device photos, priorities, and service categories
- Ticket lifecycle: new → assigned → in progress → awaiting parts → resolved → closed
- Live ticket conversation (Alpine.js polling), internal notes, and ETA/pickup dates
- Customer cancellation before work starts
- In-app notification bell plus queued email alerts
- Admin site settings: branding, SEO, homepage, SMTP, auto-assign, security, legal pages
- Optional email verification and admin two-factor authentication
- Rate limiting on auth and comment posting
- Privacy policy and terms of service pages

## Stack

- Laravel 13, PHP 8.3
- SQLite (local) or MySQL/PostgreSQL (production)
- Blade, Tailwind CSS 4, Alpine.js, Vite

## Local setup

```bash
cd app-server
composer setup
```

`composer setup` runs install, `.env` copy, key generation, migrations, **`storage:link`**, and `npm run build`.

Create the SQLite database if needed:

```bash
# Windows PowerShell
New-Item -ItemType File -Force database/database.sqlite
```

Start development (server, queue worker, logs, Vite):

```bash
composer dev
```

Or manually:

```bash
php artisan serve
php artisan queue:work
npm run dev
```

Default seeded logins (if seeder was run): `admin@example.com`, `demo@example.com` (read-only settings), `test@example.com`, `technician@example.com` — password: `password`.

## Production deployment checklist

### 1. Environment

```env
APP_ENV=production
APP_DEBUG=false
LOG_CHANNEL=daily
LOG_LEVEL=warning

# Use MySQL/PostgreSQL in production
DB_CONNECTION=mysql

# Queue (required for email)
QUEUE_CONNECTION=database
```

### 2. Build and migrate

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link --force
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Storage symlink

Uploaded photos, logos, and profile pictures are served from `public/storage`. Run once per server:

```bash
php artisan storage:link
```

This is included in `composer setup` for new environments.

### 4. Queue worker (required)

All transactional email is queued (`TicketAlertMail`, verification, password reset). Without a worker, messages stay in the `jobs` table.

**Supervisor (Linux example)** — save as `/etc/supervisor/conf.d/comtech-worker.conf`:

```ini
[program:comtech-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/comtech/app-server/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/comtech/app-server/storage/logs/worker.log
stopwaitsecs=3600
```

Then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start comtech-worker:*
```

**systemd (alternative)** — `/etc/systemd/system/comtech-queue.service`:

```ini
[Unit]
Description=ComTech Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/comtech/app-server/artisan queue:work database --sleep=3 --tries=3
WorkingDirectory=/var/www/comtech/app-server

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now comtech-queue
```

### 5. Monitor failed jobs

```bash
php artisan queue:failed          # list failures
php artisan queue:retry all       # retry all
php artisan queue:flush           # clear failed table (use with care)
```

Schedule periodic retries in `routes/console.php` if desired:

```php
Schedule::command('queue:retry all')->hourly();
Schedule::command('queue:prune-failed --hours=168')->daily();
```

### 6. Scheduler (optional)

Add to crontab:

```cron
* * * * * cd /var/www/comtech/app-server && php artisan schedule:run >> /dev/null 2>&1
```

## Testing

```bash
php artisan test
```

## Key routes

| Route | Purpose |
|-------|---------|
| `/` | Public homepage |
| `/register`, `/login` | Customer auth |
| `/dashboard` | Role-based dashboard |
| `/tickets` | Ticket list and management |
| `/admin/settings` | Site configuration |
| `/privacy`, `/terms` | Legal pages (when content is set) |

## Mail

When SMTP is enabled in **Admin → Site Settings → Email**, those settings override `.env` mail config for transactional email. A queue worker must be running for delivery.

## Deployment

See **[DEPLOYMENT.md](DEPLOYMENT.md)** for full production guides covering Ubuntu, Windows Server, cPanel, Docker, and other platforms.

---

**Laravel 13** · **PHP 8.3**

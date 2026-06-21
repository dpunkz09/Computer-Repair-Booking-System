# Production Deployment Guide

This guide covers deploying the ComTech Booking app to an Ubuntu VPS with Nginx, PHP 8.3, and MySQL.

## Server Requirements

- Ubuntu 22.04 or 24.04 LTS
- PHP 8.3 with extensions: `mbstring`, `xml`, `curl`, `mysql`, `zip`, `bcmath`
- MySQL 8.x
- Nginx
- Composer
- Node.js (optional, for asset builds)

## 1. Clone and Install

```bash
cd /var/www
git clone <your-repo-url> comtech-booking
cd comtech-booking/app-server
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

## 2. Environment Configuration

Copy and edit the environment file:

```bash
cp .env.example .env
php artisan key:generate
```

Set production values in `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=comtech_booking
DB_USERNAME=comtech
DB_PASSWORD=your-secure-password

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-mail-user
MAIL_PASSWORD=your-mail-password
MAIL_FROM_ADDRESS=noreply@your-domain.com
```

## 3. Database Setup

```bash
php artisan migrate --force
php artisan db:seed --force
```

Default seeded accounts (password: `password`):

| Email | Role |
|-------|------|
| admin@example.com | Admin |
| test@example.com | Customer |
| technician@example.com | Technician |

Change these passwords immediately in production.

## 4. Nginx Configuration

Copy the sample config and enable the site:

```bash
sudo cp deploy/nginx.conf /etc/nginx/sites-available/comtech-booking
sudo ln -s /etc/nginx/sites-available/comtech-booking /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

Update `server_name` and `root` paths in the config to match your domain and install path.

## 5. File Permissions

```bash
sudo chown -R www-data:www-data /var/www/comtech-booking/app-server/storage
sudo chown -R www-data:www-data /var/www/comtech-booking/app-server/bootstrap/cache
sudo chmod -R 775 /var/www/comtech-booking/app-server/storage
sudo chmod -R 775 /var/www/comtech-booking/app-server/bootstrap/cache
```

## 6. SSL with Certbot

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

Certbot will configure HTTPS and auto-renewal.

## 7. Queue Worker

Outgoing mail (password reset, ticket alerts, SMTP tests) is queued. Run a worker via Supervisor:

```bash
sudo apt install supervisor
```

Create `/etc/supervisor/conf.d/comtech-booking-worker.conf`:

```ini
[program:comtech-booking-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/comtech-booking/app-server/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/comtech-booking/app-server/storage/logs/worker.log
stopwaitsecs=3600
```

Then:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start comtech-booking-worker:*
```

Ensure `.env` has `QUEUE_CONNECTION=database` and the `jobs` table exists (`php artisan migrate`).

For local development:

```bash
php artisan queue:work
```

## 8. Laravel Optimization

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 9. Verify Deployment

- Visit `https://your-domain.com/up` — should return HTTP 200
- Log in as admin and confirm dashboards, ticket creation, and admin tools work
- Run tests locally before deploying: `php artisan test`

## 10. Role Boundary Testing

After deployment, manually verify:

- Customers can only see their own tickets
- Technicians cannot access `/admin/*` routes
- Internal notes are hidden from customers
- Password reset emails are delivered (requires working SMTP)

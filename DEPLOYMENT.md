# ComTech Repair Booking — Deployment Guide

This guide explains how to deploy **ComTech Repair Booking** (`app-server/`) to production on **Ubuntu**, **Windows**, **cPanel**, and other common hosting platforms.

The application is a **Laravel 13** project (PHP 8.3) with a Vite frontend, database-backed queues, file uploads, and optional SMTP email configured from the admin panel.

---

## Table of contents

1. [Before you deploy](#before-you-deploy)
2. [Server requirements](#server-requirements)
3. [Environment configuration](#environment-configuration)
4. [Standard deployment steps](#standard-deployment-steps)
5. [Deploy on Ubuntu (VPS)](#deploy-on-ubuntu-vps)
6. [Deploy on Windows Server](#deploy-on-windows-server)
7. [Deploy on cPanel / shared hosting](#deploy-on-cpanel--shared-hosting)
8. [Other platforms](#other-platforms)
9. [Post-deployment checklist](#post-deployment-checklist)
10. [Updates and rollbacks](#updates-and-rollbacks)
11. [Troubleshooting](#troubleshooting)

---

## Before you deploy

### Application layout

```
ComTechBooking/
└── app-server/          ← Laravel application root
    ├── app/
    ├── public/          ← Web server document root must point here
    ├── storage/         ← Must be writable
    ├── bootstrap/cache/ ← Must be writable
    ├── .env             ← Production secrets (never commit)
    └── artisan
```

**Important:** The web server must serve `app-server/public`, not the project root.

### What must run in production

| Component | Required? | Notes |
|-----------|-----------|-------|
| PHP 8.3 + web server | Yes | Nginx, Apache, or IIS |
| MySQL / MariaDB / PostgreSQL | Recommended | SQLite is fine for very small single-server setups |
| Queue worker | **Yes** | Email, verification, and password reset are queued |
| Scheduler (`schedule:run`) | Recommended | Prunes failed jobs and expired tokens |
| Node.js | Build-time only | Run `npm run build` before or during deploy; Node is not needed at runtime |
| `storage:link` | Yes | Serves uploaded photos, logos, and avatars |

Without a queue worker, emails stay in the `jobs` table and are never sent.

---

## Server requirements

### PHP

- **Version:** PHP **8.3** or newer
- **Extensions:**
  - `bcmath`
  - `ctype`
  - `curl`
  - `dom`
  - `fileinfo`
  - `gd` (required for photo/logo/profile image processing)
  - `json`
  - `mbstring`
  - `openssl`
  - `pdo` (+ `pdo_mysql` or `pdo_pgsql`)
  - `tokenizer`
  - `xml`

Verify extensions:

```bash
php -m
php -v
```

### Database

- **Production:** MySQL 8+, MariaDB 10.6+, or PostgreSQL 13+
- **Local / tiny installs:** SQLite (not recommended for multi-user production)

### Build tools (on your machine or CI)

- **Composer 2.x**
- **Node.js 20+** and **npm** (to run `npm run build`)

---

## Environment configuration

Copy `.env.example` to `.env` and configure for production:

```env
APP_NAME="ComTech Repair"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=daily
LOG_LEVEL=warning

# Database — use MySQL/PostgreSQL in production
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=comtech_booking
DB_USERNAME=comtech
DB_PASSWORD=your-secure-password

# Sessions, cache, and queue use the database by default
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Mail — can also be configured in Admin → Site Settings
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"
```

Generate the application key once per environment:

```bash
php artisan key:generate
```

After changing `.env` in production, refresh cached config:

```bash
php artisan config:cache
```

---

## Standard deployment steps

Run these from the `app-server/` directory on every full deploy:

```bash
# 1. Install PHP dependencies (no dev packages in production)
composer install --no-dev --optimize-autoloader

# 2. Install frontend deps and build assets
npm ci
npm run build

# 3. Run database migrations
php artisan migrate --force

# 4. Link public storage for uploads
php artisan storage:link --force

# 5. Cache configuration for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Set permissions (Linux — adjust user/group for your server)
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Optional: seed admin user (first install only)

```bash
php artisan db:seed
```

Default seeded accounts (if using the project seeder): `admin@example.com` / `password` — **change passwords immediately in production**.

---

## Deploy on Ubuntu (VPS)

This section covers Ubuntu 22.04 / 24.04 with **Nginx**, **PHP-FPM**, **MySQL**, **Supervisor** (queue worker), and **cron** (scheduler).

### 1. Install system packages

```bash
sudo apt update
sudo apt install -y nginx mysql-server git unzip curl

# Add PHP 8.3 repository if needed (Ubuntu 24.04 may include 8.3 natively)
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl
```

Install Composer: https://getcomposer.org/download/

Install Node.js 20 LTS (for building assets):

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2. Create database

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE comtech_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'comtech'@'localhost' IDENTIFIED BY 'your-secure-password';
GRANT ALL PRIVILEGES ON comtech_booking.* TO 'comtech'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Deploy application code

```bash
sudo mkdir -p /var/www/comtech
sudo chown -R $USER:www-data /var/www/comtech

git clone <your-repo-url> /var/www/comtech
cd /var/www/comtech/app-server

cp .env.example .env
nano .env   # configure APP_URL, DB_*, etc.

composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan key:generate
php artisan migrate --force
php artisan storage:link --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 4. Configure Nginx

Create `/etc/nginx/sites-available/comtech`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/comtech/app-server/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 20M;
}
```

Enable the site and reload Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/comtech /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 5. HTTPS with Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

Update `APP_URL=https://your-domain.com` and run `php artisan config:cache`.

### 6. Queue worker (Supervisor)

Create `/etc/supervisor/conf.d/comtech-worker.conf`:

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

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start comtech-worker:*
```

**Alternative — systemd** (`/etc/systemd/system/comtech-queue.service`):

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

### 7. Scheduler (cron)

```bash
sudo crontab -u www-data -e
```

Add:

```cron
* * * * * cd /var/www/comtech/app-server && php artisan schedule:run >> /dev/null 2>&1
```

### 8. Apache alternative (Ubuntu)

If you prefer Apache, enable `mod_rewrite` and point the virtual host document root to `app-server/public`. Laravel’s `.htaccess` in `public/` handles routing.

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/comtech/app-server/public

    <Directory /var/www/comtech/app-server/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/comtech-error.log
    CustomLog ${APACHE_LOG_DIR}/comtech-access.log combined
</VirtualHost>
```

---

## Deploy on Windows Server

Windows is supported for production using **IIS** or a local stack such as **Laragon** (better suited to staging or intranet use).

### Option A — IIS (recommended for Windows Server)

#### Prerequisites

1. **IIS** with URL Rewrite Module: https://www.iis.net/downloads/microsoft/url-rewrite
2. **PHP 8.3** (Non Thread Safe) from https://windows.php.net/download/
3. **MySQL** or **MariaDB**
4. **Composer** and **Node.js** (for building assets)

#### PHP setup

1. Extract PHP to e.g. `C:\PHP\8.3`
2. Copy `php.ini-production` to `php.ini`
3. Enable extensions in `php.ini`:
   ```ini
   extension=curl
   extension=fileinfo
   extension=gd
   extension=mbstring
   extension=openssl
   extension=pdo_mysql
   ```
4. Register PHP with IIS (FastCGI) via the IIS Manager or:

   ```powershell
   # Run as Administrator — adjust paths as needed
   cd C:\Windows\System32\inetsrv
   .\appcmd set config /section:system.webServer/fastCgi /+"[fullPath='C:\PHP\8.3\php-cgi.exe']"
   ```

#### Deploy the app

```powershell
cd C:\inetpub\comtech\app-server

copy .env.example .env
# Edit .env with Notepad or your editor

composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan key:generate
php artisan migrate --force
php artisan storage:link --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### IIS site configuration

1. Create a site pointing to `C:\inetpub\comtech\app-server\public`
2. Set the **Application Pool** identity to a user with read/write access to `storage/` and `bootstrap/cache/`
3. Place a `web.config` in `public/` (Laravel typically includes one; if missing, use the standard Laravel IIS rewrite rules from the Laravel documentation)

Grant write permissions:

```powershell
icacls "C:\inetpub\comtech\app-server\storage" /grant "IIS AppPool\YourAppPoolName:(OI)(CI)M"
icacls "C:\inetpub\comtech\app-server\bootstrap\cache" /grant "IIS AppPool\YourAppPoolName:(OI)(CI)M"
```

#### Queue worker — Task Scheduler

Windows has no Supervisor. Create a scheduled task that runs continuously or every minute:

**Continuous worker (recommended):**

1. Open **Task Scheduler** → Create Task
2. **Triggers:** At startup
3. **Actions:** Start a program
   - Program: `C:\PHP\8.3\php.exe`
   - Arguments: `artisan queue:work database --sleep=3 --tries=3`
   - Start in: `C:\inetpub\comtech\app-server`
4. **Settings:** Restart every 1 minute if the task fails

**Cron-style (every minute):**

- Program: `C:\PHP\8.3\php.exe`
- Arguments: `artisan queue:work database --stop-when-empty --max-time=55`
- Trigger: every 1 minute

#### Scheduler — Task Scheduler

Create a second task:

- Trigger: every 1 minute
- Program: `C:\PHP\8.3\php.exe`
- Arguments: `artisan schedule:run`
- Start in: `C:\inetpub\comtech\app-server`

### Option B — Laragon (staging / intranet)

[Laragon](https://laragon.org/) works well for internal Windows deployments:

1. Install Laragon with PHP 8.3 and MySQL
2. Place the project in `C:\laragon\www\comtech\app-server`
3. Create a virtual host pointing to `public/`
4. Run `composer setup` or the standard deployment steps
5. Use Laragon’s terminal to run `php artisan queue:work` as a background process, or use Task Scheduler as above

For production on the public internet, prefer **IIS** or deploy on **Ubuntu**.

### Option C — WSL2 (development only)

Use WSL2 with the [Ubuntu](#deploy-on-ubuntu-vps) instructions. Suitable for local/staging, not typical production hosting.

---

## Deploy on cPanel / shared hosting

Shared hosting (cPanel, Plesk, DirectAdmin) can run this app if the host provides **PHP 8.3**, **SSH access**, **Composer**, and **cron jobs**. Some hosts block long-running queue workers — use the cron-based queue workaround below.

### 1. Upload or clone the project

**With SSH (preferred):**

```bash
cd ~/comtech
git clone <your-repo-url> .
cd app-server
```

**Without SSH:**

1. Run `composer install --no-dev`, `npm ci`, and `npm run build` on your local machine
2. Upload the entire `app-server/` folder via FTP/SFTP or cPanel File Manager
3. Do **not** upload `node_modules/` — only `public/build/` is needed from the frontend build

### 2. Set the document root

In cPanel → **Domains** → **Domains** (or **Subdomains**):

- Set the document root to: `app-server/public`

Example full path:

```
/home/username/comtech/app-server/public
```

Never point the domain at `app-server/` itself — that exposes `.env` and other sensitive files.

### 3. Configure PHP

In cPanel → **Select PHP Version** (or **MultiPHP INI Editor**):

- PHP **8.3**
- Enable: `gd`, `mbstring`, `curl`, `fileinfo`, `pdo_mysql`, `bcmath`, `xml`

### 4. Create MySQL database

In cPanel → **MySQL Databases**:

1. Create database (e.g. `username_comtech`)
2. Create user and assign all privileges
3. Put credentials in `.env`

### 5. Environment and artisan commands

Via **Terminal** in cPanel (or SSH):

```bash
cd ~/comtech/app-server
cp .env.example .env
nano .env

composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan storage:link --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If Composer is unavailable on the host, run `composer install --no-dev` locally and upload the `vendor/` folder.

### 6. File permissions

Ensure these are writable by the web server user:

```
storage/
bootstrap/cache/
```

In cPanel File Manager, set permissions to **755** for directories (or **775** if uploads fail). If problems persist, contact your host about PHP write access.

### 7. Queue worker via cron (shared hosting)

Many shared hosts **do not allow** Supervisor or background daemons. Use cron instead.

In cPanel → **Cron Jobs**, add **every minute**:

```bash
cd /home/username/comtech/app-server && /usr/local/bin/php artisan queue:work database --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

Adjust the PHP path — common locations:

- `/usr/local/bin/php`
- `/usr/bin/php`
- `/opt/cpanel/ea-php83/root/usr/bin/php`

Find yours with:

```bash
which php
php -v
```

Add a second cron job for the scheduler:

```bash
cd /home/username/comtech/app-server && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

### 8. SSL

Use cPanel → **SSL/TLS Status** or **Let's Encrypt** to enable HTTPS, then set:

```env
APP_URL=https://your-domain.com
```

Run `php artisan config:cache` again.

### cPanel limitations

| Feature | Shared hosting | VPS / dedicated |
|---------|----------------|-----------------|
| Long-running queue worker | Use cron workaround | Supervisor / systemd |
| Large file uploads | May need `.htaccess` or host limit increase | Set `client_max_body_size` |
| Redis / Horizon | Usually unavailable | Supported |
| WebSockets (future) | Unlikely | Supported with extra setup |

SMTP can be configured in **Admin → Site Settings → Email** after login; you do not have to edit `.env` mail settings if you prefer the admin UI.

---

## Other platforms

### Laravel Forge / Ploi / RunCloud

These panels automate Ubuntu deployment:

1. Connect your server and repository
2. Set the web root to `app-server/public`
3. Set the project directory to `app-server/`
4. Enable **Deploy Script**:

   ```bash
   cd app-server
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   php artisan migrate --force
   php artisan storage:link --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

5. Enable the **Queue Worker** daemon (Forge/Ploi have one-click queue setup)
6. Enable the **Scheduler** cron entry

### Docker

A typical production Docker setup includes:

- `php-fpm` + `nginx` containers
- `mysql` container
- Separate `queue` container running `php artisan queue:work`
- `scheduler` container or cron sidecar running `schedule:run`

Build assets in a multi-stage Dockerfile:

```dockerfile
FROM node:20-alpine AS frontend
WORKDIR /app
COPY app-server/package*.json ./
RUN npm ci
COPY app-server/ .
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY app-server/composer*.json ./
RUN composer install --no-dev --no-scripts --prefer-dist

FROM php:8.3-fpm
# Install extensions: gd, pdo_mysql, etc.
COPY --from=vendor /app/vendor /var/www/html/vendor
COPY app-server/ /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build
```

Point Nginx at `/var/www/html/public`.

### Railway / Render / Fly.io

1. Set **root directory** to `app-server`
2. **Build command:**
   ```bash
   composer install --no-dev --optimize-autoloader && npm ci && npm run build
   ```
3. **Start command:** `php artisan serve --host=0.0.0.0 --port=$PORT` (or use FrankenPHP/Octane if configured)
4. Add a **worker service** with start command:
   ```bash
   php artisan queue:work database --sleep=3 --tries=3
   ```
5. Attach a managed MySQL database and set `DB_*` variables
6. Run migrations as a release command: `php artisan migrate --force`

### Amazon EC2 / DigitalOcean Droplet / Linode

Follow the [Ubuntu VPS](#deploy-on-ubuntu-vps) guide. These providers give you full control for Nginx, Supervisor, and cron.

### Cloudways / Kinsta (PHP hosting)

- Set application path to `app-server`
- Public web root: `app-server/public`
- Enable cron for scheduler and queue (or use their background worker feature if available)
- Use their MySQL add-on for the database

---

## Post-deployment checklist

- [ ] `APP_ENV=production` and `APP_DEBUG=false`
- [ ] `APP_URL` matches your live HTTPS URL
- [ ] Database migrated (`php artisan migrate --force`)
- [ ] `php artisan storage:link` completed — verify `/storage/...` URLs load
- [ ] Frontend built (`public/build/manifest.json` exists)
- [ ] Queue worker running (Supervisor, systemd, cron, or platform worker)
- [ ] Scheduler cron active (`* * * * * schedule:run`)
- [ ] HTTPS enabled and forced
- [ ] Admin password changed from any default seed value
- [ ] SMTP configured (`.env` or Admin → Site Settings)
- [ ] Test email delivery (Admin → Site Settings → Send test mail)
- [ ] Optional: enable email verification and admin 2FA in Site Settings
- [ ] Privacy policy and terms content added if required
- [ ] File upload test (ticket photo, profile picture, site logo)
- [ ] `storage/` and `bootstrap/cache/` writable
- [ ] Monitor `storage/logs/laravel.log` and failed jobs: `php artisan queue:failed`

---

## Updates and rollbacks

### Deploying an update

```bash
cd /path/to/app-server
git pull origin main

composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers after code changes
sudo supervisorctl restart comtech-worker:*
# or: sudo systemctl restart comtech-queue
```

### Rollback

```bash
git checkout <previous-tag-or-commit>
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force   # only if migrations are reversible; otherwise restore DB backup
php artisan config:cache
sudo supervisorctl restart comtech-worker:*
```

Always take a **database backup** before running migrations in production.

---

## Troubleshooting

### 500 error after deploy

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
tail -f storage/logs/laravel.log
```

Check permissions on `storage/` and `bootstrap/cache/`.

### Photos / logos / avatars not showing

```bash
php artisan storage:link --force
```

Verify the web server can read `public/storage` and write to `storage/app/public`.

### Emails not sending

1. Confirm a queue worker is running
2. Check `jobs` and `failed_jobs` tables
3. Verify SMTP in Admin → Site Settings or `.env`
4. Inspect `storage/logs/laravel.log`

```bash
php artisan queue:failed
php artisan queue:retry all
```

### `Vite manifest not found`

Assets were not built. Run:

```bash
npm ci && npm run build
```

Ensure `public/build/manifest.json` exists on the server.

### CSRF / session errors

- Confirm `APP_URL` uses the correct scheme (`https://`)
- For multiple subdomains, configure `SESSION_DOMAIN` in `.env`
- Ensure `storage/framework/sessions` or the database sessions table is writable

### cPanel: 403 or 404 on all routes except home

Document root must be `app-server/public`, and `mod_rewrite` (Apache) or equivalent must be enabled. Check that `public/.htaccess` exists.

### Image upload fails

Verify the **GD** extension is installed:

```bash
php -m | grep gd
```

---

## Quick reference

| Task | Command |
|------|---------|
| Install locally | `composer setup` |
| Production install | See [Standard deployment steps](#standard-deployment-steps) |
| Run migrations | `php artisan migrate --force` |
| Link uploads | `php artisan storage:link --force` |
| Build frontend | `npm ci && npm run build` |
| Start queue (dev) | `php artisan queue:work` |
| List failed jobs | `php artisan queue:failed` |
| Run tests | `php artisan test` |

For application features and local development, see [README.md](README.md).

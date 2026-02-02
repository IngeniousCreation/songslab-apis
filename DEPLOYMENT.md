# SongsLab API - Deployment Guide

## Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0 or higher
- Redis (for queues and caching)
- Node.js & NPM (for asset compilation)

## Hostinger Deployment Steps

### 1. Server Requirements

**Recommended Hostinger Plan:** VPS or Cloud Hosting

**Required PHP Extensions:**
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- PDO_MySQL
- Tokenizer
- XML
- cURL
- GD
- Redis

### 2. Upload Files

Upload all files to your server (via FTP, SFTP, or Git):
```bash
# If using Git
git clone <your-repo-url> /path/to/your/app
cd /path/to/your/app
```

### 3. Install Dependencies

```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node dependencies (if needed)
npm install
npm run build
```

### 4. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file with your settings
nano .env
```

**Important .env Settings:**
```env
APP_NAME="SongsLab"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mail.spacemail.com
MAIL_PORT=465
MAIL_USERNAME=noreply@stabene.net
MAIL_PASSWORD=your_mail_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@stabene.net
MAIL_FROM_NAME="${APP_NAME}"

FRONTEND_URL=https://your-frontend-domain.com

FILESYSTEM_DISK=public
```

### 5. Set Permissions

```bash
# Storage and cache permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Create storage symlink
php artisan storage:link
```

### 6. Database Setup

```bash
# Run migrations
php artisan migrate --force

# Seed feedback topics
php artisan db:seed --class=FeedbackTopicSeeder
```

### 7. Optimize for Production

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### 8. Configure Web Server

**For Apache (.htaccess already included):**
- Point document root to `/public` directory
- Enable `mod_rewrite`

**For Nginx:**
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/your/app/public;

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
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 9. Setup Queue Worker

Add to crontab:
```bash
* * * * * cd /path/to/your/app && php artisan schedule:run >> /dev/null 2>&1
```

Setup supervisor for queue worker:
```ini
[program:songslab-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/app/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/worker.log
```

## Post-Deployment Checklist

- [ ] `.env` file configured correctly
- [ ] Database migrations run successfully
- [ ] Storage symlink created
- [ ] File upload permissions set (775)
- [ ] Queue worker running
- [ ] Cron job configured
- [ ] SSL certificate installed
- [ ] CORS configured for frontend domain
- [ ] Email sending tested
- [ ] File uploads tested

## Troubleshooting

**500 Error:**
- Check storage permissions
- Check `.env` file exists
- Run `php artisan config:clear`

**File Upload Issues:**
- Check storage permissions (775)
- Check `storage/app/public` exists
- Verify storage symlink: `php artisan storage:link`

**Queue Not Processing:**
- Check supervisor is running
- Check Redis connection
- View logs: `tail -f storage/logs/laravel.log`

## Security Notes

- Never commit `.env` file
- Set `APP_DEBUG=false` in production
- Use strong `APP_KEY`
- Keep dependencies updated
- Use HTTPS only
- Restrict database access


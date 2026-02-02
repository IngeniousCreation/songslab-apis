# SongsLab API - Hostinger VPS Deployment Guide

## Step 1: Access Your Hostinger VPS

1. **Login to Hostinger VPS Panel**
   - Go to Hostinger control panel
   - Navigate to your VPS
   - Note your VPS IP address

2. **SSH into your VPS**
   ```bash
   ssh root@your-vps-ip
   # Enter your password when prompted
   ```

## Step 2: Install Required Software

```bash
# Update system packages
apt update && apt upgrade -y

# Install Nginx
apt install nginx -y

# Install PHP 8.2 and required extensions
apt install software-properties-common -y
add-apt-repository ppa:ondrej/php -y
apt update
apt install php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-zip \
    php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath \
    php8.2-redis php8.2-intl -y

# Install MySQL
apt install mysql-server -y

# Install Redis
apt install redis-server -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Install Git
apt install git -y

# Install Supervisor (for queue workers)
apt install supervisor -y
```

## Step 3: Configure MySQL Database

```bash
# Secure MySQL installation
mysql_secure_installation

# Login to MySQL
mysql -u root -p

# Create database and user
CREATE DATABASE songslab;
CREATE USER 'songslab_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON songslab.* TO 'songslab_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## Step 4: Setup Application Directory

```bash
# Create directory for the application
mkdir -p /var/www/songslab-api
cd /var/www/songslab-api

# Clone your repository (or upload files via SFTP)
git clone https://github.com/your-username/songslab-apis.git .

# Or if you haven't pushed to GitHub yet, use SFTP to upload files
```

## Step 5: Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Edit environment file
nano .env
```

**Update these values in .env:**

```env
APP_NAME="SongsLab"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=songslab
DB_USERNAME=songslab_user
DB_PASSWORD=your_strong_password

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

FRONTEND_URL=https://yourdomain.com

FILESYSTEM_DISK=public
```

## Step 6: Install Dependencies and Setup

```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Generate application key
php artisan key:generate

# Create storage symlink
php artisan storage:link

# Set correct permissions
chown -R www-data:www-data /var/www/songslab-api
chmod -R 775 /var/www/songslab-api/storage
chmod -R 775 /var/www/songslab-api/bootstrap/cache

# Run migrations
php artisan migrate --force

# Seed feedback topics
php artisan db:seed --class=FeedbackTopicsSeeder --force

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 7: Configure Nginx

```bash
# Create Nginx configuration
nano /etc/nginx/sites-available/songslab-api
```

**Paste this configuration:**

```nginx
server {
    listen 80;
    server_name api.yourdomain.com;
    root /var/www/songslab-api/public;

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

**Enable the site:**

```bash
# Create symbolic link
ln -s /etc/nginx/sites-available/songslab-api /etc/nginx/sites-enabled/

# Test Nginx configuration
nginx -t

# Restart Nginx
systemctl restart nginx
```

## Step 8: Setup Queue Worker with Supervisor

```bash
# Create supervisor configuration
nano /etc/supervisor/conf.d/songslab-worker.conf
```

**Paste this configuration:**

```ini
[program:songslab-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/songslab-api/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/songslab-api/storage/logs/worker.log
stopwaitsecs=3600
```

**Start the worker:**

```bash
# Reload supervisor
supervisorctl reread
supervisorctl update
supervisorctl start songslab-worker:*

# Check status
supervisorctl status
```

## Step 9: Setup Cron Job for Scheduler

```bash
# Edit crontab for www-data user
crontab -e -u www-data
```

**Add this line:**

```
* * * * * cd /var/www/songslab-api && php artisan schedule:run >> /dev/null 2>&1
```

## Step 10: Install SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
apt install certbot python3-certbot-nginx -y

# Get SSL certificate
certbot --nginx -d api.yourdomain.com

# Follow the prompts to complete SSL setup
# Certbot will automatically update your Nginx configuration
```

## Step 11: Configure Firewall

```bash
# Allow SSH, HTTP, and HTTPS
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw enable

# Check firewall status
ufw status
```

## Step 12: Test Your Deployment

```bash
# Test API endpoint
curl http://api.yourdomain.com/api/health

# Check logs if there are issues
tail -f /var/www/songslab-api/storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

## Post-Deployment Checklist

- [ ] VPS accessible via SSH
- [ ] All required software installed (Nginx, PHP 8.2, MySQL, Redis)
- [ ] Database created and configured
- [ ] Application files uploaded/cloned
- [ ] `.env` file configured with production settings
- [ ] Dependencies installed (`composer install`)
- [ ] Database migrations run successfully
- [ ] Storage symlink created
- [ ] File permissions set correctly (775 for storage)
- [ ] Nginx configured and running
- [ ] SSL certificate installed
- [ ] Queue worker running via Supervisor
- [ ] Cron job configured for scheduler
- [ ] Firewall configured
- [ ] API endpoints tested and working

## Useful Commands

```bash
# Restart services
systemctl restart nginx
systemctl restart php8.2-fpm
systemctl restart redis-server
systemctl restart mysql

# Check service status
systemctl status nginx
systemctl status php8.2-fpm
systemctl status redis-server
systemctl status mysql

# View logs
tail -f /var/www/songslab-api/storage/logs/laravel.log
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log

# Clear Laravel cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Restart queue workers
supervisorctl restart songslab-worker:*

# Update application
cd /var/www/songslab-api
git pull
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
supervisorctl restart songslab-worker:*
```

## Troubleshooting

### 502 Bad Gateway

- Check PHP-FPM is running: `systemctl status php8.2-fpm`
- Check PHP-FPM socket path in Nginx config
- Restart PHP-FPM: `systemctl restart php8.2-fpm`

### 500 Internal Server Error

- Check storage permissions: `chmod -R 775 storage bootstrap/cache`
- Check `.env` file exists and is readable
- Clear config cache: `php artisan config:clear`
- Check logs: `tail -f storage/logs/laravel.log`

### Database Connection Error

- Verify MySQL is running: `systemctl status mysql`
- Check database credentials in `.env`
- Test connection: `mysql -u songslab_user -p songslab`

### File Upload Not Working

- Check storage permissions: `chmod -R 775 storage`
- Verify storage symlink: `php artisan storage:link`
- Check `storage/app/public` directory exists

### Queue Not Processing

- Check supervisor status: `supervisorctl status`
- Check Redis is running: `systemctl status redis-server`
- View worker logs: `tail -f storage/logs/worker.log`

## Security Best Practices

1. **Never expose `.env` file** - Already protected by Nginx config
2. **Use strong passwords** for database and other services
3. **Keep software updated**: `apt update && apt upgrade`
4. **Use HTTPS only** - Redirect HTTP to HTTPS
5. **Disable root SSH login** - Use sudo user instead
6. **Enable firewall** - Only allow necessary ports
7. **Regular backups** - Database and uploaded files
8. **Monitor logs** - Check for suspicious activity

## Domain Setup

1. **Point your domain to VPS IP:**
   - Go to your domain registrar (e.g., Namecheap, GoDaddy)
   - Add an A record: `api.yourdomain.com` → `your-vps-ip`
   - Wait for DNS propagation (5-30 minutes)

2. **Update CORS settings** in your Laravel API to allow your frontend domain

## Next Steps

After deployment, update your Next.js frontend to use the new API URL:

- Update `NEXT_PUBLIC_API_URL` to `https://api.yourdomain.com`
- Test all API endpoints from frontend
- Verify file uploads work correctly
- Test email verification flow

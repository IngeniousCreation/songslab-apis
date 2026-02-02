# Quick Deployment Guide - Hostinger VPS

## Prerequisites

1. **Hostinger VPS** with Ubuntu 20.04 or 22.04
2. **Domain name** pointed to your VPS IP (e.g., api.yourdomain.com)
3. **SSH access** to your VPS
4. **Git repository** with your code (or files ready to upload)

## Option 1: Automated Deployment (Recommended)

### Step 1: Upload Files to VPS

```bash
# On your local machine, push to GitHub
cd /home/wahaj/workspace/songslab/songslab-apis
git add -A
git commit -m "Ready for deployment"
git push origin main
```

### Step 2: SSH into VPS and Clone Repository

```bash
# SSH into your VPS
ssh root@your-vps-ip

# Clone repository
mkdir -p /var/www
cd /var/www
git clone https://github.com/your-username/songslab-apis.git songslab-api
cd songslab-api
```

### Step 3: Run Deployment Script

```bash
# Make script executable and run
chmod +x deploy.sh
sudo ./deploy.sh
```

The script will:
- ✅ Install Nginx, PHP 8.2, MySQL, Redis
- ✅ Install Composer
- ✅ Create database and user
- ✅ Install dependencies
- ✅ Configure environment
- ✅ Run migrations
- ✅ Setup Nginx
- ✅ Configure queue workers

### Step 4: Install SSL Certificate

```bash
# Install Certbot
apt install certbot python3-certbot-nginx -y

# Get SSL certificate
certbot --nginx -d api.yourdomain.com
```

### Step 5: Setup Cron Job

```bash
# Edit crontab
crontab -e -u www-data

# Add this line:
* * * * * cd /var/www/songslab-api && php artisan schedule:run >> /dev/null 2>&1
```

### Step 6: Test Your API

```bash
curl https://api.yourdomain.com/api/health
```

## Option 2: Manual Deployment

Follow the detailed guide in `HOSTINGER_VPS_DEPLOYMENT.md`

## Important Configuration

### Update .env File

After deployment, edit `/var/www/songslab-api/.env`:

```bash
nano /var/www/songslab-api/.env
```

**Critical settings:**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

# Your actual mail password
MAIL_PASSWORD=your_actual_password

# Your frontend URL
FRONTEND_URL=https://yourdomain.com
```

After editing:
```bash
cd /var/www/songslab-api
php artisan config:cache
```

## Post-Deployment Tasks

### 1. Test All Endpoints

```bash
# Health check
curl https://api.yourdomain.com/api/health

# Register test user
curl -X POST https://api.yourdomain.com/api/register \
  -H "Content-Type: application/json" \
  -d '{"username":"testuser","email":"test@example.com","password":"password123","password_confirmation":"password123","first_name":"Test","last_name":"User"}'
```

### 2. Monitor Logs

```bash
# Laravel logs
tail -f /var/www/songslab-api/storage/logs/laravel.log

# Nginx error logs
tail -f /var/log/nginx/error.log

# Queue worker logs
tail -f /var/www/songslab-api/storage/logs/worker.log
```

### 3. Check Services Status

```bash
systemctl status nginx
systemctl status php8.2-fpm
systemctl status mysql
systemctl status redis-server
supervisorctl status
```

## Updating Your Application

When you make changes and need to deploy updates:

```bash
# SSH into VPS
ssh root@your-vps-ip

# Navigate to app directory
cd /var/www/songslab-api

# Pull latest changes
git pull origin main

# Update dependencies
composer install --optimize-autoloader --no-dev

# Run migrations (if any new ones)
php artisan migrate --force

# Clear and rebuild cache
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
supervisorctl restart songslab-worker:*

# Restart PHP-FPM
systemctl restart php8.2-fpm
```

## Common Issues & Solutions

### Issue: 502 Bad Gateway
```bash
# Check PHP-FPM status
systemctl status php8.2-fpm

# Restart PHP-FPM
systemctl restart php8.2-fpm
```

### Issue: 500 Internal Server Error
```bash
# Check permissions
cd /var/www/songslab-api
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Clear cache
php artisan config:clear
php artisan cache:clear
```

### Issue: Database Connection Failed
```bash
# Test database connection
mysql -u songslab_user -p songslab

# Check .env database credentials
nano /var/www/songslab-api/.env
```

### Issue: File Uploads Not Working
```bash
# Recreate storage symlink
cd /var/www/songslab-api
php artisan storage:link

# Check permissions
chmod -R 775 storage
chown -R www-data:www-data storage
```

## Security Checklist

- [ ] SSL certificate installed (HTTPS)
- [ ] Firewall enabled (ufw)
- [ ] Strong database password
- [ ] APP_DEBUG=false in production
- [ ] .env file not publicly accessible
- [ ] Regular backups configured
- [ ] Only necessary ports open (22, 80, 443)

## Backup Strategy

### Database Backup
```bash
# Create backup
mysqldump -u songslab_user -p songslab > backup_$(date +%Y%m%d).sql

# Restore backup
mysql -u songslab_user -p songslab < backup_20260202.sql
```

### Files Backup
```bash
# Backup uploaded files
tar -czf storage_backup_$(date +%Y%m%d).tar.gz /var/www/songslab-api/storage/app/public
```

## Support

For detailed instructions, see:
- `HOSTINGER_VPS_DEPLOYMENT.md` - Complete step-by-step guide
- `DEPLOYMENT.md` - General deployment information
- Laravel logs: `/var/www/songslab-api/storage/logs/laravel.log`


#!/bin/bash

# SongsLab API Deployment Script for Hostinger VPS
# This script automates the deployment process

set -e

echo "🚀 SongsLab API Deployment Script"
echo "=================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root (use sudo)${NC}"
    exit 1
fi

echo -e "${YELLOW}Step 1: Installing system packages...${NC}"
apt update && apt upgrade -y
apt install software-properties-common -y
add-apt-repository ppa:ondrej/php -y
apt update

echo -e "${YELLOW}Step 2: Installing Nginx, PHP 8.2, MySQL, Redis...${NC}"
apt install nginx mysql-server redis-server git supervisor -y
apt install php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-zip \
    php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath \
    php8.2-redis php8.2-intl -y

echo -e "${YELLOW}Step 3: Installing Composer...${NC}"
if [ ! -f /usr/local/bin/composer ]; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
fi

echo -e "${GREEN}✓ All software installed successfully${NC}"
echo ""

echo -e "${YELLOW}Step 4: Database Setup${NC}"
read -p "Enter database name [songslab]: " DB_NAME
DB_NAME=${DB_NAME:-songslab}

read -p "Enter database username [songslab_user]: " DB_USER
DB_USER=${DB_USER:-songslab_user}

read -sp "Enter database password: " DB_PASS
echo ""

mysql -u root <<MYSQL_SCRIPT
CREATE DATABASE IF NOT EXISTS ${DB_NAME};
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
MYSQL_SCRIPT

echo -e "${GREEN}✓ Database created successfully${NC}"
echo ""

echo -e "${YELLOW}Step 5: Application Setup${NC}"
read -p "Enter application directory [/var/www/songslab-api]: " APP_DIR
APP_DIR=${APP_DIR:-/var/www/songslab-api}

if [ ! -d "$APP_DIR" ]; then
    echo -e "${RED}Directory $APP_DIR does not exist. Please upload your files first.${NC}"
    exit 1
fi

cd $APP_DIR

echo -e "${YELLOW}Step 6: Installing dependencies...${NC}"
composer install --optimize-autoloader --no-dev

echo -e "${YELLOW}Step 7: Environment configuration...${NC}"
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi

# Update .env with database credentials
sed -i "s/DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/" .env

echo -e "${YELLOW}Step 8: Setting permissions...${NC}"
chown -R www-data:www-data $APP_DIR
chmod -R 775 $APP_DIR/storage
chmod -R 775 $APP_DIR/bootstrap/cache

echo -e "${YELLOW}Step 9: Running migrations...${NC}"
php artisan storage:link
php artisan migrate --force
php artisan db:seed --class=FeedbackTopicsSeeder --force

echo -e "${YELLOW}Step 10: Optimizing for production...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo -e "${GREEN}✓ Application setup complete${NC}"
echo ""

echo -e "${YELLOW}Step 11: Configuring Nginx...${NC}"
read -p "Enter your domain (e.g., api.yourdomain.com): " DOMAIN

cat > /etc/nginx/sites-available/songslab-api <<NGINX_CONFIG
server {
    listen 80;
    server_name ${DOMAIN};
    root ${APP_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX_CONFIG

ln -sf /etc/nginx/sites-available/songslab-api /etc/nginx/sites-enabled/
nginx -t && systemctl restart nginx

echo -e "${GREEN}✓ Nginx configured successfully${NC}"
echo ""

echo -e "${YELLOW}Step 12: Setting up queue worker...${NC}"
cat > /etc/supervisor/conf.d/songslab-worker.conf <<SUPERVISOR_CONFIG
[program:songslab-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stopwaitsecs=3600
SUPERVISOR_CONFIG

supervisorctl reread
supervisorctl update
supervisorctl start songslab-worker:*

echo -e "${GREEN}✓ Queue worker configured${NC}"
echo ""

echo -e "${GREEN}=================================="
echo -e "✓ Deployment Complete!"
echo -e "==================================${NC}"
echo ""
echo "Next steps:"
echo "1. Point your domain ${DOMAIN} to this server's IP"
echo "2. Install SSL: certbot --nginx -d ${DOMAIN}"
echo "3. Setup cron job: crontab -e -u www-data"
echo "   Add: * * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
echo "4. Test your API: curl http://${DOMAIN}/api/health"
echo ""


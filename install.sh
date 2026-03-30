#!/bin/bash
set -e

# ─────────────────────────────────────────────────────────────────────
# Aero Installer - Cloud Server Panel by Cloudito
# Voor Ubuntu 22.04 / 24.04
# ─────────────────────────────────────────────────────────────────────

CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${CYAN}"
echo "  ╔═══════════════════════════════════════╗"
echo "  ║     Aero Installer by Cloudito        ║"
echo "  ║     Cloud Server Management Panel     ║"
echo "  ╚═══════════════════════════════════════╝"
echo -e "${NC}"

# Check root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Dit script moet als root worden uitgevoerd.${NC}"
    echo "Gebruik: sudo bash install.sh"
    exit 1
fi

# Vraag om gegevens
echo -e "${YELLOW}=== Configuratie ===${NC}"
echo ""

read -p "Domeinnaam voor Aero (bijv. panel.cloudito.nl): " DOMAIN
read -p "MySQL database naam [aero]: " DB_NAME
DB_NAME=${DB_NAME:-aero}
read -p "MySQL database gebruiker [aero]: " DB_USER
DB_USER=${DB_USER:-aero}
read -sp "MySQL database wachtwoord: " DB_PASS
echo ""
read -p "VirtFusion URL (bijv. https://vf.cloudito.nl): " VF_URL
read -sp "VirtFusion API Token: " VF_TOKEN
echo ""
read -p "VirtFusion Hypervisor Group ID [1]: " VF_HV_GROUP
VF_HV_GROUP=${VF_HV_GROUP:-1}
read -sp "Mollie API Key (live_xxx of test_xxx): " MOLLIE_KEY
echo ""
read -p "Support e-mail [support@cloudito.nl]: " SUPPORT_EMAIL
SUPPORT_EMAIL=${SUPPORT_EMAIL:-support@cloudito.nl}

echo ""
echo -e "${GREEN}Installatie wordt gestart...${NC}"
echo ""

# ── 1. System updates ────────────────────────────────────────────────
echo -e "${CYAN}[1/8] Systeem updaten...${NC}"
apt update && apt upgrade -y

# ── 2. Installeer vereiste packages ──────────────────────────────────
echo -e "${CYAN}[2/8] PHP 8.3, Nginx, MySQL, Composer installeren...${NC}"
apt install -y software-properties-common curl git unzip
add-apt-repository -y ppa:ondrej/php
apt update

apt install -y \
    php8.3 \
    php8.3-fpm \
    php8.3-cli \
    php8.3-mysql \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-gd \
    php8.3-intl \
    php8.3-readline \
    nginx \
    mysql-server \
    certbot \
    python3-certbot-nginx

# Installeer Composer
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# ── 3. MySQL database aanmaken ───────────────────────────────────────
echo -e "${CYAN}[3/8] Database aanmaken...${NC}"
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# ── 4. Aero bestanden instellen ──────────────────────────────────────
echo -e "${CYAN}[4/8] Aero installeren in /var/www/aero...${NC}"
INSTALL_DIR="/var/www/aero"

if [ -d "$INSTALL_DIR" ]; then
    echo -e "${YELLOW}Map /var/www/aero bestaat al. Backup wordt gemaakt...${NC}"
    mv "$INSTALL_DIR" "${INSTALL_DIR}.backup.$(date +%s)"
fi

# Kopieer bestanden (als je het script vanuit de Aero map draait)
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
mkdir -p "$INSTALL_DIR"
cp -r "$SCRIPT_DIR"/* "$INSTALL_DIR/" 2>/dev/null || true
cp -r "$SCRIPT_DIR"/.env.example "$INSTALL_DIR/" 2>/dev/null || true
cp -r "$SCRIPT_DIR"/.gitignore "$INSTALL_DIR/" 2>/dev/null || true

cd "$INSTALL_DIR"

# ── 5. Laravel configuratie ──────────────────────────────────────────
echo -e "${CYAN}[5/8] Laravel configureren...${NC}"
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader --no-interaction

cp .env.example .env

# .env invullen
sed -i "s|APP_ENV=local|APP_ENV=production|g" .env
sed -i "s|APP_DEBUG=true|APP_DEBUG=false|g" .env
sed -i "s|APP_URL=http://localhost:8000|APP_URL=https://${DOMAIN}|g" .env
sed -i "s|DB_DATABASE=aero|DB_DATABASE=${DB_NAME}|g" .env
sed -i "s|DB_USERNAME=root|DB_USERNAME=${DB_USER}|g" .env
sed -i "s|DB_PASSWORD=|DB_PASSWORD=${DB_PASS}|g" .env
sed -i "s|VIRTFUSION_URL=https://your-virtfusion-instance.com|VIRTFUSION_URL=${VF_URL}|g" .env
sed -i "s|VIRTFUSION_API_TOKEN=your-virtfusion-api-token|VIRTFUSION_API_TOKEN=${VF_TOKEN}|g" .env
sed -i "s|VIRTFUSION_HYPERVISOR_GROUP_ID=1|VIRTFUSION_HYPERVISOR_GROUP_ID=${VF_HV_GROUP}|g" .env
sed -i "s|MOLLIE_KEY=test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx|MOLLIE_KEY=${MOLLIE_KEY}|g" .env
sed -i "s|MOLLIE_WEBHOOK_URL=https://your-domain.com/webhooks/mollie|MOLLIE_WEBHOOK_URL=https://${DOMAIN}/webhooks/mollie|g" .env
sed -i "s|CLOUDITO_SUPPORT_EMAIL=support@cloudito.nl|CLOUDITO_SUPPORT_EMAIL=${SUPPORT_EMAIL}|g" .env

php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissies
chown -R www-data:www-data "$INSTALL_DIR"
chmod -R 755 "$INSTALL_DIR"
chmod -R 775 "$INSTALL_DIR/storage" "$INSTALL_DIR/bootstrap/cache"

# ── 6. Nginx configuratie ────────────────────────────────────────────
echo -e "${CYAN}[6/8] Nginx configureren...${NC}"
cat > /etc/nginx/sites-available/aero <<NGINX
server {
    listen 80;
    server_name ${DOMAIN};
    root ${INSTALL_DIR}/public;

    index index.php;
    charset utf-8;

    # Max upload size
    client_max_body_size 64M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

ln -sf /etc/nginx/sites-available/aero /etc/nginx/sites-enabled/aero
rm -f /etc/nginx/sites-enabled/default

nginx -t && systemctl reload nginx

# ── 7. SSL certificaat ───────────────────────────────────────────────
echo -e "${CYAN}[7/8] SSL certificaat aanvragen...${NC}"
certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "$SUPPORT_EMAIL" || {
    echo -e "${YELLOW}SSL kon niet automatisch worden ingesteld. Draai later: certbot --nginx -d ${DOMAIN}${NC}"
}

# ── 8. Cronjob voor Laravel scheduler ────────────────────────────────
echo -e "${CYAN}[8/8] Cronjob instellen...${NC}"
(crontab -l 2>/dev/null; echo "* * * * * cd ${INSTALL_DIR} && php artisan schedule:run >> /dev/null 2>&1") | sort -u | crontab -

# ── Klaar! ────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  Aero is succesvol geinstalleerd!${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "  Panel URL:    ${CYAN}https://${DOMAIN}${NC}"
echo -e "  Admin login:  ${CYAN}admin@cloudito.nl${NC}"
echo -e "  Wachtwoord:   ${CYAN}password${NC}"
echo ""
echo -e "  ${RED}BELANGRIJK: Wijzig het admin wachtwoord direct na inloggen!${NC}"
echo ""
echo -e "  Bestanden:    ${INSTALL_DIR}"
echo -e "  Nginx config: /etc/nginx/sites-available/aero"
echo -e "  Logs:         ${INSTALL_DIR}/storage/logs/laravel.log"
echo ""
echo -e "  Mollie webhook: https://${DOMAIN}/webhooks/mollie"
echo -e "  (Stel deze URL in bij Mollie Dashboard > Developers > Webhooks)"
echo ""

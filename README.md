# Aero - Cloud Server Panel by Cloudito

Aero is een modern klantenpaneel voor cloud server management, gebouwd op Laravel met volledige VirtFusion en Mollie integratie.

## Features

- **Server Management** - Aanmaken, upgraden, downgraden, herstarten, verwijderen
- **VirtFusion Integratie** - Alle 75 API endpoints geimplementeerd
- **Mollie Betalingen** - Eenmalige betalingen, abonnementen, automatische facturering
- **Klantenpaneel** - Modern dashboard met server overzicht, power controls, VNC console
- **Admin Panel** - Gebruikers, servers, pakketten beheren + VirtFusion sync
- **Facturatie** - Automatische factuur generatie met 21% BTW

## Vereisten

- Ubuntu 22.04 of 24.04 VPS
- PHP 8.2+ (8.3 aanbevolen)
- MySQL 8.0+
- Nginx
- Composer
- VirtFusion instance met API toegang
- Mollie account
- Domeinnaam (voor SSL)

---

## Installatie op Ubuntu VPS

### Optie 1: Automatisch (aanbevolen)

Upload de bestanden naar je VPS en draai het installatiescript:

```bash
# 1. Op je Windows PC: push naar GitHub
cd C:\Users\vdvpc\Documents\GitHub\Aero
git init
git add .
git commit -m "Initial commit - Aero panel"
git remote add origin https://github.com/jouw-account/Aero.git
git push -u origin main

# 2. Op je Ubuntu VPS: clone en installeer
ssh root@jouw-vps-ip
git clone https://github.com/jouw-account/Aero.git /tmp/aero-install
cd /tmp/aero-install
chmod +x install.sh
sudo bash install.sh
```

Het script vraagt om je gegevens en installeert alles automatisch:
- PHP 8.3, Nginx, MySQL, Composer
- Database aanmaken
- Laravel configureren
- Nginx + SSL (Let's Encrypt)
- Cronjob

### Optie 2: Handmatig

#### Stap 1: Server voorbereiden

```bash
sudo apt update && sudo apt upgrade -y

# PHP 8.3 installeren
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-mysql \
    php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath \
    php8.3-gd php8.3-intl php8.3-readline

# Nginx en MySQL
sudo apt install -y nginx mysql-server git unzip curl

# Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

#### Stap 2: Database aanmaken

```bash
sudo mysql
```

```sql
CREATE DATABASE aero CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'aero'@'localhost' IDENTIFIED BY 'JOUW_WACHTWOORD_HIER';
GRANT ALL PRIVILEGES ON aero.* TO 'aero'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### Stap 3: Aero bestanden uploaden

```bash
# Via Git (aanbevolen)
cd /var/www
sudo git clone https://github.com/jouw-account/Aero.git aero

# OF via SFTP: upload alle bestanden naar /var/www/aero/
```

#### Stap 4: Laravel installeren

```bash
cd /var/www/aero

# Dependencies installeren
sudo composer install --no-dev --optimize-autoloader

# Configuratie
sudo cp .env.example .env
sudo nano .env
```

Pas deze waarden aan in `.env`:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://panel.jouwdomein.nl

DB_DATABASE=aero
DB_USERNAME=aero
DB_PASSWORD=JOUW_WACHTWOORD_HIER

VIRTFUSION_URL=https://jouw-virtfusion.com
VIRTFUSION_API_TOKEN=jouw-api-token-hier
VIRTFUSION_HYPERVISOR_GROUP_ID=1

MOLLIE_KEY=live_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
MOLLIE_WEBHOOK_URL=https://panel.jouwdomein.nl/webhooks/mollie
```

```bash
# App key genereren
sudo php artisan key:generate

# Database tabellen aanmaken
sudo php artisan migrate --force

# Demo data laden (admin account + voorbeeldpakketten)
sudo php artisan db:seed --force

# Cache optimalisatie
sudo php artisan config:cache
sudo php artisan route:cache
sudo php artisan view:cache

# Permissies instellen
sudo chown -R www-data:www-data /var/www/aero
sudo chmod -R 755 /var/www/aero
sudo chmod -R 775 /var/www/aero/storage /var/www/aero/bootstrap/cache
```

#### Stap 5: Nginx configureren

```bash
sudo nano /etc/nginx/sites-available/aero
```

Plak deze configuratie:

```nginx
server {
    listen 80;
    server_name panel.jouwdomein.nl;
    root /var/www/aero/public;

    index index.php;
    charset utf-8;
    client_max_body_size 64M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Activeren
sudo ln -sf /etc/nginx/sites-available/aero /etc/nginx/sites-enabled/aero
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

#### Stap 6: SSL certificaat (gratis via Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d panel.jouwdomein.nl
```

#### Stap 7: Cronjob instellen

```bash
sudo crontab -e
```

Voeg deze regel toe:

```
* * * * * cd /var/www/aero && php artisan schedule:run >> /dev/null 2>&1
```

---

## Na installatie

### Eerste login

- **URL:** `https://panel.jouwdomein.nl`
- **Email:** `admin@cloudito.nl`
- **Wachtwoord:** `password`

**Wijzig dit wachtwoord direct via Profiel!**

### Pakketten instellen

1. Ga naar **Admin > Pakketten**
2. Klik op **Sync VirtFusion** om pakketten automatisch op te halen
3. Stel de **prijzen** in per pakket (worden niet automatisch overgenomen)
4. Of maak handmatig pakketten aan met het juiste VirtFusion Package ID

### Mollie instellen

1. Log in op [mollie.com](https://www.mollie.com)
2. Ga naar **Developers > API Keys**
3. Kopieer je **Live API key** naar `.env` (`MOLLIE_KEY`)
4. De webhook URL is automatisch: `https://jouwdomein.nl/webhooks/mollie`

### DNS instellen

Maak een **A-record** aan voor je paneel domein:

```
panel.cloudito.nl  →  A  →  123.45.67.89  (je VPS IP)
```

---

## Handige commando's

```bash
# Logs bekijken
tail -f /var/www/aero/storage/logs/laravel.log

# Cache legen na .env wijzigingen
cd /var/www/aero
php artisan config:clear
php artisan config:cache

# Database opnieuw opzetten (WIST ALLE DATA)
php artisan migrate:fresh --seed

# VirtFusion connectie testen
php artisan tinker
>>> app(App\Services\VirtFusionService::class)->testConnection()

# Onderhoudsmodus
php artisan down    # Panel offline
php artisan up      # Panel online
```

## Architectuur

```
app/
├── Http/Controllers/
│   ├── Auth/               # Login & Registratie
│   ├── Admin/              # Admin paneel
│   ├── ServerController    # Server CRUD & power management
│   ├── OrderController     # Bestellingen overzicht
│   ├── InvoiceController   # Facturen
│   ├── WebhookController   # Mollie webhooks + auto-provisioning
│   └── ProfileController   # Profiel beheer
├── Models/                 # Eloquent modellen (6 stuks)
├── Services/
│   ├── VirtFusionService   # 75 VirtFusion API endpoints
│   └── MollieService       # Mollie payments & subscriptions
└── Http/Middleware/
    └── AdminMiddleware     # Admin access control
```

## Licentie

Eigendom van Cloudito. Alle rechten voorbehouden.

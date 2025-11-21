# Uniliga-LoL-Übersicht
## Webseite
Meine Webseite, auf der dieses Projekt läuft, findet ihr hier:  
https://uniliga.silence.lol

## Unterstützung
Wenn ihr mich unterstützen wollt, könnt ihr das gerne hier tun:  
[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/O5O8UYOG4)

## Eigene Instanz aufsetzen

### Voraussetzungen:

- Apache Webserver
- PHP >8.3
- MariaDB Server

Alternativ: Docker-Setup, siehe unten

### Festlegen von Daten:
.env aus .env.template erstellen und _Datenbank-Informationen_, _API-Keys_, _User-Agent_ und _Admin-Passwort_ setzen.
- (User-Agent ist notwendig, da OPL für Anfragen einen sinnvollen User-Agent benötigt. Bspw. in meinem Fall: ```'UniligaLoL-Übersicht/1.0 (+https://uniliga.silence.lol)'```)
- (Admin-Passwort ist das Passwort, das benötigt wird, um sich auf der Seite in den Admin-Bereich (/admin) einzuloggen)
```
DB_HOST=
DB_DATABASE=
DB_USER=
DB_PASS=
DB_PORT=
RIOT_API_KEY=
OPL_BEARER_TOKEN=
USER_AGENT=
ADMIN_PASS=
```

### Datenbank erstellen:
Folgendes Skript erstellt die benötigte Datenbank in der MariaDB-Instanz die in .env angegeben ist.
* ```php bin/database/create_database.php```

### Cron-Jobs aktivieren
Im Verzeichnis `cron` liegen cron-jobs. Diese sollten manuell als crontab angelegt werden, oder in cron.d kopiert werden.  
(Installationsskript in bin/setup/install-cronjobs.sh, automatisches Setup auch im Beispiel Docker-Setup unten)

## Wartungsaufwand:

### Turnier-Updates:
1. Manuell (Buttons im Backend)
   * Updates von OPL API (/admin)
   * Updates von Riot API (/admin/rgapi)
   * Updates der LoL-Bilder nach Patch (/admin/ddragon)
2. Automatisch (Cron-Jobs)
   * siehe "Cron-Jobs aktivieren" oben

### LoL-Game-Updates:
Updates der verwendeten Bilder für Champions, Items, etc. können wie oben beschrieben unter _/admin/ddragon_ aktualisiert werden, automatische Aktualisierung über Cron-Jobs ist geplant

---
<details>

<summary><b><font size="+2">Beispiel Docker-Setup</font></b></summary>

_(Folgende Dateien sollen noch in ein eigenes Repository ausgelagert werden)_

_Dockerfile_ mit benötigten Einstellungen
```
FROM php:8.3-apache

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli \
    && docker-php-ext-enable pdo_mysql mysqli

RUN apt-get update && apt-get install -y \
        libpng-dev \
        libjpeg62-turbo-dev \
        libwebp-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd

# Activate Apache modules
RUN a2enmod rewrite \
    && a2enmod http2 \
    && a2enmod headers \
    && a2enmod socache_shmcb \
    && a2enmod ssl \
    && a2enmod proxy \
    && a2enmod proxy_http

# VHosts kopieren
COPY vhosts/*.conf /etc/apache2/sites-available/

# VHost aktivieren
RUN a2ensite uniliga_silence_lol.conf \
    && a2dissite 000-default.conf \
    && apache2ctl -k graceful

# memory-limit.ini erstellen und ins Image kopieren
COPY php_ini/memory-limit.ini /usr/local/etc/php/conf.d/

# Xdebug installieren (nur dev)
# RUN pecl install xdebug \
#    && docker-php-ext-enable xdebug

# Cron installieren
RUN apt-get update && apt-get install -y cron \
    && rm -rf /var/lib/apt/lists/*
RUN touch /var/log/cron.log \
    && chown www-data:www-data /var/log/cron.log \
    && chmod 0644 /var/log/cron.log

# Start-Script kopieren und ausführbar machen
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Cron-Daemon und Apache im Vordergrund starten
ENTRYPOINT ["/entrypoint.sh"]
```

Zum Setzen der Berechtigungen und Starten von cron mit Apache:  
_entrypoint.sh_
```
#!/bin/bash

# Rechte setzen
chown -R www-data:www-data /var/www/uniliga_silence_lol

# Cronjobs installieren
chmod +x /var/www/uniliga_silence_lol/bin/setup/install-cronjobs.sh
/var/www/uniliga_silence_lol/bin/setup/install-cronjobs.sh

# Prozesse starten
cron
apache2-foreground
```

Und zum Starten der benötigten Container (Mit traefik als Router):  
_docker-compose.yml_  
(hierbei ist eine Ordner-Struktur erwartet, in der der Inhalt dieses Repos in einem src/uniliga_silence_lol Verzeichnis liegt)
```
services:
  traefik:
    image: traefik:v3.3
    container_name: web_traefik
    restart: always
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock:ro
      - ./traefik/traefik.yml:/etc/traefik/traefik.yml:ro
      - ./letsencrypt:/letsencrypt
    networks:
      - web_traefik
  
  apache_uniliga:
    build: ./apache-uniliga
    container_name: web_apache_uniliga
    restart: always
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.web_silence_lol.rule=Host(`{YOUR_DOMAIN}`)"
      - "traefik.http.routers.web_silence_lol.entrypoints=websecure"
      - "traefik.http.routers.web_silence_lol.tls.certresolver=letsencrypt"
    volumes:
      - ./src/uniliga_silence_lol:/var/www/uniliga_silence_lol
    networks:
      - web_traefik

  mariadb_uniliga:
    image: mariadb:11
    container_name: web_mariadb_uniliga
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: "${MARIADB_ROOT_PASSWORD}"
    volumes:
      - ./mariadb_uniliga_data:/var/lib/mysql
    networks:
      - web_traefik

networks:
  web_traefik:
    driver: bridge
```

Und die für traefik benötigte Config:  
_traefik.yml_
```
entryPoints:
  web:
    address: ":80"
    http:
      redirections:
        entryPoint:
          to: websecure
          scheme: https
  websecure:
    address: ":443"

certificatesResolvers:
  letsencrypt:
    acme:
      email: {YOUR_EMAIL}
      storage: /letsencrypt/acme.json
      caServer: {Let's Encrypt ACME-Server URL}
      httpChallenge:
        entryPoint: web

providers:
  docker:
    exposedByDefault: false
```
</details>
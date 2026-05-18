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

Alternativ: Docker-Setup, siehe [Beispiel-Setup 'Docker-standalone'](https://github.com/Si-Lan/Uniliga-LoL-Uebersicht-Docker-standalone)
- Das 'Docker-standalone'-Setup ist eine vereinfachte Version meines Docker-Setups, welche einen Apache und einen MariaDB-Container startet, sowie Traefik als Reverse-Proxy nutzt.<br>Es ist gedacht als einfaches Setup auf einem Server, der nicht bereits andere Dienste hostet, und kann für eine lokale Testumgebung oder Integration in ein bestehendes Docker-Setup angepasst werden. 

### Festlegen von Daten:
.env aus .env.template erstellen und _Datenbank-Informationen_, _API-Keys_, _User-Agent_ und _Admin-Passwort_ setzen.
- (User-Agent ist notwendig, da OPL für Anfragen einen sinnvollen User-Agent benötigt. Bspw. in meinem Fall: ```'UniligaLoL-Übersicht/1.0 (+https://uniliga.silence.lol)'```)
- (Admin-Passwort ist das Passwort, das benötigt wird, um sich auf der Seite in den Admin-Bereich (/admin) einzuloggen)
- (Umami-Analytics sind optional und standardmäßig deaktiviert. Wenn `UMAMI_ENABLED=true` gesetzt ist, kann ein Umami-Tracking-Skript eingebunden werden.)
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
UMAMI_ENABLED=false
UMAMI_URL=
UMAMI_WEBSITE_ID=
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

## Analytics
Diese Seite unterstützt [Umami-Analytics](https://umami.is/), eine datenschutzfreundliche, selbst hostbare Alternative zu Google Analytics.  
Wenn aktiviert, werden nur anonymisierte Daten gesammelt (z. B. Seitenaufrufe, Verweildauer, Gerät/Browser). Keine personenbezogenen Daten, keine IP-Adressen, keine Cookies.  
Auf [uniliga.silence.lol](https://uniliga.silence.lol) ist Analytics aktiv, weil mich interessiert, wie stark die Seite von der Community genutzt wird und wo sie sich verbessern lässt.

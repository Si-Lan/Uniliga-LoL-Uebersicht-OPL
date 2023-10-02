# Uniliga-LoL-Übersicht
## Webseite
Meine Webseite, auf der dieses Projekt läuft, findet ihr hier:  
https://silence.lol/uniliga/

## Unterstützung
Wenn ihr mich unterstützen wollt, könnt ihr das gerne hier tun  
[![PayPal](https://img.shields.io/badge/Donate-PayPal-blue?style=flat)](https://paypal.me/SimonlLang)

## Benötigt zum eigenen Aufsetzen:

### Festlegen von Zugangsdaten:
* Zugangsdaten festlegen in **setup/data.php** (*.template* entfernen)
  * Zugangsdaten zur Datenbank:
    * ```
      function create_dbcn():mysqli {
          $dbservername = "Server-Name";
          $dbdatabase = "Datenbank-Name";
          $dbusername = "Datenbank-Benutzername";
          $dbpassword = "Datenbank-Passwort";
          $dbport = Datenbank-Port (NULL wenn nicht vorhanden);
          return new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
      }
      ```
  * Admin-Passwort:
    * ```
      function get_admin_pass(): string {
          return "Admin-Passwort";
      }
      ```
  * Riot-API-Key
    * ```
      function get_rgapi_key():string {
          return "Riot-API-Key";
      }
      ```
  * OPL API Bearer Token
    * ```
      function get_opl_bearer_token():string {
          return "Bearer-Token";
      }
      ```

### Datenbank:
*MariaDB*-Datenbank:  
[SQL-File mit Datenbank-Struktur](https://silence.lol/storage/uniliga_opl.sql.zip)


## Wartungsaufwand:

### Turnier-Updates:
1. Möglichkeit: Manuell
   * Buttons im Backend (uniliga/admin)
2. Möglichkeit: Automatisch
   * Cron-Jobs einrichten
      * *Dokumentation dazu folgt*

### bei neuen LoL-Patches:
* Riots DataDragon Dateien updaten:
  * Unter uniliga/admin/ddragon im Backend herunterladbar
  * *Automation über cron-jobs geplant*

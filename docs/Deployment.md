## Lokal testen

```sh
composer install
composer test
php -S 127.0.0.1:8000 -t public public/router.php
```

Die lokale PHP-Entwicklungsserver-Variante schützt `/admin` nicht mit HTTP-Basisauthentifizierung und ist nur für lokale Entwicklung gedacht. Nicht öffentlich erreichbar machen.

## Deployment auf einem Webserver

Den Webroot auf `public/` setzen. `recipes/`, `.htpasswd` und alle übrigen Projektdateien müssen außerhalb des öffentlichen Webroots bleiben. Der PHP-FPM-Benutzer benötigt Schreibrechte auf `recipes/` und `public/uploads/images/`. Das Bildverzeichnis liegt absichtlich im Webroot, damit hochgeladene Bilder direkt ausgeliefert werden können. Verzeichnisse vor dem ersten Aufruf von `/admin` anlegen und dem PHP-FPM-Benutzer übergeben, zum Beispiel:

```sh
sudo install -d -o www-data -g www-data -m 0750 /var/www/recipe-collection/recipes
sudo install -d -o www-data -g www-data -m 0755 /var/www/recipe-collection/public/uploads/images
```

`www-data` und `/var/www/recipe-collection` durch den Benutzer/die Gruppe des PHP-FPM-Pools und den tatsächlichen Projektpfad ersetzen. HTTPS ist zwingend, da HTTP-Basisauthentifizierung Zugangsdaten übermittelt.

Wichtiger Hinweis zu Uploads: Eine Datei mit 413 Response wird in der Regel nicht vom PHP-Code zurückgegeben, sondern vom Webserver oder PHP verhindert, bevor das Formular überhaupt verarbeitet wird. Deshalb müssen die Limits in PHP und im Reverse Proxy/Apache gesetzt werden:

```ini
; /etc/php/8.1/fpm/php.ini oder php.ini
upload_max_filesize = 16M
post_max_size = 16M
max_file_uploads = 20
```

```nginx
# nginx
client_max_body_size 16M;
```

```apache
# Apache
LimitRequestBody 16777216
```

Das Bild-Upload der Anwendung unterstützt bis zu 8 MB pro Datei, daher sollte `upload_max_filesize` und `post_max_size` mindestens 16 M sein und das Reverse Proxy-Limit ebenfalls mindestens 16 M. Nach der Änderung den PHP-FPM- bzw. Webserver-Dienst neu starten.

Beispielkonfigurationen:

- [Apache VirtualHost](../config/apache-vhost.conf.example)
- [Nginx mit PHP-FPM](../config/nginx.conf.example)

Die Beispielkonfigurationen an Serverpfade und PHP-FPM-Socket anpassen und TLS-Zertifikate ergänzen. Zugangsdaten außerhalb des Webroots anlegen, zum Beispiel mit `htpasswd -c /etc/recipe-collection/.htpasswd admin` (Apache `htpasswd`-Werkzeug erforderlich). Bei Apache muss die Konfiguration im HTTPS-VirtualHost stehen. Nginx benötigt `auth_basic` im `/admin`-Location-Block. Zusätzlich prüft die Anwendung, ob Apache/Nginx den authentifizierten Benutzer an PHP weitergegeben hat, und verweigert `/admin` andernfalls mit HTTP 401.

In `config/app.php` für `site_url` die öffentliche HTTPS-Basisadresse ohne Pfad eintragen, zum Beispiel `https://rezepte.example.org`. Diese Adresse wird für absolute Open-Graph- und Twitter-Bild-/Seiten-URLs verwendet. Für iOS-Homescreen-Icons empfiehlt sich ein quadratisches PNG mit 180 × 180 Pixeln unter `apple_touch_icon`; andernfalls verwendet die Seite, sofern konfiguriert, Logo oder Favicon. Auf einzelnen Rezeptseiten wird das erste Rezeptbild für Linkvorschauen verwendet.

## Alternativ: Docker-Deployment

Alternativ kann die Anwendung mit Docker Compose als einzelner Apache/PHP-Container gestartet werden.

Im Projektverzeichnis starten:

```sh
cp docs/.env.example .env
# .env bearbeiten  und dabei ein sicheres Passwort vergeben!
docker compose up --build -d
```

Die Anwendung ist danach unter [http://localhost:8080](http://localhost:8080) erreichbar. Der Port kann mit `RECIPEREPO_PORT` geändert werden. Zum Beenden:

```sh
docker compose down
```

Die Volumes werden dabei nicht gelöscht. Für den produktiven öffentlichen Betrieb weiterhin HTTPS vor den Container setzen; die Docker-Variante bringt bewusst kein TLS-Zertifikat mit.

Alle anpassbaren Docker-Einstellungen stehen in `.env`; die vollständige Vorlage liegt in `.env.example`. Neben `ADMIN_USER` und `ADMIN_PASSWORD` können dort unter anderem `RECIPEREPO_SITE_NAME`, `RECIPEREPO_SITE_URL`, `RECIPEREPO_LOGO_IMAGE`, `RECIPEREPO_FAVICON`, `RECIPEREPO_APPLE_TOUCH_ICON`, `RECIPEREPO_FOOTER_NAME`, `RECIPEREPO_PRIVACY_URL` und `RECIPEREPO_IMPRINT_URL` geändert werden. Die mitgelieferten Standardbilder liegen unter `public/assets/` und sind in der Vorlage automatisch eingetragen. Ohne `.env` beendet Docker Compose den Start mit einer Fehlermeldung.

## Namen und Erscheinungsbild anpassen

`config/app.php` ist die gemeinsame Standardkonfiguration. Bei einer klassischen Installation ohne Docker bearbeitest du die Werte dort direkt. Ohne weitere Anpassung werden die mitgelieferten Standardbilder verwendet.

Im Docker-Betrieb musst du `config/app.php` nicht ändern: Die gleichnamigen `RECIPEREPO_*`-Variablen im `environment`-Block von `docker-compose.yml` überschreiben die Standardwerte nur für den Container.

### `app.php` einfach ändern

Wenn du **kein Docker** verwendest, gehst du so vor:

1. Öffne im Projekt die Datei `config/app.php`.
2. Ändere nur den Wert hinter dem Komma. Zum Beispiel:

   ```php
   'site_name' => $environmentValue('RECIPEREPO_SITE_NAME', 'Meine Rezeptsammlung'),
   'logo_text' => $environmentValue('RECIPEREPO_LOGO_TEXT', 'M'),
   'logo_image' => $environmentValue('RECIPEREPO_LOGO_IMAGE', '/assets/logo.svg'),
   'logo_alt' => $environmentValue('RECIPEREPO_LOGO_ALT', 'Meine Rezeptsammlung'),
   'site_url' => $environmentValue('RECIPEREPO_SITE_URL', 'https://rezepte.example.org'),
   ```
3. Speichere die Datei.
4. Lade die Website neu. Bei PHP-FPM kann je nach Server ein Neustart von PHP-FPM erforderlich sein.

Die Bildpfade beginnen mit `/assets/` und zeigen auf Dateien im Verzeichnis `public/assets/`. Wenn du kein eigenes Bild hast, lass `/assets/logo.svg`, `/assets/favicon.ico` und `/assets/apple-touch-icon.png` stehen. Datenschutz- und Impressums-URLs kannst du leer lassen, wenn du sie nicht verwendest.

Wenn du **Docker** verwendest, ändere stattdessen die entsprechenden `RECIPEREPO_*`-Werte in `docker-compose.yml` und starte den Container neu:

```sh
docker compose up -d --build
```

Die wichtigsten Angaben sind:

- Name der Sammlung und Logo
- Browser-Titel und Linkvorschauen
- Footer, Datenschutz und Impressum
- öffentliche Adresse der Installation

Das mitgelieferte Icon liegt in [public/assets/logo.svg](public/assets/logo.svg). Zusätzlich sind [favicon.ico](../public/assets/favicon.ico) und [apple-touch-icon.png](../public/assets/apple-touch-icon.png) enthalten.

## 413 Fehler bei Uploads

Wenn beim Hochladen von YAML oder Bildern ein HTTP 413 erscheint, liegt das normalerweise nicht am PHP-Code der App, sondern an Server-Limits:

```ini
; php.ini / PHP-FPM
upload_max_filesize = 16M
post_max_size = 16M
max_file_uploads = 20
```

```nginx
client_max_body_size 16M;
```

```apache
LimitRequestBody 16777216
```

Danach PHP-FPM oder Nginx/Apache neu starten. Die Beispielkonfigurationen in [config/apache-vhost.conf.example](../config/apache-vhost.conf.example) und [config/nginx.conf.example](../config/nginx.conf.example) zeigen die nötigen Werte.

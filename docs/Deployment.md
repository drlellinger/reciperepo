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

Die Beispielkonfigurationen an Serverpfade und PHP-FPM-Socket anpassen und TLS-Zertifikate ergänzen. Zugangsdaten außerhalb des Webroots anlegen, zum Beispiel mit `htpasswd -c /etc/recipe-collection/.htpasswd admin` (Apache `htpasswd`-Werkzeug erforderlich). Bei Apache muss die Konfiguration im HTTPS-VirtualHost stehen. Nginx benötigt `auth_basic` im `/admin`-Location-Block.

In `config/app.php` für `site_url` die öffentliche HTTPS-Basisadresse ohne Pfad eintragen, zum Beispiel `https://rezepte.example.org`. Diese Adresse wird für absolute Open-Graph- und Twitter-Bild-/Seiten-URLs verwendet. Für iOS-Homescreen-Icons empfiehlt sich ein quadratisches PNG mit 180 × 180 Pixeln unter `apple_touch_icon`; andernfalls verwendet die Seite, sofern konfiguriert, Logo oder Favicon. Auf einzelnen Rezeptseiten wird das erste Rezeptbild für Linkvorschauen verwendet.

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

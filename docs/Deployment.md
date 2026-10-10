
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

Beispielkonfigurationen:

- [Apache VirtualHost](config/apache-vhost.conf.example)
- [Nginx mit PHP-FPM](config/nginx.conf.example)

Die Beispielkonfigurationen an Serverpfade und PHP-FPM-Socket anpassen und TLS-Zertifikate ergänzen. Zugangsdaten außerhalb des Webroots anlegen, zum Beispiel mit `htpasswd -c /etc/recipe-collection/.htpasswd admin` (Apache `htpasswd`-Werkzeug erforderlich). Bei Apache muss die Konfiguration im HTTPS-VirtualHost stehen. Nginx benötigt `auth_basic` im `/admin`-Location-Block.

# reciperepo

Eine kleine Rezeptesammlung mit PHP und YAML: Rezepte sind öffentlich lesbar und einzeln herunterladbar. Der Adminbereich nutzt HTTP-Basisauthentifizierung des Webservers.

## Voraussetzungen

- PHP 8.1 oder neuer mit `iconv` und den üblichen Datei-/Session-Funktionen
- Composer
- Apache mit PHP-FPM/mod_php oder Nginx mit PHP-FPM

## Lokal starten

```sh
composer install
composer test
php -S 127.0.0.1:8000 -t public public/router.php
```

Die lokale PHP-Entwicklungsserver-Variante schützt `/admin` nicht mit HTTP-Basisauthentifizierung und ist nur für lokale Entwicklung gedacht. Nicht öffentlich erreichbar machen.

## Deployment

Den Webroot auf `public/` setzen. `recipes/`, `.htpasswd` und alle übrigen Projektdateien müssen außerhalb des öffentlichen Webroots bleiben. PHP benötigt Schreibrechte auf `recipes/`. HTTPS ist zwingend, da HTTP-Basisauthentifizierung Zugangsdaten übermittelt.

Beispielkonfigurationen:

- [Apache VirtualHost](config/apache-vhost.conf.example)
- [Nginx mit PHP-FPM](config/nginx.conf.example)

Die Beispielkonfigurationen an Serverpfade und PHP-FPM-Socket anpassen und TLS-Zertifikate ergänzen. Zugangsdaten außerhalb des Webroots anlegen, zum Beispiel mit `htpasswd -c /etc/recipe-collection/.htpasswd admin` (Apache `htpasswd`-Werkzeug erforderlich). Bei Apache muss die Konfiguration im HTTPS-VirtualHost stehen. Nginx benötigt `auth_basic` im `/admin`-Location-Block.

## YAML-Format

Ein vollständiges Beispiel liegt in [examples/zitronenreis.yaml](examples/zitronenreis.yaml). Jedes Rezept enthält `version: 1`, einen Titel und geordnete Inhaltsblöcke:

- `text`: `text`
- `table`: `headers` und `rows`
- `image`: `url`, `alt`, optional `caption`
- `video` oder `audio`: `url`, optional `caption`
- `link`: `url` und `label`

Für die automatisierte Übertragung von Rezepten aus Textdokumenten siehe die [LLM-Anleitung](docs/llm-yaml-authoring.md).

Medien- und Link-URLs müssen HTTP(S) verwenden. Beliebiges HTML und eingebettete iframes werden nicht unterstützt. Der Upload akzeptiert Dateien bis 1 MB. Ein Upload mit demselben normalisierten Titel ersetzt das Rezept; ein anderer Titel legt ein neues Rezept an. Der Download auf jeder Rezeptseite liefert die gespeicherte YAML-Datei.

## Sicherheit und Daten

YAML wird vor dem Speichern vollständig geparst und validiert; Rezeptdateien werden atomar ersetzt. Admin-POSTs nutzen CSRF-Tokens, und Löschungen erfordern Bestätigung. Ausgaben werden escaped. Medien bleiben externe URLs und werden nicht auf den Server kopiert.

Das Rezeptverzeichnis ist die Datenquelle. Für Sicherungen `recipes/` regelmäßig kopieren und die Dateien auf dem Zielsystem wiederherstellen; es gibt keine Datenbankmigration.

## Gestaltung

Das Webinterface hält sich an die [Apple-Design-Skill](https://github.com/emilkowalski/skills/blob/main/skills/apple-design/SKILL.md). Die Umsetzung folgt außerdem dem [Ponytail-Skill](https://github.com/DietrichGebert/ponytail/blob/main/skills/ponytail/SKILL.md) im Standardmodus: kleinste vollständige Lösung, gezielte Tests, keine unnötigen Abhängigkeiten.
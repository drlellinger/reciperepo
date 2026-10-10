# reciperepo

Eine kleine Rezeptesammlung mit PHP und YAML: Rezepte sind öffentlich lesbar und einzeln herunterladbar. Der Adminbereich nutzt HTTP-Basisauthentifizierung des Webservers.

## Voraussetzungen

- PHP 8.1 oder neuer mit `iconv` und den üblichen Datei-/Session-Funktionen
- Composer
- Apache mit PHP-FPM/mod_php oder Nginx mit PHP-FPM

## YAML-Format

Ein vollständiges Beispiel liegt in [examples/zitronenreis.yaml](examples/zitronenreis.yaml). Jedes Rezept enthält `version: 1`, einen Titel und geordnete Inhaltsblöcke. `summary` und `tags` sind optionale Textfelder auf Rezeptebene. Tags werden als Kategorien in der Übersicht verwendet:

- `text`: `text`
- `heading`: `text` für eine Kapitelüberschrift
- `table`: `headers` und `rows`
- `image`: `url`, `alt`, optional `caption`
- `video` oder `audio`: `url`, optional `caption`
- `link`: `url` und `label`

Für die automatisierte Übertragung von Rezepten aus Textdokumenten siehe die [LLM-Anleitung](docs/llm-yaml-authoring.md).

## Oberflächenkonfiguration

Navbar, Browser-Titel und Footer lassen sich in [config/app.php](config/app.php) anpassen.

Medien- und Link-URLs müssen HTTP(S) verwenden. Beliebiges HTML und eingebettete iframes werden nicht unterstützt. Der Upload akzeptiert Dateien bis 1 MB. Ein Upload mit demselben normalisierten Titel ersetzt das Rezept; ein anderer Titel legt ein neues Rezept an. Der Download auf jeder Rezeptseite liefert die gespeicherte YAML-Datei.

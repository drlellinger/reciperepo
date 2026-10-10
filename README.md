# reciperepo

Eine kleine Rezeptesammlung mit PHP und YAML: Rezepte sind öffentlich lesbar und einzeln als YAML oder A4-PDF herunterladbar. Der Adminbereich nutzt HTTP-Basisauthentifizierung des Webservers.

## Voraussetzungen

- PHP 8.1+ mit `fileinfo`, `iconv`, `exif` und `gd`
- PHP-Erweiterung `curl` für externe Bilder in PDF-Dateien
- Composer
- Apache mit PHP-FPM/mod_php oder Nginx mit PHP-FPM

Mehr Informationen zum Deployment siehe [hier](docs/Deployment.md)

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

Navbar, Browser-Titel und Footer lassen sich in [config/app.php](config/app.php) anpassen. In `footer_name`, `privacy_url` und `imprint_url` sind Markdown-Links wie `[Kontakt](mailto:post@example.org)` möglich. Bei `privacy_url` und `imprint_url` funktionieren weiterhin reine URLs mit den Standardlabels „Datenschutzerklärung“ und „Impressum“. Rohes HTML und unsichere Linkprotokolle werden nicht ausgegeben. Für korrekte Linkvorschauen `site_url` auf die öffentliche HTTPS-Adresse setzen. `apple_touch_icon` sollte auf ein quadratisches PNG mit 180 × 180 Pixeln zeigen; ohne eigene Angabe werden Logo oder Favicon verwendet.

## Rezeptbilder hochladen

In der Verwaltung lassen sich YAML-Dateien und Rezeptbilder getrennt hochladen. Bilder werden als JPEG, PNG, GIF oder WebP bis 8 MB in `public/uploads/images/` gespeichert und sind dort öffentlich abrufbar. JPEG-, PNG- und WebP-Dateien werden beim Upload neu encodiert, um Metadaten wie GPS-Standorte zu entfernen; JPEG-EXIF-Ausrichtung wird dabei in die Bildpixel übernommen. Animierte GIFs bleiben unverändert, damit ihre Animation erhalten bleibt. Die Verwaltung zeigt Vorschaubilder und die zugehörigen Pfade an. Den kopierten Pfad als `url` eines `image`-Blocks in der Rezept-YAML verwenden, zum Beispiel `/uploads/images/<Dateiname>.jpg`. Externe HTTP(S)-Bild-URLs sind weiterhin zulässig. Für Sicherungen auch `public/uploads/images/` einschließen.

Der PDF-Download verwendet Dompdf und bildet Rezeptkopf, Kategorien und Inhaltsblöcke im A4-Hochformat ab. Logo und Name werden aus der Oberflächenkonfiguration übernommen. Lokale Bilder unter `public/` und externe HTTP(S)-Bilder erscheinen im PDF. Externe Bilder werden mit Größen- und Zeitlimits geladen; private IP-Adressen und Weiterleitungen werden blockiert.

Externe Medien- und Link-URLs müssen HTTP(S) verwenden; Bilder aus der Verwaltung dürfen zusätzlich ihren lokalen Pfad unter `/uploads/images/` verwenden. Beliebiges HTML und eingebettete iframes werden nicht unterstützt. YAML-Dateien dürfen bis 1 MB groß sein, Bilddateien bis 8 MB. Ein YAML-Upload mit demselben normalisierten Titel ersetzt das Rezept; ein anderer Titel legt ein neues Rezept an. Der Download auf jeder Rezeptseite liefert die gespeicherte YAML-Datei.

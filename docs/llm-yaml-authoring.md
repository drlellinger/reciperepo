# Rezept-YAML aus Textdokumenten erstellen

Verwende diese Anleitung, wenn ein LLM ein Rezept aus einem beliebigen Textdokument in das yaml-Format überträgt.

## Anweisung an das LLM

Übertrage ausschließlich Informationen, die im Quelldokument stehen. Erfinde keine Zutaten, Mengen, Zeiten, Portionen, Zubereitungsschritte oder URLs. Bewahre Reihenfolge und Bedeutung. Wenn eine wichtige Angabe unklar oder widersprüchlich ist, frage nach; wenn keine Rückfrage möglich ist, lass sie weg. Gib als Ergebnis nur eine YAML-Datei ohne Markdown-Zaun oder zusätzliche Erklärung aus.

Das Dokument muss diesem Schema entsprechen:

```yaml
version: 1
title: Rezepttitel
summary: Kurze Beschreibung des Gerichts.
image:
  url: https://example.org/rezeptbild.jpg
  alt: Sachliche Bildbeschreibung
aliases: [Alternativname, Name in einer anderen Sprache]
tags: [Hauptgericht, Asiatisch]
blocks:
  - type: heading
    text: Zutaten
  - type: text
    duration: 20
    text: "Ein Absatz oder Zubereitungsschritt."
  - type: table
    headers: [Zutat, Menge]
    rows:
      - [Mehl, "200 g"]
  - type: image
    url: https://example.org/rezeptbild.jpg
    alt: Sachliche Bildbeschreibung
    caption: Optionale Bildunterschrift
  - type: video
    url: https://example.org/anleitung.mp4
    caption: Optionale Beschreibung
  - type: youtube
    url: https://www.youtube.com/watch?v=dQw4w9WgXcQ
    caption: Optionaler Videotitel
  - type: audio
    url: https://example.org/anleitung.mp3
  - type: link
    url: https://example.org/rezeptquelle
    label: Rezeptquelle
```

## Formatregeln

- `version` ist die Zahl `1`. Auf oberster Ebene sind nur `version`, `title`, die optionalen Felder `summary`, `aliases`, `tags` und `image` sowie `blocks` erlaubt.
- `title` ist nicht leer und höchstens 160 UTF-8-Bytes lang.
- `summary` ist optional. Wenn die Quelle genug Angaben enthält, formuliere daraus eine kurze Beschreibung; erfinde keine Eigenschaften. Wenn keine Kurzbeschreibung sicher ableitbar ist, lass das Feld weg. Ist es vorhanden, muss es ein nicht leerer Text sein.
- `aliases` ist optional und eine nicht leere Liste eindeutiger Alternativnamen bis jeweils 160 UTF-8-Bytes. Verwende nur Namen, die im Quelldokument ausdrücklich genannt werden; sie werden in der Suche und auf der Rezeptseite angezeigt.
- `tags` ist optional und eine Liste eindeutiger, nicht leerer Texte bis jeweils 80 UTF-8-Bytes. Verwende kurze Kategorienamen, die aus dem Dokument klar hervorgehen; ohne sichere Kategorie `tags` weglassen.
- `image` ist optional und enthält genau ein Hauptbild mit `url` und nicht leerem `alt`. Es wird als Thumbnail in Listen und als Bild beim Teilen verwendet. Ohne sichere Bild-URL und sachlichen Alt-Text `image` weglassen.
- `blocks` ist eine geordnete Liste. Jeder Block hat genau einen bekannten `type` und nur die dafür erlaubten Felder aus dem Schema.
- `text` benötigt ein nicht leeres Textfeld `text`. Mehrzeilige Absätze oder Schritte können mit `text: |-` geschrieben werden. Optional kann das `duration`verwendet werden um eine Zeitangabe in Minuten anzugeben.
- `heading` benötigt ein nicht leeres Textfeld `text` und wird als Kapitelüberschrift innerhalb des Rezepts dargestellt. Verwende Überschriften, wenn das Quelldokument erkennbare Abschnitte hat.
- `table` benötigt eine nicht leere Liste von Textüberschriften `headers` und eine Liste `rows`. Jede Zeile muss gleich viele Zellen wie Überschriften haben. Zellen sind Text oder Zahlen. Mengen mit Einheiten als Text quotieren, zum Beispiel `"200 g"`.
- `image` benötigt `url` und ein nicht leeres `alt`; `caption` ist optional. `video` und `audio` benötigen `url`; `caption` ist optional. `youtube` benötigt eine YouTube-URL zu einem Video; unterstützt werden normale Video-URLs, Kurzlinks sowie `shorts`- und `embed`-URLs. `caption` ist optional. `link` benötigt `url` und nicht leeres `label`.
- Ein Bild nur dann ausgeben, wenn eine echte URL vorliegt und sich ein sachlicher Alt-Text aus der Quelle ableiten lässt; sonst den Bildblock weglassen.
- Externe URLs müssen vollständig und absolut sein, mit `http://` oder `https://` beginnen und einen Host enthalten. URLs niemals ausdenken. Für ein zuvor in der Verwaltung hochgeladenes Bild darf dessen Pfad `/uploads/images/<Dateiname>` als `image.url` übernommen werden. Nur direkte Medien-URLs als `image`, `video` oder `audio` verwenden; andere Webseiten als `link` ausgeben.
- YouTube-Videos werden nur in der Webansicht eingebettet. Im PDF erscheint kein gerendertes Video, sondern höchstens die optionale Beschriftung.
- YAML-Text mit Doppelpunkten, `#`, Anführungszeichen oder möglichen YAML-Sonderwerten sicher quotieren. Beliebiges HTML, iframes, zusätzliche Felder und unbekannte Blocktypen sind nicht erlaubt.
- Eine Datei darf höchstens 1 MB groß sein.

## Abschlussprüfung

Vor der Ausgabe kontrollieren: YAML ist syntaktisch gültig; Titel und Version sind vorhanden; jedes Feld passt exakt zum Blocktyp; Tabellenzeilen haben die richtige Spaltenzahl; URLs stammen aus der Quelle und sind HTTP(S); keine Information wurde hinzuerfunden. Ist etwas nicht erfüllbar, nicht raten, sondern nachfragen oder den betreffenden optionalen Inhalt weglassen.

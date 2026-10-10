# Rezept-YAML aus Textdokumenten erstellen

Verwende diese Anleitung, wenn ein LLM ein Rezept aus einem beliebigen Textdokument in das Küchenarchiv-Format überträgt.

## Anweisung an das LLM

Übertrage ausschließlich Informationen, die im Quelldokument stehen. Erfinde keine Zutaten, Mengen, Zeiten, Portionen, Zubereitungsschritte oder URLs. Bewahre Reihenfolge und Bedeutung. Wenn eine wichtige Angabe unklar oder widersprüchlich ist, frage nach; wenn keine Rückfrage möglich ist, lass sie weg. Gib als Ergebnis nur eine YAML-Datei ohne Markdown-Zaun oder zusätzliche Erklärung aus.

Das Dokument muss diesem Schema entsprechen:

```yaml
version: 1
title: Rezepttitel
summary: Kurze Beschreibung des Gerichts.
blocks:
  - type: text
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
  - type: audio
    url: https://example.org/anleitung.mp3
  - type: link
    url: https://example.org/rezeptquelle
    label: Rezeptquelle
```

## Formatregeln

- `version` ist die Zahl `1`. Auf oberster Ebene sind nur `version`, `title`, das optionale Feld `summary` und `blocks` erlaubt.
- `title` ist nicht leer und höchstens 160 UTF-8-Bytes lang.
- `summary` ist optional. Wenn die Quelle genug Angaben enthält, formuliere daraus eine kurze Beschreibung; erfinde keine Eigenschaften. Wenn keine Kurzbeschreibung sicher ableitbar ist, lass das Feld weg. Ist es vorhanden, muss es ein nicht leerer Text sein.
- `blocks` ist eine geordnete Liste. Jeder Block hat genau einen bekannten `type` und nur die dafür erlaubten Felder aus dem Schema.
- `text` benötigt ein nicht leeres Textfeld `text`. Mehrzeilige Absätze oder Schritte können mit `text: |-` geschrieben werden.
- `table` benötigt eine nicht leere Liste von Textüberschriften `headers` und eine Liste `rows`. Jede Zeile muss gleich viele Zellen wie Überschriften haben. Zellen sind Text oder Zahlen. Mengen mit Einheiten als Text quotieren, zum Beispiel `"200 g"`.
- `image` benötigt `url` und ein nicht leeres `alt`; `caption` ist optional. `video` und `audio` benötigen `url`; `caption` ist optional. `link` benötigt `url` und nicht leeres `label`.
- Ein Bild nur dann ausgeben, wenn eine echte URL vorliegt und sich ein sachlicher Alt-Text aus der Quelle ableiten lässt; sonst den Bildblock weglassen.
- URLs müssen vollständig und absolut sein, mit `http://` oder `https://` beginnen und einen Host enthalten. URLs niemals ausdenken. Nur direkte Medien-URLs als `image`, `video` oder `audio` verwenden; andere Webseiten als `link` ausgeben.
- YAML-Text mit Doppelpunkten, `#`, Anführungszeichen oder möglichen YAML-Sonderwerten sicher quotieren. Beliebiges HTML, iframes, zusätzliche Felder und unbekannte Blocktypen sind nicht erlaubt.
- Eine Datei darf höchstens 1 MB groß sein.

## Abschlussprüfung

Vor der Ausgabe kontrollieren: YAML ist syntaktisch gültig; Titel und Version sind vorhanden; jedes Feld passt exakt zum Blocktyp; Tabellenzeilen haben die richtige Spaltenzahl; URLs stammen aus der Quelle und sind HTTP(S); keine Information wurde hinzuerfunden. Ist etwas nicht erfüllbar, nicht raten, sondern nachfragen oder den betreffenden optionalen Inhalt weglassen.
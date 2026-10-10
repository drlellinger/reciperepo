## Plan: Leichte YAML-Rezeptesammlung

Schlanke, serverseitig gerenderte Rezeptesammlung mit PHP, HTML/CSS und nur dem nötigen JavaScript. Rezepte liegen einzeln als YAML-Dateien; Besucher können sie ansehen und einzeln herunterladen. Ein per Apache/Nginx-Basisauthentifizierung geschützter Adminbereich nimmt YAML-Uploads an, ersetzt bei gleichem Rezeptnamen und erlaubt Löschen.

**Schritte**
1. Projektgerüst und YAML-Verarbeitung einrichten: Composer-Projekt mit `symfony/yaml` als Parser/Serializer, öffentliche Rezeptdateien außerhalb des Webroots ablegen, `public/` als Document Root verwenden. Beispielkonfiguration bzw. Einrichtungsnotizen für Apache `.htaccess` und Nginx `auth_basic` bereitstellen; `.htpasswd` außerhalb des Webroots halten.
2. Rezeptmodell und Dateispeicher implementieren: versioniertes, dokumentiertes YAML-Format mit Titel und geordneten Inhaltsblöcken für Text, Tabellen, Bilder, Video, Audio und Links. Rezeptnamen normalisieren, neue/gleiche Namen deterministisch zuordnen, doppeldeutige Namen ablehnen und atomar speichern. Ungültige YAML-Dateien dürfen vorhandene Rezepte nicht überschreiben.
3. Öffentlichen Bereich bauen: Rezeptübersicht und Rezeptdetailseiten mit responsivem, semantischem HTML; Inhaltsblöcke typgerecht darstellen; Text escapen und Medien-URLs auf sichere HTTP(S)-URLs beschränken. Detailseite bietet den Download genau dieser Rezeptdatei als YAML an. Gestaltung und Bedienung orientieren sich an den Prinzipien der [Apple-Design-Skill](https://github.com/emilkowalski/skills/blob/main/skills/apple-design/SKILL.md): klare Hierarchie und Orientierung, vertraute Bedienmuster, unmittelbares und verständliches Statusfeedback, sorgfältige Typografie und responsive Layouts. Bewegungen bleiben zweckmäßig und respektieren `prefers-reduced-motion`, `prefers-reduced-transparency` und `prefers-contrast`; transparente Materialien nur dort einsetzen, wo sie Hierarchie und Lesbarkeit unterstützen. Gestenphysik und interruptible Springs nur implementieren, wenn tatsächlich zieh- oder wischbare Elemente vorgesehen sind.
4. Adminbereich umsetzen: `/admin` ausschließlich durch Webserver-Basisauthentifizierung schützen. YAML-Datei hochladen und vor dem Speichern vollständig parsen und validieren; bei identischem normalisiertem Titel ersetzen, andernfalls hinzufügen. Rezepte auflisten und Löschen mit Bestätigung anbieten; Mutation-Endpunkte zusätzlich gegen CSRF schützen. Upload und Löschung geben unmittelbares, verständliches Erfolgs- oder Fehlerfeedback; Validierungsfehler erscheinen nahe der Eingabe.
5. Dokumentation ergänzen: YAML-Beispiel und erlaubte Blocktypen, Installation/Composer, Schreibrechte, Apache- und Nginx-Konfiguration, Sicherung und Wiederherstellung der Rezeptdateien beschreiben.

**Relevante Dateien**
- `/Users/maluck/reciperepo/composer.json` — PHP-Abhängigkeit und Projektmetadaten.
- `/Users/maluck/reciperepo/public/index.php` — schlanker Einstiegspunkt für öffentliche Ansichten und Adminaktionen.
- `/Users/maluck/reciperepo/src/RecipeRepository.php` — Laden, Auflisten, Zuordnen, Ersetzen und Löschen einzelner YAML-Rezepte.
- `/Users/maluck/reciperepo/src/RecipeValidator.php` — Schema- und Inhaltsvalidierung inklusive sicherer Medien-URLs.
- `/Users/maluck/reciperepo/templates/` — Übersicht, Detailansicht und Adminoberfläche.
- `/Users/maluck/reciperepo/public/assets/` — CSS und bei Bedarf minimales JS.
- `/Users/maluck/reciperepo/recipes/` — YAML-Dateien; nicht direkt über HTTP abrufbar.
- `/Users/maluck/reciperepo/README.md` — Installation, Betrieb und Webserver-Konfiguration.

**Verifikation**
1. Automatisierte PHP-Tests für gültige/ungültige YAML, Pflichtfelder und Inhaltsblöcke; ergänzend Upload-Fälle: neue Datei legt Rezept an, gleicher normalisierter Titel ersetzt, ungültiger Upload lässt das bestehende Rezept unverändert, Löschen entfernt nur den ausgewählten Eintrag.
2. Prüfen, dass Rezeptliste und Detailansicht öffentlich erreichbar sind, `/admin` ohne Zugangsdaten abgewiesen wird und mit Zugangsdaten Upload/Löschen funktioniert.
3. Rezeptdetail-Download laden und erneut mit dem YAML-Parser validieren; prüfen, dass Rezepte im Speicherverzeichnis nicht direkt über HTTP abrufbar sind.
4. Manuell Apache- und Nginx-Konfiguration anhand der Dokumentation prüfen sowie Medien-URL- und HTML-Escaping-Verhalten testen.
5. Öffentliche Seiten und Adminoberfläche auf Mobil- und Desktopbreiten sowie mit reduzierter Bewegung, reduzierter Transparenz und erhöhtem Kontrast prüfen. In Upload- und Löschabläufen unmittelbares Feedback, klare Orientierung und verständliche Fehlerzustände prüfen.

**Entscheidungen**
- Die öffentliche Sammlung ist frei lesbar; nur Administration ist geschützt.
- Eine Datei je Rezept; öffentlicher Download umfasst das aktuell angezeigte Rezept.
- Admin nutzt Upload statt Formular oder Browser-YAML-Editor; separate Löschaktion bleibt enthalten.
- Gleichheit für Ersetzen basiert auf normalisiertem Rezepttitel. Titeländerung wird als neues Rezept behandelt; keine implizite ID-/Rename-Funktion.
- Freie Inhaltsblöcke sind typisierte YAML-Daten, kein frei ausführbares HTML. Videos/Audio werden über sichere URL-Quellen eingebunden bzw. verlinkt, ohne beliebiges iframe-HTML.
- Keine Datenbank, kein Framework und keine Registrierung mehrerer Admin-Konten im Anwendungscode; Zugangsdaten verwaltet der Webserver.
- Das Webinterface folgt den anwendbaren Prinzipien der verlinkten Apple-Design-Skill; nicht benötigte Animationen, Gesten und dekorative Transluzenz werden nicht ergänzt.
- Für Planung und Umsetzung gilt Ponytail im Standardmodus (`full`): die kleinste vollständige Lösung wählen, vorhandene Projektmuster und Plattformfunktionen nutzen, unnötige Abstraktionen, Abhängigkeiten und Zusatzfunktionen vermeiden. Nichttriviale Logik erhält einen passenden Test oder Selbstcheck. Validierung an Vertrauensgrenzen, Fehlerbehandlung gegen Datenverlust, Sicherheit und Barrierefreiheit bleiben erhalten. Abschlussberichte nennen knapp ausgelassene Prüfungen und relevante Restrisiken. Referenz: [Ponytail-Skill](https://github.com/DietrichGebert/ponytail/blob/main/skills/ponytail/SKILL.md).

**Weitere Überlegungen**
1. Hosting wird als PHP-fähig mit Schreibrechten auf das nicht-öffentliche Rezeptverzeichnis angenommen. Ein YAML-PHP-Extension ist nicht vorausgesetzt, da die YAML-Bibliothek über Composer eingebunden wird.
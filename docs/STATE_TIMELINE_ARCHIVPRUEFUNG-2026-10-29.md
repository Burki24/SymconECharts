# StateTimeline: Archiv-Dauern am 29.10.2026

## Ergebnis und korrigierte Einordnung

Der bekannte Fehler ist durch eine eng begrenzte Gateway-Korrektur behoben.
Eine der vier ausgewählten Boolean-Quellen enthält neun Sekunden mit mehreren
unterschiedlichen Werten. Jeweils genau ein Datensatz besitzt positive native
Archiv-Dauer; die weiteren Wechsel besitzen Dauer 0. Die bisherige pauschale
Einordnung dieser Fälle als Datenfehler war deshalb nicht ausreichend begründet.

Die bestehende aktuelle 24-Stunden-Ansicht funktionierte bereits, weil die
betroffenen älteren Sekunden außerhalb ihres Fensters lagen. Die Prüfung
beschränkte sich daher nicht auf diese aktuelle fehlerfreie Ansicht.

[ADR 0108](adr/0108-duration-aware-archive-states.md) beschreibt die zentrale
Policy für StateTimeline und CalendarPie-Zustandsdauern: nur genau ein positiver
Integer-Dauereintrag bei sonst Dauer 0 kann einen unterschiedlichen Zustand
eindeutig bestimmen. Andere Widersprüche bleiben Fehler. Das zusätzliche
optionale `RawPointCount` erhält bei einer Reduktion die Rohdatenbudgetierung.
Kein First-/Last-wins, keine künstlichen Untersekunden und keine Archivbereinigung.

## Reale MCP-Prüfung

Geprüft wurde ausschließlich die freigegebene Testinstallation auf Symcon 9.1,
PHP 8.5 unter Windows. Produktivsystem und native IPSView-App wurden nicht
geöffnet oder verändert. Reale IDs, Quellenpfade und Kanäle stehen nicht hier.

| Prüfung | Ergebnis |
| --- | --- |
| Historischer Fehler vor Korrektur | Separate temporäre StateTimeline mit den vier vorhandenen Quellen und kurzem festem historischem Fenster. Status 204, `INVALID_ARCHIVE_DATA`; der Fehler ist reproduziert. |
| Derselbe Fall nach Korrektur | Dieselbe temporäre Instanz liefert Status 102 und exakte Abschnitte. Der reale Dauerhinweis wird ausgewertet. |
| Alle neun betroffenen Sekunden | Jeweils ein festes Vier-Sekunden-Fenster um den Zeitpunkt, alle vier Quellen. Neunmal Status 102 und der durch den positiven Rohdateneintrag belegte Zustand. |
| Ausgabe/Transport | Für alle neun Fenster IPSView-HTML unter 1 MiB, HTTP 200 und gültiges JSON. Größtes Dokument 635062 Bytes. Kein neuer visueller Geräte-/Touch-Nachweis. |
| Großes historisches Fenster | Weiterhin Status 204 und `ARCHIVE_TRUNCATED` mit Fehlerdokument. Die Reduktion überspielt keinen überschrittenen Rohdatenrahmen. |
| Historische Archivwerte | Für alle vier Quellen Zeitpunkt-/Wertfolgen bis zum fest abgeschlossenen Vortag vor/nach dem Lauf per SHA-256 und Anzahl verglichen: unverändert. |
| Konfiguration und Archivierung | Originalkonfiguration und Gateway-Parent unverändert. Alle vier Quellen weiterhin mit aktivierter Archivierung; kein Schalt- oder Variablen-Schreibaufruf. |
| Bereinigung | Eine temporäre Instanz, ihre Ausgabevariable und die Testkategorie entfernt. Keine temporären Archive angelegt. |
| Vorhandene Instanz danach | Status 102, gültiges Modell mit vier Zeilen. |

Nur die beiden betroffenen PHP-Dateien wurden zur Vorabprüfung auf die
Testinstallation übertragen; ihre Syntaxprüfung war erfolgreich. Es war kein
Bibliotheksreload erforderlich, da sich nur Methodenrümpfe ändern. Der Quelltext
entspricht dem lokalen Arbeitspaket; ein unveränderlicher Freigabe-Commit und
die CI nach Eigentümer-Push entstehen erst später.

## Regressionen und verbleibende Grenzen

- Gateway-Test RED am bisherigen Ablehnungszweig, danach GREEN für mehrere
  Eingangsreihenfolgen, Boolean-/Integer-Werte, fehlende/ungültige/mehrdeutige
  Dauern und unveränderte Rohzeilenkürzung.
- Ein zweiter RED-/GREEN-Fall prüft neun Quellen mit jeweils 2000 gleichen
  Rohzeilen: Auch bei nur einem normalisierten Punkt pro Quelle darf das
  16000er-Gesamtbudget nicht umgangen werden. Ungültige Rohzählmetadaten werden
  abgewiesen, alte Antworten ohne Reduktion bleiben kompatibel.
- Modulintegration erhält die exakten StateTimeline-Abschnitte, Vorgänger und
  unbekannten Anfänge sowie die CalendarPie-Tagessekunden ohne Dauer-0-Anteile.
- Die vollständige lokale Qualitätssuite `php tests/quality.php --fix` endet
  nach der Budgetergänzung mit Exit 0: Formatierung, Syntax, Struktur,
  Repositoryintegration und alle vorgesehenen Layout-/Runtime-Prüfungen sind
  bestanden. Der Log enthält keine PHP-Warnungen oder Fatalfehler. LF/UTF-8
  ohne BOM und `git diff --check` sind geprüft.

Die beiden übertragenen Dateien entsprechen bytegleich dem geprüften lokalen
Stand (Gateway SHA-256-Präfix `e4a0028f0459ff8b`, StateTimeline
`7f0c72ab2de8a5e6`). Die Korrektur ist als Teil der bereits beauftragten
Beta-Vorbereitung im Changelog enthalten; Versionsmetadaten wurden nicht
manuell verändert. Commit, Push und Veröffentlichung verbleiben beim Eigentümer.

Es wurde kein echter mehrdeutiger Archivzustand künstlich auf der Installation
angelegt; diese negativen Fälle sind lokal geprüft. Ungeprüfte Touch-, Versions-
und Langlastvarianten bleiben offen. Renderer, Designs und Vorschauengeometrie
sind unverändert; die bestehende Eigentümerabnahme bleibt davon unabhängig.

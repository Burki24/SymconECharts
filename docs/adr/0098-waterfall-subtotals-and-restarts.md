# ADR 0098: Waterfall-Zwischensummen und unabhängige Neustarts

- Status: Angenommen
- Datum: 2026-10-23
- Ergänzt: ADR 0034 und ADR 0071

## Kompatibler Quellenvertrag

Die bestehende geordnete `Sources`-Liste erhält je Zeile zwei optionale
Boolean-Felder mit Default false: `SubtotalAfter` und `Restart`. Fehlend und
false liefern exakt die bisherigen Schritte und Endsumme; alte JSON-Listen,
Properties, Idents, GUIDs und unabhängige Designs bleiben erhalten. Neue
Properties oder eine Datenmigration sind nicht erforderlich. Falsch typisierte
Flags liefern eine Quelldiagnose. Die erste Quelle bleibt der implizite
absolute Startwert; ein zusätzliches `Restart=true` dort wird abgewiesen.

`SubtotalAfter` hängt unmittelbar nach der zugeordneten Quelle einen berechneten
Schritt `kind=subtotal` an: Nullbasis bis zum aktuellen Stand, ohne erneute
Addition. Die Identität `subtotal-variable-<VariableID>` folgt derselben
Quellen-ID bei Updates/Reorder, nicht Label oder Index. `calculated=true` und
`afterSource` kennzeichnen die Ableitung; ein Messzeitstempel oder eine weitere
Quellvariable wird nicht erfunden. Die Endsumme behält die bestehende ID `total`.

Nach dieser ersten Vertikale ergänzt `Restart` einen neuen Abschnitt: Die
ausgewählte Variable ist dessen absoluter, auch negativer/null Startwert,
nicht eine Differenz zur vorherigen Summe. Die Säule beginnt an null; ihr
`previousTotal` bewahrt den Abschluss des vorherigen Abschnitts. Ab diesem
Schritt läuft die Summierung getrennt weiter. Anfangs-/Neustartlabels bilden
die expliziten Abschnittsbezüge; Modellmetadaten `section` und `sectionLabel`
werden nur bei tatsächlich konfigurierten Neustarts ergänzt.

Beispiel: `100, -20, Zwischensumme 80, Neustart -5, +2` endet bei `-3`,
nicht bei `77` und nicht bei einer Summe unabhängiger Abschlüsse. Ein Neustart
maskiert keine vorherige ungültige Quelle oder einen früheren Summenüberlauf.
Ein endlicher absoluter Start wird dagegen niemals vorübergehend zum alten
Stand addiert; zwei maximale endliche Starts nacheinander sind zulässig.
Gewählte Zwischensummen unmittelbar nach Anfangs-/Neustartwerten sind erlaubt.
Abschlusssäulen vor einem Neustart bleiben ausdrücklich wählbare Zwischensummen,
keine automatisch eingefügten oder doppelt gezählten Änderungen.

## Gemeinsames Modell und Ausgabe

Der vorhandene familienlokale `WaterfallData` übernimmt Optionsprüfung,
vorzeichenbehaftete Totals und den vollständigen Schrittplan für Live-Modell
und beide SVG-Entwürfe. Die alte doppelte Modellbildung entfällt. Quellen-/
Präsentationsprüfung verbleibt im Modul und in vorhandenen lesenden Helfern;
Gatewayzugriff und ID-Abonnements nutzen weiterhin `EChartsCurrentSources`.
Gemeinsame kompakte Formular-/SVG-Infrastruktur sowie Kategorieanker und
ECharts-Renderer werden wiederverwendet. Im geprüften Symcon_ModuleHelper
existiert keine fachliche Waterfall-Bilanzbildung; keine lokale Helper-Kopie,
neue generische Rechenabstraktion oder Änderung an synchronisierten Helfern.

Zwischensummen sind im Namen und Tooltip als berechnet gekennzeichnet;
Neustarts nennen Abschnitt und vorherigen Abschluss. Die letzte Säule erhält
bei Neustarts den Zusatz „Letzter Abschnitt“. Ein dauerhafter Hinweis erklärt,
dass Abschnitte nicht addiert werden; er bleibt auch ohne Wertlabels sichtbar.
Die vorhandene Startfarbe gilt für Neustarts, die Summenfarbe für Zwischensummen.
Alle alten Designwerte bleiben gespeichert. SVG-Titel erhalten dieselben
Abschnitts-/Berechnungsangaben escaped. Der letzte SVG-Standardtext wird nun
wie im Live-Modell übersetzt, statt in englischen Sitzungen „Gesamt“ zu erzwingen.

Die vollständige Liste bestimmt sämtliche Zwischenstände und die Werteskala,
einschließlich früherer unsichtbarer Abschnitte. Navigation ist weiterhin nur
Ansicht und verwendet die bestehenden ID-Anker. Die begrenzte SVG-Projektion
zeichnet höchstens fünf Schritte, gegebenenfalls ein Auslassungszeichen und
die echte letzte Summe; das Quellenverhältnis zählt nur gezeigte echte Quellen.
Ohne neue Auswahlen bleiben damit fünf Quellen plus Endsumme erhalten.

## Schutzbudgets und Nachweise

2–100 eindeutige numerische Quellen derselben Einheit, höchstens eine
Zwischensumme pro Quelle und genau eine letzte Summe ergeben maximal 201
Schritte. Es entstehen keine zusätzlichen Gateway-Abfragen, Referenzen oder
Abonnements. Alle vor einem Neustart liegenden Quellen bleiben in Validierung
und Ladeumfang. Die bisherigen 200000 Modellbytes und finalen 1-MiB-Ausgaben
umfassen auch abgeleitete Schritte und Abschnittsmetadaten. Überläufe liefern
weiterhin einen kontrollierten Fehler statt unvollständiger Bilanzen.

Die reinen Tests begannen für Zwischensummen RED am fehlenden Schrittplan,
für Neustarts anschließend RED an der fälschlichen Addition. Orakel prüfen
konkrete Beträge, exakte Altmodelle, stabile IDs, Flags, Null/negative Werte,
Überläufe und 201 Schritte. Die fokussierte echte Modul-/Gateway-Integration
prüft unabhängige Designs, originale Metadaten/Einheiten, Live-Updates,
ausgeführte Entwurfscallbacks ohne Persistenz/Statusänderung, beide SVGs,
Fehler/Erholung sowie maximale Listen und Byteguards. Gepinnte native
Renderer-Tests prüfen absolute Geometrie, vollständige Skalen, sichere
Tooltips und Navigation/Resize in beiden Ausgabeadaptern.
Lokale Browserprüfungen bleiben von der Host-Abnahme getrennt. Der Eigentümer
hat die Erweiterung inzwischen in Symcon-Kachel und tatsächlichem IPSView bestätigt.

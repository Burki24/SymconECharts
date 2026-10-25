# ADR 0083: Benutzerdefinierte und feste historische Heatmap-Zeiträume

- Status: Angenommen
- Datum: 2026-10-21
- Entscheider: Burki24
- Ergänzt: ADR 0041, 0043, 0062 und 0077

## Entscheidung und Kompatibilität

EChartsHeatmap ergänzt `Range=custom|absolute`, ohne die bisherigen sechs
Kalenderzeiträume `1w/1m/3m/6m/12m/ytd` zu ändern. Default bleibt `1m`.
Alte Properties, GUIDs, Präfixe, Kennzahlen und unabhängige Designs bleiben
unverändert. Die schon zuvor abgelösten Werte `7d/30d` werden nicht reaktiviert.

Additive Properties sind `CustomRangeValue=7`, `CustomRangeUnit=day` sowie
`AbsoluteStart={}` und `AbsoluteEnd={}` als Strings. Die freie Dauer verwendet
die vorhandenen 1–1000 Minuten/Stunden/Tage/Wochen. Nur aktive Felder werden
validiert; inaktive Auswahlwerte werden weder ausgewertet noch umgeschrieben.
Neue Fenster dürfen höchstens 366 × 24 Stunden verstrichene Zeit umfassen.
Das ist kein Limit von 366 zivilen Kalendertagen über einen Offsetwechsel.

Native SelectDateTime-Felder verwenden sechs Integer-Felder
year/month/day/hour/minute/second. Die SDK-Dokumentation wurde am 21.10.2026
per MCP gelesen. Grenzen liegen in der Symcon-Zeitzone; Von ist eingeschlossen,
Bis ausgeschlossen, strikt später als Von und höchstens jetzt. Ungültige,
nicht existierende oder mehrdeutige lokale Uhrzeiten werden abgewiesen.

## Gemeinsame Planung und Ressourcen

`HeatmapWindow` wiederverwendet `EChartsArchiveQuery::ParseLocalDateTime`,
`ResolveAbsoluteWindow` und `ResolveAbsolute`. Die benutzerdefinierte Dauer
verwendet dieselben Einheiten wie andere Archivfamilien; ihr Ende folgt dem
Beginn der laufenden Archivstunde. Feste Grenzen folgen nicht der laufenden
Uhr. Angefangene Randstunden gehen nicht ein, auch wenn schon Werte vorliegen.
Ein gültiges Fenster ohne ganze Stunde bleibt leer und fragt kein Archiv ab.
Die allgemeinen Datums-/Archivplaner werden dafür nicht erneut implementiert.

Die alten Heatmap-Kalenderfenster bleiben im familienbezogenen Planer:
Monatsabschlüsse, datierte Woche und Jahresabschnitt haben andere fachliche
Regeln als eine verstrichene Dauer. Eine gemeinsame Abstraktion darf diese
Unterschiede nicht verdecken. Für neue Modi bleiben sieben Wochentage und
24 Stunden erhalten, nicht beliebig viele datierte Zeilen.

Archivzugriff bleibt `archive.read`, Aggregationsstufe 0, Chunks von höchstens
60 × 86400 Sekunden und je Chunk maximal 2000 Aggregate. Der bisherige
längste Kalenderzeitraum benötigt höchstens sieben solcher Abfragen; diese
Anzahl wird jetzt explizit geschützt. Die neue Höchstdauer entspricht 8784
Stunden. Kein Limit wird hochgesetzt, keine automatische Reduktion oder
unvollständige Teilantwort als vollständiges Modell ausgegeben. Modell- und
HTML-Grenzen bleiben erhalten.

Die vier Kennzahlen bleiben Mittel der Stundenmittel, Minimum der Minima,
Maximum der Maxima und Summe nativer Zähler-Stundenverbräuche. Standard- und
Zählerarchive bleiben ausdrücklich getrennt; kein Verbrauch aus Zählerständen.
Fehlende Stunden bleiben leer, echte Nullwerte erhalten. Beide wiederholten
lokalen DST-Stunden tragen wie bisher zur selben Wochentag-/Stundenzelle bei.

## Navigation, Ausgaben und Vorschauen

Die vorhandene authentifizierte, nur lesende State-Navigation verschiebt neue
Fenster um die exakte angeforderte Dauer in Sekunden, erst danach werden ganze
Stunden bestimmt. Bis zu 120 Schritte, niemals vor die unterstützte Epoche.
Bei angeschnittenen Randstunden gibt es daher keine künstlich nahtlose Folge
von Messwerten. Die Anzeige nennt tatsächliche angeforderte Grenzen samt
Uhrzeit, UTC-Offset und Zeitzone; der Tooltip benennt das ausgeschlossene Ende.
Der Rücksetzknopf heißt bei festen Grenzen „Ausgewählter Zeitraum“.
Navigation ist browserlokal und verändert keine Instanz- oder Archivwerte.

Schema 1 bleibt erhalten. Neue Metadaten für angeforderte Grenzen und Dauer
ergänzen nur neue Modi. Ein Ansichtsschlüssel berücksichtigt Quelle, aktive
Zeitplanung, Kennzahl, Formatierung und Design, nicht den beweglichen Zeitpunkt
relativer Fenster. Geänderte Konfigurationen verwerfen alte Antworten; normale
Refreshes erhalten den navigierten Ausschnitt. Kachel und IPSView verwenden
weiter ihre eigenen Designs. Beide SVG-Entwurfsvorschauen verwenden dieselbe
Planung, lesen keine Archive und zeigen bei ungültigen Grenzen nur einen Hinweis.

Die existierende ECharts-6.1.0-Heatmap-Runtime und ihre APIs bleiben unverändert.
Weder neue Fremdkomponenten noch neue Gateway-Properties oder Änderungen an
synchronisierten Helpern sind erforderlich.

## Nachweise und Roadmap

`heatmap_window.php` begann mit einem roten Test für den bisher unbekannten
Modus. Es prüft anschließend ganze Randstunden, leere Kurzfenster, exakte
Navigation, stabile historische Grenzen, strikte Dates, 23-/25-Stunden-Tage,
366/367 Tage und unveränderte Altfenster trotz inaktiver neuer Felder.
`heatmap_periods.php` testet den echten Modul-/Gateway-Pfad mit Kernel-Doppeln:
vier Kennzahlen, beide Ausgaben/WebContent, unabhängige Farben, Navigation,
Refresh und reine Formularentwürfe. Ein dichtes 8784-Stunden-Archiv braucht
genau sieben zulässige Abfragen; gekürzte oder übergroße siebte Antworten
dürfen kein Teilmodell liefern. Alte Kennzahl- und Fehlerprüfungen bleiben aktiv.

`heatmap_layout.js` prüft beide Modi/Ausgaben, schmale und breite Renderer,
echtes gepinntes ECharts, Zeitbeschriftung, Navigation und verspätete Antworten
bei geänderter Dauer, Grenze, Quelle oder Design. `heatmap_browser.mjs` bietet
synthetische Canvas-Prüfungen ohne Symcon-Zugriff. Die lokalen 400×600-/790×440-
Ansichten sowie Navigation und Reset wurden geprüft; lange Zeitraumtexte dürfen
die Bedienelemente nicht verdrängen. Das ersetzt keinen realen Kachel-/IPSView-Test.

Calendar ist nach MCP-Prüfung und Eigentümerbestätigung in Kachel/IPSView
abgenommen. Auch Heatmap ist inzwischen funktional per MCP geprüft und vom Eigentümer in
Kachel/IPSView bestätigt. Die separaten Vergleichsansichten folgen ADR 0106 und
sind ebenfalls abgenommen. Den Gesamtstatus führt die
[abgeschlossene Roadmap](../NUTZUNGSGRENZEN.md).
Automatisierte Budgetprüfungen sind keine Live-Lastfreigabe.

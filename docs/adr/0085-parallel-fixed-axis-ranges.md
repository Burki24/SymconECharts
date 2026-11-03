# ADR 0085: Individuelle feste Achsenbereiche für Parallel

- Status: Angenommen
- Datum: 2026-10-21
- Entscheider: Burki24
- Ergänzt: ADR 0050, 0078, 0084 und 0036

## Vertrag und Kompatibilität

Quellenzeilen in `Sources` erhalten additive Felder `AxisRangeMode` (`auto` oder
`manual`), `AxisMinimum` und `AxisMaximum`. Fehlender Modus bedeutet weiterhin
automatische ECharts-Skalierung; inaktives Minimum/Maximum wird ignoriert.
Neue Zeilen beginnen mit `auto` und vorbereiteten Grenzen 0/100. Feste Achsen
benötigen beide expliziten numerischen Grenzen; fehlende, null, boolesche oder
als Text gespeicherte Grenzen werden nicht als Zahlen oder Defaults interpretiert.
Unbekannte Modi sind ungültig. GUID, Präfix, Properties, Idents und sämtliche
Design-Defaults bleiben erhalten; keine Migration schreibt Quellenlisten um.

Die Grenze gehört zur Variablen-ID und ihrer Quellenzeile, nicht zur Position,
Beschriftung oder Einheitengruppe. Kachel und IPSView teilen die fachlichen
Achsenbereiche, behalten aber ihre bisherigen unabhängigen Designs. Es werden
keine individuellen Kennzahlen, Normalisierung oder inversen Achsen eingeführt.

## Gemeinsame numerische Prüfung

`libs/EChartsValueRange.php` prüft ausschließlich int/float, Endlichkeit,
nach float-Konvertierung strikt aufsteigende Grenzen und eine endliche Spanne.
Damit werden auch überlaufende Differenzen und nach float-Konvertierung
zusammenfallende große Integergrenzen abgewiesen. Parallel verwendet die
Prüfung vor Archivzugriffen und in der reinen Entwurfsvorschau.

`EChartsNormalization::Range()` delegiert seine identische bisherige Prüfung
an diese gemeinsame Funktion. Defaults, Fehlermeldung, Projektion und
Originalmetadaten der Normalisierung bleiben unverändert. Die gemeinsame
Prüfung aktiviert keine Normalisierung in Parallel und verschiebt keine
Familienlogik ins Gateway. Synchronisierte Helper bleiben unverändert.

## Daten und native Geometrie

Nur feste Modellachsen erhalten additive `minimum`/`maximum`. Automatische
Altmodelle behalten ihre bisherigen Felder. Sobald feste Achsen vorhanden sind,
enthält `parallel.outOfRangeCount` die Anzahl strikt außerhalb liegender Werte
aller gepaarten Zeilen und aller Achsen, einschließlich unsichtbarer Achsen.
Grenzwerte selbst zählen nicht als Überschreitungen. Nicht gemeinsame oder
budgetbedingt ausgelassene Intervalle werden nicht als sichtbare Daten gezählt.

Jeder Archivwert bleibt unverändert in `rows.values` und in der nativen
ECharts-Serie. Nur native Achsen bekommen feste `min`/`max` und `scale=true`.
Es gibt keine Begrenzung der Daten auf Randwerte: Eine solche Ersetzung würde
auch native Achsenmarkierungen verfälschen, etwa 125 als ausgewählte 100 behandeln.
Feste Skalen begrenzen den sichtbaren Bereich; eine komplett außerhalb liegende
Linie kann unsichtbar sein. Die Warnung zählt Überschreitungen, Tooltip-Zeilen
erhalten Originalwert/Einheit und einen Überschreitungshinweis. Warnung und
Archivkürzung werden gemeinsam angezeigt, mit Platz oberhalb der Navigation.

Die gepinnte native Parallel-View entfernt ihren Startclip nach der Animation.
Ein zusätzlicher dauerhafter Clip nur an der Serienwurzel verhindert deshalb
überlaufende Linien in Titel-, Achsentitel- und Warnbereichen. Der Renderer nutzt
den exportierten `registerPostUpdate`-Hook, die ExtensionAPI-Serienansicht,
deren Root-Gruppe und ein `graphic.Rect` der tatsächlichen Plotgeometrie.
Private `_dataGroup`-Felder und die Runtime-Dateien werden nicht verändert.
Extrem kleine endliche Bereiche können die native numerische Skalenplanung
dennoch überfordern. Rendererfehler werden sichtbar und wiederherstellbar
abgewiesen, statt Grenzen automatisch zu erweitern oder Daten zu ersetzen.
Der zusätzliche Clip wird bei Rückkehr zu automatischen Achsen entfernt und
bei Layout-/Titelupdates erneuert. Als versionierte Primärquellen dienen
[ParallelView](https://github.com/apache/echarts/blob/6.1.0/src/chart/parallel/ParallelView.ts)
und [ECharts-ExtensionAPI/Lifecycle](https://github.com/apache/echarts/blob/6.1.0/src/core/echarts.ts).

Verschieben aktualisiert weiterhin nur die Titelposition. Feste Grenzen bleiben
beim Refresh, Zeitraumwechsel und Resize unverändert. Eine Rückkehr zu `auto`
entfernt alte feste native Grenzen. Gemeinsame Intervalle, Mittel-/Verbrauchs-
Regeln, Stunden/Tage, absolute/relative Zeitplanung, 16 Achsen und das gemeinsame
10000-Aggregate-Budget bleiben unverändert; keine zusätzlichen Archivabfragen.

## Formular und Vorschau

Die Quellenliste zeigt den Modus, der Einzelzeileneditor zusätzlich beide
Grenzen. Verdeckte gespeicherte Spalten erhalten diese Werte beim Bearbeiten.
Beide Vorschauen verwenden dieselbe Bereichsprüfung für alle Quellen, auch
außerhalb des Sechs-Achsen-Ausschnitts. Angezeigte feste Achsen kennzeichnen
Grenzen in SVG-Metadaten und Beschriftungen. Beispielkurven bleiben ausdrücklich
schematisch; weder aktuelle Werte noch Archive werden dafür geladen.
Ungültige Entwürfe zeigen eine Meldung und keine scheinbar gültigen Kurven.
Formularänderungen aktualisieren Bilder, nicht gespeicherte Konfigurationen.

## Nachweise und Abnahme

Der neue öffentliche Modul-/Gateway-Test begann RED an den fehlenden
Grenzfeldern. Er prüft Altmodelle, gemischte Modi, gleichnamige/gleichartige
Quellen, ID-stabile Reihenfolge, Originalwerte, Grenzwerte und Überschreitungen,
ungültige Grenzen auf der 16. Achse vor I/O, Wiederanlauf, unabhängige Designs,
beide Ausgaben/IPSView-WebContent, Vorschauen und nicht gespeicherte Entwürfe.
Normalisierung behält ihre bisherigen positiven/negativen Grenztests.

Der Renderer-Test begann RED an den fehlenden nativen `min`/`max`. Die gepinnte
ECharts-Runtime prüft feste tatsächliche Skalen, unveränderte Daten,
Achsenmarkierungen, Panning und Rückkehr zu automatisch. Die vorhandene
16×625-Lastmatrix und historischen Zeitraumtests bleiben Regressionen.
Lokale Canvas-Prüfungen ergänzen dies, ersetzen aber keine tatsächliche
Symcon-/IPSView-Live-Abnahme der neuen Skalen nach Modulupdate.

Die 16-Achsen-Ansicht selbst ist inzwischen per MCP funktional geprüft und
vom Eigentümer in Kachel und IPSView bestätigt. Ein voller 10000-Punkte-
Live-Lasttest liegt wegen der kurzen vorhandenen Archive nicht vor.
Die damalige Folgeerweiterung um Kennzahlen je Achse ist gemäß ADR 0086
ebenfalls umgesetzt und funktional abgenommen.

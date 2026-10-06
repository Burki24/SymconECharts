# ADR 0018: Erste Zeitreihenfamilie und Archivvertrag festlegen

- Status: Angenommen
- Datum: 2026-10-06
- Entscheider: Burki24
- Ergänzt: ADR 0001, ADR 0002, ADR 0005 und ADR 0017
- Ersetzt durch: –

## Kontext

Die Gauge-Familie deckt numerische Momentanwerte ab. Als nächste fachliche
Ausbaustufe soll SymconECharts historische Werte aus dem vorhandenen Symcon-
Archiv darstellen. Damit entsteht erstmals ein realer Consumer für die in
ADR 0002 bewusst zurückgestellte Archiv- und Cache-Funktion des Gateways.

Symcon stellt Rohwerte über `AC_GetLoggedValues()` und voraggregierte Werte
über `AC_GetAggregatedValues()` bereit. Beide Funktionen liefern höchstens
10.000 Datensätze und sortieren vom neuesten zum ältesten Wert. Rohwertabfragen
können das System erheblich belasten; die offizielle Dokumentation empfiehlt
deshalb, soweit möglich voraggregierte Werte zu verwenden. Aggregierte
Standardwerte enthalten Durchschnitt, Minimum und Maximum. Bei als Zähler
geführten Variablen entspricht das Feld `Avg` dagegen der Summe der positiven
Differenzen.

Eine Zeitreiheninstanz benötigt einen anderen fachlichen Vertrag als ein
Gauge: Zeitraum, Verdichtung, Zeitachse, mehrere Datenreihen und ein begrenztes
Punktbudget müssen gemeinsam festgelegt werden. Gleichzeitig dürfen bereits
gelöste Ausgabe- und Theme-Funktionen gemäß ADR 0017 nicht erneut parallel
implementiert werden.

## Wiederverwendungsprüfung nach ADR 0017

Folgende vorhandene Funktionen sind semantisch kompatibel und sollen
weiterverwendet beziehungsweise bei der Implementierung zuerst aus den
fertigen Charts in projektspezifische Bausteine unter `libs` überführt werden:

- der versionierte Transport über `DataFlowHelper` und
  `EChartsDataProtocol`;
- der lokale, integritätsgeprüfte ECharts-Assetkatalog und die offiziellen
  Theme-IDs aus `EChartsAsset`;
- die HTML- und IPSView-Ausgabegrundlagen aus `IPSViewHTMLPageHelper`,
  `VisualizationAssetHelper`, `VisualizationThemeHelper` und
  `ResponsiveVisualizationHelper`;
- das Muster für getrenntes Kachel- und IPSView-Design;
- Variablenreferenzen, Beschriftung, Einheit und Nachkommastellen aus der
  nativen Variablendarstellung;
- Resize-, Fehler-, Verbindungs- und Zugänglichkeitsbehandlung im Browser.

Gauge-Geometrie, Zeiger, Naben, Skalenbögen, Zifferblattplatten und
`EChartsGaugeDesign` sind nicht Teil des gemeinsamen Zeitreihenvertrags. Eine
neue universelle Rendererabstraktion wird nicht vorab eingeführt. Erst
tatsächlich identische Teile werden extrahiert und mit beiden bestehenden
Verwendungsfällen getestet.

Die Archivabfrage ist Symcon-spezifisch, aber ihr fachlicher ECharts-Vertrag
gehört zunächst zum Projekt. Eine Überführung nach `Symcon_ModuleHelper` wird
erst geprüft, wenn ein zweites unabhängiges Modul denselben validierten
Abfragevertrag benötigt.

## Entscheidung

### Modul und fachlicher Umfang

Die erste historische Chartfamilie wird als eigenes Gerätemodul
`EChartsTimeSeries` mit dem vorgeschlagenen Präfix `ECTS` angelegt. Eine
Instanz stellt genau ein Zeitreihendiagramm in einer nativen Symcon-Kachel und
optional in einem eigenen IPSView-WebContent-Widget dar.

Die Modul-GUID ist `{EF172F3B-50F5-41D1-B18E-6BCDEAECCABA}`. Das Modul
verwendet dieselben versionierten Datenfluss-IDs wie die anderen
ECharts-Geräte; der neue Präfix `ECTS` ist damit verbindlich vergeben.

Die erste Version umfasst:

- eine bis acht numerische Symcon-Variablen;
- Linien- und gefüllte Flächendarstellung je Datenreihe;
- rollende Zeiträume von 1 Stunde, 6 Stunden, 24 Stunden, 7 Tagen und 30 Tagen;
- gemeinsame Zeitachse, Legende, Tooltip und optionalen internen Zoom;
- höchstens zwei unterschiedliche Einheitengruppen und damit höchstens zwei
  Y-Achsen;
- eine ausdrückliche Auswahl zwischen Rohdaten, automatischer Verdichtung und
  einer manuell gewählten Aggregationsstufe;
- Durchschnitt beziehungsweise Zählersumme, Minimum oder Maximum als
  darzustellenden aggregierten Wert;
- getrenntes Kachel- und IPSView-Design auf demselben fachlichen Datenmodell.

Der nachträglich ergänzte Echtzeitmodus für Variablen ohne aktivierte
Archivierung ist als eigenständige Semantik in
[`ADR 0019`](0019-timeseries-realtime-without-archive.md) festgelegt. Er
verändert insbesondere nicht die hier definierte Bedeutung von `raw`.

Kreis-, Balken-, Kalender-, Streu- und kombinierte Diagramme, frei gewählte
absolute Zeiträume, serverseitige Formeln sowie mehr als zwei Einheitengruppen
sind nicht Teil der ersten vertikalen Umsetzung. Sie benötigen erst nach der
Laufzeitmessung dieser Basis eine eigene Erweiterungsentscheidung.

### Persistenter Quellenvertrag

Die Instanz speichert eine geordnete `Sources`-Liste. Jeder Eintrag enthält
mindestens:

- `VariableID`: eindeutige vorhandene Integer- oder Float-Variable;
- `Label`: optionale Beschriftung, leer verwendet den Variablennamen;
- `Unit`: manuelle Einheit als Rückfall;
- `Decimals`: manuelle Nachkommastellen als Rückfall;
- `UseVariablePresentation`: Übernahme kompatibler Darstellungsparameter;
- `Color`: optionale Serienfarbe;
- `Style`: `line` oder `area`;
- `Reducer`: `auto`, `average`, `sum`, `minimum` oder `maximum`.

Doppelte Variablen sind nicht zulässig. Die Reihenfolge bestimmt Legende,
Farbzuordnung und Achsenzuordnung. Einheiten werden nach ihrer effektiven
Zeichenfolge gruppiert; mehr als zwei Gruppen führen zu einem eindeutigen
Konfigurationsfehler.

### Zeitraum, Punktbudget und Verdichtung

Jede Instanz besitzt ein Gesamtpunktbudget für alle Datenreihen. Festgelegt
sind 2.000 Punkte als Standard, ein gültiger Bereich von 200 bis 8.000 Punkten
und höchstens 2.000 Punkte je Reihe. Das Modul verteilt das Budget
deterministisch auf die konfigurierten Quellen.

Der Anwender wählt den Datenmodus der Instanz selbst:

- `raw`: unveränderte Archivwerte aus `AC_GetLoggedValues()`;
- `auto`: automatische Wahl einer passenden Aggregationsstufe;
- `minute`, `five-minutes`, `fifteen-minutes`, `hour` oder `day`: ausdrücklich
  gewählte Aggregationsstufe.

`raw` ist ein gleichwertiger Darstellungsweg und kein interner Notfallmodus.
Das Modul darf eine ausdrückliche Rohdatenauswahl niemals still auf
aggregierte Werte umstellen. Der Reducer ist bei Rohdaten ohne Bedeutung; der
unveränderte Wert jedes zurückgegebenen Archivdatensatzes wird verwendet.
Zeitraum und Punktbudget gelten weiterhin als Schutz vor unkontrollierten
Datenmengen. Überschreitet die Anzahl der Rohwerte das wirksame Budget, liefert
das Gateway die neuesten Rohwerte ohne Verdichtung mit `Truncated: true`. Die
Visualisierung weist sichtbar auf die unvollständige Zeitspanne hin, damit der
Anwender das Punktbudget erhöhen oder einen kürzeren Zeitraum wählen kann.

Im Modus `auto` wählt das Gerät anhand von Zeitraum und verfügbarem Budget die
feinste gemeinsame Symcon-Aggregationsstufe, deren theoretische Anzahl von
Zeitfenstern das Budget nicht überschreitet. Dafür stehen zunächst eine,
fünf und fünfzehn Minuten sowie Stunde und Tag zur Verfügung. Die
15-Minuten-Stufe ist auf der unterstützten Zielplattform Symcon 9.0/9.1
verfügbar. Nur `auto` darf die Verdichtungsstufe selbst bestimmen.

Bei einem Standardarchivwert bedeutet `auto` den Durchschnitt. Bei einem
Zähler bedeutet `auto` die von Symcon im Feld `Avg` gelieferte Summe der
positiven Differenzen. Diese Semantik wird im fachlichen Modell ausdrücklich
als `average` beziehungsweise `sum` ausgewiesen und nicht allein aus dem
Feldnamen `Avg` abgeleitet. `average` ist für Zähler und `sum` für
Standardvariablen unzulässig; eine Auswahl wird nicht still in die jeweils
andere Bedeutung umgedeutet.

### Gateway-Operation `archive.read`

Der bestehende Protokollstand 1 wird kompatibel um die Operation
`archive.read` ergänzt. Das Ergänzen einer bekannten Operation verändert die
Transporthülle nicht und erfordert deshalb keine neue Protokollversion.

Eine Anfrage enthält:

```json
{
  "ProtocolVersion": 1,
  "Operation": "archive.read",
  "Payload": {
    "VariableID": 12345,
    "StartTimestamp": 1780869600,
    "EndTimestamp": 1780956000,
    "Mode": "aggregated",
    "AggregationLevel": 6,
    "Reducer": "auto",
    "Limit": 1000
  }
}
```

`Mode` ist `raw` oder `aggregated`. Bei `raw` entfällt
`AggregationLevel`. Gültige Aggregationsstufen sind die von der Zielplattform
unterstützten Stufen 0, 1, 5, 6 und 8. Start und Ende sind positive Unix-
Zeitstempel mit `StartTimestamp < EndTimestamp`. `Limit` ist positiv und wird
zusätzlich durch das Gateway begrenzt. `raw` wird ausschließlich über
`AC_GetLoggedValues()` bedient; `aggregated` ausschließlich über
`AC_GetAggregatedValues()`.

Eine erfolgreiche Antwort enthält normalisierte, zeitlich aufsteigend
sortierte Punkte:

```json
{
  "ProtocolVersion": 1,
  "Operation": "archive.read",
  "Success": true,
  "Payload": {
    "VariableID": 12345,
    "VariableType": 2,
    "ArchiveAggregationType": "standard",
    "EffectiveReducer": "average",
    "StartTimestamp": 1780869600,
    "EndTimestamp": 1780956000,
    "Points": [
      [1780869600, 18.4],
      [1780869660, 18.5]
    ],
    "Truncated": false
  }
}
```

Der Gateway-Adapter wandelt die Symcon-Felder in ein einheitliches
`[timestamp, value]`-Format um. Interne Archiv-IDs, `Avg`-Sondersemantik und
die absteigende Sortierung der Symcon-API werden nicht an Renderer
weitergereicht. Bei aggregierten Minimum- und Maximumreihen bleibt der Beginn
des Aggregationsfensters aus `TimeStamp` der X-Wert; `MinTime` und `MaxTime`
verschieben den Punkt nicht aus seinem gemeinsamen Zeitraster. Zur Erkennung
einer Kürzung fragt das Gateway intern höchstens einen Datensatz mehr als das
wirksame Limit ab und entfernt diesen Zusatz vor der Antwort. Es werden keine
künstlichen Zwischenwerte erzeugt. Eine
leere, aber gültige Archivantwort bleibt eine erfolgreiche Antwort mit leerem
`Points`-Array.

Strukturierte Fehler unterscheiden mindestens fehlende Archivinstanz,
fehlende oder nicht numerische Variable, ungültigen Zeitraum, ungültige
Verdichtung, nicht archivierte Quelle und fehlgeschlagene Archivabfrage. Bei
mehreren Quellen ruft das Gerät die Einquellenoperation je Quelle auf. Eine
Bulk-Operation wird erst bei nachgewiesenem Konsistenz- oder
Leistungsbedarf eingeführt.

### Begrenzter Gateway-Cache

Das Gateway darf erfolgreiche `archive.read`-Antworten kurzzeitig anhand der
vollständig normalisierten Anfrage zwischenspeichern. Der erste Cache ist
speicherbasiert, pro Gateway-Instanz auf 32 Einträge begrenzt und verwendet
eine Lebensdauer von höchstens 15 Sekunden. Fehlerantworten werden nicht
gespeichert. Es werden weder Archivdaten persistiert noch Änderungen am
Symcon-Archiv vorgenommen.

Diese Grenzen dienen der Zusammenfassung nahezu gleichzeitiger identischer
Kachel- und IPSView-Abfragen. Eine weitergehende Cache-Strategie benötigt
Messwerte aus der realen Laufzeit und eine eigene Entscheidung.

### Fachliches Diagrammmodell und Live-Fortschreibung

Das Gerät liefert ein Modell mit `schemaVersion: 1`,
`family: time-series`, Zeitfenster, Achsengruppen und einer geordneten
`series`-Liste. Jede Reihe besitzt eine stabile ID auf Basis der Variablen-ID,
Formatierungsdaten, Darstellungsstil, effektiven Reducer und die aufsteigend
sortierten Punkte.

Nach dem initialen Archivabruf werden `VM_UPDATE`-Ereignisse als neuer oder
bei gleichem Zeitstempel ersetzter Punkt an den Browser übertragen, sofern die
Reihe Rohwerte darstellt. Der Browser entfernt Punkte außerhalb des rollenden
Zeitfensters. Ein vollständiger Archivabruf bei jeder Wertänderung ist
ausgeschlossen. Bei aggregierter Darstellung darf ein Momentanwert nicht als
scheinbarer Durchschnitt oder Zählersumme in die Reihe gemischt werden.
Aggregierte Reihen enden deshalb am letzten vollständig abgeschlossenen
Zeitfenster. Nach der jeweils nächsten Intervallgrenze lädt das Gerät das neu
abgeschlossene Fenster nach.

Zeitstempel bleiben im Transport Unix-Zeitstempel. Ihre Beschriftung erfolgt
im Browser in der lokalen Zeitzone. Sommerzeitgrenzen verändern keine
gespeicherten Zeitpunkte.

## Kompatibilität und Sicherheit

- `current.read` und alle vorhandenen Gauge-Verträge bleiben unverändert.
- Die neue Operation liest ausschließlich. Sie aktiviert kein Logging,
  verändert keine Aggregationsart und schreibt keine Archivdaten.
- Archivzugriffe liegen ausschließlich im Gateway; Renderer erhalten keine
  Archive-Control-ID und rufen keine Symcon-Funktion direkt auf.
- Der neue ECharts-Build wird vor der Implementierung erneut anhand der
  offiziellen ECharts-Einstiege auf Version, API, Lizenz, Größe und Integrität
  geprüft. Der vorhandene Gauge-Build wird nicht stillschweigend ersetzt.
- Nicht archivierte oder leere Quellen werden sichtbar gemeldet und nicht mit
  erfundenen Daten aufgefüllt.
- Eine vom Anwender gewählte Rohdatenabfrage wird weder durch den Gateway-Cache
  noch durch das Gerät in aggregierte Werte umgewandelt.

## Folgen

Die erste Zeitreihenfamilie bildet eine schmale, messbare Vertikale vom
Symcon-Archiv bis zu beiden Ausgabewegen. Der Gateway erhält erstmals eine
begründete Archivzuständigkeit, ohne Chartkonfiguration oder Rendererlogik zu
übernehmen.

Die Begrenzung auf zwei Einheitengruppen und ein Gesamtpunktbudget hält
Achsenbild, HTML-Größe und Browserlast kontrollierbar. Rohwertabfragen bleiben
möglich, werden aber nicht zum automatischen Standard. Weitere Diagrammtypen
können später auf dem Archivvertrag aufbauen, müssen jedoch weiterhin als
eigene Chartfamilie oder ausdrücklich kompatible Erweiterung entschieden
werden.

## Bestätigte Ausgangsgrenzen

- Modulname `EChartsTimeSeries` und Präfix `ECTS`;
- eine bis acht Quellen und höchstens zwei Einheitengruppen;
- Punktbudget von 200 bis 8.000 mit Standard 2.000;
- die fünf rollenden Zeiträume sowie die Anwenderauswahl zwischen Rohdaten,
  Automatik und festen Aggregationsstufen;
- Cachegrenzen von 32 Einträgen und 15 Sekunden;
- Aggregierte Reihen zeigen ausschließlich abgeschlossene Zeitfenster. Das
  Gerät setzt das Abfrageende auf die letzte abgeschlossene Intervallgrenze
  und lädt nach jeder folgenden Grenze das neu abgeschlossene Fenster nach.
  Dadurch wird kein einzelner Momentanwert als scheinbarer Durchschnitt oder
  Zählersumme dargestellt.

## Nachweise und Quellen

- [Symcon `AC_GetLoggedValues`](https://www.symcon.de/de/service/dokumentation/modulreferenz/kern-instanzen/archive-control/ac-getloggedvalues/)
- [Symcon `AC_GetAggregatedValues`](https://www.symcon.de/de/service/dokumentation/modulreferenz/kern-instanzen/archive-control/ac-getaggregatedvalues/)
- [Symcon `AC_GetAggregationType`](https://www.symcon.de/de/service/dokumentation/modulreferenz/kern-instanzen/archive-control/ac-getaggregationtype/)
- [Symcon `AC_GetLoggingStatus`](https://www.symcon.de/de/service/dokumentation/modulreferenz/kern-instanzen/archive-control/ac-getloggingstatus/)
- Bestehender Protokollvertrag in `libs/EChartsDataProtocol.php`
- Wiederverwendungsentscheidung in
  [`ADR 0017`](0017-reuse-before-new-chart-development.md)

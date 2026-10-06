# ADR 0022: Zeitreihe getrennt in IPSView ausgeben und gestalten

- Status: Teilweise ersetzt durch ADR 0024 für Laufzeitaktualisierungen und ADR 0025 für den gemeinsamen Hintergrundvertrag
- Datum: 2026-10-06
- Entscheider: Burki24
- Ergänzt: ADR 0008, ADR 0017, ADR 0018, ADR 0019 und ADR 0020
- Ersetzt durch: –

## Kontext

`EChartsTimeSeries` besitzt ein gemeinsames Quellen-, Archiv-, Achsen- und
Punktbudgetmodell sowie einen nativen Kacheldesigner. Dieselbe Zeitreihe soll
zusätzlich als frei platzierbares IPSView-HTML-Widget verwendbar sein, ohne
eine zweite Diagramminstanz und damit eine doppelte Datenkonfiguration
anzulegen. Kachel und IPSView können jedoch unterschiedliche visuelle
Umgebungen besitzen.

Die Gauge-Module haben die benötigte Ausgabe bereits praktisch erprobt. Nach
ADR 0017 werden deshalb der synchronisierte `IPSViewHTMLPageHelper`, der
gemeinsame HTML-Renderer und das Muster eines vererbten oder unabhängigen
Designs wiederverwendet. Zeitreihenspezifische Properties und Vorschauen
verbleiben im Gerätemodul.

## Entscheidung

- Eine TimeSeries-Instanz kann optional genau eine Stringvariable mit
  WebContent-Darstellung und dem stabilen Ident `IPSViewTimeSeries` verwalten.
- Die Ausgabe verwendet dieselben Quellen, Zeiträume, Datenmodi, Achsen,
  Reducer und Punktbudgets wie die native Kachel.
- Standardmäßig erbt IPSView Theme, Zoom und den vollständigen
  Zeitreihen-Designvertrag der Kachel.
- Der Anwender kann die Vererbung deaktivieren. Theme, Legendenposition, Zoom,
  Linienstärke, Glättung, Datenpunkte, Flächendeckkraft, Raster und
  Achsensichtbarkeit werden dann als eigene typisierte IPSView-Properties
  gespeichert.
- Unabhängig von der Designvererbung kann IPSView seinen realen Hintergrund
  durchscheinen lassen. Die Grundfarbe stammt wahlweise aus dem Theme oder
  einem eigenen Farbwähler. Eine eigene Deckkraft von 0 bis 100 Prozent mischt
  diese Farbe genau einmal auf der äußeren Diagrammfläche ein; die ECharts-
  Canvas bleibt dabei transparent. Die native Kachel wird nicht verändert.
- Eine Formularaktion kopiert das aktuelle Kacheldesign in die unabhängigen
  IPSView-Properties. Beide Designer bleiben standardmäßig kollabiert und
  besitzen eine sofort aktualisierte SVG-Vorschau.
- Aktivierung, Beibehaltung beim Abschalten, bestätigte Löschung und manuelle
  Regeneration der WebContent-Variable verbleiben beim unveränderten
  `IPSViewHTMLPageHelper`.
- Das initiale WebContent bleibt ein vollständiges eigenständiges
  HTML-Dokument. Laufzeitaktualisierungen erfolgen gemäß ADR 0024 persistent;
  `raw` und `realtime` ergänzen neue Punkte im geöffneten IPSView-Browser per
  `append`, ohne das Dokument und seine lokale Punktfolge neu zu laden.

## Folgen

Kachel und IPSView können dasselbe Diagramm mit unterschiedlicher Gestaltung
zeigen, ohne Datenquellen zu duplizieren. Der kurze Gateway-Cache aus ADR 0018
fasst nahezu gleichzeitige identische Archivabfragen beider Ausgabewege
weiterhin zusammen.

Der persistente Vertrag wächst additiv um IPSView-Properties. Bestehende
Instanzen bleiben kompatibel, weil die Ausgabe standardmäßig deaktiviert ist
und das IPSView-Design bei Aktivierung zunächst das Kacheldesign erbt. Die
lokalen Integrationstests prüfen WebContent-Erzeugung, Vererbung, unabhängiges
Design und beide Formularvorschauen. Ein realer IPSView-Laufzeittest bleibt
mangels Lizenz als Testlücke dokumentiert.

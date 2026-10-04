# ADR 0007: ECharts-Theme-Assets und instanzbezogene Auswahl festlegen

- Status: Angenommen
- Datum: 2026-10-04
- Entscheider: Burki24
- Ergänzt: ADR 0005 und ADR 0006
- Ersetzt durch: –

## Kontext

Apache ECharts unterscheidet zwischen den chartbezogenen Optionen und einem
bei der Initialisierung registrierten Theme. Die offizielle Download-Seite
bietet Dark, Vintage, Macarons, Infographic, Shine und Roma an. Der offizielle
Theme Builder erzeugt darüber hinaus eigene JavaScript-Dateien, die sich über
`echarts.registerTheme()` registrieren.

Gauge Single besitzt bereits layoutbezogene Presets. Preset und Theme dürfen
nicht zu einem kombinierten Modus werden: Dasselbe Layout soll mit
unterschiedlichen Farb- und Schriftvorgaben funktionieren. Gleichzeitig muss
die Visualisierung ohne CDN und unter dem Symcon-Output-Buffer-Limit bleiben.

## Entscheidung

`EChartsGaugeSingle` erhält die persistente String-Property `EChartsTheme`.
Die stabilen IDs dieser Stufe sind:

- `auto`: verwendet weiterhin die gemeinsamen Symcon-Designtokens;
- `dark`;
- `vintage`;
- `macarons`;
- `infographic`;
- `shine`;
- `roma`.

`auto` ist der Default und bewahrt damit das bisherige Erscheinungsbild. Eine
unbekannte Theme-ID führt zum Modulstatus `206`. Die Theme-ID wird im
Gauge-Datenmodell als oberstes Feld `theme` übertragen; das layoutbezogene
`gauge.preset` bleibt unverändert.

Die sechs offiziellen JavaScript-Dateien stammen aus dem bereits
festgeschriebenen npm-Paket `echarts@6.1.0`. Sie werden durch den vorhandenen
Buildprozess reproduzierbar nach `libs/echarts/6.1.0/themes` übernommen.
`libs/EChartsAsset.php` ist der gemeinsame projektspezifische Katalog für IDs,
Integritätswerte, Browser-Assets und die für eigene Layouts benötigten
Grundfarben. Dies ist keine Aufgabe der synchronisierten
`Symcon_ModuleHelper`.

Die native Kachel registriert die unveränderten offiziellen Theme-Dateien und
initialisiert ECharts mit der ausgewählten ID. Alle unterstützten Themes werden
lokal in die HTML-SDK-Seite eingebettet, damit eine bereits geöffnete Kachel
nach `ApplyChanges()` ohne externen Abruf auf eine andere Theme-ID wechseln
kann. Bei `auto` bleibt ECharts ohne benanntes Theme und der Renderer setzt die
Symcon-Farbtokens ausdrücklich. Bei offiziellen Themes bleiben die
Theme-Konfiguration und insbesondere ihre Palette sowie Gauge-Segmentierung
maßgeblich. Der Renderer ergänzt aus dem gemeinsamen Katalog Hintergrund- und
Kontrastfarben für Text, Skala und das Speed-Detail. Das ist erforderlich,
weil mehrere offizielle Themes einen hellen Hintergrund oder die Geometrie
ihres ursprünglichen Gauge-Defaults voraussetzen. Die Apache-Dateien selbst
bleiben unverändert.

Die SVG-Formularvorschau führt die Browser-Runtime nicht aus. Sie verwendet
deshalb dieselben festgelegten Grundfarben und bleibt eine Annäherung. Die
native Canvas-Kachel ist für die tatsächliche Theme-Ausgabe maßgeblich.

Die Theme-Auswahl gehört zur jeweiligen Diagramminstanz. Das Gateway erhält
keine globale Theme-Property, weil mehrere Diagramme am selben Gateway
unterschiedliche Themes verwenden dürfen. Die Assets und ihr Katalog sind
dagegen familienübergreifend wiederverwendbar.

## Theme Builder und eigene Dateien

Eine Builder-Datei ist ausführbarer JavaScript-Code und nicht lediglich eine
Palette. Eigene Dateien werden in dieser Stufe weder ungeprüft gespeichert noch
in einer Visualisierung ausgeführt. Ein späterer Import benötigt einen eigenen
Vertrag für explizite Bestätigung, Größenbegrenzung, Validierung, Namenskonflikte,
Speicherort, Fehlerbehandlung und sichere Entfernung. Diese Grenze verhindert
nicht die fest eingebauten, anhand von Herkunft und SHA-256 geprüften
Apache-Dateien.

## Folgen

Gauge Single kann jedes der vier Layout-Presets unabhängig mit jedem der
sieben Theme-Modi kombinieren. Andere Chartfamilien können denselben
`EChartsAsset`-Katalog verwenden, ohne die Gauge-Optionen zu übernehmen. Erst
wenn ein sicherer benutzerdefinierter Theme-Katalog als gemeinsamer Dienst
umgesetzt wird, ist eine zusätzliche Gateway-Zuständigkeit zu entscheiden.

Alle Theme-Dateien erhöhen die HTML-SDK-Ausgabe nur innerhalb des weiterhin
geprüften Output-Buffer-Budgets. ECharts-Version, Dateien, Prüfsummen und
Lizenznachweis werden gemeinsam aktualisiert.

## Nachweise

- Assetherkunft und Integrität unter `tests/echarts_assets.php`
- Property-, Formular- und Statusvertrag unter `tests/symcon_strict.php`
- Datenmodell, SVG-Vorschau, HTML-Einbettung und Output-Buffer unter
  `tests/gateway_gauges.php`
- offizielle Theme-Dokumentation unter
  `https://echarts.apache.org/handbook/en/concepts/style/`
- offizielle Theme-Auswahl unter
  `https://echarts.apache.org/en/download-theme.html`
- offizieller Theme Builder unter
  `https://echarts.apache.org/en/theme-builder.html`

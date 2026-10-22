# Diagrammfamilienspezifische ECharts-Runtimes erzeugen

`npm run build:bar-category-app` erzeugt zusätzlich das eigene BarCategory-
Anwendungsskript `EChartsBarCategory/visualization/app.min.js` aus der lesbaren
`app.js`. Es wird ausschließlich mit dem bereits festgeschriebenen esbuild
whitespace-minifiziert (Ziel ES2018), ohne ECharts einzubündeln oder dessen
Runtime zu verändern. `npm run build` enthält diesen Schritt. Die Layouttests
vergleichen das ausgelieferte Artefakt exakt mit der erneuten Transformation
und führen die Tests darauf aus. Die kleinere Anwendung erhält den 1-MiB-
Ausgabepuffer bei 100 langen Quellenbezeichnungen plus großem SVG-Muster.

Der BarCategory-Einstieg enthält Bar Chart, Grid, Legende, Titel, Tooltip,
Aria, DataZoom und Canvas Renderer aus dem unveränderten ECharts 6.1.0.
`npm run build:bar-category` schreibt ausschließlich
`../../libs/echarts/6.1.0/echarts.bar-category.min.js`. Der kleinere Build
enthält keine Line-/Scatter-Charts oder MarkLine-/MarkArea-Komponenten;
die bisherige gemeinsame Cartesian-Runtime bleibt unverändert. Der eigene
Build erhält den Ausgabepuffer für 100 Quellen zusammen mit großen SVG-Mustern.
Die BarCategory verwendet keine `labelLayout`-Callbacks; deshalb wird wie
bei ihrer bisherigen Cartesian-Basis kein zusätzliches LabelLayout-Feature
eingebunden. Startseite, llms.txt, aktuelle stabile npm-Version, Lizenz und
Lieferartefakt wurden am 18.10.2026 erneut geprüft: `6.1.0`, `Apache-2.0`,
offizielles npm-Paket. Hash und Bytezahl stehen in `THIRD_PARTY_NOTICES.md`.

Der CalendarPie-Einstieg enthält Pie Chart, Scatter Chart für Tagesnummern
und Fehlstellenmarker, Calendar, Legende, Titel, Tooltip, Aria, LabelLayout und Canvas
Renderer aus dem unveränderten gepinnten ECharts 6.1.0. Der gezielte Befehl
`npm run build:calendar-pie` schreibt ausschließlich
`../../libs/echarts/6.1.0/echarts.calendar-pie.min.js`.
Offizielle Startseite, llms.txt, das offizielle Beispiel `calendar-pie`,
npm-Version und Apache-2.0-Lizenz wurden am 18.10.2026 geprüft. Die native
Pie-Anbindung verwendet `coordinateSystem: 'calendar'` und `center: Datum`.

Der StateTimeline-Einstieg enthält Custom Chart, Grid, Data Zoom, Titel,
Tooltip, Aria und Canvas Renderer aus demselben unveränderten ECharts 6.1.0.
`npm run build:state-timeline` erzeugt ausschließlich
`../../libs/echarts/6.1.0/echarts.state-timeline.min.js`. Offizielle Startseite,
llms.txt, Custom-Series-Handbuch, npm-Version und Apache-2.0-Lizenz wurden
am 17.10.2026 geprüft. Die Anwendungsdarstellung verwendet einen lokalen
`renderItem` ohne zusätzliche Erweiterung oder Veränderung des ECharts-Kerns.

Der ThemeRiver-Einstieg enthält ThemeRiver Chart, SingleAxis, Legende, Graphic, Titel,
Tooltip, Aria und Canvas Renderer aus demselben unveränderten ECharts 6.1.0.
`npm run build:theme-river` erzeugt ausschließlich
`../../libs/echarts/6.1.0/echarts.theme-river.min.js`. Die offiziellen Einstiege,
API, aktuelle stabile npm-Version und Lizenz wurden am 17.10.2026 erneut geprüft.

Der PictorialBar-Einstieg enthält PictorialBar Chart, Grid, Titel, Tooltip,
Aria, DataZoom, LabelLayout und CanvasRenderer. `npm run build:pictorial-bar` erzeugt
ausschließlich `../../libs/echarts/6.1.0/echarts.pictorial-bar.min.js` aus dem
unveränderten gepinnten ECharts 6.1.0. Offizielle Einstiege und benötigte
APIs wurden am 18.10.2026 erneut geprüft; siehe ADR 0057. Die Kategoriennavigation
verwendet native Slider-/Inside-DataZoom-Komponenten. Startseite und llms.txt
sowie npm-Metadaten bestätigen weiterhin Version 6.1.0 und Apache-2.0; die
konkreten Zoom-Optionen wurden zusätzlich am gepinnten Originalpaket geprüft.

Der Candlestick-Einstieg enthält Candlestick Chart, Grid, Titel, Tooltip,
Aria und Canvas Renderer aus demselben unveränderten ECharts 6.1.0.
`npm run build:candlestick` erzeugt ausschließlich
`../../libs/echarts/6.1.0/echarts.candlestick.min.js`.
Die offiziellen Einstiege Startseite und llms.txt sowie die Candlestick-
Dokumentation wurden am 17.10.2026 geprüft.

Die native Symcon-Kachel darf das Output-Buffer-Limit von 1.048.576 Byte nicht
überschreiten. Deshalb verwenden Gauge und kartesische Diagramme nicht den vollständigen
ECharts-Browser-Build, sondern getrennte Einstiegspunkte der offiziellen
Tree-Shaking-Schnittstelle mit den tatsächlich benötigten Bestandteilen.
Der Gauge-Einstieg enthält:

- ECharts Core;
- Gauge Chart;
- Aria, Titel, Tooltip und Graphic Component;
- Canvas Renderer.

Am 20.10.2026 wurde der fehlende `TitleComponent` über die offizielle
Tree-Shaking-Schnittstelle ergänzt. `npm run build:runtime` erzeugt nur das
Gauge-Bundle neu; ECharts bleibt unverändert bei 6.1.0 unter Apache-2.0.
Offizielle Startseite, llms.txt, Import-Handbuch und npm-Metadaten wurden erneut
geprüft. Bytezahl und SHA-256 stehen in `THIRD_PARTY_NOTICES.md`.
`node tests/gauge_runtime_titles.js` vergleicht den Build bytegleich und prüft
die tatsächlichen Canvas-Titelzeichenaufrufe aller Gauge-Verbraucher.

Der kartesische Einstieg enthält Line Chart, Bar Chart, Scatter Chart, Grid, Legende, Titel,
Tooltip, Data Zoom, Referenzlinien, Wertebereiche, Aria und Canvas Renderer.
Der separate Scatter-Einstieg erweitert dieselben Komponenten um Visual Map,
damit andere kartesische Kacheln ihr bisheriges Ausgabevolumen behalten.
Der Heatmap-Einstieg enthält Heatmap Chart, Grid, Visual Map, Titel, Tooltip,
Aria und Canvas Renderer als eigenes Bundle.
Der separate Polar-Einstieg enthält Bar Chart, Polar Component, Titel, Tooltip,
Aria und Canvas Renderer. Er verändert den bestehenden kartesischen Build nicht.
Der Pie-Einstieg enthält Pie Chart, Legende, Titel, Tooltip, Aria und Canvas
Renderer. Auch er verändert die bestehenden Builds nicht.
Der Tree-Einstieg enthält Tree Chart, Titel, Tooltip, Aria und Canvas Renderer
als eigenen Build für die Symcon-Objekthierarchie.
Der Parallel-Einstieg enthält Parallel Chart und Component, Titel, Tooltip,
Aria und Canvas Renderer als eigenen Build für Archivintervall-Vergleiche.
Der Sankey-Einstieg enthält Sankey Chart, Titel, Tooltip, Aria, Label Layout und Canvas
Renderer als eigenen Build für aktuelle Mengenflüsse.
Der Calendar-Einstieg enthält Heatmap Chart, Calendar Component, Visual Map,
Titel, Tooltip, Aria und Canvas Renderer als getrennten Jahreskalender-Build.
Er erweitert nicht die bestehende Stunden-Heatmap-Runtime. Ein gezielter Build
erfolgt mit `npm run build:calendar`.

Der Boxplot-Einstieg enthält Boxplot und Scatter Chart, Grid, Titel, Tooltip, Aria, DataZoom und
Canvas Renderer. `npm run build:boxplot` erzeugt ausschließlich
`../../libs/echarts/6.1.0/echarts.boxplot.min.js`; andere Builds bleiben unverändert.
Startseite, llms.txt, stabile npm-Version, Lizenz und Lieferartefakt wurden am
22.10.2026 erneut geprüft: unverändertes ECharts 6.1.0 unter Apache-2.0.
Scatter zeichnet die separat berechneten IQR-Ausreißer; es wird kein ECharts-
Datentransform mit abweichender Quartils- oder Whiskerberechnung verwendet.
Native Slider-/Inside-DataZoom-APIs werden am gepinnten Paket getestet;
Bytezahl und SHA-256 stehen in `THIRD_PARTY_NOTICES.md`.

Der Funnel-Einstieg enthält Funnel Chart, Titel, Tooltip, Aria, Label Layout
und Canvas Renderer. `npm run build:funnel` erzeugt ausschließlich
`../../libs/echarts/6.1.0/echarts.funnel.min.js`. Die unveränderte gepinnte
ECharts-6.1.0-Basis wurde am 17.10.2026 mit den offiziellen Einstiegen
[Startseite](https://echarts.apache.org/en/index.html) und
[LLM-Dokumentation](https://echarts.apache.org/en/llms.txt) abgeglichen.

Zusätzlich kopiert der Build die sechs im Modul angebotenen offiziellen
ECharts-Themes unverändert aus derselben festgeschriebenen npm-Abhängigkeit:
Dark, Vintage, Macarons, Infographic, Shine und Roma.

Die Build-Abhängigkeiten sind in `package-lock.json` festgeschrieben. Der Build
benötigt Node.js 18 oder neuer und wird aus diesem Verzeichnis erzeugt:

```text
npm ci
npm run build
```

Die Ergebnisse werden als
`../../libs/echarts/6.1.0/echarts.gauge.min.js`,
`../../libs/echarts/6.1.0/echarts.cartesian.min.js`,
`../../libs/echarts/6.1.0/echarts.bar-category.min.js`,
`../../libs/echarts/6.1.0/echarts.scatter.min.js`,
`../../libs/echarts/6.1.0/echarts.heatmap.min.js`,
`../../libs/echarts/6.1.0/echarts.polar.min.js`,
`../../libs/echarts/6.1.0/echarts.pie.min.js` und
`../../libs/echarts/6.1.0/echarts.tree.min.js`,
`../../libs/echarts/6.1.0/echarts.parallel.min.js`,
`../../libs/echarts/6.1.0/echarts.sankey.min.js`,
`../../libs/echarts/6.1.0/echarts.calendar.min.js` sowie unter
`../../libs/echarts/6.1.0/themes` geschrieben. Nach einem bewussten Neuaufbau
müssen Dateigrößen und SHA-256-Werte in `libs/EChartsAsset.php`, den Tests und
`THIRD_PARTY_NOTICES.md` geprüft und aktualisiert werden. `node_modules` wird
nicht eingecheckt.

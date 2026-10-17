# Diagrammfamilienspezifische ECharts-Runtimes erzeugen

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
Aria, LabelLayout und CanvasRenderer. `npm run build:pictorial-bar` erzeugt
ausschließlich `../../libs/echarts/6.1.0/echarts.pictorial-bar.min.js` aus dem
unveränderten gepinnten ECharts 6.1.0. Offizielle Einstiege und benötigte
APIs wurden am 17.10.2026 erneut geprüft; siehe ADR 0057.

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
- Aria, Tooltip und Graphic Component;
- Canvas Renderer.

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

Der Boxplot-Einstieg enthält Boxplot Chart, Grid, Titel, Tooltip, Aria und
Canvas Renderer. `npm run build:boxplot` erzeugt ausschließlich
`../../libs/echarts/6.1.0/echarts.boxplot.min.js`; andere Builds bleiben unverändert.

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

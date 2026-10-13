# Diagrammfamilienspezifische ECharts-Runtimes erzeugen

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
`../../libs/echarts/6.1.0/echarts.pie.min.js` sowie unter
`../../libs/echarts/6.1.0/themes` geschrieben. Nach einem bewussten Neuaufbau
müssen Dateigrößen und SHA-256-Werte in `libs/EChartsAsset.php`, den Tests und
`THIRD_PARTY_NOTICES.md` geprüft und aktualisiert werden. `node_modules` wird
nicht eingecheckt.

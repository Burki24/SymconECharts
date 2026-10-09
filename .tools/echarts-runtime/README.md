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

Der kartesische Einstieg enthält Line Chart, Bar Chart, Grid, Legende, Titel,
Tooltip, Data Zoom, Referenzlinien, Wertebereiche, Aria und Canvas Renderer.
Der separate Polar-Einstieg enthält Bar Chart, Polar Component, Titel, Tooltip,
Aria und Canvas Renderer. Er verändert den bestehenden kartesischen Build nicht.

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
`../../libs/echarts/6.1.0/echarts.polar.min.js` und unter
`../../libs/echarts/6.1.0/themes` geschrieben. Nach einem bewussten Neuaufbau
müssen Dateigrößen und SHA-256-Werte in `libs/EChartsAsset.php`, den Tests und
`THIRD_PARTY_NOTICES.md` geprüft und aktualisiert werden. `node_modules` wird
nicht eingecheckt.

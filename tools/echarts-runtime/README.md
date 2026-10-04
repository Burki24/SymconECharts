# Gauge-spezifische ECharts-Runtime erzeugen

Die native Symcon-Kachel darf das Output-Buffer-Limit von 1.048.576 Byte nicht
überschreiten. Deshalb verwendet Gauge Single nicht den vollständigen
ECharts-Browser-Build, sondern die offizielle Tree-Shaking-Schnittstelle mit
den tatsächlich benötigten Bestandteilen:

- ECharts Core;
- Gauge Chart;
- Aria und Tooltip;
- Canvas Renderer.

Die Build-Abhängigkeiten sind in `package-lock.json` festgeschrieben. Der Build
benötigt Node.js 18 oder neuer und wird aus diesem Verzeichnis erzeugt:

```text
npm ci
npm run build
```

Das Ergebnis wird als
`../../libs/echarts/6.1.0/echarts.gauge.min.js` geschrieben. Nach einem
bewussten Neuaufbau müssen Dateigröße und SHA-256-Wert in
`libs/EChartsAsset.php`, den Tests und `THIRD_PARTY_NOTICES.md` geprüft und
aktualisiert werden. `node_modules` wird nicht eingecheckt.

# Fremdkomponenten und Lizenzabgrenzung

Die Projektlizenz [LICENSE](LICENSE) gilt für die eigenen Beiträge zu
SymconECharts. Sie ersetzt oder beschränkt nicht die Originallizenzen separat
gekennzeichneter Fremdkomponenten.

## Apache ECharts

Projekt: [Apache ECharts](https://github.com/apache/echarts)

Lizenz: [Apache License 2.0](https://www.apache.org/licenses/LICENSE-2.0)

SPDX-Identifier: `Apache-2.0`

Ausgelieferte Version: `6.1.0`

Herkunft: offizielles npm-Paket `echarts@6.1.0`
(`https://registry.npmjs.org/echarts/-/echarts-6.1.0.tgz`)

Paket-SHA-1 laut npm: `ae0f68590f5ebbd728d900907c27acde7c5456d1`

Ausgelieferte Dateien:

- `libs/echarts/6.1.0/echarts.gauge.min.js` – reproduzierbarer,
  Gauge-spezifischer Browser-Build aus ECharts Core, Gauge Chart, Aria,
  Tooltip, Graphic Component und Canvas Renderer; 486.506 Byte; SHA-256
  `37d8c9774f27fc12e048e269e8ec6a114782529fb85262a6be8c6b887207c07f`;
- `libs/echarts/6.1.0/echarts.timeseries.min.js` – reproduzierbarer,
  Zeitreihen-spezifischer Browser-Build aus ECharts Core, Line Chart, Grid,
  Legende, Titel, Tooltip, Data Zoom, Aria und Canvas Renderer; 575.472 Byte;
  SHA-256
  `bf44d8bc8285f9a27d4989a74b309c248a2d28a5f7bc62f867c2e3d8b327225c`;
- `libs/echarts/6.1.0/themes/dark.js` – offizielles Theme Dark; 5.981 Byte;
  SHA-256 `ae60e563617cb87514690c1946ee202e78c9f1487820490614b58934ed037458`;
- `libs/echarts/6.1.0/themes/vintage.js` – offizielles Theme Vintage;
  1.922 Byte; SHA-256
  `15c22b1f26961f972d23a9297362d42c4e59764e4b38025364f341cf144fb84f`;
- `libs/echarts/6.1.0/themes/macarons.js` – offizielles Theme Macarons;
  5.869 Byte; SHA-256
  `1aa73f933f0fd92b02e3e1de18299b15a2d536eb29c4b9755b8ab25d54f5640d`;
- `libs/echarts/6.1.0/themes/infographic.js` – offizielles Theme
  Infographic; 5.829 Byte; SHA-256
  `43f803926e8625a1f1cbf6b6189d1735c0526e3e9e7e12aa8531a8296550c1b4`;
- `libs/echarts/6.1.0/themes/shine.js` – offizielles Theme Shine;
  4.412 Byte; SHA-256
  `33f28aaec40952dbad84de2d8cb3d58f49ea131806fad9497f06ad4d46f2a8f3`;
- `libs/echarts/6.1.0/themes/roma.js` – offizielles Theme Roma;
  3.044 Byte; SHA-256
  `01303d34787f242664d5ad35bb1d535bb21d701ff60f6f1412e8c872008b5a05`;
- `libs/echarts/6.1.0/LICENSE.txt` – Originallizenz aus demselben Paket;
- `libs/echarts/6.1.0/NOTICE.txt` – Originalhinweise aus demselben Paket.

Der Build wird lokal ausgeliefert und zur Laufzeit nicht von einem CDN geladen.
Sein Einstiegspunkt, die exakt festgeschriebenen npm-Abhängigkeiten und der
Buildbefehl liegen unter `.tools/echarts-runtime`. Die ECharts-Quellen werden
nicht inhaltlich verändert, sondern über die offizielle Tree-Shaking-API auf
die von Gauge Single benötigten Bestandteile begrenzt. Die Theme-Dateien werden
unverändert aus dem Paket übernommen.

Das Preset `speed` adaptiert die charakteristische Konfiguration und den
SVG-Zeigerpfad aus dem offiziellen Apache-ECharts-Beispiel
[`gauge-speed`](https://echarts.apache.org/examples/en/editor.html?c=gauge-speed).
Wertebereich, Skalenteilung, Theme-Farben, Beschriftung und responsive
Dimensionierung werden projektspezifisch ergänzt; Beispiel und übernommener
Zeigerpfad verbleiben unter der Apache License 2.0.

`.gitattributes` verhindert eine Zeilenendenkonvertierung der Fremdartefakte.
`libs/EChartsAsset.php` normalisiert bei Runtime und Theme-Dateien zusätzlich
eine bereits durch einen Windows-Modulcheckout erfolgte CRLF-Konvertierung
zurück auf die originalen LF-Zeilenenden und prüft anschließend die
SHA-256-Prüfsumme. Andere
Byteabweichungen werden abgelehnt. Der ECharts-Kern und die genannten
Fremddateien wurden nicht projektspezifisch verändert. ECharts und die im
offiziellen Bundle enthaltenen Komponenten bleiben unter ihren in
`LICENSE.txt` und `NOTICE.txt` genannten Bedingungen.

## Weitere Komponenten

Vor Aufnahme weiterer Bibliotheken, Helper, Vorlagen, Bilder oder Schriftarten
werden deren Herkunft, Lizenz und Weitergabebedingungen geprüft und dokumentiert.
Vorhandene Copyright- und Lizenzhinweise bleiben erhalten. Fremdcode wird nicht
pauschal mit dem PolyForm-Lizenzkopf des Projekts versehen.

Vorhandene Copyright-, Lizenz- und Herkunftshinweise werden nicht entfernt.

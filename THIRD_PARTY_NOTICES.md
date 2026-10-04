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

- `libs/echarts/6.1.0/echarts.min.js` – unveränderter vollständiger
  Browser-Build, SHA-256
  `b66b25aeb4df84e33199dc21694014d336d222cbd9deb0e5a7c14bd6aa0d0fd0`;
- `libs/echarts/6.1.0/LICENSE.txt` – Originallizenz aus demselben Paket;
- `libs/echarts/6.1.0/NOTICE.txt` – Originalhinweise aus demselben Paket.

Der Build wird lokal ausgeliefert und zur Laufzeit nicht von einem CDN geladen.
`.gitattributes` verhindert eine Zeilenendenkonvertierung der Fremdartefakte.
`libs/EChartsAsset.php` normalisiert zusätzlich eine bereits durch einen
Windows-Modulcheckout erfolgte CRLF-Konvertierung zurück auf die originalen
LF-Zeilenenden und prüft anschließend die SHA-256-Prüfsumme. Andere
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

# ADR 0035: Polar Bar für aktuelle Werte als eigenes Gerätemodul

## Status

Angenommen

## Kontext

[ADR 0029](0029-bar-chart-families-and-category-bar.md) trennt Kategorie-, historische, Waterfall- und Polar-Balken nach Datenvertrag. Nach der Umsetzung von Waterfall ist Polar der verbleibende Kandidat. Der Eigentümer hat am 09.10.2026 die Umsetzung als eigenes Modul beauftragt. Die zwei üblichen Polar-Geometrien teilen denselben Datenvertrag: unabhängige aktuelle Werte, deren Kategorien einmal um den Kreis oder auf konzentrischen Bahnen angeordnet werden. Sie benötigen daher keine zwei Modulidentitäten.

## Entscheidung

`EChartsBarPolar` erhält die GUID `{B22B5AB4-CDA4-416F-9FF1-4126734E078B}` und den Präfix `ECBP`. Die bestehenden Datenfluss-IDs und der Gateway-Aufruf `current.read` bleiben erhalten. `Sources` enthält 1 bis 16 eindeutige numerische Variablen-IDs mit gemeinsamer effektiver Einheit. Die Variable-ID ist alleinige technische Identität; gleiche Namen und Beschriftungen bleiben getrennte Werte. Ein konfigurierter Anzeigename hat Vorrang, sonst gilt der aktuelle Symcon-Name. Farbe, Einheit und Nachkommastellen sind je Quelle konfigurierbar; ein Archiv ist nicht erforderlich.

Die beiden Layouts `radial` (Kategorien auf der Winkelachse, Werte auf der Radiusachse) und `tangential` (Kategorien auf der Radiusachse, Werte auf der Winkelachse) sind Designeroptionen desselben Moduls. Weitere Optionen sind Sortierung, Beschriftungen, Raster, Balkenbreite, Radien, Startwinkel, Drehrichtung und runde Enden. Kachel und IPSView nutzen dieselben Quellen, können aber unabhängige Designs haben. Negative Werte bleiben vorzeichenbehaftet; der Renderer führt keine stillschweigende Normalisierung auf positive Werte aus. Grenzen und Lesbarkeit der automatischen Werteskala werden im Browser geprüft.

## Wiederverwendungsprüfung

Gemäß [ADR 0017](0017-reuse-before-new-chart-development.md) wurden Category Bar und Waterfall vor der Umsetzung geprüft. `EChartsCurrentSources` deckt den gemeinsamen Gateway-Zugriff und die ID-basierten Referenzen bereits ab; `EChartsSourceIdentity` löst Anzeigenamen auf. Für Themes und integritätsgeprüfte Assets dient `EChartsAsset`, für Formzustand `EChartsIPSViewDesignForm`, für den HTML-Ausgabeweg die synchronisierten ModuleHelper und der vorhandene IPSView-Transport. Diese Bausteine werden wiederverwendet. Die Polar-Achsen und ECharts-Optionen bleiben im neuen Gerätemodul und seinem Renderer. Für eine neue allgemeine ModuleHelper-Funktion besteht kein Bedarf.

Der bestehende kartesische Build wird nicht verändert. Ein zusätzlicher, lokal aus dem festgeschriebenen offiziellen Paket `echarts@6.1.0` gebauter und per SHA-256 geprüfter Polar-Build enthält ECharts Core, Bar Chart, Polar Component, Titel, Tooltip, Aria und Canvas Renderer. Die bestehende Lizenz und Herkunft gelten auch für diesen Build; ohne CDN.

## Folgen

Category Bar, BarHistory und Waterfall behalten GUIDs, Präfixe, Properties und Laufzeitdateien. Polar wird nicht als Modus eines dieser Module eingeführt. Die ersten Versionen umfassen keine Archivwerte, gestapelten Polar-Serien, mehrere Einheitenachsen oder importierte SVG-Balkenmuster. Solche Erweiterungen benötigen jeweils eine fachliche Prüfung innerhalb des Polar-Vertrags.

Vertrags-, Integrations-, Asset- und Layouttests sichern die erste Umsetzung. Ein installierter Symcon-9.0/9.1-Laufzeittest sowie ein IPSView-Clienttest sind dadurch nicht ersetzt und bleiben bis zu ihrer tatsächlichen Durchführung als Lücke benannt.

## Quellen

- [Offizielle Apache-ECharts-Beispiele für Polar-Balken](https://echarts.apache.org/examples/en/index.html#chart-type-bar)
- [ECharts 5.2: Polar-Bar-Beschriftungen](https://echarts.apache.org/handbook/en/basics/release-note/5-2-0/)
- [Apache ECharts 6.1.0 Release](https://github.com/apache/echarts/releases/tag/6.1.0)
- [Apache-ECharts-Lizenz und Download](https://echarts.apache.org/en/download.html)

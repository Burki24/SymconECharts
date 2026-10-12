# ADR 0038: Aktuelle Anteile als eigene Pie-Familie

- Status: Angenommen
- Datum: 2026-10-12
- Entscheider: Burki24

## Kontext

Nach den Balken- und Gauge-Familien soll SymconECharts weitere Möglichkeiten
von Apache ECharts erschließen. Kreis, Donut und Nightingale-Rose verwenden
denselben Vertrag für aktuelle Anteile, nicht den Archivvertrag der
TimeSeries- oder BarHistory-Module. Sie werden daher nicht als Modus eines
bestehenden Balkenmoduls hinzugefügt.

## Entscheidung

`EChartsPie` erhält die GUID `{176F1531-ABC4-4AE5-9DE5-8F3F006C8237}` und
den Präfix `ECPI`. Es liest 1 bis 16 eindeutige numerische Variablen über
das vorhandene Gateway und `current.read`. Die Variablen-ID bleibt alleinige
technische Identität; ein konfigurierter Anzeigename hat Vorrang vor dem
aktuellen Variablennamen. Gleichlautende Namen erzeugen keine Zusammenführung.
Alle Quellen nutzen eine gemeinsame wirksame Einheit. Negative Werte führen
zu einem sichtbaren Datenfehler; eine Nullsumme zu einem Leerzustand. Werte
werden weder betragsmäßig umgerechnet noch künstlich gleichverteilt.

Die drei Geometrien sind Designeroptionen eines Gerätemoduls. Die
Nightingale-Rose nutzt `roseType: 'area'` mit gleich breiten Winkeln und
linear wertabhängigen Radien. Kachel und IPSView besitzen eigene Designs mit
Vererbungsmodus und je eine Formularvorschau. Thema, Animation, Font- und
Farboptionen folgen dem bestehenden ECharts-Designer-Vertrag.

## Wiederverwendung und Runtime

Die gemeinsame ID-basierte Quellenanbindung, `EChartsSourceIdentity`,
Variablendarstellung, Theme-Assets, Animation, IPSView-Transport,
Hintergrund und transiente Fehlerbehandlung werden aus `libs` genutzt.
Die vorhandene read-only-Vorschauverdrahtung und SVG-Grundfunktionen werden
verwendet; allein Sektorgeometrie und Pie-Optionen bleiben im Modul.
Die wiederverwendete Vorschauverdrahtung trägt aus Kompatibilitätsgründen
weiterhin interne `BarPreview`-Methoden- und Feldnamen; diese Namen sind nicht
Teil der sichtbaren Formulardarstellung. Eine spätere technische Umbenennung
bedarf eines eigenen, getesteten Refactorings der vorhandenen Bar-Module.
`libs/helper` wird nicht verändert. Ein eigener, integritätsgeprüfter
Tree-Shaking-Build aus dem festgeschriebenen offiziellen Paket `echarts@6.1.0`
enthält Core, Pie, Legende, Titel, Tooltip, Aria und Canvas Renderer. Er wird
lokal ohne CDN ausgeliefert; die bestehende Apache-2.0-Lizenz gilt.

## Folgen und Prüfung

Bestehende Module, GUIDs und Properties bleiben unverändert. Vertrags-,
Asset-, PHP-, Browser- und Layouttests werden ergänzt. Ein Test auf einer
erreichbaren Symcon-Installation und in der nativen IPSView-App wird separat
ausgewiesen und nicht aus Stub- oder Browserchecks abgeleitet.

## Quellen

- [Apache ECharts: Basic Pie](https://echarts.apache.org/handbook/en/how-to/chart-types/pie/basic-pie/)
- [Apache ECharts: Doughnut](https://echarts.apache.org/handbook/en/how-to/chart-types/pie/doughnut/)
- [Apache ECharts 6.1.0](https://echarts.apache.org/en/changelog.html)

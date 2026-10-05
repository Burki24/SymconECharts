# ADR 0016: Eigenständige Tacho- und Chronograph-Module

## Status

Angenommen am 5. Oktober 2026. Diese Entscheidung ersetzt die Einordnung von
Tacho und Chronograph als Presets von `EChartsGaugeMulti` aus ADR 0012 und
ADR 0014.

## Kontext

`EChartsGaugeMulti` ist für kompakte Übersichten mit bis zu 16 Quellen und
einem gemeinsamen Design bestimmt. Tacho und Chronograph benötigen dagegen
eine bewusst instrumentenbezogene Gestaltung: Zeiger, Nabe, Skala, Farben,
Zifferblatt und SVG-Assets sollen je Quelle abweichen können. Diese
Konfigurationsdichte würde das allgemeine Multi-Modul unnötig verkomplizieren.
Der bisherige Stand ist noch nicht veröffentlicht; eine Migration alter
Tacho- oder Chronograph-Konfigurationen ist daher nicht erforderlich.

## Entscheidung

- `EChartsGaugeMulti` behält 2 bis 16 Quellen, ein gemeinsames Design und die
  Presets Multi Title, Ringraster, konzentrische Ringe und Wetterstation.
- `EChartsGaugeTacho` wird als eigenes Gerätemodul mit dem Präfix `ECGT`, der
  Modul-GUID `{9D072BFE-45B4-4442-B1D2-4FC458C3BABD}` und 2 bis 5 Quellen
  geführt.
- `EChartsGaugeChronograph` wird als eigenes Gerätemodul mit dem Präfix
  `ECGC`, der Modul-GUID `{B99C3ADA-9A90-486F-97E3-96C39554D9A6}` und 2 bis 5
  Quellen geführt.
- Die erste Quelle bestimmt jeweils das Hauptinstrument; die Reihenfolge der
  weiteren Quellen bestimmt die Nebeninstrumente.
- Das gemeinsame Moduldesign bleibt die Basis. Eine Quellenzeile kann eine
  individuelle Überschreibung für Zeiger, Nabe, Skala, Farben und Zifferblatt
  aktivieren. SVG-Zeiger, SVG-Naben und SVG-Zifferblätter werden mit denselben
  zentralen, validierten Importadaptern wie die übrigen Gauge-Module
  verarbeitet.
- Bei unabhängigem IPSView-Grunddesign kann jede Quelle ihr Kacheldesign
  übernehmen oder eine eigene IPSView-Überschreibung verwenden.
- Die Designwerte werden in der Quellenzeile gespeichert, damit sie beim
  Sortieren mit dem Instrument verbunden bleiben.
- Beide Module verwenden den bestehenden Gauge↔Gateway-Datenfluss und bieten
  native Kachel- sowie optionale IPSView-Ausgabe.

## Folgen

Die spezialisierten Formulare bleiben trotz umfangreicher Einzelgestaltung
verständlich, während `EChartsGaugeMulti` seine Rolle als skalierbare
Übersicht behält. Familienübergreifende Designsemantik liegt in
`libs/EChartsGaugeDesign.php`; Layoutgeometrie, Properties, Validierung und
Renderer verbleiben in den jeweiligen Gerätemodulen.

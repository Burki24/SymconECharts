# ADR 0053: Radar für aktuelle Vergleichsgruppen

- Status: Angenommen
- Datum: 2026-10-16
- Entscheider: Burki24
- Ergänzt: ADR 0001, 0002, 0005, 0017, 0036 und 0037

`EChartsRadar` besitzt die GUID `{B596EE88-C7A6-4A9B-891A-DD81BFF08A94}`
und das Präfix `ECRA`. Eine Instanz vergleicht 1–8 manuell benannte Gruppen
an 3–10 gemeinsamen Achsen. Jede Achse hat einen stabilen Schlüssel, Titel,
Minimum, Maximum, Einheit und Nachkommastellen. Jede Gruppe benötigt pro
Achse genau eine numerische aktuelle Quellvariable. Gleich benannte Variablen
bleiben ID-getrennt; dieselbe ID darf mehrfach zugeordnet werden und wird nur
einmal gelesen beziehungsweise abonniert. Fehlende Achsenwerte, doppelte
Zuordnungen, unbekannte Achsen und ungültige Werte werden abgewiesen.

Die Radien zeigen den relativen Stand im ausdrücklich konfigurierten
Achsenbereich (0–100 %), nicht eine automatische Qualitätsbewertung.
Originalwerte, Einheiten und Zeitstempel bleiben für Tooltips erhalten.
Außerhalb des Bereichs liegende Werte werden ausschließlich geometrisch
begrenzt und mit einem sichtbaren Hinweis gemeldet, nicht still geändert.
Es werden weder Archivdaten gelesen noch Summen verschiedener Einheiten
gebildet. Eine gleichzeitige atomare Messaufnahme wird nicht versprochen.

Wiederverwendung: `EChartsCurrentSources` für Gateway-Zugriff und
VM_UPDATE-Abonnements, gemeinsame Designer-/Vorschau-, Animations-, Theme-,
IPSView-Transport- und Wiederholungsbausteine. Radar-Validierung und Geometrie
bleiben lokal. Das Listen-/Selektormuster stammt aus Sankey; Axisschlüssel
werden auch bei ungespeicherten Änderungen angeboten. Kachel und IPSView
haben getrennte Designer; aktuelle SVG-Vorschauen lesen nur aktuelle Werte
und ändern keinen Instanzzustand. Keine lokale Änderung synchronisierter Helper.

Die eigene reproduzierbare Runtime ergänzt die unveränderte gepinnte
ECharts-6.1.0-Basis um RadarChart und RadarComponent, Titel, Legende, Tooltip,
Aria und Canvas. Grundlage: [Radar-Serie](https://echarts.apache.org/en/option.html#series-radar)
und [Radar-Koordinaten](https://echarts.apache.org/en/option.html#radar).
Dataset ist für diese einzelne Vergleichsserie nicht erforderlich.

Lokale Modell-, Gateway-, Formular-, Budget- und echte Rendererprüfungen sind
erforderlich. Symcon-/IPSView-Live-Tests erfolgen nach Installation durch den
Anwender; sie sind noch nicht bestätigt. Bestehende Instanzen bleiben unverändert.

## Prüfnachweise vom 16.10.2026

- `php tests/radar_model.php`: Achsenreihenfolge, fehlende/doppelte Zuordnungen,
  negative und Nullwerte, Originalwerte und ausschließlich optische Begrenzung.
- `tests/radar_integration.php` in `php tests/gateway_gauges.php`: aktueller
  Gateway-Datenweg, VM_UPDATE, Referenzen, Transportwiederholung, ungespeicherte
  Achsenwahl, zustandsneutrale Vorschauen, unabhängiger IPSView-Designer,
  Übersetzungen und Maximalgrößen unter dem 1-MiB-Ausgabelimit.
- `node tests/radar_layout.js`: echte ECharts-Geometrie, Werteformatierung,
  HTML-Escaping, Designoptionen, Kachelkopf und Fehlerdarstellung.
- `node tests/radar_browser.mjs`: lokale Browserprüfung der ausgelieferten
  Canvas-Runtime mit synthetischen Daten in einer schmalen Kachel und einem
  breiten IPSView-artigen Widget. Kein Symcon-/IPSView-Laufzeittest.
- `php tests/quality.php --fix`: vollständige gemeinsame Qualitätsbasis.

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

Die erste Radar-Version wurde am 16.10.2026 durch den Anwender in Kachel und
IPSView erfolgreich live geprüft. Auch die nachfolgende Designer-Erweiterung
wurde am 16.10.2026 durch den Anwender in beiden Ausgabewegen positiv bestätigt.
Bestehende Instanzen,
Datenzuordnungen und Wertebereiche bleiben unverändert.

## Designer-Ergänzung vom 16.10.2026

Abgleich mit der offiziellen [Radar-Serie](https://echarts.apache.org/en/option.html#series-radar)
und den [Radar-Koordinaten](https://echarts.apache.org/en/option.html#radar):
Linienarten, Symbolformen, Drehung, Symbolrand und -deckkraft, Originalwert-
Beschriftungen sowie Flächenschatten werden als begrenzte statische Optionen
angeboten. Achsenbereiche, Achsen-/Rasterlinien, Rasterflächenfarben,
Schriftgewicht und Legende sind separat gestaltbar. Hover-Fokus bleibt
abschaltbar. `GroupColors`/`IPSViewGroupColors` sind begrenzte Listen aus
`Group` und Integer-`Color` (-1 = Theme); Gruppennamen sind die bereits
bestehenden expliziten fachlichen Identitäten, keine Variablennamen.
Pro Design sind maximal acht eindeutige Farbzuordnungen zulässig.
Nicht verwendete Zuordnungen bleiben für spätere Änderungen erhalten.

Defaultwerte erhalten die erste Darstellung; es gibt keine automatische
Änderung gespeicherter Properties. Neue Optionen nutzen die vorhandene
Vorschau-, Animations-, Theme- und IPSView-Verdrahtung. Die SVG-Punktsymbole
aus TimeSeries werden nach `libs/EChartsPreviewSymbol.php` extrahiert und
von beiden Familien verwendet; Radar ergänzt begrenzte Rand-/Rotationsstile.
Rechteckbezogene Textpositionierung nutzt `EChartsLabelDesign::PreviewRect`.
Die Gruppenfarbvalidierung bleibt wegen des abweichenden Acht-Gruppen-
Vertrags und hilfreicher Fehlerdiagnosen lokal. Keine Helper-Kopie wird geändert.

Freie JavaScript-Formatter, externe Bild-/Pfadsymbole, beliebige Decal- oder
Gradientenobjekte und zusätzliche Auswahlzustände werden nicht als ungeprüfte
Konfigurationsinhalte importiert. Die Radar-Runtime ergänzt ausschließlich das
offizielle `LabelLayout` der unveränderten ECharts-6.1.0-Basis für den
abschaltbaren Überlappungsschutz der Punktwerte. Integritätswert und
Lieferumfang werden aktualisiert; das 550-KB-Runtimebudget bleibt eingehalten.

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

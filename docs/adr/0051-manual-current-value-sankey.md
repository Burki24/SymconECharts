# ADR 0051: Sankey mit manuellen Knoten und aktuellen Verbindungswerten

- Status: Angenommen
- Datum: 2026-10-16
- Entscheider: Burki24
- Ergänzt: ADR 0001, ADR 0002, ADR 0005, ADR 0017, ADR 0036 und ADR 0037

## Entscheidung

`EChartsSankey` erhält die Modul-GUID
`{D0F77DFF-6B50-4901-A2F0-5A98159E2559}` und das freie Präfix `ECSA`.
Die Familie bildet aktuelle Mengenflüsse zwischen manuell gewählten Knoten ab.

`Nodes` enthält 2 bis 100 Einträge mit `Key` und `Label`. Der Schlüssel ist
eindeutig, beginnt mit einem ASCII-Buchstaben und enthält höchstens 40
ASCII-Buchstaben, Ziffern, Unterstriche oder Bindestriche. Sichtbare Namen
dürfen gleich sein. Die ECharts-Knotenidentität verwendet den Schlüssel,
Beschriftungen werden unabhängig davon formatiert.

`Sources` enthält 1 bis 200 Verbindungen mit `Source`, `Target` und
`VariableID`. Das bestehende ID-basierte Quellenabonnement und `current.read`
werden wiederverwendet. Mehrfach verwendete Variablen werden je Ausgabe einmal
gelesen; der vollständige Wert gilt für jede explizit konfigurierte Verbindung.
Jedes gerichtete Knotenpaar kommt höchstens einmal vor. Selbstverbindungen,
fehlende Endpunkte und Zyklen werden vor dem Rendern abgewiesen, auch wenn die
beteiligten Werte aktuell null sind. `Unit` ist eine optionale gemeinsame
Anzeigeeinheit mit höchstens 24 Zeichen und führt keine Umrechnung aus.

Alle Breiten müssen endlich und nichtnegativ sein. Nullverbindungen und nicht
positiv verbundene Knoten gelangen nicht in das sichtbare Modell. Fehlende
oder fehlerhafte Werte werden nicht zu null umgedeutet. Knotengrößen folgen
dem Maximum aus Eingangs- und Ausgangssumme; es wird keine Bilanz erzwungen
und keine Gesamtsumme über mehrere Flussstufen gebildet.

## Wiederverwendung und Ablage

Gateway-Protokoll, `EChartsCurrentSources`, Animation, Vorschauverdrahtung,
Formularrubriken, Theme, Hintergrund und IPSView-Transport werden verwendet.
Die Sankey-spezifische Validierung und Modellbildung liegen im Modul in
`SankeyModel.php`, die begrenzte SVG-Beispielvorschau in `SankeyPreview.php`.
Es entsteht kein universeller Graphmodus und keine lokale Helper-Kopie.

Kachel und IPSView besitzen getrennte Designer. Unsichtbare beziehungsweise
unabhängige Felder folgen denselben Vererbungsregeln wie die anderen Geräte.
Knotenauswahlfelder reagieren auf ungespeicherte Knotenänderungen. Die
Vorschau übernimmt bis zu zwölf Knoten und zwanzig Verbindungen mit
synthetischen Gewichten; größere oder ungültige Entwürfe nutzen Beispieldaten.

## Runtime und Nachweise

Die offizielle ECharts-Startseite, `llms.txt`, die Sankey-Optionsdokumentation
und die Lizenz wurden am 16.10.2026 geprüft. Die vorhandene, festgeschriebene
Apache-ECharts-Version 6.1.0 wird um einen separaten reproduzierbaren Build
mit Sankey Chart, Titel, Tooltip, Aria und Canvas Renderer ergänzt.
Herkunft, Lizenz, Größe und SHA-256 stehen in `THIRD_PARTY_NOTICES.md`.

Lokale Tests prüfen Konfiguration, Zyklen, Grenzen, gleichlautende Namen,
Null- und Negativwerte, Quellenabonnements, Wertaktualisierungen,
Formularauswahl, unabhängige Designs, Übersetzungen, Ausgabelimit und den
Optionsaufbau samt Layout in der echten ECharts-Engine. Ein lokaler Browsertest
mit der gebündelten Canvas-Runtime bestätigt waagerechtes und senkrechtes
Layout, Nullflussanzeige, Wiederherstellung nach Aktualisierung und den
freigehaltenen simulierten Kachelkopf. Ein Live-Test des neuen Moduls in
Symcon-Kachel und IPSView bleibt erforderlich.

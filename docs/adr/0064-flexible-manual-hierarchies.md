# ADR 0064: Flexible gemeinsame Hierarchien für Treemap und Sunburst

- Status: Angenommen
- Datum: 2026-10-19
- Entscheider: Burki24
- Ergänzt: ADR 0048 und 0049

## Entscheidung und Reihenfolge

Treemap wird zuerst auf einen rekursiven manuellen Hierarchievertrag erweitert;
anschließend übernimmt Sunburst genau dieses Modell. Beide Geräte bleiben
eigenständige Familien mit getrennten Geometrien, Designern und Ausgabewegen.
Die bisher feste Struktur Gruppe/Untergruppe/Blatt wird zu höchstens acht
Ebenen erweitert. Die künstliche ECharts-Wurzel zählt nicht mit, das Variablen-
Blatt zählt mit: maximal sieben Gruppen plus eine Quelle.

## Additive Konfiguration ohne Umschreiben

`Sources` behält VariableID, Group, Subgroup und Label. Optional kommt
`GroupPath` als Text hinzu, beispielsweise `Haus / EG / Wohnzimmer / Strom`.
Ein nichtleerer Gruppenpfad ersetzt nur für diese Zeile die Zuordnung aus
Group/Subgroup. Ein fehlender oder leerer Pfad verwendet unverändert die
alten Felder. Gespeicherte Quellen und Designs werden nicht migriert oder
normalisiert zurückgeschrieben; bestehende Instanzen bleiben verwendbar.

Nur das ausdrücklich neue Feld wird an unescaped `/` aufgeteilt. Leerzeichen
um Segmente werden entfernt; leere Segmente werden abgewiesen.
`\/` bezeichnet einen wörtlichen Schrägstrich, `\\` einen wörtlichen Backslash.
Andere und unvollständige Escape-Folgen werden abgewiesen. Alte Group- und
Subgroup-Namen mit Schrägstrichen bleiben stets wörtliche Namen. Gruppen
besitzen weiterhin 1–80 Zeichen, optionale Blattbeschriftungen höchstens 120.
Gruppen werden nur bei gleichem vollständigem Pfad zusammengeführt, nicht
allein wegen gleicher Namen in verschiedenen Zweigen.

## Zentrale Zuständigkeiten

- `EChartsHierarchySources` validiert JSON, Quellen-IDs/-typen und Pfade für
  beide Geräte. Es liefert einen kanonischen internen `Path` als Segmentliste.
  Die Vorschau verwendet dieselbe Validierung und rein synthetische Gewichte,
  ohne GetValue-, Gateway- oder Archivabfragen und ohne persistierende Aktionen.
- `TreemapModel` behält seinen bestehenden Einstieg und baut rekursiv den
  gemeinsamen Baum. Es summiert Kinder, erhält Reihenfolge, Nullblätter und
  technische IDs und erzwingt Tiefe, Knotenzahl und Modellgröße.
- `echarts-design.js::updateHierarchyChart` erhält den nativen geöffneten
  Gruppen-Wurzelknoten bei Aktualisierung und Resize. Die Familien geben ihre
  native Aktion an; nach Entfernen der Gruppe oder Abschalten der Navigation
  erfolgt keine Wiederherstellung. Browsernavigation wird nicht gespeichert.
  Treemap stellt dabei die Vorfahren der Reihe nach wieder her, damit die
  native Tiefenbegrenzung auch die tatsächlich sichtbaren Kindflächen aufbaut.
- Rechteck- und Ringgeometrie, Ebenendesign und SVG-Zeichenlogik bleiben in
  Treemap beziehungsweise Sunburst. Deklarative Formularfelder werden nicht
  wegen weniger identischer Zeilen in ein universelles Formularframework verlagert.

Synchronisierte Helper, Gateway und fremde ECharts-Runtimes bleiben unverändert.
Das ist konkrete Zentralisierung für zwei bestehende Verbraucher, keine neue
universelle Widget- oder Migrationsschicht.

## Identität, Werte und Budgets

Variablen-IDs bleiben alleinige Blattidentität (`variable-<ID>`). Die bisherigen
Group-/Subgroup-IDs bleiben auch für die ersten zwei Ebenen eines expliziten
Pfads stabil. Tiefere Gruppen verwenden Hashes vollständiger Segmentlisten,
damit Trennzeichen im Namen keine falsche Zusammenführung erzeugen.
Aktuelle endliche nichtnegative Rohwerte bleiben die Gewichte. Es werden weder
Archive noch konstante Benutzergewichte oder neue Normalisierungsmodi eingeführt.
Null bleibt im Modell, ohne sichtbare Fläche beziehungsweise sichtbaren Winkel.

Die Grenze bleibt 100 eindeutige Quellvariablen. Mit acht Ebenen sind höchstens
800 echte Knoten möglich; dieser Wert ist ein ausdrückliches Gesamtbudget.
Der HTML-sicher serialisierte gemeinsame Baum ist auf 200000 Bytes begrenzt,
die Quellenkonfiguration auf 256 KiB. Ungültige, zu tiefe oder zu große Modelle
werden sichtbar abgewiesen, nicht heimlich beschnitten. Damit muss nicht jede
Kombination maximal langer Zeichenketten und maximal vieler Ebenen hineinpassen.
Der bestehende Symcon-Ausgabepuffer bleibt maßgeblich und wird separat getestet.

## Darstellung und Kompatibilität

Treemap behält die bisherigen Ebenen-1/2/3-Designfelder. Tiefere Gruppen
setzen das vorhandene Untergruppen-/tiefe Ebenendesign fort. Sunburst behält
die ersten drei Ring-Einstellungen; tiefere Ringe übernehmen die äußeren
Ring-Einstellungen. Es werden keine gespeicherten Styles umgeschrieben.
Native Drilldown-/Zurücknavigation bleibt verfügbar, tiefe Tooltip-Pfade
werden escaped und innerhalb des Widgets umgebrochen.
Bei mehr als drei Ebenen verwendet Treemap einen navigierbaren Drei-Ebenen-
Ausschnitt. Der standardmäßige Navigationspfad bleibt dann auch in kompakten
Kacheln sichtbar; ausdrückliches Abschalten bleibt möglich. Sunburst verwendet
seine native Rücknavigation über die Mitte.

Beide Formularvorschauen zeigen die validierte ausgewählte Hierarchie mit
ausdrücklich synthetischen Gewichten. Vereinfachungen werden gekennzeichnet;
ungültige konfigurierte Pfade dürfen keine plausible Ersatzhierarchie ergeben.
Die Quellenlisten und ihre Entwürfe sind in Kachel/IPSView gleich, die Designer
bleiben unabhängig beziehungsweise vererbbar wie bisher.

## Nachweise und Grenzen

Zunächst rote Modell-/Rendererprüfungen belegen die bisher fehlende Tiefe.
Regressionen prüfen alte Quellen/IDs, wörtliche Schrägstriche, neue Escapes,
leere und zu tiefe Pfade, gleiche Anzeigenamen, richtige Summen/Reihenfolge,
100 Quellen/800 Knoten, HTML-sichere Größenlimits und schreibfreie Entwürfe.
Echte ECharts-Tests prüfen tiefe Geometrie und Navigation einschließlich
Aktualisierung, Resize, Entfernen des geöffneten Knotens und Zurücknavigation.
Lokale synthetische Browserprüfungen ergänzen beide Ausgabewege. Die echte
Symcon-/IPSView-Abnahme nach Modulupdate bleibt eine separate Prüfung.

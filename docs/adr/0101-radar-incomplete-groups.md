# ADR 0101: Optionale unvollständige Radar-Vergleichsgruppen

- Status: Angenommen
- Datum: 2026-10-24
- Ergänzt: ADR 0053, 0076 und 0100

## Vertrag

`AllowIncompleteGroups` ist eine gemeinsame Boolean-Property mit Default false.
Alte Konfigurationen bleiben streng: Jede Gruppe benötigt pro Achse genau eine
numerische Variable. Erst bei ausdrücklicher Aktivierung sind ausgelassene
Zuordnungen zulässig. Jede Gruppe benötigt mindestens eine konfigurierte Quelle;
eine nur in Farb-/Sichtbarkeitslisten genannte Gruppe wird nicht erfunden.
Weder VariableID 0 noch eine ungültige, nichtnumerische oder nicht lesbare Quelle
ist eine fehlende Zuordnung. Solche konfigurierten Quellen bleiben Fehler,
auch bei ausgeblendeten Gruppen. Doppelte Zuordnungen und unbekannte Achsen
bleiben unzulässig. Abschalten verändert oder löscht keine Zuordnungen.

Das Schema 1 erhält achsenparallele `null`-Plätze in `values`, `percentValues`,
`variableIDs` und `timestamps`. Nur unvollständige Gruppen erhalten zusätzlich
`missingAxes` (Achsenschlüssel) und `segments` (Paare benachbarter belegter
Achsenindizes einschließlich letzter → erster Achse). Das Gesamtmodell ergänzt
`incompleteGroupCount`. Originalwerte, Einheiten, Präzision, Gruppen-/Quell-IDs,
inverse Achsen, Messzeitpunkte und vollständiger Clippingzähler bleiben erhalten.
Ein echter Nullwert wird gezeichnet; fehlende Werte werden nicht als Null
gelesen, interpoliert oder mit einem Messzeitpunkt versehen.

## Ausgabe und gemeinsame Logik

Der vorhandene familienlokale `RadarModel` validiert die Policy und plant alle
Kanten für beide HTML-Ausgaben und beide lesenden SVGs. Bestehende Auswahl-,
Formular-, Theme-, Ausgabe- und Transporthelper werden weiterverwendet. Keine
lokale Helper-Kopie oder neue allgemeine Lückenabstraktion wird eingeführt.

Die native ECharts-Radar-Implementierung ersetzt fehlende Punkte durch die
Mitte und schließt Polygone (`radarLayout.js` im gepinnten Originalpaket).
Deshalb gelangen ausschließlich vollständige Gruppen in die native Radar-Serie.
Für Teilgruppen ergänzt die Radar-Runtime den offiziellen
[CustomChart](https://raw.githubusercontent.com/apache/echarts-doc/master/en/option/series/custom.md).
Er zeichnet nur die gemeinsamen Segmente, vorhandene Punktsymbole und `?` an
fehlenden Achsen. Keine gefüllte Fläche und kein Flächenschatten für Teilgruppen.
Die geometrische Projektion verwendet die gemeinsame native Radar-Mitte,
Radius, Startwinkel und Drehrichtung; vollständige Gruppen bleiben nativ.

Linien, Punktsymbole, Ränder, Drehung, Deckkraft und Originalwertlabels folgen
dem jeweiligen Designer. Bei Punktsymbol „Keine“ oder Größe 0 erhalten Teilgruppen
minimale leere Kreise, damit einzelne Werte sichtbar bleiben. Fehlstellenmarker
und dauerhafter Hinweis sind unabhängig von Tooltip, Legende und Zahlenlabels.
Der Hinweis bleibt auch bei versteckten Gruppen und vorübergehenden Ladefehlern
erhalten. Tooltip/ARIA nennen jede ausgelassene Achse ausdrücklich „Nicht
zugeordnet“; die Legende kennzeichnet Teilgruppen. Gemeinsame ID-stabile Auswahl,
Legendenanker und Farben bleiben bei Umordnung und Aktualisierung erhalten.

ECharts 6.1.0 und seine Lizenz bleiben unverändert. Der gezielte Radar-Build
hat 539843 Bytes, bleibt unter 550000 und erhält einen aktualisierten
Integritätswert sowie Herkunftsnachweis. Andere Runtime-Artefakte bleiben gleich.
Keine freien Renderer-Skripte oder unvalidierten Formen werden importiert.

## Grenzen und Nachweise

Unverändert 3–10 Achsen, 1–16 Gruppen, höchstens 160 Zuordnungen, 65536 Bytes
je Konfigurationsliste, 200000 Modellbytes und 1 MiB HTML. Im optionalen Modus
sind entsprechend eine bis 160 Quellenzuordnungen möglich. Die Vorschau prüft
weiterhin sämtliche Quellen und zeichnet höchstens acht ausgewählte Gruppen.

Der Modelltest begann RED an der strengen Ablehnung einer Teilgruppe. Modell,
echtes Modul/Gateway, tatsächlich ausgeführte Checkbox-Callbacks und native
ECharts-Engine prüfen Nullplätze, Originalmetadaten, Nachbarschaft und Umkehr,
einzelne Werte, strengen Default, Fehler statt Best-Effort, beide Designs/SVGs,
Auswahl und alle bestehenden Byteguards. Browser- und Qualitätssuitennachweise
stehen im Testdokument. Die funktionale MCP-Prüfung ist bestanden; der Eigentümer
bestätigt Teilgruppen in Kachel und tatsächlichem IPSView. Bestehende
Live-Konfigurationen wurden nicht verändert.

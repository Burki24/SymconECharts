# ADR 0060: Monatskalender mit Tages-Kreisdiagrammen

- Status: Angenommen
- Datum: 2026-10-18
- Entscheider: Burki24
- Ergänzt: ADR 0001, 0002, 0005, 0017, 0036, 0037, 0052 und 0059

## Entscheidung und Wiederverwendung

`EChartsCalendarPie` erhält das freie Präfix `ECCP` und die GUID
`{8F3D4E67-A52E-46AB-976E-25EC4F37FB52}`. Es zeigt einen Monatskalender
mit einem Kreis je abgeschlossenem Tag gemäß dem offiziellen
[Calendar-Pie-Beispiel](https://echarts.apache.org/examples/en/editor.html?c=calendar-pie).
Die drei fachlichen Berechnungen sind Modi derselben Instanz, keine eigenen
Module. Das bestehende Jahres-Calendar und das aktuelle Pie bleiben unverändert.

Geprüfte Ausgangspunkte sind CalendarData, StateTimelineData, der Pie-Renderer,
Gateway-`archive.read` und `archive.states.read`, Variablenidentität und
-darstellung, Vorschauverdrahtung, Animation, Theme, Designerstruktur und
IPSView-Transport. Vorhandene Bausteine werden wiederverwendet, zusätzliche
Gateway-Operationen und lokale Änderungen synchronisierter Helper sind unnötig.

## Persistente Eingaben und Zeitvertrag

`DataMode` wählt `average`, `counter` oder `states`. `Month` ist leer für den
aktuellen Monat oder enthält ein explizites `YYYY-MM` ab 1971 bis zum aktuellen
Monat. Der vorhandene abgesicherte `WindowOffset` lädt höchstens 120 frühere
Monate ohne Property-Schreibzugriff. Alle Modi verwenden die Symcon-Zeitzone,
lokale Monats-/Tagesgrenzen und ausschließlich abgeschlossene Tage. Ein
Monatswechsel im Browser verändert nicht die gespeicherte Auswahl.

Numerische Modi verwenden `Sources` mit 1–5 eindeutigen Integer-/Float-IDs,
optionalen Namen und Farben. Gleiche sichtbare Namen bleiben getrennt.
Ohne Normalisierung muss die wirksame Einheit gleich sein. `states` verwendet `StateVariableID`
(Boolean oder Integer) und `States` mit eindeutigen Zustandswerten, Namen und
Farben. Inaktive Eingaben bleiben gespeichert, werden aber nicht abgefragt
oder als aktive Quellen referenziert. Aktuelle Quellwerte ersetzen nie Archive.

## Fachliche Datengrundlagen

- `average`: unveränderte Standardarchiv-Tagesmittelwerte aus `archive.read`,
  Tagesaggregation und explizitem Reducer `average`. Keine Mittelung von
  Stundenmitteln. Teilweise aufgezeichnete Tagesaggregate bleiben zulässig.
- `counter`: dieselbe Tagesabfrage mit explizitem Reducer `sum` ausschließlich
  für Zählerarchive. Laut offizieller Symcon-Dokumentation enthält `Avg` hier
  die Summe positiver Deltas; das Modul interpretiert keine negativen Sprünge
  selbst und addiert keine kumulativen Zählerstände.
- `states`: eine begrenzte Rohabfrage mit Vorgänger über `archive.states.read`.
  StateTimelineData bildet halboffene Intervalle. Diese werden an lokalen
  Tagesgrenzen geschnitten; die Summe einschließlich unbekannter Zeit entspricht
  exakt der realen Tageslänge. Unzugeordnete Zustände bleiben numerisch sichtbar.

Ohne Normalisierung müssen numerische Kreisanteile endlich und nichtnegativ sein. Fehlende Quellen
ergeben einen ausdrücklich unvollständigen Tag ohne Kreis; verfügbare Werte
und fehlende Kategorien bleiben im Modell/Tooltip. Vollständige echte Nulltage
werden als Nulltage markiert, nie als gleich große Anteile. Laufende und
zukünftige Tage bleiben ausstehend. Unbekannte Zustandszeit erhält einen eigenen
grauen Anteil und wird nicht stillschweigend aus dem Nenner entfernt.
Monats- und Tagesgrenzen werden aus unabhängigen lokalen Datumswerten gebildet,
damit ein Sommerzeitwechsel um Mitternacht keine Stunde in Folgetage verschiebt.
Vollständig übersprungene lokale Datumswerte enthalten keine reale Zeit und
werden aus den Tagesdaten ausgelassen; Datumsschlüssel bleiben eindeutig.
Zu viele Rohwechsel, widersprüchliche oder gekürzte Antworten führen zu einem
sichtbaren Fehler statt Sampling oder angenommener Vollständigkeit.

## Ausgabe und Nachweise

### Ergänzung: optionale Normalisierung (2026-10-18)

`NormalizeValues` ist standardmäßig `false`; alte `Sources` bleiben unverändert
gültig. Bei Aktivierung verwenden numerische Quellen zusätzlich `Minimum` und
`Maximum` (Defaults 0/100). Die endliche Differenz muss positiv sein. Die Grenzen
werden nicht aus Profilen oder Monatswerten übernommen. Der begrenzte Wert wird
auf 0–100 abgebildet; auch negative Originalwerte sind dann zulässig.
Unterschiedliche Einheiten sind ausdrücklich erlaubt. Source-IDs bleiben die
Identität, unabhängig von Einheit oder Namen.

Im additiv erweiterten Schema-v1 kennzeichnet `normalized` den aktiven Modus.
`slice.value` bleibt das gezeichnete Gewicht. Nur normalisierte numerische Slices
ergänzen `originalValue`, `unit`, `decimals`, `minimum` und `maximum`. Die originale
Einheit/Präzision stammt pro Variable aus dem bestehenden Presentation-Resolver;
globale Formatierungsfelder sind inaktiv und werden nicht umgeschrieben.
Tooltip und optionale Innenlabels verwenden die Originalzahlen, Winkel und
Nulltagbewertung ausschließlich die Gewichte. Unvollständige Tage werden nicht
renormalisiert. `states` ignoriert die Option vollständig. Beide Vorschauen
verwenden dieselbe reine Normalisierungsfunktion mit synthetischen Originalen.
Ungültige aktive Bereiche ergeben einen Hinweis, keine plausible Ersatzgrafik.

Beide Designer besitzen Theme, Typografie/Farben, Kalenderzellen, Kreisradius,
Donut-Innenradius, Startwinkel, Drehrichtung, Kontur, Labels, Legende und
Animation. Historische Vorschauen sind synthetisch und schreibfrei. Der native
Kachelkopf bleibt geschützt. Die gepinnte unveränderte ECharts-6.1.0-Runtime
enthält Calendar und Pie sowie benötigte Komponenten, keine externen CDNs.
Schema-v1 und bestehende Datenfluss-IDs bleiben kompatibel.

Die neue Instanz benötigt eine erneute Live-Abnahme nach dem Modulupdate in
Symcon und IPSView. Lokale Tests ersetzen diese nicht.

Der Zählervertrag wurde über die versionsbezogene MCP-Dokumentation
`AC_GetAggregatedValues` anhand der
[offiziellen Referenz](https://www.symcon.de/en/service/documentation/module-reference/core-instances/archive-control/ac-getaggregatedvalues/)
geprüft. Die neue Runtime bleibt auf der vorhandenen Version 6.1.0; Herkunft,
Integrität und reproduzierbarer Build stehen in den Fremdkomponenten-Hinweisen.
Die native `LabelLayout`-Funktion unterdrückt überlappende Innenwerte. Zusätzlich
werden deren vollständige Textgrenzen gegen Kreis und Donut-Innenraum geprüft.
Einheiten bleiben im Tooltip; Innenlabels enthalten nur Zahlen.

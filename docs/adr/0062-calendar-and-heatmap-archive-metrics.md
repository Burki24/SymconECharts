# ADR 0062: Kennzahlen für Calendar und Heatmap

- Status: Angenommen
- Datum: 2026-10-19
- Entscheider: Burki24
- Ergänzt: ADR 0018, 0041, 0043 und 0052

## Entscheidung und Kompatibilität

Calendar und Heatmap erhalten eine ausdrückliche Property `Metric` mit den
Werten `average`, `minimum`, `maximum` und `consumption`. Standard ist weiterhin
`average`. Die Kennzahl gehört zum gemeinsamen fachlichen Datenmodell einer
Instanz, nicht zum Kachel- oder IPSView-Designer. Quellen, Zeiträume, Idents,
GUIDs und bereits gespeicherte Designs werden nicht verändert. Fehlende
Metric-Felder älterer Renderermodelle bedeuten weiterhin Mittelwert.

Die Auswahl steht vor den Designern und reagiert auch im ungespeicherten
Formularentwurf. Vorschauen bleiben synthetisch, gekennzeichnet und frei von
Archivabfragen. Bestehende Properties wie `ShowValues` werden nicht umbenannt;
ihre Beschriftung bezeichnet nun allgemein Werte statt ausschließlich Mittelwerte.

## Archivsemantik

Die vorhandene Gateway-Operation `archive.read` unterstützt die benötigten
Reducer bereits. Sie wird weder erweitert noch automatisch auf einen anderen
Reducer umgeschaltet. Die Familien validieren Archiveigenschaft und
`EffectiveReducer` der Antwort gegen die ausgewählte Kennzahl.

| Kennzahl | Archivtyp | Gateway-Reducer | Calendar je Tag | Heatmap je Wochentag/Stunde |
| --- | --- | --- | --- | --- |
| Mittelwert | Standard | `average` | Tagesaggregat `Avg` | Arithmetisches Mittel der vorhandenen Stundenmittel |
| Minimum | Standard | `minimum` | Tagesaggregat `Min` | Minimum aller vorhandenen Stundenminima |
| Maximum | Standard | `maximum` | Tagesaggregat `Max` | Maximum aller vorhandenen Stundenmaxima |
| Zählerverbrauch | Zähler | `sum` | Tagesverbrauch aus `Avg` | Summe der vorhandenen Stundenverbräuche |

Die [offizielle Symcon-Referenz zu AC_GetAggregatedValues](https://www.symcon.de/en/service/documentation/module-reference/core-instances/archive-control/ac-getaggregatedvalues/)
wurde am 19.10.2026 über die MCP-Dokumentation geprüft. Bei Standardarchiven
ist `Avg` ein Mittelwert, bei Zählerarchiven die Summe positiver Änderungen
des Intervalls. Der Verbrauch ist nicht der Zählerstand und keine selbst
gebildete Differenz zwischen erstem/letztem Punkt. Symcons Zähler-/Resetlogik
bleibt maßgeblich; es wird keine eigene Resetkompensation eingeführt.
Eine Leistung in Watt wird nicht automatisch zu Energie integriert.

Minimum und Maximum von Standardvariablen dürfen negativ sein. Echte Nullen
bleiben gültig; Verbrauch muss nichtnegativ und endlich sein. Eine falsche
Kombination aus Kennzahl und Archivtyp führt zu einem sichtbaren Fehler,
nicht zu einem stillen Wechsel. Einheiten und Präzision folgen wie bisher
der Variablendarstellung oder den ausdrücklichen manuellen Angaben.

## Zeitgrenzen, Lücken und Budgets

Calendar liest weiterhin genau eine Quelle mit täglichen Aggregaten und
höchstens 366 Punkten eines Kalenderjahres. Laufende lokale Tage bleiben
ausgeschlossen. Teilarchive und ein Aufzeichnungsbeginn innerhalb eines
Tages bleiben zulässig; das Tagesaggregat beschreibt nur die aufgezeichnete
Zeit. Fehlende Tage werden nicht als Null ergänzt. Die Jahresnavigation
bleibt rein lesend und pro Darstellung unabhängig.

Heatmap verwendet weiterhin abgeschlossene stündliche Aggregate. Die Woche
zeigt datierte Tage, längere Zeiträume gruppieren nach lokalem Wochentag und
Stunde. Dabei wird die gewählte Kennzahl auch bei der zweiten Reduktion
beibehalten: kein Mittelwert von Minima/Maxima und keine Durchschnittsbildung
von Verbrauchssummen. Bei Sommerzeitende tragen beide realen Stunden bei:
beim Mittelwert mit gleichem Gewicht, bei Min/Max als jeweilige Extrema und
beim Verbrauch beide additiv. Fehlende Frühlingsstunden werden nicht erzeugt.
`count` zählt weiterhin tatsächlich berücksichtigte Stundenintervalle.

Die bestehenden lückenlosen, begrenzten Archivabschnitte von höchstens
60 Tagen und die 2000-Punkte-Grenze je Anfrage bleiben erhalten. Gekürzte
Antworten werden nicht als vollständige Kennzahl ausgegeben. Summen dürfen
den endlichen Zahlenbereich nicht überschreiten. Die Ausgabe bleibt bei
höchstens 7 × 24 Zellen und unter dem Symcon-HTML-Pufferlimit.

Kennzahl und Reduktionsart werden in Beschriftung, Tooltip und Aria korrekt
benannt; längere Heatmap-Verbrauchsfenster heißen ausdrücklich Summe statt
Durchschnitt. Bestehende Celsius-Farbvorlagen bleiben Temperaturvorlagen,
keine universelle Verbrauchsskala.

## Nachweise und Grenzen

Gezielte zunächst rote Tests belegen die bisher fehlende Kennzahlenauswahl.
Die Erweiterung wird an reinen Datenfunktionen, realen Modul-/Gatewaypfaden
mit Kernel-Doppeln und an der gepinnten ECharts-Runtime abgesichert. Dazu
gehören unterschiedliche `Avg`/`Min`/`Max`, Verbrauch statt Zählerstand,
Standard-/Zähler-Mismatches, negative und Nullwerte, ungültige Metadaten,
Trunkierung, DST, Schaltjahre, Lücken und Navigation. Formular-/Vorschautests
prüfen Entwürfe ohne Archivzugriff oder persistierende Seiteneffekte.

Die Library nutzt dieselben ECharts-Runtimes; neue Komponenten oder veränderte
Fremdartefakte sind nicht erforderlich. Lokale Browserprüfungen mit synthetischen
Daten ergänzen die Tests. Die Live-Abnahme der neuen Kennzahlen in Symcon-Kachel
und IPSView erfolgt nach Modulupdate durch den Eigentümer. Bei dieser
Implementierung werden weder Live-Instanzen noch Archivkonfigurationen geändert.

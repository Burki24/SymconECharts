# ADR 0069: Sechzehn ThemeRiver-Quellen mit optionaler Bandauswahl

- Status: Angenommen
- Datum: 2026-10-19
- Entscheider: Burki24
- Ergänzt: ADR 0058, ADR 0067, ADR 0068

## Entscheidung und unveränderte Regeln

ThemeRiver erlaubt 1–16 eindeutige numerische Standard-Archivvariablen gleicher
Einheit. Höchstens 720 abgeschlossene Stunden-/Tagesintervalle werden je Quelle
dargestellt. Nichtnegative endliche Mittelwerte, echte Nullen, strenger
gemeinsamer Join und fehlende Intervalle bleiben unverändert. Keine Zähler,
Interpolation, Nullauffüllung oder Ersetzung durch aktuelle Werte.

`ThemeRiverData` besitzt die zentralen Quellen-/Ladegrenzen: 16 Quellen,
1000 empfangene Aggregate je Quelle und 16000 über die gesamte Aktualisierung.
`CountLoadedAggregates` zählt vor dem Join, nicht erst nach Weglassen fehlender
Intervalle. Der Renderer darf Quellen ausblenden, aber niemals das Ladebudget
oder die Verfügbarkeit eines ursprünglichen gemeinsamen Intervalls ändern.
Übergroße oder gekürzte Antworten führen zu einem expliziten Fehler statt
einer stillschweigend vollständigen Teilansicht. Gateway und Cache bleiben gleich.

## Additive Konfiguration und Ansicht

`EnableBandSelection` ist standardmäßig `false`. Bestehende Konfigurationen
zeigen damit weiterhin alle Bänder und ihre nicht auswählbare Scroll-Legende.
Das optionale boolesche Quellenfeld `InitiallyVisible` fehlt in Altlisten und
gilt als `true`; es wird erst bei aktivierter Auswahl wirksam. Schema 1 erhält
additive Felder `themeRiver.enableBandSelection`, `sources[].initiallyVisible`
und Ladebudgetinformationen in `range`. Properties, Listen, GUIDs, Präfix,
Ident, Farben und Zeitvorgaben werden nicht migriert oder umgeschrieben.

Die gemeinsame ID-basierte Browserauswahl von TimeSeries/BarHistory wird über
`echarts-series-selection.js` wiederverwendet. Auswahl und Legendenanker bleiben
bei Aktualisierung, Umsortierung und Resize erhalten; neue/geänderte Anfangswerte
gelten für die jeweilige ID. Ein Wechsel des globalen Auswahlmodus setzt den
flüchtigen Auswahlzustand zurück. Reload verwendet die Anfangswerte. Keine
Browserauswahl wird in Symcon geschrieben.

Aktive Auswahl bietet Einzelwahl, Alle und Umkehren. Die Legende wird dann
auch bei `ShowLegend=false` eingeblendet, um versteckte Bänder wiederherstellbar
zu halten. Ohne Auswahl bleibt die Designerentscheidung unverändert. Vollständig
ausgewählt, nichts gewählt, echte Gesamt-Null und ausgewählte Nullbänder mit
positiven versteckten Quellen werden unterscheidbar behandelt.

## Familienbezogene Projektion

Originalzeilen einschließlich `values=null` bleiben unverändert im Modell.
Nur vollständige Abschnitte werden mit den ausgewählten Bandspalten gezeichnet.
Der gemeinsame Maßstab wird für diese sichtbare Projektion über alle Abschnitte
bestimmt. Originalwerte, Einheiten und Zeitzonenintervalle bleiben im Tooltip.
Die Auswahl kann fehlende Werte versteckter Quellen nicht nachträglich heilen.
Native ThemeRiver-Nullauffüllung wird weiterhin durch getrennte vollständige
Abschnitte vermieden. Leere benannte Serien erhalten die native Legendenauswahl
für versteckte Quellen, ohne Epsilonwerte oder Ersatzmessungen zu erzeugen.

## Ressourcen, Vorschau und Nachweise

Modelle sind auf 900000 JSON-Bytes begrenzt, vollständiges HTML auf 1 MiB.
`EChartsStartupState` und `echarts-startup.js` liefern die gemeinsame verlustfreie
Startkompression oberhalb von 200000 HTML-sicheren JSON-Bytes. Neuere Vollzustände
und Unload verwerfen verzögert entpackte alte Zustände. Browser benötigen dafür
gzip-`DecompressionStream`. Runtime, Lizenz und Lieferartefakte bleiben gleich.

Die schematische Vorschau zeigt höchstens acht wirksam anfangs sichtbare Bänder,
ursprüngliche Farbindizes, eine unveränderte Beispiellücke und Ausschnittszahlen.
Alle versteckt bedeutet keine erfundenen Bänder; die Legende bleibt vorhanden.
Vorschauen lesen keine Archive und persistieren keine Entwürfe.

Grenz-, Budget-, Altlisten-, Lücken-, Null-/Fehler-, Ausgabe- und ID-Tests sowie
native ECharts-Rendererprüfungen sichern den Vertrag. Eine lokale Canvas-Prüfung
ergänzt die Tests; die nachfolgende erneute Live-Abnahme in Symcon-Kachel und IPSView ist
durch den Eigentümer bestätigt. Live-Konfigurationen und Archive bleiben unangetastet.

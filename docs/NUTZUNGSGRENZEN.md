# Prüfung der Nutzungsgrenzen

Stand: 18.10.2026. Geprüft wurden alle 29 Diagrammfamilien und das Gateway:
Quellenvalidierung, Datenaufbereitung, Renderer, Vorschau, Benutzerdokumentation
und vorhandene Tests. Das leere Altverzeichnis `EChartsGauge` ist kein Modul.
Diese Bestandsaufnahme ist keine Performancefreigabe für die vorgeschlagenen
größeren Modelle und keine erneute Live-Abnahme sämtlicher Familien.

## Ergebnis und Reihenfolge

Viele Obergrenzen stammen aus der ersten gut prüfbaren Vertikale, nicht aus
einer fachlichen ECharts-Grenze. Andere Beschränkungen verhindern hingegen
falsche Summen, erfundene Archivwerte, unlesbare Geometrie oder übergroße
Symcon-Ausgaben. Deshalb werden nicht pauschal alle Grenzen auf 100 gesetzt.

Inzwischen wurden **PictorialBar und BarCategory auf 100 Quellen** erweitert.
PictorialBar besitzt außerdem eine anpassbare Bezeichnungsspalte. Die übrigen
Punkte sind Vorschläge; ihre Verträge bleiben unverändert.

| Reihenfolge | Empfohlener nächster Schritt | Nutzen und Voraussetzung |
| --- | --- | --- |
| Erledigt | PictorialBar: 1–100 Quellen, Kategoriennavigation | Alle 72 Batterievariablen passen in eine Instanz; lesbarer Ausschnitt statt 100 zusammengedrängter Zeilen. |
| Erledigt | BarCategory: bis 100 Quellenzuordnungen, Kategoriennavigation | Größere Gerätevergleiche; die Obergrenze gilt für sämtliche Zuordnungen zusammen, nicht je Kategorie oder Reihe. |
| 2 | StateTimeline: zunächst 32 Quellen mit Zeilennavigation | Fenster, Türen und Verbraucher gemeinsam darstellen; Gesamtbudget für Zustandswechsel und Ausgabe ergänzen. 100 erst nach Lastprüfung. |
| 3 | Boxplot: bis 32 Quellen und Kategoriennavigation | Viele Räume/Sensoren vergleichen; gemeinsames Archivabfragebudget statt 32 unbeschränkter Einzelabfragen. |
| 4 | Benutzerdefinierte Archivzeiträume | Vorhandenen Zeitfenstervertrag dort wiederverwenden, wo bisher nur 24 h / 7 d / 30 d angeboten werden. |
| 5 | Calendar/Heatmap: passende Tages-/Stundenkennzahlen | Minimum, Maximum und ausdrücklich definierter Zählerverbrauch statt ausschließlich Mittelwert. |
| 6 | Explizite Normalisierung für weitere Vergleiche | Bei Pie, BarCategory, BarPolar und PictorialBar optional unterschiedliche Einheiten vergleichbar machen; Originalwerte erhalten. |
| Danach | Tiefere Hierarchien, größere Graphen, weitere Reihen | Nur zusammen mit Navigation, Vorschaukennzeichnung und messbaren Ressourcenbudgets. |

Normalisierung ist keine allgemeine Lösung für unterschiedliche Einheiten:
Sie kann relative Vergleiche sinnvoll machen, ersetzt aber keine physikalische
Summe. Waterfall-Änderungen und Sankey-Flüsse dürfen nicht unabhängig voneinander
normalisiert und anschließend als unveränderte Mengenbilanz ausgegeben werden.

## Aktuelle Werte und Strukturen

Die folgenden Dateiverweise zeigen die maßgeblichen Verträge. Angegebene
Erweiterungszahlen außerhalb PictorialBar sind Planungsziele, keine implementierten
oder bereits lastgeprüften Zusagen.

| Familie | Aktueller Vertrag | Bewertung und sinnvolle Erweiterung |
| --- | --- | --- |
| EChartsGaugeSingle | Genau eine numerische Quelle; eigener Wertebereich, Einheit und Präzision; negative Werte zulässig. Bis acht Farbzonen. | Eine Quelle gehört zur Familie und bleibt. Mehr Farbzonen erst bei konkretem Bedarf. Mehrere Instrumente gehören zu GaugeMulti. |
| EChartsGaugeMulti | 2–16 Quellen, jeweils eigene Skala und Einheit. | 16 ist eine Layoutgrenze. Perspektivisch 32–100 mit Seiten/Raster und Auswahl, nicht 100 konzentrische Ringe auf derselben Kachel. |
| EChartsGaugeTacho | 2–5 Quellen mit unabhängigen Skalen; festgelegte Instrumentenanordnung. | Fünf ist durch die besondere Geometrie begründet. Neue Anordnungen wären Voraussetzung für mehr; reine Grenzerhöhung nicht empfohlen. |
| EChartsGaugeChronograph | 2–5 Quellen als Hauptinstrument mit Nebeninstrumenten. | Ebenso Geometrievertrag. Optional ein einzelnes Hauptinstrument erst nach ausdrücklicher Produktentscheidung; kein allgemeiner 100-Quellen-Kandidat. |
| EChartsBarCategory | Jetzt 1–100 numerische Quellenzuordnungen; gleiche effektive Einheit; auch vorzeichenbehaftete Werte. Kategorien und Reihen, gruppiert/gestapelt/einfach. | Umgesetzt: Kategoriennavigation oberhalb 16 Kategorien, vollständige Skala und viewportstabile Updates. Optional Min/Max-Normalisierung für relative Vergleiche weiterhin offen; unterschiedliche Roh-Einheiten nicht stillschweigend stapeln. |
| EChartsBarWaterfall | Startwert plus 1–15 signierte Änderungen, also 2–16 Quellen; gleiche Einheit; Endsumme berechnet. | Bis 100 Schritte mit Navigation sinnvoll. Zusätzliche Zwischensummen/Neustarts wären fachlich wertvoller als eine alleinige Grenzerhöhung. Gemeinsame additive Einheit beibehalten. |
| EChartsBarPolar | 1–16 Quellen gleicher Einheit; gemeinsame Skala, auch negative Werte werden nicht einfach in Beträge umgewandelt. | Größere Modelle nur mit Auswahl/Seiten und lesbarer Ring-/Sektorbreite. Optionale Normalisierung eignet sich für relative Vergleiche. Nicht 100 Ringe in eine kleine Kachel zwingen. |
| EChartsPie | 1–16 nichtnegative Quellen gleicher Einheit; Null wird ausdrücklich behandelt. | Optionale Normalisierung nach CalendarPie-Vorbild. Größere Listen mit scrollbarer Legende und ausdrücklich gewähltem Restsegment/Top-N; keine unsichtbar ausgelassenen Werte. |
| EChartsFunnel | 1–16 nichtnegative numerische Quellen gleicher Einheit. Null gültig; manuelle oder wertbezogene Reihenfolge, keine erzwungene Abnahme zwischen Stufen. | 16 ist eine Darstellungsgrenze. 32, später 100 nur mit Navigation und Beschriftungsprüfung. Negative Trichterbreiten sind keine sinnvolle Aufhebung. Der gemeinsame Resolver behält hier die Grenze 16. |
| EChartsPictorialBar | Jetzt 1–100 nichtnegative numerische Quellen gleicher Einheit; Null gültig. Feste/automatische Skala, einheitliche Wertfarbe oder interner Farbverlauf, Icons und eigene SVGs. | Umgesetzt: responsive Kategoriennavigation oberhalb 16, ohne Datenverlust. Vorschau zeigt dann ausdrücklich die ersten acht sortierten Kategorien. Später optionale Normalisierung und Mehrfachauswahl nach Objektzweig/Filter. |
| EChartsTree | Objektwurzel, Kategorien/Instanzen/Variablen; Tiefe 1–6; 10–3000 sichtbare Knoten, Standard 80; Suchbudget 30000 Objekte und Modellbudget 200000 Bytes. | Tiefe sechs ist für reale Anlagen teilweise eng. 12–20 Ebenen bei unveränderten Knoten-/Such-/Bytebudgets prüfen. Links/Medien/Skripte nur mit expliziter Bedeutung und Zyklusbehandlung ergänzen. |
| EChartsTreeSystemHealth | Gleiche Hierarchiegrenzen; bis 3000 ausdrückliche Boolean-/Zahlenregeln; Vorfahrenbewertung nur anhand des berücksichtigten Baums. | Wie Tree: tiefere begrenzte Zweige. Regeln, ausgeschlossene Zweige und unvollständige Sicht müssen erkennbar bleiben; keine vermeintliche Gesamtgesundheit aus einer abgeschnittenen Teilmenge. |
| EChartsGraph | 2–80 Objektknoten, 1–200 ungerichtete Kanten; keine Selbstkanten oder doppelten ungeordneten Paare. Isolierte Knoten sind im ansonsten verbundenen Modell erlaubt. | 300 Knoten/1000 Kanten nach Force-/Layoutprofiling prüfen. Gerichtete oder gewichtete Beziehungen benötigen einen ausdrücklich erweiterten Vertrag, nicht nur neue Höchstzahlen. |
| EChartsGraphZ2M | Nur gespeicherter Export, kein Scan; bis 500 Rohknoten/10000 Rohkanten. Sichtbare Verbindungen 1–2000, Standard 300; LQI 0–255; Export höchstens 16 MiB. | Schutzgrenzen zunächst behalten. Schwache Verbindungen gezielt auswählbar machen, statt nur die stärksten zu zeigen. Mehrdeutige Exportproduzenten nicht automatisch zusammenwerfen und keinen Scan versteckt auslösen. |
| EChartsTreemap | 1–100 nichtnegative Blätter; feste Ebenen Gruppe/Untergruppe/Eintrag. Null besitzt keine Fläche. Eine gemeinsame Einheit wird nicht erzwungen; Anzeigeeinheit ist global. | Rekursive Hierarchie mit explizitem Tiefen-/Knotenbudget. Optional normalisierte Gewichte und Originalformat je Blatt. Fehlende Einheitenprüfung ist keine bereits vorhandene saubere Mehr-Einheiten-Unterstützung. |
| EChartsSunburst | Gleiches 1–100-Blatt-Modell mit Gruppe/Untergruppe/Eintrag. | Rekursive Hierarchie sinnvoll; navigierbare Ebenen statt beliebig vieler unlesbarer Ringe. Originaleinheiten und optionale Gewichte ebenso ausdrücklich modellieren. |
| EChartsSankey | 2–100 Knoten, 1–200 gerichtete nichtnegative Flüsse; azyklisch, keine Selbstkanten/doppelten gerichteten Paare. Gleiche Variable an mehreren Kanten möglich. Gleiche Einheit dokumentiert, derzeit nicht je Quelle validiert. | Zunächst Einheitenmodell/Validierung sauber machen. Optional negative Werte durch ausdrücklich gewählte Kantenumkehr behandeln, danach erneut Zyklen prüfen. Rest-/Bilanzknoten können helfen. DAG und nichtnegative Breiten sind keine unnötigen Beschränkungen. |
| EChartsRadar | 3–10 Achsen, 1–8 vollständige Vergleichsgruppen; je Achse eigener Min/Max-Bereich, Einheit und Präzision. Unterschiedliche Einheiten bereits sinnvoll skaliert. | Mindestens drei Achsen sind geometrisch erforderlich. 12–16 Achsen/16 Gruppen erst mit Filter-/Layoutkonzept. Unvollständige Gruppen optional auslassen und kennzeichnen, fehlende Werte nicht als null erfinden. Inverse Achsen wären eine gesondert beschriftete Erweiterung. |

Maßgebliche Implementierungen:

- Quellen-/Familienvalidierung in den jeweiligen `ECharts*/module.php`.
- Funnel/PictorialBar: `libs/EChartsCurrentComparisonSources.php`.
- Hierarchien: `libs/TreeHierarchy.php`, `EChartsTreeSystemHealth/TreeHealth.php`.
- Graphen: `libs/GraphModel.php`, `libs/ZigbeeGraphModel.php`,
  `EChartsGraphZ2M/ZigbeeGraphExportReader.php`.
- Flächen/Flüsse/Achsen: `libs/TreemapModel.php`, `EChartsSankey/SankeyModel.php`,
  `EChartsRadar/RadarModel.php`.
- Geometrie und Navigation: die jeweiligen `visualization/app.js`.

## Archivfamilien

Mehr Variablen, größere Zeiträume und andere Kennzahlen sind getrennte
Erweiterungen. Eine stündliche Mittelwertverteilung ist zum Beispiel keine
Verteilung sämtlicher Rohmessungen; ein Mittelwert darf auch nicht als
Candlestick-Anfangswert ausgegeben werden.

| Familie | Aktueller Vertrag | Bewertung und sinnvolle Erweiterung |
| --- | --- | --- |
| EChartsTimeSeries | 1–8 Integer-/Float-Quellen; insgesamt 200–8000 Punkte, höchstens 2000 je Quelle; bis 32 Annotationen. Roh-/Realtime-/Auto- und feste Aggregationen; unterschiedliche Einheiten und benutzerdefinierte Zeitfenster bereits möglich. | 16–32 Reihen bei unverändertem Gesamtpunktbudget und Reihenfilter prüfen. Keine falsche neue Einheitenbeschränkung einführen; 32 unabhängige Achsen sind keine gute Standardansicht. |
| EChartsBarHistory | 1–16 Quellen, insgesamt 200–2000 Punkte; Roh-/Auto-/feste Aggregationen mit verschiedenen Reduktionen. Unterschiedliche Einheiten bereits möglich. | Bis 32 mit Dichte-/Auswahlkonzept. 100 Quellen bei 2000 Gesamtpunkten wären nur etwa 20 Punkte je Quelle und nicht automatisch hilfreicher. Zeitstempel nicht künstlich aufeinander schieben. |
| EChartsScatter | X und Y, optional eine Größenquelle: genau 2–3 Rollen, keine allgemeine Quellenliste. Gemeinsame abgeschlossene Archivintervalle ohne Interpolation; unterschiedliche Einheiten erlaubt. Insgesamt 200–2000 Punkte über die Rollen verteilt. | Drei ist keine willkürliche Vergleichsobergrenze. Mehrere benannte X/Y-Gruppen benötigen ein Gruppenmodell. Optional feste Min/Max-Grenzen für Bubble-Größen statt allein beobachteter Extrema. |
| EChartsHeatmap | Eine numerische Quelle; Wochentag × Stunde, feste Zeitfenster von einer Woche bis zwölf Monate/Jahresbeginn. Abgeschlossene Stundenmittel, bei längeren Zeiträumen Mittel gleicher Wochentag-Stunden. | Weitere Kennzahlen und ausdrücklich definierter Zählerverbrauch. Benutzerdefinierte Zeiträume/Vergleichspanels. Die einzelne Quelle ist für diesen Feldinhalt schlüssig, nicht einfach 100 Quellen in dieselbe Zelle mischen. |
| EChartsParallel | 2–10 Achsen; 1–1000 gemeinsame Zeitlinien, Standard 750; 24 h / 7 d / 30 d sowie Stunde/Tag. Standardarchive als Mittel, Zähler als Verbrauch; unterschiedliche Einheiten zulässig, nur gemeinsame Intervalle. | Benutzerdefinierte Zeitfenster und Reduktion/Skala je Achse zuerst. 16–20 Achsen nur mit vergrößerbarer/verschiebbarer Achsenansicht. Mindestens zwei Achsen gehört zur Diagrammform. |
| EChartsCalendar | Eine numerische Standard-Archivquelle; abgeschlossene Tagesmittel eines Jahres (1971 bis aktuelles Jahr), höchstens 366 Tage. Teilarchive, negative Werte und null funktionieren. | Tagesminimum/-maximum und Zählerverbrauch ergänzen. Monat/Quartal/freies Fenster als Auswahl. Mehrere Tageswerte gehören zu CalendarPie oder ausdrücklich getrennten Kalenderpanels. |
| EChartsCalendarPie | Je nach Modus 1–5 numerische Quellen oder eine diskrete Zustandsquelle; Tagesmittel, Zählerverbrauch oder Zustandsdauer. Bis 32 Zustände. Optionale Min/Max-Normalisierung samt Originaleinheiten bereits vorhanden; Monatsmodell höchstens 250000 Bytes. | 1–5 ist die ausdrückliche aktuelle Produktentscheidung, keine sofort aufzuhebende Grenze. Optional verfügbare Teilquellen eines Tages anzeigen, aber nur mit deutlichem Unvollständigkeitshinweis; strenger Standard bleibt sinnvoll. |
| EChartsBoxplot | 1–10 Standard-Archivquellen gleicher Einheit; 24 h / 7 d / 30 d; bis 1000 abgeschlossene Stundenmittel je Quelle. R7-Quartile und Min/Max-Whisker, nicht Rohwertverteilung oder 1,5-IQR-Ausreißermodell. | Hohe Priorität: 32 Quellen plus Navigation und gemeinsames Abfragebudget. Freie Zeiträume. IQR/Ausreißer als ausdrücklich anderer Modus; unterschiedliche Einheiten nur normalisiert oder in getrennten Panels vergleichen. |
| EChartsCandlestick | Eine numerische Standard-Archivquelle; Stunde/Tag in 24 h / 7 d / 30 d. Tatsächliche Rohwerte für ersten/letzten Wert und Minimum/Maximum; leere Intervalle bleiben leer. Bis 63 Abfragen/64000 Rohänderungen pro Aktualisierung. | Freie Zeitfenster, weitere feste Intervalle und Zeitzoom. Mehrere benannte Panels später; Rohwert- und Abfragebudgets behalten. Einzelmessungen ergeben flache Kerzen, keine erfundenen Endpunkte. |
| EChartsThemeRiver | 1–8 nichtnegative Standard-Archivmittel gleicher Einheit; Stunde/Tag, 24 h / 7 d / 30 d; höchstens 720 Intervalle und 1000 geladene Aggregate je Quelle. Fehlende Quelle unterbricht den gemeinsamen Abschnitt. | Zählerverbrauch, freie Zeiträume und anschließend 16–20 Bänder mit Gesamtbudget prüfen. Optional normalisierte relative Gewichte; keine negativen Flussbreiten und keine stillschweigende Nullfüllung von Archivlücken. |
| EChartsStateTimeline | 1–8 Boolean-/Integer-Standardarchive, bis 32 Zustandsdefinitionen je Quelle; 24 h / 7 d / 30 d; bis 2000 Wechsel je Quelle plus Vorgänger; Modell höchstens 250000 Bytes. Unbekannte Zustände sichtbar. | Hohe Priorität: 32, später nach Lastprüfung 100 Quellen mit Zeilennavigation und gemeinsamem Wechselbudget. Freie Zeiträume. Float-/String-Zustände erst mit ausdrücklichem Gleichheits-/Zuordnungsvertrag. |

Maßgebliche Implementierungen:

- `libs/EChartsArchiveQuery.php` und Quellenvalidierung in `ECharts*/module.php`.
- `EChartsHeatmap/HeatmapWindow.php` und `HeatmapAggregation.php`.
- `EChartsScatter/ScatterPairing.php`, `EChartsParallel/ParallelPairing.php`.
- `EChartsCalendar/CalendarData.php`, `EChartsCalendarPie/CalendarPieData.php`.
- `EChartsBoxplot/BoxplotStatistics.php`,
  `EChartsCandlestick/CandlestickData.php`.
- `EChartsThemeRiver/ThemeRiverData.php`,
  `EChartsStateTimeline/StateTimelineData.php`.

## Gemeinsame Grenzen und Vorschauen

Das Gateway hat keine allgemeine 16-Quellen-Grenze. `current.read` liest
allerdings eine Quelle pro Aufruf: 100 Quellen bedeuten mehr Initialisierungs-
und Aktualisierungsarbeit, nicht einen kostenlos vergrößerten Sammelaufruf.
Das ist bei späteren Massenauswahlen zu messen. Archivantworten bleiben auf
2000 Einträge je Anfrage begrenzt, der Cache auf 32 Einträge mit 15 Sekunden
Lebensdauer (`EChartsGateway/module.php`). Größere Familien sollen gezielt
portionieren und ein Gesamtbudget besitzen, nicht diese Schutzgrenzen entfernen.

Der Symcon-Ausgabepuffer von 1 MiB ist eine reale Randbedingung. Vorhandene
Modellbudgets von 200000/250000 Bytes berücksichtigen neben JSON auch HTML und
Einbettung. SVG-/Bildimportgrenzen schützen Parser und Ausgaben und sind keine
willkürlichen Quellenbeschränkungen. Ein Ausbau muss die größte erlaubte
Importform zusammen mit der größten Quellenliste prüfen.

Vorschauen sind gesondert zu betrachten:

- `libs/EChartsBarPreview.php::Sources()` akzeptiert standardmäßig höchstens
  16 Quellen, BarCategory verwendet ausdrücklich 100. Andere Familien bleiben
  unverändert. Bei ungeeigneten Listen kann der Baustein auf Beispieldaten
  zurückfallen. Eine neue Obergrenze nur in `module.php` wäre deshalb
  unvollständig. PictorialBar verwendet einen eigenen, vollständig validierten
  Vorschauausschnitt.
- TimeSeries (vier), BarHistory (drei), Parallel (sechs) und mehrere aktuelle
  Vergleiche begrenzen ihre Vorschau separat. Die Mengenhinweise müssen bei
  jeder Erweiterung erhalten und korrekt sein.
- Graph verwendet bei großen Modellen ein Beispiel; Tree/Health reduzieren
  Tiefe/Verzweigung, Treemap/Sunburst Gruppen. Diese Vereinfachungen sollten
  künftig noch klarer als Struktur-/Designvorschau erkennbar sein.
- Waterfall darf in einer Teilvorschau keine aus der Teilmenge berechnete
  Endsumme als die tatsächliche Gesamtbilanz erscheinen lassen.

Neue Grenztests sollen neben N und N+1 auch doppelte Anzeigenamen, unveränderte
Quellidentitäten, Sortierung, Aktualisierung, Ausrichtung, Einheiten, Lücken,
größtmögliche Imports und beide Ausgabewege abdecken. Eine Auswahl/Navigation
darf keine Daten verändern oder unsichtbar aus dem fachlichen Modell entfernen.

## Nachweise dieses Änderungsschritts und verbleibende Lücken

PictorialBar hat Regressionen für 72/100 gültige Quellen und 101 abgewiesene
Quellen, unveränderte Funnel-Grenze, beide Ausgaben unter 1 MiB sowie große
eigene SVGs und gekennzeichnete Vorschauen. Die Layoutprüfung verwendet die
festgeschriebene echte ECharts-Runtime: beide Ausrichtungen/Ausgabearten,
Rechteck/Icon/SVG, Farbverlauf, Navigation bis zum letzten Eintrag und korrekte
Werttextposition auch nach Filterung des sichtbaren Ausschnitts.

Die Folgeänderung prüft zusätzlich die automatische/manuelle Bezeichnungsspalte
und BarCategory mit 72/100/101 Quellen, signierter Gesamtskala, allen drei
Balkenmodi, nativer Navigation und stabiler Ansicht bei Aktualisierungen.
Der Puffer-Stresstest kombiniert 100 lange Bezeichnungen mit einem fast
256-KiB-großen SVG-Muster und eigenen Themes je Ausgabe. BarCategory erhält
dafür ein eigenes, lokal gebautes Runtime-Subset ohne die für diese Familie
ungenutzten Line-/Scatter-/Referenzkomponenten. Die übrigen Familien verwenden
ihre bisherigen Runtimes weiter.

Eine lokale Browseransicht mit synthetischen 72/100 Quellen wurde zusätzlich
visuell geprüft; die 100er-Ansicht lässt sich bis zur letzten Kategorie
verschieben. Der Eigentümer hat PictorialBar mit allen 72 echten Variablen
anschließend in Kachel und IPSView live bestätigt. Die neue Spaltenbreite und
BarCategory-Erweiterung sind nach dem nächsten Modulupdate gesondert live
abzunehmen. Live-Konfigurationen, Archivaufzeichnung und Quellwerte wurden bei
dieser Folgeänderung nicht verändert. Die vorgeschlagenen
Erweiterungen anderer Familien sind noch nicht umgesetzt oder lastgeprüft.

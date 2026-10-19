# Prüfung der Nutzungsgrenzen

Stand: 19.10.2026. Geprüft wurden alle 29 Diagrammfamilien und das Gateway:
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
PictorialBar besitzt außerdem eine anpassbare Bezeichnungsspalte. BarCategory
bietet optional einen festen Min-/Max-Farbverlauf innerhalb der Balken.
**StateTimeline unterstützt 32 Quellen mit Zeilennavigation**, Boxplot 32
Quellen mit Kategoriennavigation und gemeinsamem Archivbudget. Calendar und
Heatmap bieten vier Archivkennzahlen. Pie, BarCategory, BarPolar und
PictorialBar erlauben jetzt optionale Min/Max-Normalisierung. Treemap und
Sunburst unterstützen flexible Hierarchien mit bis zu acht Ebenen. Tree und
TreeSystemHealth erlauben zwölf Nachkommensebenen. EChartsGraph unterstützt
300 Knoten und 1000 Verbindungen mit gemeinsamer Graph-Ansichtsverwaltung.
TimeSeries bietet 32 Reihen mit Reihenauswahl und bis zu 32000 Gesamtpunkten.
BarHistory bietet ebenfalls 32 Reihen mit Reihenauswahl und bis zu 32000
Gesamtpunkten bei unverändertem Default von 1000.
ThemeRiver unterstützt 16 Quellen mit optionaler Bandauswahl und einem
gemeinsamen Ladebudget von 16000 Aggregaten bei unveränderten Lückenregeln.
Die übrigen
Punkte sind Vorschläge; ihre Verträge bleiben unverändert.

| Reihenfolge | Empfohlener nächster Schritt | Nutzen und Voraussetzung |
| --- | --- | --- |
| Erledigt | PictorialBar: 1–100 Quellen, Kategoriennavigation | Alle 72 Batterievariablen passen in eine Instanz; lesbarer Ausschnitt statt 100 zusammengedrängter Zeilen. |
| Erledigt | BarCategory: bis 100 Quellenzuordnungen, Kategoriennavigation | Breiten-/fontabhängige Dichte statt festem 16-Kategorien-Fenster; die Obergrenze gilt für sämtliche Zuordnungen zusammen, nicht je Kategorie oder Reihe. |
| Erledigt | StateTimeline: 32 Quellen mit Zeilennavigation | Fenster, Türen und Verbraucher gemeinsam darstellen; insgesamt höchstens 16000 Rohwerte, bis 2000 je Quelle, weiterhin begrenzte Ausgabe. 100 erst nach Lastprüfung. |
| Erledigt | Boxplot: bis 32 Quellen und Kategoriennavigation | ID-stabiler Ausschnitt, vollständige Skala und höchstens 10000 Stundenmittel insgesamt statt 32 unbeschränkter Einzelabfragen. |
| Erledigt | Benutzerdefinierte Archivzeiträume | Boxplot, Parallel, Candlestick, ThemeRiver und StateTimeline bieten 1–1000 Minuten/Stunden/Tage/Wochen bei unveränderten Datenbudgets. |
| Erledigt | Calendar/Heatmap: passende Tages-/Stundenkennzahlen | Mittelwert, Minimum, Maximum für Standardarchive und ausdrücklicher Zählerverbrauch; Heatmap reduziert je Wochentag/Stunde passend statt alle Kennzahlen erneut zu mitteln. |
| Erledigt | Explizite Normalisierung für weitere Vergleiche | Pie, BarCategory, BarPolar und PictorialBar vergleichen optional Quellen anhand eigener Min/Max auf 0–100; Originalwerte und Einheiten bleiben erhalten. |
| Erledigt | Treemap/Sunburst: flexible Hierarchien bis acht Ebenen | Gemeinsames Modell, höchstens sieben Gruppen plus Variablenblatt, native Navigation, vollständige Strukturvorschau und begrenzte Modellgröße. |
| Erledigt | Tree/TreeSystemHealth: zwölf Nachkommensebenen | Unveränderte Defaults und Knoten-/Suchbudgets, gemeinsame Vorschaugeometrie und native Ansichtserhaltung; ausdrücklicher Health-Bewertungsumfang. |
| Erledigt | Graph: 300 Knoten und 1000 Verbindungen | ID-stabile Pan-/Zoom-Ansicht, weiterverwendete Kräftepositionen, begrenzter echter Vorschauausschnitt und Ausgabeprüfungen. Z2M-Budgets bleiben getrennt. |
| Erledigt | TimeSeries: 32 Reihen mit Reihenauswahl | Scrollbare Legende, anfangs sichtbare Reihen, ID-stabile Auswahl/Zoom und höchstens 32000 Gesamtpunkte bei weiterhin höchstens 2000 je Reihe. |
| Erledigt | BarHistory: 32 Reihen mit Reihenauswahl | Gemeinsame Auswahl-/Startlogik, tatsächliche Archivzeitstempel, bis 16 Einheitengruppen und 32000 Gesamtpunkte; Default 1000 bleibt erhalten. |
| Erledigt | ThemeRiver: 16 Quellen mit optionaler Bandauswahl | 16000 Aggregate Ladebudget, höchstens 1000 je Quelle und weiterhin 720 Intervalle; versteckte Quellen bleiben in der gemeinsamen Lückenprüfung. |
| Danach | Weitere Archiv- und Vergleichsreihen | Nur zusammen mit Navigation, Vorschaukennzeichnung und messbaren Ressourcenbudgets. |

Normalisierung ist keine allgemeine Lösung für unterschiedliche Einheiten:
Sie kann relative Vergleiche sinnvoll machen, ersetzt aber keine physikalische
Summe. Waterfall-Änderungen und Sankey-Flüsse dürfen nicht unabhängig voneinander
normalisiert und anschließend als unveränderte Mengenbilanz ausgegeben werden.

## Aktuelle Werte und Strukturen

Die folgenden Dateiverweise zeigen die maßgeblichen Verträge. Angegebene
Erweiterungszahlen außerhalb der ausdrücklich als umgesetzt bezeichneten
Schritte sind Planungsziele, keine implementierten oder lastgeprüften Zusagen.

| Familie | Aktueller Vertrag | Bewertung und sinnvolle Erweiterung |
| --- | --- | --- |
| EChartsGaugeSingle | Genau eine numerische Quelle; eigener Wertebereich, Einheit und Präzision; negative Werte zulässig. Bis acht Farbzonen. | Eine Quelle gehört zur Familie und bleibt. Mehr Farbzonen erst bei konkretem Bedarf. Mehrere Instrumente gehören zu GaugeMulti. |
| EChartsGaugeMulti | 2–16 Quellen, jeweils eigene Skala und Einheit. | 16 ist eine Layoutgrenze. Perspektivisch 32–100 mit Seiten/Raster und Auswahl, nicht 100 konzentrische Ringe auf derselben Kachel. |
| EChartsGaugeTacho | 2–5 Quellen mit unabhängigen Skalen; festgelegte Instrumentenanordnung. | Fünf ist durch die besondere Geometrie begründet. Neue Anordnungen wären Voraussetzung für mehr; reine Grenzerhöhung nicht empfohlen. |
| EChartsGaugeChronograph | 2–5 Quellen als Hauptinstrument mit Nebeninstrumenten. | Ebenso Geometrievertrag. Optional ein einzelnes Hauptinstrument erst nach ausdrücklicher Produktentscheidung; kein allgemeiner 100-Quellen-Kandidat. |
| EChartsBarCategory | 1–100 numerische Quellenzuordnungen, gleiche Roh-Einheit oder ausdrücklich normalisierte Vergleiche mit eigenen Min/Max; auch negative Originale. Kategorien und Reihen, gruppiert/gestapelt/einfach. | Umgesetzt: responsive Kategoriennavigation, viewportstabile Updates und optionale Normalisierung. Stapel addieren normierte Einzelwerte, sind aber keine physikalischen Summen oder 100-Prozent-Stapel. |
| EChartsBarWaterfall | Startwert plus 1–15 signierte Änderungen, also 2–16 Quellen; gleiche Einheit; Endsumme berechnet. | Bis 100 Schritte mit Navigation sinnvoll. Zusätzliche Zwischensummen/Neustarts wären fachlich wertvoller als eine alleinige Grenzerhöhung. Gemeinsame additive Einheit beibehalten. |
| EChartsBarPolar | 1–16 Quellen gleicher Roh-Einheit oder ausdrücklich aktivierte Normalisierung je Quelle auf 0–100; negative Originalwerte bleiben erhalten. | Normalisierung umgesetzt. Größere Modelle nur mit Auswahl/Seiten und lesbarer Ring-/Sektorbreite, nicht 100 Ringe in einer kleinen Kachel. |
| EChartsPie | 1–16 nichtnegative Quellen gleicher Roh-Einheit oder optional normalisierte Gewichte mit beliebigen Originaleinheiten und eigenen Min/Max. Nullgewichte bleiben ausdrücklich leer. | Normalisierung nach CalendarPie-Vorbild umgesetzt. Größere Listen mit scrollbarer Legende und ausdrücklich gewähltem Restsegment/Top-N weiterhin offen; keine unsichtbar ausgelassenen Werte. |
| EChartsFunnel | 1–16 nichtnegative numerische Quellen gleicher Einheit. Null gültig; manuelle oder wertbezogene Reihenfolge, keine erzwungene Abnahme zwischen Stufen. | 16 ist eine Darstellungsgrenze. 32, später 100 nur mit Navigation und Beschriftungsprüfung. Negative Trichterbreiten sind keine sinnvolle Aufhebung. Der gemeinsame Resolver behält hier die Grenze 16. |
| EChartsPictorialBar | 1–100 nichtnegative Quellen gleicher Roh-Einheit oder optional normalisierte Quellen mit eigenen Min/Max und Originaleinheiten. Wertfarbe, interner Verlauf, Icons und eigene SVGs. | Umgesetzt: Normalisierung und responsive Kategoriennavigation ohne Datenverlust; Vorschau kennzeichnet ihren Acht-Quellen-Ausschnitt. Mehrfachauswahl nach Objektzweig/Filter weiterhin offen. |
| EChartsTree | Objektwurzel Ebene 0, Kategorien/Instanzen/Variablen; 1–12 Nachkommensebenen; 10–3000 Knoten, Standard 80; gefiltertes Suchbudget 30000 Objekte. Startzustände ab 200000 Bytes werden komprimiert, nicht abgelehnt. | Umgesetzt: tiefere Zweige, getrennte Anfangstiefe, ID-stabile Aufklapp-/Pan-/Zoom-Ansicht und gemeinsame begrenzte Strukturvorschau. Links/Medien/Skripte bleiben ausgeschlossen. |
| EChartsTreeSystemHealth | Dieselben Hierarchiegrenzen; bis 3000 ausdrückliche Boolean-/Zahlenregeln; Vorfahrenbewertung nur anhand des berücksichtigten Baums. | Umgesetzt: zwölf Nachkommensebenen und sichtbarer lokaler Bewertungsumfang einschließlich nicht enthaltener Regelvariablen. Keine zusätzliche Vollsuche oder Zustandsaussage außerhalb der ausgewählten Hierarchie. |
| EChartsGraph | 2–300 Objektknoten, 1–1000 ungerichtete Kanten; keine Selbstkanten oder doppelten ungeordneten Paare. Isolierte Knoten und mehrere getrennte Komponenten sind erlaubt. | Umgesetzt: größere Modelle, gemeinsam mit Z2M erhaltene Pan-/Zoom-Ansicht und Kräfte-Ausgangspositionen sowie begrenzter echter Vorschauausschnitt. Gerichtete oder gewichtete Beziehungen bleiben ein gesonderter Vertrag. |
| EChartsGraphZ2M | Nur gespeicherter Export, kein Scan; bis 500 Rohknoten/10000 Rohkanten. Sichtbare Verbindungen 1–2000, Standard 300; LQI 0–255; Export höchstens 16 MiB. | Schutzgrenzen zunächst behalten. Schwache Verbindungen gezielt auswählbar machen, statt nur die stärksten zu zeigen. Mehrdeutige Exportproduzenten nicht automatisch zusammenwerfen und keinen Scan versteckt auslösen. |
| EChartsTreemap | 1–100 nichtnegative Blätter, bis acht Ebenen einschließlich Blatt, höchstens 800 Knoten und 200000 HTML-sicher serialisierte Modellbytes. Optionale Gruppenpfade; alte Gruppe/Untergruppe bleibt unverändert gültig. Null besitzt keine Fläche; Anzeigeeinheit ist global. | Umgesetzt: rekursives gemeinsames Modell, native Navigation und gekennzeichnete vollständige Strukturvorschau. Optionale normalisierte Gewichte und Originalformat je Blatt bleiben offen. Fehlende Einheitenprüfung ist keine saubere Mehr-Einheiten-Unterstützung. |
| EChartsSunburst | Dasselbe rekursive 1–100-Blatt-Modell und dieselben Tiefen-/Knoten-/Bytebudgets wie Treemap. Die ersten drei Ringdesigns bleiben erhalten; tiefere Ringe übernehmen das äußere Design. | Umgesetzt: native Navigation mit stabilem geöffnetem Zweig bei Aktualisierung und Resize. Originaleinheiten und optionale Gewichte bleiben gesonderte Erweiterungen. |
| EChartsSankey | 2–100 Knoten, 1–200 gerichtete nichtnegative Flüsse; azyklisch, keine Selbstkanten/doppelten gerichteten Paare. Gleiche Variable an mehreren Kanten möglich. Gleiche Einheit dokumentiert, derzeit nicht je Quelle validiert. | Zunächst Einheitenmodell/Validierung sauber machen. Optional negative Werte durch ausdrücklich gewählte Kantenumkehr behandeln, danach erneut Zyklen prüfen. Rest-/Bilanzknoten können helfen. DAG und nichtnegative Breiten sind keine unnötigen Beschränkungen. |
| EChartsRadar | 3–10 Achsen, 1–8 vollständige Vergleichsgruppen; je Achse eigener Min/Max-Bereich, Einheit und Präzision. Unterschiedliche Einheiten bereits sinnvoll skaliert. | Mindestens drei Achsen sind geometrisch erforderlich. 12–16 Achsen/16 Gruppen erst mit Filter-/Layoutkonzept. Unvollständige Gruppen optional auslassen und kennzeichnen, fehlende Werte nicht als null erfinden. Inverse Achsen wären eine gesondert beschriftete Erweiterung. |

Maßgebliche Implementierungen:

- Quellen-/Familienvalidierung in den jeweiligen `ECharts*/module.php`.
- Funnel/PictorialBar: `libs/EChartsCurrentComparisonSources.php`.
- Hierarchien: `libs/TreeHierarchy.php`, `EChartsTreeSystemHealth/TreeHealth.php`.
- Graphen: `libs/GraphModel.php`, `libs/ZigbeeGraphModel.php`,
  `EChartsGraphZ2M/ZigbeeGraphExportReader.php`.
- Flächen/Flüsse/Achsen: `libs/EChartsHierarchySources.php`, `libs/TreemapModel.php`, `EChartsSankey/SankeyModel.php`,
  `EChartsRadar/RadarModel.php`.
- Geometrie und Navigation: die jeweiligen `visualization/app.js`.

## Archivfamilien

Mehr Variablen, größere Zeiträume und andere Kennzahlen sind getrennte
Erweiterungen. Eine stündliche Mittelwertverteilung ist zum Beispiel keine
Verteilung sämtlicher Rohmessungen; ein Mittelwert darf auch nicht als
Candlestick-Anfangswert ausgegeben werden.

| Familie | Aktueller Vertrag | Bewertung und sinnvolle Erweiterung |
| --- | --- | --- |
| EChartsTimeSeries | 1–32 Integer-/Float-Quellen; insgesamt 200–32000 Punkte (Default 2000), höchstens 2000 je Quelle; bis acht effektive Einheitengruppen und 32 Annotationen. Modell höchstens 900000 JSON-Bytes, HTML höchstens 1 MiB. Roh-/Realtime-/Auto- und feste Aggregationen bleiben erhalten. | Umgesetzt: scrollbare Reihenauswahl mit Anfangssichtbarkeit und ID-stabiler Ansicht. Versteckte Reihen bleiben im vollständigen Modell, Archivbudget und Live-Abonnement. Kompression ist verlustfrei; das Bytebudget kann vor dem Punktbudget greifen. |
| EChartsBarHistory | 1–32 Quellen, insgesamt 200–32000 Punkte (Default 1000), höchstens 2000 je Quelle und 16 Einheitengruppen; Modell höchstens 900000 Bytes, HTML höchstens 1 MiB. Roh-/Auto-/feste Aggregationen bleiben erhalten. | Umgesetzt: gemeinsame ID-stabile Reihenauswahl, scrollbare Legende, Anfangssichtbarkeit und erhaltene Zeitansicht. Versteckte Reihen werden weiterhin gelesen und budgetiert. Tatsächliche Zeitstempel bleiben unabhängig; das Bytebudget kann vor dem Punktbudget greifen. |
| EChartsScatter | X und Y, optional eine Größenquelle: genau 2–3 Rollen, keine allgemeine Quellenliste. Gemeinsame abgeschlossene Archivintervalle ohne Interpolation; unterschiedliche Einheiten erlaubt. Insgesamt 200–2000 Punkte über die Rollen verteilt. | Drei ist keine willkürliche Vergleichsobergrenze. Mehrere benannte X/Y-Gruppen benötigen ein Gruppenmodell. Optional feste Min/Max-Grenzen für Bubble-Größen statt allein beobachteter Extrema. |
| EChartsHeatmap | Eine numerische Quelle; Wochentag × Stunde, feste Zeitfenster von einer Woche bis zwölf Monate/Jahresbeginn. Abgeschlossene Stundenaggregate mit Mittelwert, Minimum, Maximum oder Zählerverbrauch; passende Reduktion je Wochentag/Stunde. | Kennzahlen umgesetzt: Mittel der Stundenmittel, Minimum der Minima, Maximum der Maxima oder Summe der Stundenverbräuche. Benutzerdefinierte Zeiträume/Vergleichspanels bleiben offen. Die einzelne Quelle ist für diesen Feldinhalt schlüssig, nicht einfach 100 Quellen in dieselbe Zelle mischen. |
| EChartsParallel | 2–10 Achsen; 1–1000 gemeinsame Zeitlinien, Standard 750; 24 h / 7 d / 30 d oder benutzerdefinierte Dauer sowie Stunde/Tag. Standardarchive als Mittel, Zähler als Verbrauch; unterschiedliche Einheiten zulässig, nur gemeinsame Intervalle. | Relative Custom-Zeitfenster umgesetzt; Reduktion/Skala je Achse bleibt offen. 16–20 Achsen nur mit vergrößerbarer/verschiebbarer Achsenansicht. Mindestens zwei Achsen gehört zur Diagrammform. |
| EChartsCalendar | Eine numerische Archivquelle; abgeschlossene Tageskennzahlen eines Jahres (1971 bis aktuelles Jahr), höchstens 366 Tage. Mittelwert/Minimum/Maximum erfordern Standardarchive, Verbrauch ein Zählerarchiv. Teilarchive und echte Nullwerte bleiben erhalten. | Tageskennzahlen umgesetzt, ohne Verbrauch aus Standardwerten zu erfinden. Monat/Quartal/freies Fenster bleibt offen. Mehrere Tageswerte gehören zu CalendarPie oder ausdrücklich getrennten Kalenderpanels. |
| EChartsCalendarPie | Je nach Modus 1–5 numerische Quellen oder eine diskrete Zustandsquelle; Tagesmittel, Zählerverbrauch oder Zustandsdauer. Bis 32 Zustände. Optionale Min/Max-Normalisierung samt Originaleinheiten bereits vorhanden; Monatsmodell höchstens 250000 Bytes. | 1–5 ist die ausdrückliche aktuelle Produktentscheidung, keine sofort aufzuhebende Grenze. Optional verfügbare Teilquellen eines Tages anzeigen, aber nur mit deutlichem Unvollständigkeitshinweis; strenger Standard bleibt sinnvoll. |
| EChartsBoxplot | Jetzt 1–32 Standard-Archivquellen gleicher Einheit; 24 h / 7 d / 30 d oder Custom; bis 1000 abgeschlossene Stundenmittel je Quelle, insgesamt 10000. R7-Quartile und Min/Max-Whisker, nicht Rohwertverteilung oder 1,5-IQR-Ausreißermodell. | Umgesetzt: responsive Kategoriennavigation mit vollständiger Skala und ID-stabilem Ausschnitt. Budgetüberschreitung verlangt einen kürzeren Zeitraum ohne Teilstatistik. Relative Custom-Zeitfenster umgesetzt; absolute Zeitfenster und IQR/Ausreißer bleiben gesonderte Erweiterungen; unterschiedliche Einheiten nur normalisiert oder in getrennten Panels vergleichen. |
| EChartsCandlestick | Eine numerische Standard-Archivquelle; Stunde/Tag in 24 h / 7 d / 30 d oder Custom (höchstens 720 Intervalle). Tatsächliche Rohwerte für ersten/letzten Wert und Minimum/Maximum; leere Intervalle bleiben leer. Bis 63 Abfragen/64000 Rohänderungen pro Aktualisierung. | Relative Custom-Zeitfenster umgesetzt; absolute Fenster, weitere Intervalle und Zeitzoom bleiben offen. Mehrere benannte Panels später; Rohwert- und Abfragebudgets behalten. Einzelmessungen ergeben flache Kerzen, keine erfundenen Endpunkte. |
| EChartsThemeRiver | 1–16 nichtnegative Standard-Archivmittel gleicher Einheit; Stunde/Tag, 24 h / 7 d / 30 d oder Custom; höchstens 720 Intervalle, 1000 geladene Aggregate je Quelle und 16000 gemeinsam. Modell höchstens 900000 Bytes, HTML höchstens 1 MiB. | Umgesetzt: optionale ID-stabile Bandauswahl, gemeinsame Auswahl-/Startlogik, gekennzeichnete Vorschau. Versteckte Quellen schließen keine Lücken und behalten ihre Archivabfragen. Zählerverbrauch bleibt ein getrennter späterer Vertrag; keine negativen Breiten oder Nullfüllung. |
| EChartsStateTimeline | Jetzt 1–32 Boolean-/Integer-Standardarchive, bis 32 Zustandsdefinitionen je Quelle; 24 h / 7 d / 30 d oder Custom; bis 2000 Rohwerte je Quelle plus Vorgänger, insgesamt 16000 Rohwerte; Modell höchstens 250000 Bytes. Unbekannte Zustände sichtbar. | Umgesetzt: unabhängige Zeilennavigation ab neun Quellen und Gesamtbudget ohne stille Kürzung. Relative Custom-Zeitfenster ebenfalls umgesetzt. Später nach Lastprüfung bis 100 Quellen und absolute Zeitfenster. Float-/String-Zustände erst mit ausdrücklichem Gleichheits-/Zuordnungsvertrag. |

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
  16 Quellen, BarCategory verwendet ausdrücklich 100 und StateTimeline sowie Boxplot 32. Andere Familien bleiben
  unverändert. Bei ungeeigneten Listen kann der Baustein auf Beispieldaten
  zurückfallen. Eine neue Obergrenze nur in `module.php` wäre deshalb
  unvollständig. PictorialBar verwendet einen eigenen, vollständig validierten
  Vorschauausschnitt.
- TimeSeries (vier), BarHistory (drei), Parallel (sechs) und mehrere aktuelle
  Vergleiche begrenzen ihre Vorschau separat. Die Mengenhinweise müssen bei
  jeder Erweiterung erhalten und korrekt sein.
- Graph zeigt bei großen gültigen Entwürfen höchstens 40 echte Knoten und
  80 enthaltene Kanten mit ausdrücklichen Gesamtzahlen; nur ungültige Entwürfe
  verwenden ein Beispiel. Tree/Health zeigen eine
  gekennzeichnete Strukturvorschau mit höchstens 80 Knoten und konfigurierter
  Anfangstiefe; echte Eltern-Kind-Beziehungen bleiben erhalten. Treemap/Sunburst zeigen inzwischen
  die vollständige konfigurierte Struktur mit gekennzeichneten Beispielgewichten.
- Waterfall darf in einer Teilvorschau keine aus der Teilmenge berechnete
  Endsumme als die tatsächliche Gesamtbilanz erscheinen lassen.

Neue Grenztests sollen neben N und N+1 auch doppelte Anzeigenamen, unveränderte
Quellidentitäten, Sortierung, Aktualisierung, Ausrichtung, Einheiten, Lücken,
größtmögliche Imports und beide Ausgabewege abdecken. Eine Auswahl/Navigation
darf keine Daten verändern oder unsichtbar aus dem fachlichen Modell entfernen.

## Nachweise dieses Änderungsschritts und verbleibende Lücken

ThemeRiver prüft 16×720 Originalwerte, 16/17 Quellen und das gemeinsame
16000-Ladebudget einschließlich ausgeblendeter Quellen. Native Tests
bestätigen ID-stabile Auswahl, ursprüngliche Lücken und Nullwerte, während
Vorschauen ihre maximal acht gezeichneten Bänder kennzeichnen. Beide
verlustfrei komprimierten Ausgaben passen im gemessenen Szenario unter 1 MiB;
übermäßige Modell-/HTML-Größen werden ausdrücklich abgewiesen. Die lokale
Canvas-Prüfung für Auswahl, Wiederherstellung und Aktualisierung ist positiv.
Die neue Live-Abnahme nach Modulupdate bleibt offen.

BarHistory prüft 32 Quellen/32000 Punkte samt unverändertem Standardbudget,
16 Einheitengruppen, versteckten Quellen, tatsächlichen Zeitstempeln und
beiden komprimierten Ausgaben. Native Auswahl-/Zoom-Tests sichern gemeinsam
mit TimeSeries die zentralisierte Ansichtslogik ab. Vorschauen mit nur spät
sichtbaren Einheitengruppen erhalten kompakte Achsen statt versteckter Offsets.
Die lokale Canvas-Prüfung beider Ausgabewege ist positiv; Live-Abnahme nach
Modulupdate bleibt offen. Konfigurationen und Archive wurden nicht verändert.

TimeSeries prüft 32 Reihen mit 32000 Archivpunkten gegen den echten Gateway
mit Kernel-Doppeln. Modell (590044 Bytes), Kachel (897755 Bytes) und IPSView
(901043 Bytes) passen im gemessenen Szenario in die festgelegten Budgets.
Byteguards weisen komplexere übergroße Modelle explizit zurück. Native
Engine-Tests und eine lokale Canvas-Prüfung belegen ID-stabile Auswahl,
Legendenanker und Zeitzoom bei Aktualisierung/Umsortierung in beiden
Ausgabewegen. Große SVG-Muster und versteckte Quellen sind separat geprüft.
Die neue Live-Abnahme nach Modulupdate bleibt offen; vorhandene
Konfigurationen und Archive wurden nicht verändert.

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
anschließend in Kachel und IPSView live bestätigt, ebenso inzwischen BarCategory
mit 72 Variablen. Die nachfolgende Erweiterung um Balkenverläufe und 32
Timeline-Zeilen wurde lokal mit synthetischen Daten in den beiden Ausgabe-
Varianten im Browser geprüft, einschließlich Navigation bis zur letzten Zeile.
Ihre Live-Abnahme nach Modulupdate steht noch aus. Regressionsprüfungen sichern
die 32/33-Grenze, unbekannte Archive, ID-stabile Zeilen bei Updates/Resize,
unabhängigen Zeitzoom und das gemeinsame 16000-Rohwerte-Budget ab.
Live-Konfigurationen, Archivaufzeichnung und Quellwerte wurden bei
dieser Folgeänderung nicht verändert. Die übrigen vorgeschlagenen
Erweiterungen anderer Familien sind noch nicht umgesetzt oder lastgeprüft.

Boxplot prüft nun ebenfalls 32/33 Quellen, gleiche Anzeigenamen mit eigener ID,
leere/negative Quellen, unveränderte R7-Statistik und beide Ausgaben unter 1 MiB.
32 vollständige Sieben-Tage-Archive passen in das gemeinsame 10000-Punkte-Budget;
dichte 30-Tage-Archive werden ohne Teilstatistik mit Zeitraumhinweis abgewiesen.
Native Engine-Tests decken beide Ausrichtungen, Fonts/Boxbreiten, letzte Kategorie,
Tooltips nach Filterung sowie Ansichtsanker bei Aktualisierung und Resize ab.
Eine lokale Canvas-Browserprüfung mit synthetischen Daten ergänzt diese Tests;
die Live-Abnahme der Boxplot-Erweiterung nach Modulupdate bleibt offen.

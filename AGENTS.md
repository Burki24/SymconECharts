# Projektregeln für SymconECharts

Vor Arbeiten in diesem Repository sind zuerst die zentralen Vorgaben aus
`../SymconDevelopment/AGENTS.md` und
`../SymconDevelopment/ARCHITECTURE.md` zu lesen. Die dort referenzierten
Prinzipien und die für die konkrete Aufgabe relevanten Standards gelten auch
für SymconECharts. Projektspezifische Ziele und der vorhandene Stand stehen in
`README.md`, `docs/ENTWICKLUNG.md` und den README-Dateien der Module.

## Produktumfang und Abgrenzung

- SymconECharts ist die eigenständige Neuentwicklung für Diagramme auf Basis
  von Apache ECharts. JSLive bleibt ein getrenntes Wartungsprojekt und wird aus
  diesem Repository heraus nicht geändert.
- Zielplattform sind IP-Symcon 9.0/9.1 und PHP 8.5. Eine gesonderte
  IP-Symcon-9.0-Testinstallation ist nicht erforderlich; nicht auf der
  erreichbaren Testebene geprüfte Varianten werden sichtbar als Testlücke
  benannt.
- Der aktuelle Repository-Stand besitzt eine getestete technische
  Gauge→Gateway-Datenbasis, die Archivoperation `archive.read` sowie native
  HTML-SDK-Ausgaben für Gauge Single, Gauge Multi, Gauge Tacho, Gauge
  Chronograph und die erste Time-Series-Vertikale. Nicht
  implementierte Funktionen, Store-Freigaben oder nicht real geprüfte
  Laufzeitkompatibilität dürfen nicht als vorhanden dargestellt werden.
- Native Symcon-Kacheln und einzelne IPSView-HTML-Widgets sind vorgesehene
  Ausgabewege. Die vereinbarten Nutzungsgrenzen-Erweiterungen sind auf der
  erreichbaren Testebene per MCP geprüft und vom Eigentümer in Kachel und
  IPSView funktional bestätigt. Nicht geprüfte Versions-, Geräte-, Last- und
  Fehlerszenarien bleiben getrennt als Testlücken dokumentiert.

## Architekturgrenzen

- Die Modulstruktur besteht aus `EChartsGateway` und getrennten
  Geräteinstanzen je Diagrammfamilie. Die erste Familie ist in
  `EChartsGaugeSingle` für genau eine Quellvariable, `EChartsGaugeMulti` für
  allgemeine Mehrquellenmodelle sowie `EChartsGaugeTacho` und
  `EChartsGaugeChronograph` für individuell gestaltbare Instrumentenpanels
  aufgeteilt.
  `EChartsGaugeMulti` erlaubt gemäß ADR 0074 zwei bis 32 Quellen mit optionaler
  ID-stabiler Quellenauswahl und responsiven Seiten für seine vier Vorlagen.
  Individuelle Skalen und Einheiten sowie kleine Altlisten bleiben erhalten.
  Auswahl-/Seiten-DOM und CSS werden mit BarPolar geteilt; Geometrie bleibt lokal.
  `EChartsFunnel` erlaubt gemäß ADR 0075 bis zu 32 aktuelle nichtnegative
  Quellen gleicher Einheit mit optionaler Quellenauswahl und Stufennavigation.
  Die vollständige Quellenmenge bestimmt die Breitenskala; Auswahl/Seiten bleiben
  reine Ansichten. Gemeinsame Auswahl-/Anker-/DOM-Bausteine werden wiederverwendet,
  die native Funnel-Geometrie verbleibt in der Familie.
  Historische Linien- und Flächendiagramme liegen im eigenen
  Gerätemodul `EChartsTimeSeries`, gemäß ADR 0067 mit bis zu 32 Reihen,
  Reihenauswahl und einem gemeinsamen Punktbudget bis 32000.
  Aktuelle Kategorienvergleiche liegen im
  eigenen Gerätemodul `EChartsBarCategory`; historische Balken liegen im
  eigenen Gerätemodul `EChartsBarHistory`, gemäß ADR 0068 mit bis zu 32 Quellen,
  16 Einheitengruppen und bis zu 32000 Gesamtpunkten. Die ID-stabile flüchtige
  Reihenauswahl wird mit TimeSeries geteilt; Fachmodelle bleiben getrennt.
  Waterfall-Balken mit aktuellem
  Startwert, signierten Änderungen und berechneter Endsumme liegen im eigenen
  Gerätemodul `EChartsBarWaterfall`. Polar-Balken für aktuelle Werte liegen im
  eigenen Gerätemodul `EChartsBarPolar` und sind kein Modus dieser Instanzen.
  BarPolar erlaubt gemäß ADR 0073 bis zu 100 Quellen mit optionaler
  ID-stabiler Quellenauswahl und responsiver Seitennavigation. Das vollständige
  Modell bestimmt weiterhin die Werteskala; Auswahl und Seiten sind reine
  Ansichten. Gemeinsame Auswahl- und Kategorieankerlogik liegt unter `libs`.
  Waterfall erlaubt gemäß ADR 0071 bis zu 100 Quellen einschließlich Startwert,
  zusätzlich die berechnete Endsumme. Kategoriennavigation ändert niemals die
  vollständige Bilanz oder Skala; gemeinsamer flüchtiger Kategorieanker mit
  BarCategory liegt in `libs/echarts-category-navigation.js`.
  ADR 0098 ergänzt `Sources[].SubtotalAfter` und `Sources[].Restart` als optionale
  Boolean-Felder mit Default false. Zwischensummen ändern keine laufende Summe;
  Neustarts beginnen absolute unabhängige Abschnitte, deren letzte Endsumme
  ausdrücklich keine Summe aller Abschnitte ist. Derselbe `WaterfallData`-
  Schrittplan versorgt Live-Ausgaben und SVG-Vorschauen. Weiterhin 2–100 Quellen,
  höchstens 201 Schritte einschließlich berechneter Säulen, 200000 Modellbytes
  und 1 MiB HTML; vollständige Prüfung/Skala auch außerhalb des Ausschnitts.
  Kreis-, Donut- und Nightingale-Diagramme für aktuelle Anteile liegen im
  eigenen Gerätemodul `EChartsPie` und sind kein Balkenmodus.
  Pie erlaubt gemäß ADR 0072 bis zu 100 Quellen mit optionaler erweiterter
  Quellenauswahl. Auswahl- und Gesamtanteil bleiben ausdrücklich getrennt;
  versteckte Quellen werden weiterhin gelesen und validiert. Die ID-stabile
  flüchtige Auswahl nutzt `libs/echarts-series-selection.js`.
  Pie, BarCategory, BarPolar und PictorialBar erlauben gemäß ADR 0063 eine
  ausdrücklich aktivierte Min/Max-Normalisierung je Quelle auf 0–100.
  Originalwerte, Einheiten und Quellenidentitäten bleiben erhalten;
  unterschiedliche Einheiten sind nur im normalisierten Modus zulässig.
  Ohne Aktivierung gelten unverändert die Rohwertverträge der Familien.
  Historische X/Y-Vergleiche und optionale Bubble-Größen liegen im eigenen
  Gerätemodul `EChartsScatter` und werden ohne Interpolation nach gemeinsamen
  abgeschlossenen Archivintervallen verbunden.
  ADR 0089 ergänzt bis zu 16 benannte X/Y-Gruppen mit optionaler Größenquelle,
  getrennten Intervallschnitten und insgesamt höchstens 48 Zuordnungen.
  Das gemeinsame 200–2000er-Archivbudget, alte ungruppierte Quellen und Designs
  bleiben erhalten; gemeinsame Auswahl- und Zeitraumlogik wird wiederverwendet.
  Eine archivbasierte Tages- beziehungsweise Wochentag-Stunden-Heatmap liegt im eigenen Gerätemodul
  `EChartsHeatmap`. Sie ist kein Kalenderdiagramm und kein Scatter-Modus.
  Eine begrenzte, lesende IP-Symcon-Objekthierarchie liegt im eigenen
  Gerätemodul `EChartsTree` und ist kein Graph-Modus. Die ausdrückliche
  Zustandsbewertung dieser Hierarchie liegt getrennt in
  `EChartsTreeSystemHealth`; der einfache Tree enthält keine Zustandsregeln.
  Beide erlauben gemäß ADR 0065 zwölf Nachkommensebenen ab Wurzel 0 bei
  unveränderten Defaults sowie Knoten-/Suchbudgets. Projektion, SVG-Geometrie
  und flüchtige Ansichtsverwaltung liegen zentral unter `libs`; Zustandsregeln
  und deren Bewertung verbleiben ausschließlich bei TreeSystemHealth.
  Frei festgelegte Knoten und Kanten zwischen ausgewählten Symcon-Objekten
  liegen in `EChartsGraph`. Die zuletzt exportierte Zigbee2MQTT-Netzwerktopologie
  liegt im getrennten, nur lesenden Gerätemodul `EChartsGraphZ2M`; es löst keinen
  Netzwerkscan aus und ist kein manueller Kantenmodus des allgemeinen Graphen.
  Der allgemeine Graph erlaubt gemäß ADR 0066 300 Knoten und 1000 ungerichtete
  Kanten. Gemeinsame flüchtige Ansichtsverwaltung und Beschriftungsgrundlagen
  liegen unter `libs`; Z2M-Export, LQI, Identitäten und Budgets bleiben getrennt.
  ADR 0102 ergänzt optional `DirectedEdges=false`. Nur im gerichteten Modell
  dürfen Gegenrichtungen gleichzeitig bestehen; gleiche geordnete Paare bleiben
  ungültig. `GraphModel` prüft beide Modelle und kennzeichnet Gegenrichtungen
  gemeinsam für Renderer und Vorschauen. Pfeile folgen Von nach Nach; gerichtete
  Kreise sind erlaubt. 300/1000, 64 KiB je Liste, 1 MiB Ausgabe und Designs bleiben
  unverändert. Die native Graph-Runtime und geteilte Ansichtsverwaltung bleiben
  erhalten; nur gerichtetes Kreislayout projiziert Kreispositionen explizit,
  damit native einfache Kurven Gegenrichtungen auch bei zwei Knoten trennen.
  ADR 0103 ergänzt `Edges[].WeightMode=none|manual|variable` mit Default `none`.
  Gewichte sind nichtnegativ, null bleibt als minimale Breite sichtbar. Feste
  gemeinsame `WeightMinimum`/`WeightMaximum` begrenzen ausschließlich die
  Breitenprojektion, Originalwerte/Einheiten/IDs/Zeitstempel bleiben erhalten.
  `MinimumEdgeWidth`/`MaximumEdgeWidth` sind unabhängige Designwerte (1–20 px).
  `GraphModel` teilt Prüfung und Breitenberechnung mit den SVGs; aktuelle Quellen
  nutzen `EChartsCurrentSources` und ID-stabile Referenzen/VM_UPDATE-Abonnements.
  Gewichtsdaten werden nicht als native Kräftewerte verwendet. Keine Umrechnung,
  Bilanz oder Änderung der 300/1000-, 64-KiB- und 1-MiB-Schutzgrenzen.
  ADR 0094 ergänzt für GraphZ2M `LinkSelection=strongest|weakest|range`.
  Default `strongest`, bestehender Mindest-LQI und alle Schutzbudgets bleiben
  erhalten. Nur `range` wertet den zusätzlichen Höchst-LQI einschließlich aus;
  nach höchstem LQI je gerichteter Dublette wird gefiltert und dann begrenzt.
  Alle Knoten und die absolute Farbskala 0–255 bleiben erhalten. Live-Modell und
  synthetische Vorschauen teilen `ZigbeeGraphModel`; kein Netzwerkscan.
  Eine Treemap aus manuell gewählten Gruppen und aktuellen numerischen
  Symcon-Variablenwerten liegt im eigenen Gerätemodul `EChartsTreemap`.
  Ein Sunburst mit derselben manuell benannten Hierarchie und aktuellen
  numerischen Variablenwerten liegt getrennt in `EChartsSunburst`.
  Beide verwenden gemäß ADR 0064 denselben manuellen Hierarchievertrag mit
  optionalen Gruppenpfaden, höchstens acht Ebenen einschließlich Blatt,
  100 Quellen und 800 Knoten. Alte Group-/Subgroup-Quellen bleiben unverändert
  verwendbar; gemeinsame Quellenprüfung und Modellbildung liegen unter `libs`.
  ADR 0090 ergänzt für beide Familien optionale Min/Max-Gewichte auf 0–100
  und eigene Originaleinheiten/-präzision je Blatt. Alte Rohwertmodelle und
  Designs bleiben unverändert. Gemeinsame Quellen-/Modell-/Formatlogik wird
  wiederverwendet; Gruppensummen normalisierter Gewichte sind keine Mengensummen.
  Archivierte Werte mehrerer Variablen werden als Parallelkoordinaten nach
  gemeinsamen abgeschlossenen Stunden- oder Tagesintervallen im eigenen
  Gerätemodul `EChartsParallel` dargestellt.
  Parallel verwendet gemäß ADR 0078 für zusätzliche feste historische
  Von-/Bis-Zeiträume die zentrale Planung aus `EChartsArchiveQuery`;
  gemeinsame Zeitstempel und Stunden-/Tagesregeln bleiben erhalten.
  ADR 0084 erweitert Parallel auf 2–16 Achsen mit horizontaler Ansichtsverschiebung.
  Alle Achsen bleiben im gemeinsamen Join und im unveränderten 10000er-Ladebudget;
  die gemeinsame Query-Planung verteilt es vor den Abfragen. Die ID-stabile
  Positionshaltung verwendet die vorhandene Kategorieankerlogik; Designs und
  Altlisten bleiben erhalten. ADR 0085 ergänzt optionale feste Min-/Max-Skalen je
  Quelle mit unveränderten Originalwerten und nativer Achsenmarkierung. Die rein
  numerische Bereichsprüfung wird mit Normalisierung zentral geteilt; Parallel
  normalisiert nicht. ADR 0086 ergänzt je Quelle die Kennzahl `Metric` mit
  kompatiblem Default `auto`, expliziten Standardarchiv-Mittelwerten/-Extrema
  oder getrenntem Zählerverbrauch. Gateway-Reducer und zentrale Zeitraumplanung
  werden unverändert wiederverwendet; ein Archivtypwechsel wird nicht kaschiert.
  Candlestick verwendet gemäß ADR 0079 dieselbe absolute Zeitplanung und eine
  zentrale begrenzte Intervallliste für Stunden/Tage. Echte Rohwert-Endpunkte,
  höchstens 720 Intervalle, 63 Abfragen und 64000 Rohpunkte bleiben erhalten.
  ADR 0092 ergänzt optionalen rein visuellen Zeitzoom mit unabhängigem
  `EnableZoom` je Designer (Default false). Die gesamte geladene Datenmenge
  bestimmt weiterhin die Werteskala; zentrale ECharts-Zoomlogik und die
  bestehende Helper-Frame-/Mausradunterstützung werden wiederverwendet.
  ADR 0093 ergänzt `fifteen-minutes`, `thirty-minutes` und lokale ISO-Wochen
  (`week`, Montag bis Montag) über die zentrale begrenzte Rohintervallplanung.
  Vorhandene Stunden-/Tagesverträge, Designs und 720/63/64000-Budgets bleiben
  erhalten; die Aggregatmodi anderer Familien werden nicht erweitert.
  ADR 0105 ergänzt optional `UseComparisonPanels=false` und `Sources` mit ein bis
  vier benannten, ID-eindeutigen Archivquellen. Die alte Einzel-`VariableID` bleibt
  erhalten. Eigene Originalskalen/-einheiten je Panel, gemeinsamer Zeitraum und
  Zeitzoom; 720 Zeitintervalle sowie insgesamt 63 Abfragen/64000 Rohwerte bleiben
  erhalten. Rohwertleser, Zeitplanung, Vorschauverkabelung, Zoom und verlustfreie
  Startzustands-Komprimierung werden wiederverwendet. Kleine Ausgaben scrollen.
  Aktuelle Mengenflüsse zwischen manuell angelegten Knoten liegen im eigenen
  Gerätemodul `EChartsSankey`; Verbindungsbreiten folgen numerischen
  Quellvariablen, Zyklen und standardmäßig negative Werte sind unzulässig.
  ADR 0096 ergänzt `ReverseNegative` je Verbindung mit kompatiblem Default false.
  Nur ausdrücklich freigegebene negative Werte kehren die tatsächliche Richtung
  um; Breiten verwenden den Betrag, Originalwert und konfigurierte Endpunkte
  bleiben als Tooltip-Metadaten erhalten. Konfigurierte und tatsächliche Struktur
  bleiben kreisfrei/eindeutig einschließlich Nullflüssen. Dieselbe familienlokale
  Topologieprüfung wird für Live-Modell und beide Vorschauen wiederverwendet.
  ADR 0097 ergänzt das optionale Boolean-Feld `Nodes[].Balance` (Default false).
  Je ausgewähltem Knoten erzeugt die gemessene Zu-/Abflussdifferenz nach Umkehr
  höchstens einen klar berechneten Terminalknoten und Fluss, ohne Quellen-ID
  oder erfundenen Messzeitpunkt. Keine Rückkopplung zwischen Bilanzknoten;
  relative Rundungsreste bis 1e-12 bleiben ohne Zusatzfluss. Alle konfigurierten
  und berechneten Elemente teilen unverändert die 100-/200-Gesamtgrenzen.
  Modellbauer, Live-Ausgaben und SVG-Vorschauen teilen die familienlokale Logik.
  ADR 0095 ergänzt das optionale Einheitenmodell `UnitMode=manual|automatic|checked`.
  `manual` erhält die bisherige ungeprüfte Anzeigeeinheit. Geprüfte Modi
  verwenden die gemeinsame native/Legacy-Metadatenauflösung strikt und prüfen
  auch Nullflüsse; fehlende Suffixe bedeuten einheitenlos, Lesefehler nicht.
  Die Sankey-Policy und alle Ausgabe-/Vorschaupfade teilen dieselbe Prüfung;
  keine Umrechnung, Normalisierung oder Änderung der 100-/200-Grenzen.
  Archivierte Tageskennzahlen einer einzelnen numerischen Variable liegen im
  eigenen Gerätemodul `EChartsCalendar`; Mittelwert, Minimum und Maximum
  erfordern Standardarchive, der ausdrücklich gewählte Zählerverbrauch ein
  Zählerarchiv gemäß ADR 0062. Laufende Tage bleiben ausgeschlossen.
  Jahr (unveränderter Standard), Quartal, Monat und feste historische
  Von-/Bis-Datumsfenster mit Navigation folgen ADR 0082; maximal 366 Tage und
  unveränderte Kennzahlen-/Archivbudgets. Calendar ist kein Modus der Stunden-Heatmap. Auch deren stündliche
  Kennzahlen und Wochentag-/Stundenreduktion folgen ADR 0062.
  Heatmap ergänzt gemäß ADR 0083 benutzerdefinierte Dauern und feste historische
  Von-/Bis-Uhrzeitfenster mit lesender Navigation. Die zentrale strikte Datums-
  und Vollstundenplanung wird wiederverwendet; neue Fenster umfassen höchstens
  366 × 24 Stunden bei unveränderten Kennzahlen, Altzeiträumen und Ladebudgets.
  ADR 0106 ergänzt optional `UseComparisonPanels=false` und ein bis vier
  `Sources`-Ansichten mit benannter ID-Quelle, `PeriodOffset` sowie optionalen
  eigenen absoluten Grenzen. Dieselbe ID darf verschiedene Zeiträume vergleichen.
  `HeatmapComparison` plant alle Ansichten und dedupliziert exakte Abfragen vor
  dem Laden; sieben 60-Tage-/2000er-Abfragen gelten gemeinsam. Vorhandene zentrale
  Zeitplanung, Reduktion und Vorschaugeometrie werden wiederverwendet.
  `SharedColorScale=true` verlangt gleiche streng aufgelöste Einheiten; getrennte
  Skalen erhalten verschiedene Originaleinheiten. Celsius-Vorlagen bleiben fest.
  Scrollbare native Flächen teilen die Optionsfunktion; Altquelle/Designs und
  366-Tage-Grenze je Fenster bleiben erhalten.
  Aktuelle Vergleichsgruppen auf expliziten normierten Achsen liegen im eigenen
  Gerätemodul `EChartsRadar` gemäß ADR 0053; keine Archive oder automatische
  Qualitätsbewertung. Werte außerhalb des Bereichs werden nur optisch begrenzt.
  Gemäß ADR 0076 erlaubt Radar bis zu 16 vollständige Gruppen auf unverändert
  3–10 Achsen. Optionale Gruppenauswahl und Anfangssichtbarkeit sind reine
  Ansichten; alle Werte bleiben in Validierung, Gateway und Abonnements.
  Flüchtige Auswahl-/Legendenankerlogik wird mit anderen Familien geteilt.
  ADR 0100 ergänzt `Axes[].Inverse` als optionalen Boolean mit Default false.
  Nur die begrenzte Radiusprojektion wird umgekehrt (Maximum innen, Minimum
  außen); aufsteigende Min/Max, Originalwerte und Metadaten bleiben erhalten.
  Live-Ausgaben und SVGs teilen `RadarModel`, die numerische Bereichsprüfung
  verwendet `EChartsValueRange`. Designs und sämtliche Schutzbudgets bleiben gleich.
  ADR 0101 ergänzt `AllowIncompleteGroups=false`: nur ausgelassene Zuordnungen
  sind optional. Konfigurierte Quellen bleiben strikt geprüft, jede Gruppe
  benötigt mindestens eine Quelle. `RadarModel` liefert achsenparallele Nullplätze
  ohne erfundene IDs/Zeitstempel und ausschließlich benachbarte belegte Kanten
  für beide Renderer und SVGs. Teilgruppen zeigen Punkte, offene Linien und
  Fehlstellenmarker, keine Fläche. Native vollständige Radar-Gruppen sowie
  alle 3–10-/16-/160-/Bytebudgets bleiben erhalten.
  Verteilungen abgeschlossener Stundenmittelwerte von Archivvariablen liegen
  in `EChartsBoxplot` gemäß ADR 0054; Quartile werden nicht aus Tagesaggregaten
  approximiert und fehlende Intervalle nicht als Null ergänzt.
  ADR 0091 ergänzt optional `WhiskerMode=iqr`: R7-Quartile aller Stundenmittel,
  beobachtete Enden innerhalb der 1,5-IQR-Grenzen und separate Ausreißer mit
  Stundenzeitstempel. Default `minmax`, Designs und Archivbudgets bleiben erhalten;
  Live- und Vorschau-Statistik nutzen denselben familienlokalen Baustein.
  Boxplot ergänzt gemäß ADR 0077 feste historische Von-/Bis-Zeiträume mit
  eingeschlossenem Beginn und ausgeschlossenem Ende. Datumsprüfung und
  Vollintervallplanung liegen zentral in `EChartsArchiveQuery`; andere Familien
  behalten ihre bisherigen Zeitmodelle und alle Archivbudgets bleiben erhalten.
  Nichtnegative Stunden- oder Tagesmittelwerte gleicher Einheit liegen als
  Archivbänder im eigenständigen `EChartsThemeRiver` gemäß ADR 0058;
  fehlende Quellen unterbrechen ein Intervall ohne Nullauffüllung.
  Gemäß ADR 0069 sind bis zu 16 Quellen mit optionaler Bandauswahl und einem
  gemeinsamen Ladebudget von 16000 Aggregaten zulässig. Auch versteckte
  Quellen bleiben in Ladebudget und vollständiger Lückenprüfung enthalten.
  ADR 0070 ergänzt ausdrücklich wählbaren Zählerverbrauch je abgeschlossenem
  Stunden-/Tagesintervall über den bestehenden Gateway-Reducer `sum`.
  Der Standard `average` bleibt unverändert; Archivtypen werden nicht gemischt.
  ADR 0080 ergänzt feste historische Von-/Bis-Zeiträume über die zentrale
  Datumsprüfung und `AbsoluteIntervals`. Beide Kennzahlen, gemeinsame Lücken
  einschließlich versteckter Quellen sowie 720/1000/16000-Budgets bleiben erhalten.
- Das Gateway stellt ausschließlich familienübergreifende Infrastruktur wie
  Archivzugriff, begrenztes Caching, gemeinsame Ressourcen und gegebenenfalls
  abgesicherte IPSView-Kommunikation bereit. Familienbezogene Properties,
  Validierung und ECharts-Optionen verbleiben im jeweiligen Gerätemodul.
- Im regulären Betrieb verwenden alle ECharts-Geräte eine gemeinsame
  Gateway-Instanz. Das erste Gerät kann sie neu anlegen, weitere Geräte wählen
  das vorhandene Gateway. Zusätzliche Gateways bleiben technisch zulässig,
  werden aber weder als Standard empfohlen noch durch eine harte
  Singleton-Logik verhindert.
- Weitere Diagrammfamilien erhalten bei belegtem Bedarf ein eigenes
  Gerätemodul. Sie werden nicht als Modi einer universellen Widget-Instanz
  ergänzt.
- Die bestehende Gauge-Modul-GUID gehört nach der Aufteilung zu
  `EChartsGaugeSingle`; `EChartsGaugeMulti` besitzt eine eigene Modul-GUID.
  `EChartsGaugeTacho` und `EChartsGaugeChronograph` besitzen ebenfalls eigene
  Modul-GUIDs; ihre Festlegung dokumentiert ADR 0016. `EChartsTimeSeries`
  besitzt die in ADR 0018 festgelegte eigene Identität.
  Datenfluss-IDs werden von beiden Geräten gemeinsam verwendet. Namen,
  Präfixe und Zuständigkeiten aus [`ADR 0001`](docs/adr/0001-chart-family-modules.md)
  und [`ADR 0003`](docs/adr/0003-single-and-multi-gauge-modules.md) sowie das
  Multi-Quellenmodell aus
  [`ADR 0004`](docs/adr/0004-gauge-multi-source-contract.md) sind verbindliche
  Verträge und ändern sich nur mit einer ausdrücklichen Architektur- und
  Kompatibilitätsentscheidung.
- Parent-Auswahl, Zuständigkeiten und das versionierte Datenprotokoll aus
  [`ADR 0002`](docs/adr/0002-gateway-gauge-contract.md) sind verbindliche
  technische Verträge und werden kompatibel weiterentwickelt.
- Symcon-Datenanbindung, Archivzugriff, fachliches Chart-Modell, Formatierung,
  Ausgabeadapter und ECharts-Renderer werden als getrennte Zuständigkeiten
  behandelt. Gemeinsame Abstraktionen entstehen nur für belegte gemeinsame
  Anwendungsfälle.
- Die gemeinsame Grundreihenfolge der Diagramm-Instanzformulare und eine
  unmittelbar auf Formularänderungen reagierende Vorschau für Kachel und
  IPSView folgen [`ADR 0036`](docs/adr/0036-consistent-chart-forms-and-live-previews.md).
  Das Gateway ohne Diagramm-Designer ist ausgenommen. Der Umsetzungsstand und
  die weiterhin erforderlichen Laufzeitprüfungen stehen in der ADR.
- Fachlich benannte, standardmäßig eingeklappte Unterbereiche in Kachel- und
  unabhängigem IPSView-Designer folgen
  [`ADR 0037`](docs/adr/0037-topic-sections-in-chart-designers.md). Rubriken
  und Feldzuordnung bleiben je Diagrammfamilie festgelegt.
- Repositoryweit geteilter projektspezifischer Code liegt direkt unter `libs`;
  `libs/helper` enthält ausschließlich die über `.helper-sync.json`
  abonnierten Kopien aus `Symcon_ModuleHelper`. Tests liegen unter `tests`.
  Vorhandene zentrale Funktionen werden wiederverwendet und synchronisierte
  Helper nicht lokal verändert.

## Modulpräfixe

- `ECCP` ist `EChartsCalendarPie` zugeordnet (`CP` = Calendar Pie), GUID
  `{8F3D4E67-A52E-46AB-976E-25EC4F37FB52}` gemäß ADR 0060. Das eigene
  Monatskalendermodul bietet Tagesmittelwerte, Zähler-Tagesverbrauch und
  Zustandsdauer als auswählbare Datengrundlagen, nicht als getrennte Module.
  ADR 0099 ergänzt `ShowIncompleteDays` (Default false) ausschließlich für
  numerische Tagesquellen. Die gemeinsame familienlokale Tagespolicy steuert
  Live-Modell und SVGs. Fehlende IDs und Originalwerte bleiben erhalten;
  Teilkreise beziehen ihre Anteile nur auf vorhandene Gewichte und tragen
  dauerhafte Kennzeichnung. Zustandsdauern, Designs und Archivbudgets bleiben
  unverändert; gekürzte/fehlerhafte Archivantworten bleiben Fehler.

- `ECST` ist `EChartsStateTimeline` zugeordnet (`ST` = State Timeline), GUID
  `{581AD0EF-4E14-4796-902B-08381E299232}` gemäß ADR 0059. Archivierte
  Boolean-/Integer-Zustandswechsel werden als Zeitabschnitte dargestellt,
  ohne Aggregation oder Ersetzung durch aktuelle Werte.
  ADR 0081 ergänzt feste historische Von-/Bis-Zeiträume über zentrale strikte
  Datumsprüfung und ungerundete Grenzen. Vorgänger, halboffene Zustandsabschnitte
  sowie 32 Quellen, 2000 Rohpunkte je Quelle und 16000 insgesamt bleiben erhalten.

- `ECRV` ist `EChartsThemeRiver` zugeordnet (`RV` = River), GUID
  `{6767D036-5E2B-4D33-B7F1-C9F1B4376C04}` gemäß ADR 0058.
  Die eigenständige Archivfamilie verwendet abgeschlossene Stunden-/Tagesmittelwerte
  nichtnegativer Standardvariablen oder ausdrücklich ausgewählten Zählerverbrauch
  gemäß ADR 0070. Die Quellen müssen dieselbe Einheit und passenden Archivtyp besitzen.

- Technische Modulnamen und deren Anzeige folgen einheitlich `ECharts` plus
  Familienname ohne Leerzeichen, beispielsweise `EChartsPictorialBar`.
  `module.json` verwendet `aliases: []`, damit keine abweichenden
  Anzeige-Aliase entstehen. Individuelle Instanznamen bleiben Benutzerentscheidungen.

- `ECPB` ist `EChartsPictorialBar` zugeordnet (PB = Pictorial Bar), GUID `{CF6B1F8C-7219-4D59-94EA-19CF7570637F}` gemäß ADR 0057.
  ADR 0104 ergänzt die formulargebundene Mehrfachübernahme nach Objektzweig,
  Namensbestandteil und genauer Einheit. `VariableSearchHelper` bleibt die zentrale
  Baumsuche; `EChartsSourceImport` ergänzt Metadatenfilter und ID-stabiles Anhängen,
  `EChartsSourceImportForm` die Entwurfs-/Vorschauadapter. Keine persistierten
  Sucheinstellungen oder direkten Konfigurationsschreibzugriffe. Frische Prüfung
  vor Übernahme, 5000 Objekte/500 numerische Treffer, 100 Quellen/64 KiB und
  unveränderte fachliche Quellenprüfung; keine Änderung von Designs oder Runtime.

- `ECFU` ist `EChartsFunnel` zugeordnet (`FU` = Funnel), siehe ADR 0055.

- Modulpräfixe bestehen aus vier Großbuchstaben. `EC` bezeichnet stets
  ECharts; die dritte und vierte Stelle kennzeichnen Aufgabe oder
  Diagrammfamilie.
- `ECGW` ist dem `EChartsGateway` zugeordnet (`GW` = Gateway).
- `ECGS` ist `EChartsGaugeSingle` zugeordnet (`GS` = Gauge Single).
- `ECGM` ist `EChartsGaugeMulti` zugeordnet (`GM` = Gauge Multi).
- `ECGT` ist `EChartsGaugeTacho` zugeordnet (`GT` = Gauge Tacho).
- `ECGC` ist `EChartsGaugeChronograph` zugeordnet (`GC` = Gauge Chronograph).
- `ECTS` ist `EChartsTimeSeries` zugeordnet (`TS` = Time Series).
- `ECBC` ist `EChartsBarCategory` zugeordnet (`BC` = Bar Category).
- `ECBH` ist `EChartsBarHistory` zugeordnet (`BH` = Bar History).
- `ECBW` ist `EChartsBarWaterfall` zugeordnet (`BW` = Bar Waterfall).
- `ECBP` ist `EChartsBarPolar` zugeordnet (`BP` = Bar Polar).
- `ECPI` ist `EChartsPie` zugeordnet (`PI` = Pie).
- `ECSC` ist `EChartsScatter` zugeordnet (`SC` = Scatter).
- `ECHM` ist `EChartsHeatmap` zugeordnet (`HM` = Heatmap).
- `ECTR` ist `EChartsTree` zugeordnet (`TR` = Tree).
- `ECTH` ist `EChartsTreeSystemHealth` zugeordnet (`TH` = Tree System Health).
- `ECGR` ist `EChartsGraph` zugeordnet (`GR` = Graph).
- `ECGZ` ist `EChartsGraphZ2M` zugeordnet (`GZ` = Graph Zigbee2MQTT).
- `ECTM` ist `EChartsTreemap` zugeordnet (`TM` = Treemap).
- `ECSB` ist `EChartsSunburst` zugeordnet (`SB` = Sunburst).
- `ECPA` ist `EChartsParallel` zugeordnet (`PA` = Parallel).
- `ECSA` ist `EChartsSankey` zugeordnet (`SA` = Sankey).
- `ECCL` ist `EChartsCalendar` zugeordnet (`CL` = Calendar).
- `ECRA` ist `EChartsRadar` zugeordnet (`RA` = Radar).
- `ECBX` ist `EChartsBoxplot` zugeordnet (`BX` = Boxplot).
- Das zuvor für das unveröffentlichte Gauge-Gerüst verwendete Kürzel `ECGA`
  ist abgelöst und wird nicht für ein anderes Modul wiederverwendet.
- Neue Kürzel werden vor ihrer Verwendung eindeutig festgelegt und in dieser
  Datei sowie im zugehörigen Architekturkontext dokumentiert. Ein bestehendes
  Kürzel wird nicht für eine andere Bestimmung wiederverwendet.

- `ECCA` ist `EChartsCandlestick` zugeordnet (CA = Candlestick Archive); GUID `{A5C60024-BB95-4417-9632-763CBE9C14D7}` gemäß ADR 0056.

## ECharts und Fremdkomponenten

- Vor der Festlegung oder Aktualisierung einer ECharts-Abhängigkeit sind die
  aktuelle stabile Version, die benötigten APIs, Lizenz und Lieferartefakte
  anhand der offiziellen Einstiege
  `https://echarts.apache.org/en/index.html` und
  `https://echarts.apache.org/en/llms.txt` zu prüfen.
- ECharts wird lokal, reproduzierbar und mit festgeschriebener Version
  ausgeliefert. Im Betrieb wird weder `latest` noch eine externe CDN-Ressource
  automatisch geladen.
- Der ECharts-Kern wird nicht projektspezifisch verändert. Herkunft, Version,
  Integrität und Lizenz der ausgelieferten Fremdkomponenten werden
  dokumentiert.
- Offizielle ECharts-Themes liegen versioniert bei der ECharts-Runtime unter
  `libs/echarts`; ihre gemeinsamen IDs, Integritätswerte und
  layoutkompatiblen Grundfarben verwaltet `libs/EChartsAsset.php`. Die Auswahl bleibt
  eine Property der jeweiligen Diagramminstanz und ist keine
  Gateway-Konfiguration. Layout-Preset und Theme sind getrennte Verträge.
- Theme-Builder-Dateien sind ausführbarer JavaScript-Code. Ein späterer Import
  eigener Themes benötigt deshalb einen ausdrücklich festgelegten,
  validierten Importvertrag und wird nicht als ungeprüfter Dateiinhalt in eine
  Visualisierung übernommen.
- Die festgelegte Runtime- und Rendererbasis aus
  [`ADR 0005`](docs/adr/0005-echarts-runtime-and-native-renderer.md) ist ein
  technischer Vertrag. Aktualisierungen der ECharts-Version oder Änderungen am
  Auslieferungsmodell benötigen eine erneute Herkunfts-, Integritäts-, Lizenz-
  und Kompatibilitätsprüfung.

## JSLive-Export und Übernahme

- Maßgebliche Produktentscheidung ist
  `../JSLive/docs/adr/0006-maintenance-scope.md`. JSLive dient ausschließlich
  als lesende Referenz, sofern kein eigener Auftrag für dieses Repository
  erteilt wurde.
- JSLive liefert den Export bestehender Charts; SymconECharts erhält die
  passende Übernahmefunktion. Vor der Implementierung ist ein gemeinsamer,
  versionierter Austauschvertrag zu spezifizieren.
- Der gemeinsame Vertrag `de.burki24.jslive-echarts` 1.0 steht kanonisch in
  `docs/JSLIVE_MIGRATION_CONTRACT.md` gemäß ADR 0109 und JSLive ADR 0007.
  JSLive-Producer und gemeinsame Darstellungsnormalisierung sind regulär
  verteilt; 40 Exporte wurden vom Eigentümer per MCP bestätigt. Die lesende
  Gateway-Importvorschau gemäß ADR 0110 prüft Pakete, ID-Zuordnungen und native
  Formate unabhängig und liefert vollständige Pläne/Abweichungsberichte.
  Ihre Eingaben sind ausschließlich flüchtige Formularaktionen; es gibt keine
  Importanlage, kein Journal und keine vorgetäuschte Renderinggleichheit.
  Vollständige Mapper, Sonderregeln und gemeinsame Zielabnahme bleiben offen.
  Profilinformationen werden
  vollständig in profilunabhängige native Darstellungen und Chart-Metadaten
  übersetzt; der Import stellt bestehende Quellvariablen nicht um. Vollständige
  Feldbilanz, Vorschau, Bestätigung und wiederaufnehmbares Journal sind Pflicht.
- Variablen- und Archivbezüge werden erhalten. Originalinstanzen,
  Variablenwerte und Archivdaten werden weder gelöscht noch überschrieben.
- Eine Übernahme benötigt Vorschau und ausdrückliche Bestätigung. Sie muss bei
  Wiederholung sicher bleiben, Teilfehler kontrolliert melden und das
  JSLive-Original als Vergleich und Rückfall erhalten.
- Nicht übertragbare Einstellungen, eigene Templates, Skripte sowie
  Chart.js-spezifische Erweiterungen werden sichtbar gemeldet. Skript- oder
  Templateinhalte werden nicht stillschweigend ausgeführt oder als verlustfrei
  übertragbar behandelt.
- Es wird keine pixelgleiche oder vollständige 1:1-Migration beliebiger
  Chart.js-Konfigurationen versprochen.

## Umsetzung, Prüfung und Git

- Vor jedem Zugriff auf das Produktivsystem den Eigentümer ausdrücklich über
  Zweck und Umfang informieren, auch bei lesenden Zugriffen oder beim Öffnen
  einer produktiv verbundenen Visualisierung. Die Erreichbarkeit ist keine
  Testfreigabe; Entwicklungsprüfungen erfolgen auf der freigegebenen Testinstanz.
- Bereits vom Eigentümer bestätigte Kachel-/IPSView-Prüfungen als solche
  übernehmen. Eine erneute native Abnahme nicht ohne konkreten Prüfgrund verlangen.
- Vor Änderungen sind Arbeitsbaum, vorhandene Architektur, öffentliche
  Verträge, relevante Dokumentation, vergleichbare Implementierungen und die
  verfügbaren Tests zu prüfen. Fremde oder nicht zur Aufgabe gehörende
  Änderungen bleiben erhalten.
- Konfiguration, Import und Migration müssen deterministisch, idempotent und
  bei Teilfehlern wiederaufnehmbar sein. Veröffentlichte Properties, Idents,
  GUIDs und persistierte Formate ändern sich nur mit dokumentierter Migration.
- `ApplyChanges()` wird beim Modulupdate automatisch ausgeführt und nicht ohne
  konkreten Prüfgrund zusätzlich aufgerufen.
- Keine schreibenden Aktionen in einer Symcon-Installation ohne passenden
  Auftrag. Commit, Push, Merge, Tag, Release und Veröffentlichung führt der
  Eigentümer aus.
- Entwicklung erfolgt auf dem dauerhaften Branch `dev`; freigegebene Stände
  gelangen per Pull Request nach `main`.
- Die Library verwendet eine gemeinsame Version im Format
  `Hauptversion.Nebenstand`. Nach normalen Pushes auf `dev` pflegt der
  Metadatenworkflow `version`, `build` und `date` in `library.json` und erzeugt
  dafür einen getrennten Bot-Commit. Manuelle Änderungen dieser Felder erfolgen
  nur als ausdrücklich beauftragter Versions- oder Migrationsschritt.
- Der Metadatenworkflow erzeugt keine Tags, Releases oder Veröffentlichungen.
  Solche Schritte bleiben eine gesonderte Entscheidung des Eigentümers.
- Prüfungen folgen den dokumentierten Repositorybefehlen und der gemeinsamen
  CI-Basis. Abschlussberichte nennen ausgeführte Prüfungen und verbleibende
  Testlücken getrennt; Syntax-, Style- und Strukturprüfungen ersetzen keine
  Laufzeit- oder Browserprüfung.
- Die Haupt-README und alle Modul-READMEs bleiben Benutzerdokumentation.
  Entwicklung, Tests, CI und interne Architektur werden ausschließlich in
  `docs`, ADRs oder Testdokumenten beschrieben.
- Jede für Benutzer sichtbare Änderung wird im selben fachlichen Zusammenhang
  in `CHANGELOG.md` unter `# Unveröffentlicht` dokumentiert. Reine interne oder
  mechanische Änderungen erhalten keinen Eintrag.
- Vor der Übergabe commitbereiter Änderungen ist
  `php tests/quality.php --fix` auszuführen. Der Befehl korrigiert ausschließlich
  mechanische PHP- und JSON-Formatierung und prüft danach jeden Style-, Syntax-,
  Vertrags-, Integrations- und Layoutschritt mit eigenem Fehlerstatus erneut.
- Bei commitbereiten Änderungen werden Commit-Betreff und Commit-Erläuterung
  immer auf Englisch und in zwei getrennten kopierbaren Textblöcken geliefert.

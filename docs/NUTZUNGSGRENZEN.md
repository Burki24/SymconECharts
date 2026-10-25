# Prüfung der Nutzungsgrenzen

Stand: 25.10.2026. Geprüft wurden alle 29 Diagrammfamilien und das Gateway:
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
Optional sind gerichtete Beziehungen mit Pfeilen und getrennten Gegenrichtungen
umgesetzt; ungerichtet bleibt der kompatible Standard. Gerichtete Beziehungen
sind per MCP funktional geprüft und vom Eigentümer in Kachel/IPSView bestätigt.
Optionale Gewichte mit festem Breitenbezug sind zusätzlich umgesetzt; deren
MCP-Abnahme bestanden ist; der Eigentümer bestätigt Kachel und IPSView.
TimeSeries bietet 32 Reihen mit Reihenauswahl und bis zu 32000 Gesamtpunkten.
BarHistory bietet ebenfalls 32 Reihen mit Reihenauswahl und bis zu 32000
Gesamtpunkten bei unverändertem Default von 1000.
ThemeRiver unterstützt 16 Quellen mit optionaler Bandauswahl und einem
gemeinsamen Ladebudget von 16000 Aggregaten bei unveränderten Lückenregeln.
Zusätzlich ist Zählerverbrauch je abgeschlossenem Intervall ausdrücklich wählbar;
der bisherige Standardarchiv-Mittelwert bleibt Default.
Waterfall unterstützt jetzt 100 Quellen mit Kategoriennavigation und vollständiger
Bilanz/Skala unabhängig vom sichtbaren Ausschnitt.
Zusätzlich sind explizite berechnete Zwischensummen und absolute Neustarts
für unabhängige Bilanzabschnitte umgesetzt. Alle Abschnitte bestimmen weiterhin
die Skala; bei Neustarts gehört die Endsumme ausdrücklich zum letzten Abschnitt.
Quellen und Bytebudgets bleiben unverändert. Die funktionale MCP-Prüfung ist
bestanden; der Eigentümer bestätigt Zwischensummen und Neustarts in Kachel und IPSView.
CalendarPie bietet nun optional Teilkreise aus vorhandenen Tagesquellen mit
sichtbarer Raute, dauerhaftem Hinweis und ausdrücklich eingeschränktem Nenner.
Der strenge Standard, fehlende Quell-IDs, Originalformate, Zustandsdauern und
Archivbudgets bleiben erhalten. MCP funktional geprüft; Kachel und IPSView
wurden durch den Eigentümer bestätigt.
Pie unterstützt 100 Quellen mit optionaler erweiterter Quellenauswahl,
ID-stabiler Legendenansicht und ausdrücklich getrennten Auswahl-/Gesamtanteilen.
BarPolar unterstützt 100 Quellen mit optionaler Quellenauswahl und responsiven
Seiten für beide Polarmodi; die Werteskala berücksichtigt auch versteckte Quellen.
GaugeMulti unterstützt zwei bis 32 Quellen mit optionaler Quellenauswahl und
responsiven Instrumentenseiten bei weiterhin individuellen Skalen und Einheiten.
Funnel unterstützt 32 Quellen mit optionaler Quellenauswahl und responsiver
Stufennavigation; vollständiger Maximalwert, gleiche Einheit und nichtnegative
Originalwerte bleiben unabhängig vom gewählten Ausschnitt verbindlich.
Radar unterstützt 16 vollständige Vergleichsgruppen mit optionaler
Gruppenauswahl auf unverändert 3–10 expliziten Achsen. Auswahl, Anfangssichtbarkeit
und Legendenanker ändern weder Achsenbereiche noch die vollständige Werteprüfung.
Zusätzlich lässt sich je Messgröße der Radius ausdrücklich invertieren:
Maximum innen, Minimum außen. Originalwerte und aufsteigende Bereiche bleiben
erhalten. Inverse Achsen sind per MCP funktional geprüft und vom Eigentümer
in Kachel und IPSView bestätigt. Optional unvollständige Vergleichsgruppen sind
nun ebenfalls umgesetzt: fehlende Zuordnungen bleiben null, Fragezeichen und
dauerhafter Hinweis kennzeichnen Teilgruppen ohne geschlossene Fläche. Strenger
Default, konfigurierte Quellenprüfung, Designs und Schutzbudgets bleiben erhalten;
die neue Live-Abnahme der Teilgruppen bleibt offen.
Boxplot, Parallel, Candlestick und ThemeRiver bieten zusätzlich feste historische Von-/Bis-Zeiträume
mit gemeinsamer strikter Datumsprüfung und unveränderten Archivbudgets.
Parallel, Candlestick und ThemeRiver unterstützen dabei vollständig enthaltene Stunden und Kalendertage;
andere Familien behalten ihre bisherigen Zeitmodelle.
StateTimeline bietet ebenfalls feste Von-/Bis-Grenzen, jedoch sekundengenau
ohne Aggregatrundung bei unveränderten Zustands- und Vorgängerregeln.
Der geplante Archivzeiträume-Block ist umgesetzt und auf der verbundenen
Installation gemeinsam per MCP funktional geprüft. Der dabei gefundene
ThemeRiver-Teilarchivfall wurde korrigiert und erneut live geprüft; der
Eigentümer hat anschließend Kachel und IPSView bestätigt. Vollständige
Budget-/Fehlermatrizen bleiben automatisierte Nachweise, kein Live-Lasttest.
Calendar bietet nun zusätzlich Jahr, Quartal, Monat und feste historische
Von-/Bis-Datumsfenster mit Navigation bei unverändert höchstens 366 Tagen.
Diese Calendar-Ansichten wurden nach Modulupdate per MCP geprüft; der
Eigentümer bestätigt Kachel und tatsächliches IPSView.
Heatmap bietet nun benutzerdefinierte Dauern und feste historische Uhrzeitfenster
mit Navigation, zentraler Vollstundenplanung und unveränderten Kennzahlen/
Ladebudgets. Neue Fenster umfassen höchstens 366 × 24 Stunden; die bisherigen
Kalenderzeiträume bleiben unverändert. Die funktionale MCP-Abnahme auf Symcon 9.1
und die Eigentümerbestätigung für Kachel/IPSView liegen vor; keine Live-Lastfreigabe.
Parallel unterstützt jetzt 2–16 Achsen mit ID-stabiler horizontal verschiebbarer
Ansicht, vollständigen gemeinsamen Linien und unverändert höchstens 10000
Archivaggregaten. Die funktionale MCP-Abnahme auf Symcon 9.1 und die Bestätigung
des Eigentümers für Kachel/IPSView liegen vor; kein voller 10000-Punkte-Live-Lasttest.
Optionale feste Min-/Max-Achsenbereiche sind nun ebenfalls umgesetzt, bei
unveränderten Originalwerten, gemeinsamen Intervallen und Budgets. Ihre eigene
Live-Abnahme ist per MCP erfolgt und Kachel/IPSView sind durch den Eigentümer
bestätigt. Kennzahlen je Achse sind ebenfalls per MCP live geprüft und durch
den Eigentümer in Kachel/IPSView bestätigt.
Scatter/Bubble bietet nun optionale feste Größenbezüge mit Min/Max je Designer.
Automatische Altmodelle, Originalwerte und Archivbudgets bleiben erhalten;
die MCP-Live-Abnahme und Eigentümerbestätigung für Kachel/IPSView liegen vor.
Bis zu 16 benannte X/Y-Vergleichsgruppen mit optionaler Größenquelle sind nun
ebenfalls umgesetzt. Die funktionale MCP-Prüfung und Eigentümerbestätigung
für Kachel und tatsächliches IPSView liegen vor.
Treemap und Sunburst ergänzen nun optionale normalisierte Blattgewichte und
Originalformatierung je Quelle bei unveränderten Hierarchie- und Bytebudgets.
Die funktionale MCP-Prüfung und die Eigentümerbestätigung für Kachel und
tatsächliches IPSView liegen vor. Boxplot ergänzt jetzt optional 1,5-IQR-Whisker
mit beobachteten Enden und separaten zeitgestempelten Ausreißern. Min/Max bleibt
Standard; Quellen, Designs, Stundenmittel und Archivbudgets bleiben unverändert.
Die MCP-Live-Abnahme und Eigentümerbestätigung für Kachel und IPSView liegen vor.
Candlestick ergänzt optionalen Zeitzoom bei unveränderten Archivbudgets
und vollständiger Werteskala. Die funktionale MCP-Prüfung und die
Eigentümerbestätigung für Kachel/native App und tatsächliches IPSView
einschließlich Mausrad liegen vor; echte Touchgeräte bleiben ungeprüft.
Zusätzlich sind 15-/30-Minuten- und lokale Kalenderwochenintervalle über die
zentrale Rohintervallplanung umgesetzt und per MCP funktional geprüft.
Der Eigentümer bestätigt Kachel und tatsächliches IPSView; mehrere
Optionale benannte Vergleichspanels sind gemäß ADR 0105 lokal umgesetzt; ihre Live-Abnahme ist offen.
GraphZ2M bietet nun stärkste, schwächste und einschließlich nach LQI-Bereich
gefilterte Verbindungen. Gerichtete Dubletten behalten vor der Auswahl ihren
höchsten LQI; alle Knoten und Schutzbudgets bleiben erhalten. Die MCP-Prüfung
auf Symcon 9.1 ist bestanden; der Eigentümer bestätigt Kachel und tatsächliches IPSView.
Sankey bietet nun die optionale Quellen-Einheitenprüfung mit automatischer
gemeinsamer oder manuell erwarteter Einheit. Der manuelle Altmodus bleibt
Default; alle Quellen einschließlich Nullflüssen, unveränderte Rohwerte und
Schutzbudgets bleiben berücksichtigt. Die MCP-Prüfung auf Symcon 9.1 ist
bestanden; der Eigentümer bestätigt Kachel und tatsächliches IPSView.
Zusätzlich ist die negative Kantenumkehr je Verbindung optional umgesetzt:
Betragsbreiten mit Originalwerten und erneuter tatsächlicher Topologieprüfung.
Die MCP-Prüfung und Eigentümerbestätigung für Kachel und tatsächliches IPSView
liegen für die Kantenumkehr vor. Optionale Rest-/Bilanzknoten sind als nächster
Schritt umgesetzt: Nur ausdrücklich gewählte Knoten ergänzen aus gemessenen
effektiven Flüssen eine klar als berechnet markierte Differenz. Zusätzliche
Terminals teilen die unveränderten 100-/200-Gesamtgrenzen; Quellwerte und
Designs bleiben erhalten. Die funktionale MCP-Prüfung auf Symcon 9.1 ist bestanden;
der Eigentümer bestätigt Rest-/Bilanzknoten in tatsächlicher Kachel und IPSView.
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
| Erledigt | ThemeRiver: auswählbarer Zählerverbrauch | Symcon-Zähleraggregate je Stunde/Tag statt Zählerstand; gleiche Einheiten, unveränderte Lücken-/Auswahl-/Budgetregeln und Mittelwert als Default. |
| Erledigt | Waterfall: 100 Quellen mit Kategoriennavigation | Ein Startwert plus bis zu 99 Änderungen; vollständige Zwischenstände, Werteskala und Endsumme. Gekennzeichnete Vorschau mit echter Gesamtbilanz; gemeinsame Kategorieanker mit BarCategory. |
| Erledigt | Pie: 100 Quellen mit optionaler Quellenauswahl | Anfangssichtbarkeit, Alle/Umkehren, ID-stabile Auswahl und Legendenposition; getrennte Nenner für sichtbare und vollständige Gewichte, korrekte Ausschnittsvorschau. |
| Erledigt | BarPolar: 100 Quellen mit Auswahl und Seiten | ID-stabile Quellenauswahl und Seiten für radiale Balken und konzentrische Bögen, schrift-/platzabhängige Kapazität und vollständige Werteskala einschließlich versteckter Quellen. |
| Erledigt | GaugeMulti: 32 Quellen mit Auswahl und Seiten | Individuelle Skalen/Einheiten, ID-stabile Auswahl und Seiten für alle vier Vorlagen; DOM-Bedienung und CSS mit BarPolar geteilt, keine verkleinerten 32er-Instrumentenraster. |
| Erledigt | Funnel: 32 Quellen mit Auswahl und Stufennavigation | ID-stabile Auswahl und Seiten in beiden Ausrichtungen; vollständiger Maximalwert einschließlich versteckter Quellen, unveränderte Altlisten und gekennzeichnete maßstabstreue Vorschau. |
| Erledigt | Radar: 16 Gruppen mit optionaler Gruppenauswahl | Weiterhin 3–10 vollständige Achsen, bis 160 Zuordnungen, Anfangssichtbarkeit und Alle/Umkehren; gemeinsame flüchtige Auswahl-/Legendenlogik, unveränderte Skalen und Originalwerte. |
| Erledigt | Boxplot: feste historische Von-/Bis-Zeiträume | Reproduzierbare historische Stundenmittel-Verteilungen; Beginn eingeschlossen, Ende ausgeschlossen, nur ganze abgeschlossene Stunden. Gemeinsame Planung für weitere Familien vorbereitet. |
| Erledigt | Parallel: feste historische Von-/Bis-Zeiträume | Gemeinsame Stunden-/Tagesintervalle, Mittelwerte oder Zählerverbrauch je Achse; zentrale Planung aus Boxplot, unveränderte 2–10 Achsen und Linien-/Ladebudgets. |
| Erledigt | Candlestick: feste historische Von-/Bis-Zeiträume | Zentrale vollständige Stunden-/Tagesintervalle bei unveränderten echten Rohwert-Endpunkten; weiterhin 720 Intervalle, 63 Abfragen und 64000 Rohwerte. |
| Erledigt | ThemeRiver: feste historische Von-/Bis-Zeiträume | Zentrale vollständige Stunden-/Tagesintervalle für Mittelwert und Verbrauch; unveränderte gemeinsame Lücken, Bandauswahl und 720/1000/16000-Budgets. |
| Erledigt | StateTimeline: feste historische Von-/Bis-Zeiträume | Zentrale strikte Grenzprüfung ohne Stunden-/Tagesrundung; sekundengenaue Zustände, Vorgänger und alle Rohwert-/Ausgabebudgets bleiben erhalten. |
| Erledigt | Gemeinsame funktionale Live-Abnahme des Archivzeiträume-Blocks | Fünf Familien per MCP geprüft, ThemeRiver-Teilarchivkorrektur erneut live bestätigt; Eigentümer bestätigt Kachel/IPSView. Grenzen und vollständige Fehler-/Budgetmatrizen bleiben zusätzlich automatisiert geprüft, nicht als Live-Lastfreigabe. |
| Erledigt | Calendar: Jahr, Quartal, Monat und feste Von-/Bis-Zeiträume | Passende lesende Navigation, höchstens 366 Tage, unveränderte Tageskennzahlen/Teilarchive und unabhängige Designs; funktionale MCP-Prüfung und Kachel/IPSView durch Eigentümer bestätigt. |
| Erledigt | Heatmap: benutzerdefinierte und feste historische Zeiträume | Gemeinsame strikte Datums-/Vollstundenplanung, höchstens 366 × 24 Stunden je neuem Fenster, lesende Navigation und unveränderte Budgets; MCP funktional geprüft, Kachel und IPSView durch Eigentümer bestätigt. |
| Erledigt | Parallel: 16 Achsen mit verschiebbarer Achsenansicht | Echte horizontale Ansicht mit vollständigen Dimensionen, ID-stabile Position, sichtbarer Titel und höchstens 10000 Aggregate; bei 16 Achsen maximal 625 je Achse. MCP funktional geprüft, Kachel/IPSView durch Eigentümer bestätigt; keine Live-Lastfreigabe. |
| Erledigt | Parallel: individuelle Achsenbereiche | Automatisch bleibt Standard; feste Min-/Max-Grenzen je Quelle bei unveränderten Originalwerten, nativen Achsenmarkierungen und 10000er-Budget. Überschreitungen werden nach dem gemeinsamen Join gezählt und in beiden Ausgaben kenntlich gemacht. MCP funktional geprüft, Kachel/IPSView durch Eigentümer bestätigt. |
| Erledigt | Parallel: Kennzahlen je Achse | Automatisch erhält Altverhalten; explizit Mittelwert/Minimum/Maximum für Standardarchive oder Zählerverbrauch für Zählerarchive. Funktionale MCP-Prüfung sowie Kachel/IPSView durch Eigentümer bestätigt; unverändertes 10000er-Ladebudget. |
| Erledigt | Scatter/Bubble: feste Größenbezüge | Optionales Min/Max je Designer, lineare begrenzte Durchmesser bei unveränderten Originalwerten, Vorschau, Designkopie und unveränderten Archivbudgets. MCP sowie Kachel/IPSView durch Eigentümer bestätigt. |
| Erledigt | Scatter/Bubble: benannte Vergleichsgruppen | Bis zu 16 Gruppen mit X/Y und optional Größe; höchstens 48 Zuordnungen mit gemeinsamem 200–2000er-Archivbudget. Unabhängige Joins, stabile Legendenansicht und unveränderte Altlisten/Designs. MCP funktional geprüft, Kachel/IPSView durch Eigentümer bestätigt. |
| Erledigt | Treemap/Sunburst: normalisierte Blattgewichte und Originalformate | Optionale explizite Min/Max-Projektion auf 0–100, Originaleinheiten und Präzision je Blatt; Gruppensummen als Gewichte gekennzeichnet. MCP funktional geprüft und Kachel/IPSView durch Eigentümer bestätigt; Altlisten, Designs und Budgets bleiben erhalten. |
| Erledigt | Boxplot: optionale 1,5-IQR-Whisker und Ausreißer | R7-Quartile aller Stundenmittel, beobachtete Inlier-Enden, separate Punkte mit Originaleinheit und Stundenzeitstempel. Min/Max bleibt Standard; 32 Quellen, 1000/10000-Stundenmittelbudgets und unabhängige Designs bleiben unverändert. MCP sowie Kachel/IPSView durch Eigentümer bestätigt. |
| Erledigt | Candlestick: optionaler Zeitzoom | Zeitbereichsregler und Mausrad, Ansichtserhalt bei kompatiblen Updates, vollständige Werteskala und 720/63/64000-Budgets. MCP funktional geprüft; Kachel/native App und tatsächliches IPSView einschließlich Mausrad durch Eigentümer bestätigt. Echte Touchprüfung bleibt offen. |
| Erledigt | Candlestick: 15-/30-Minuten- und Wochenintervalle | Lokale Minutenraster und ISO-Kalenderwochen von Montag bis Montag, relative und feste historische Fenster über zentrale Planung. Echte Rohwert-Endpunkte, Designs, Lücken und 720/63/64000-Budgets bleiben erhalten. MCP funktional geprüft; Kachel und tatsächliches IPSView durch Eigentümer bestätigt. Mehrere benannte Vergleichspanels bleiben ein getrennter späterer Schritt. |
| Erledigt | GraphZ2M: stärkste, schwächste und LQI-Bereich | Bestehender Standard stärkste zuerst; inklusive Min/Max-Auswahl nach höchstem LQI je gerichteter Dublette, stabile Gleichstände und unveränderte Geräteidentitäten. Live-Modell und synthetische Vorschauen teilen Auswahl/Prüfung; feste Farbskala sowie 500/10000/2000-/16-MiB-Budgets bleiben erhalten. MCP funktional geprüft; Kachel und tatsächliches IPSView durch Eigentümer bestätigt. |
| Erledigt | Sankey: Einheitenmodell und optionale Quellenprüfung | Manueller Altmodus bleibt Default; automatische gemeinsame oder manuell erwartete Quelleinheit aus nativer Darstellung/Legacy-Profil. Alle Verbindungen einschließlich Nullflüssen werden geprüft, Metadatenfehler nicht als einheitenlos kaschiert. Gemeinsamer Metadatenleser und identische Live-/SVG-Policy; keine Umrechnung/Normalisierung, unveränderte 100-/200-/Ausgabebudgets. MCP funktional geprüft; Kachel und tatsächliches IPSView durch Eigentümer bestätigt. |
| Erledigt | Sankey: optionale Kantenumkehr bei negativen Werten | Opt-in je Verbindung, Default false; positive Betragsbreiten und tatsächliche Richtung mit signierten Originalen im Tooltip. Konfigurierte und effektive Topologie bleiben einschließlich Nullflüssen kreisfrei/eindeutig. Gemeinsamer Modellbauer und kompakte Vorschauverdrahtung; Designs, Einheitenprüfung und Schutzbudgets bleiben erhalten. Per MCP funktional geprüft; Eigentümer bestätigt Kachel und tatsächliches IPSView. |
| Erledigt | Sankey: optionale Rest-/Bilanzknoten | Opt-in je Knoten, Default false. Gemessener effektiver Zufluss minus Abfluss erzeugt einen berechneten Restabfluss oder ungeklärten Zufluss, ohne Rückkopplung und ohne erfundene Quellidentität. Dauerhafte Kennzeichnung auch ohne Labels/Tooltip; kein Verlustnachweis. Gemeinsamer Modellbauer, bisherige 100-/200-Gesamtgrenzen und Designs bleiben erhalten. MCP einschließlich Grenzen/Updates geprüft; Eigentümer bestätigt Kachel und tatsächliches IPSView. |
| Erledigt | Waterfall: explizite Zwischensummen | Option `SubtotalAfter` je Quelle, Default false. Eigene berechnete Nullbasis-Säule ohne erneute Addition, neue Quellen oder erfundene Messzeitstempel. Gemeinsamer Schrittplan für Live-Ausgaben und beide SVGs; ID-stabile Kategoriennavigation und alte Designs bleiben erhalten. MCP geprüft; Kachel/IPSView durch Eigentümer bestätigt. |
| Erledigt | Waterfall: unabhängige Neustarts | Option `Restart` ab zweiter Quelle, Default false. Absoluter neuer Abschnitt, bewahrter vorheriger Abschluss und ausdrücklich abschnittsbezogene Endsumme. Keine Addition unabhängiger Abschnitte; dauerhafter Hinweis. Weiterhin 100 Quellen, bis 201 Schritte einschließlich Zwischensummen/Endsumme, 200000 Modellbytes und 1 MiB HTML; alle Quellen/Abschnitte bleiben in Prüfung und Skala. MCP geprüft; Kachel/IPSView durch Eigentümer bestätigt. |
| Erledigt | CalendarPie: optionale unvollständige Tage | `ShowIncompleteDays` mit Default false zeichnet ausschließlich vorhandene positive Tagesgewichte. Fehlende Quell-IDs, Normalisierung und Originalformate bleiben erhalten; Raute, dauerhafter Hinweis und Tooltip erklären den eingeschränkten Nenner. Zustandsdauer, Designs und Archivbudgets bleiben unverändert. MCP funktional geprüft; Kachel und IPSView durch Eigentümer bestätigt. |
| Erledigt | Radar: optionale inverse Achsen | `Axes[].Inverse` mit Default false kehrt nur die begrenzte Radiusprojektion um: Maximum innen, Minimum außen. Gemeinsames Modell für beide Ausgaben und SVGs, Originalwerte/-formate und Quellenmetadaten bleiben erhalten. Bestehende Designs sowie 3–10 Achsen, 16 Gruppen, 160 Zuordnungen und Bytebudgets bleiben unverändert. MCP funktional geprüft; Eigentümer bestätigt Kachel und tatsächliches IPSView. |
| Erledigt | Radar: optionale unvollständige Vergleichsgruppen | `AllowIncompleteGroups=false` erhält den strengen Standard. Nur ausgelassene Zuordnungen werden optional; konfigurierte Quellen bleiben Fehler bei ungültigen Werten. Gemeinsame Nullplätze und Nachbarschaftskanten für Live-Ausgaben/SVGs; einzelne Punkte, offene Linien, Fragezeichen, Tooltip/ARIA und dauerhafter Hinweis ohne Flächen über Lücken. Designs und Schutzbudgets bleiben erhalten. MCP funktional geprüft; Eigentümer bestätigt Kachel und tatsächliches IPSView. |
| Erledigt | Graph: gerichtete Beziehungen | `DirectedEdges=false` erhält ungeordnete Altpaare. Gerichtete Kanten sind geordnet eindeutig; Gegenrichtungen sind erlaubt, Selbstkanten weiterhin ungültig. Gemeinsame Modellprüfung und Gegenrichtungsplanung für Live-Ausgaben/SVGs; native Pfeile, getrennte Kurven, Richtungstooltip und ARIA. IDs, Designs, 300/1000-, Listen- und Ausgabebudgets bleiben erhalten. MCP funktional geprüft; Eigentümer bestätigt Kachel und tatsächliches IPSView. |
| Erledigt | Graph: gewichtete Beziehungen | Optional je Kante `WeightMode=none|manual|variable`, mit ungewichtetem Standard. Fester gemeinsamer Min/Max-Bezug und unabhängige Pixelbreiten; Null bleibt sichtbar, Originalgewichte/-einheiten und Quellenmetadaten bleiben erhalten. Gemeinsames Modell für Renderer/SVGs, Gateway-/Referenzlogik wiederverwendet. Keine Änderung von Richtung, Kräfteparametern oder Schutzbudgets. MCP funktional geprüft; Eigentümer bestätigt Kachel und tatsächliches IPSView. |
| Erledigt | PictorialBar: Mehrfachübernahme von Quellen | Objektzweig, optionale Unterobjekte/ausgeblendete Zweige, Namensbestandteil und genaue native/Legacy-Einheit. Explizite Auswahl mit ID-stabiler Ergänzung des ungespeicherten Entwurfs und beiden Vorschauen; bestehende Quellen/Designs und 100-/64-KiB-Grenzen bleiben erhalten. Zentrale VariableSearchHelper-Suche und Library-Adapter, 5000 Objekte/500 numerische Treffer ohne stille Kürzung. MCP funktional einschließlich Speicherung von 100 Quellen und sichtbarer Fehlerdialoge geprüft. Auch die bereinigte Erfolgsbestätigung ist nach Modulupdate per MCP und als sichtbarer Konsolendialog bestätigt. |
| Erledigt | Candlestick: benannte Vergleichspanels | Optional ein bis vier ID-stabile Quellen mit getrennten Originalskalen/-einheiten, gemeinsamer Zeitachse und gemeinsamem Zoom. Alte Einzelquelle/Designs bleiben Standard. Zentraler Zeitraumplan, Quellidentität, Zoom und Startzustands-Komprimierung werden wiederverwendet; 720/63/64000 gelten gemeinsam. Lokale Modul-/Gateway-, Budget-, SVG- und native Geometrieprüfungen sowie synthetische Browserprüfung; nach Modulupdate per MCP funktional geprüft und vom Eigentümer für Kachel/IPSView bestätigt. |
| Umgesetzt, Live-Abnahme offen | Heatmap: getrennte Vergleichsansichten | Optional ein bis vier benannte Ansichten für unterschiedliche ID-Quellen oder Perioden, eigene feste Grenzen und frühere Basiszeiträume. Gemeinsame oder getrennte Farbskalen, Originalwerte/-einheiten, gemeinsame Navigation. Sieben Archivabfragen gelten insgesamt; identische Abfragen werden geteilt. Altquelle und Designs bleiben erhalten. Lokale Plan-/Gateway-/Form-/SVG-/native Layoutprüfungen und synthetische Browserprüfung; echte Hosts nach Modulupdate offen. |

Alle bislang konkret vereinbarten fachlichen Roadmap-Punkte sind damit lokal
umgesetzt. Die Live-Abnahme der Heatmap-Vergleichsansichten steht noch aus.
Weitere fachliche Erweiterungen werden erst bei einem neuen Bedarf vereinbart.

Weitere Grenzerhöhungen wie StateTimeline/GaugeMulti auf 100 Quellen oder mehr
Radar-Achsen bleiben bedarfsabhängig und benötigen Lastprüfung beziehungsweise
ein Layoutkonzept. Es sind keine bereits umgesetzten Fähigkeiten.

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
| EChartsGaugeMulti | 2–32 Quellen, jeweils eigene Skala, Einheit und Präzision; Modell höchstens 900000 Bytes, HTML höchstens 1 MiB. | Umgesetzt: optionale ID-stabile Quellenauswahl und responsive Seiten für vier Vorlagen; kleine Altlisten ohne Auswahl behalten ihre Geometrie. Vorhandene SVG-Importbudgets bleiben erhalten. 100 erst nach weiterem konkreten Bedarf und Lastprüfung. |
| EChartsGaugeTacho | 2–5 Quellen mit unabhängigen Skalen; festgelegte Instrumentenanordnung. | Fünf ist durch die besondere Geometrie begründet. Neue Anordnungen wären Voraussetzung für mehr; reine Grenzerhöhung nicht empfohlen. |
| EChartsGaugeChronograph | 2–5 Quellen als Hauptinstrument mit Nebeninstrumenten. | Ebenso Geometrievertrag. Optional ein einzelnes Hauptinstrument erst nach ausdrücklicher Produktentscheidung; kein allgemeiner 100-Quellen-Kandidat. |
| EChartsBarCategory | 1–100 numerische Quellenzuordnungen, gleiche Roh-Einheit oder ausdrücklich normalisierte Vergleiche mit eigenen Min/Max; auch negative Originale. Kategorien und Reihen, gruppiert/gestapelt/einfach. | Umgesetzt: responsive Kategoriennavigation, viewportstabile Updates und optionale Normalisierung. Stapel addieren normierte Einzelwerte, sind aber keine physikalischen Summen oder 100-Prozent-Stapel. |
| EChartsBarWaterfall | 2–100 Quellen gleicher Einheit, erster Wert absolut; spätere Werte signierte Änderungen oder explizite absolute Neustarts. Optional eine berechnete Zwischensumme nach jeder Quelle; Endsumme zusätzlich, höchstens 201 Schritte. Modell höchstens 200000 Bytes, HTML höchstens 1 MiB. | Umgesetzt: responsive ID-stabile Kategoriennavigation, explizite Zwischensummen ohne erneute Addition und unabhängige Bilanzabschnitte. Vollständige Skala aller Abschnitte, Endsumme bei Neustarts klar dem letzten Abschnitt zugeordnet. Gemeinsamer Schrittplan für Live und maximal fünf gezeichnete Vorschauschritte plus Endsumme; Quellwerte/Designs/Budgets bleiben erhalten. Neue Live-Abnahme offen. |
| EChartsBarPolar | 1–100 Quellen gleicher Roh-Einheit oder ausdrücklich aktivierte Normalisierung je Quelle auf 0–100; negative Originalwerte bleiben erhalten. Modell höchstens 200000 Bytes, HTML höchstens 1 MiB. | Umgesetzt: optionale ID-stabile Quellenauswahl und responsive Seiten mit vollständiger Werteskala. Bestehende Listen bis 16 ohne Auswahl behalten ihre Geometrie; die Vorschau zeigt höchstens sechs anfangs sichtbare Quellen mit vollständig berechnetem Maßstab. |
| EChartsPie | 1–100 nichtnegative Quellen gleicher Roh-Einheit oder optional normalisierte Gewichte mit beliebigen Originaleinheiten und eigenen Min/Max. Modell höchstens 200000 Bytes, HTML höchstens 1 MiB. | Umgesetzt: optionale erweiterte Quellenauswahl mit Anfangssichtbarkeit, Alle/Umkehren und ID-stabiler Legendenposition. Alle Quellen bleiben im Modell; Auswahl- und Gesamtanteil werden getrennt benannt. Keine automatische Restgruppe; eine Ausschnittsvorschau berechnet ihre Gewichte nicht neu. |
| EChartsFunnel | 1–32 nichtnegative numerische Quellen gleicher Einheit; Modell höchstens 200000 Bytes, finales HTML höchstens 1 MiB. | Umgesetzt: optionale Quellenauswahl und schrift-/platzabhängige Stufenseiten, vollständige Breitenskala auch bei verstecktem Maximum. Altlisten bis 16 ohne Auswahl behalten ihre Ansicht. Keine Prozentanteile, negative Breiten oder Normalisierung; andere Verbraucher behalten ihre ausdrücklichen Quellenbudgets. |
| EChartsPictorialBar | 1–100 nichtnegative Quellen gleicher Roh-Einheit oder optional normalisierte Quellen mit eigenen Min/Max und Originaleinheiten. Wertfarbe, interner Verlauf, Icons und eigene SVGs. | Umgesetzt: Normalisierung und responsive Kategoriennavigation ohne Datenverlust; Vorschau kennzeichnet ihren Acht-Quellen-Ausschnitt. Mehrfachübernahme nach Objektzweig/Name/Einheit ist mit 100 Quellen per MCP geprüft; Fehlermeldungen in der Konsole live bestätigt. Bereinigte Erfolgsbestätigung nach Update per MCP und als Konsolendialog bestätigt. |
| EChartsTree | Objektwurzel Ebene 0, Kategorien/Instanzen/Variablen; 1–12 Nachkommensebenen; 10–3000 Knoten, Standard 80; gefiltertes Suchbudget 30000 Objekte. Startzustände ab 200000 Bytes werden komprimiert, nicht abgelehnt. | Umgesetzt: tiefere Zweige, getrennte Anfangstiefe, ID-stabile Aufklapp-/Pan-/Zoom-Ansicht und gemeinsame begrenzte Strukturvorschau. Links/Medien/Skripte bleiben ausgeschlossen. |
| EChartsTreeSystemHealth | Dieselben Hierarchiegrenzen; bis 3000 ausdrückliche Boolean-/Zahlenregeln; Vorfahrenbewertung nur anhand des berücksichtigten Baums. | Umgesetzt: zwölf Nachkommensebenen und sichtbarer lokaler Bewertungsumfang einschließlich nicht enthaltener Regelvariablen. Keine zusätzliche Vollsuche oder Zustandsaussage außerhalb der ausgewählten Hierarchie. |
| EChartsGraph | 2–300 Objektknoten, 1–1000 Kanten; keine Selbstkanten. Standard ungerichtet mit eindeutigen ungeordneten Paaren, optional gerichtet mit eindeutigen geordneten Paaren und erlaubten Gegenrichtungen. Isolierte Knoten, getrennte Komponenten und gerichtete Kreise sind erlaubt. | Umgesetzt: größere Modelle, gemeinsam mit Z2M erhaltene Pan-/Zoom-Ansicht und Kräfte-Ausgangspositionen sowie begrenzter echter Vorschauausschnitt. Gerichtetes Modell mit nativen Pfeilen und getrennten Kurven (MCP funktional geprüft, Eigentümer bestätigt). Zusätzlich optionale feste/aktuelle Gewichte mit gemeinsamem festem Bezug, Originalmetadaten und unabhängigen Breiten; Gewichtung per MCP geprüft und vom Eigentümer in Kachel/IPSView bestätigt. |
| EChartsGraphZ2M | Nur gespeicherter Export, kein Scan; bis 500 Rohknoten/10000 Rohkanten. Sichtbare Verbindungen 1–2000, Standard 300; LQI 0–255; Export höchstens 16 MiB. | Umgesetzt und live bestätigt: stärkste/schwächste zuerst und einschließlich begrenzter LQI-Bereich. Alle Geräte und absolute Farbskala bleiben erhalten. Schutzgrenzen und eindeutiger Exportproduzent bleiben verbindlich; kein versteckter Scan. |
| EChartsTreemap | 1–100 Blätter, bis acht Ebenen einschließlich Blatt, höchstens 800 Knoten und 200000 HTML-sicher serialisierte Modellbytes. Standardmäßig nichtnegative Rohwerte; optional normierte Gewichte mit eigenen Min/Max. Eigene Originaleinheiten/Präzision je Blatt. | Umgesetzt: rekursives gemeinsames Modell, native Navigation, vollständige Strukturvorschau und ausdrücklich aktivierte Gewichte. Alte Gruppe/Untergruppe, Rohwertmodelle und Designs bleiben unverändert. Normalisierte Gruppen sind Gewichtssummen, keine physikalischen Summen. Neue Live-Abnahme offen. |
| EChartsSunburst | Dasselbe rekursive Modell und dieselben unveränderten Quellen-/Tiefen-/Knoten-/Bytebudgets wie Treemap, einschließlich optionaler Gewichte und Originalformate. Die ersten drei Ringdesigns bleiben erhalten; tiefere Ringe übernehmen das äußere Design. | Umgesetzt: native stabile Zweignavigation sowie gemeinsame Quellenprüfung, Modellbildung und Originalformatierung mit Treemap. Normalisierte Winkel und Gruppensummen werden ausdrücklich gekennzeichnet. Neue Live-Abnahme offen. |
| EChartsSankey | 2–100 Knoten, 1–200 gerichtete Flüsse insgesamt, einschließlich berechneter Bilanzterminals/-verbindungen; nichtnegative Breiten, optional `ReverseNegative` je Verbindung und `Balance` je Knoten. Konfigurierte und tatsächliche Topologie azyklisch/eindeutig, keine Selbstkanten. Gleiche Variable an mehreren Kanten möglich. Manueller Altmodus oder optionale automatische/manuell erwartete gemeinsame Einheit, höchstens 24 Zeichen. | Einheitenprüfung, Kantenumkehr und Rest-/Bilanzknoten per MCP und Eigentümer bestätigt. Betragsbreite, signierter Originalwert und konfigurierte Richtung bleiben getrennt. Berechnete lokale Rest-/Bilanzflüsse sind optional und klar von Messungen unterschieden; gemeinsame Modellbildung ohne Kaskaden. Keine Umrechnung/Normalisierung. DAG, nichtnegative Breiten und bisherige Bytebudgets bleiben Schutzregeln. |
| EChartsRadar | 3–10 Achsen, 1–16 Vergleichsgruppen, höchstens 160 Zuordnungen; je Achse eigener Min/Max-Bereich, Einheit und Präzision. Vollständigkeit bleibt Standard; optional mindestens eine Quelle je Gruppe. Modell höchstens 200000 Bytes, HTML höchstens 1 MiB. Unterschiedliche Einheiten bereits sinnvoll skaliert. | Umgesetzt: optionale Gruppenauswahl, Anfangssichtbarkeit, ID-stabile Auswahl/Legendenanker und inverse Achsen (MCP und Eigentümer bestätigt). Teilgruppen zusätzlich optional mit offenen Nachbarschaftskanten, expliziten Fehlstellen und unveränderten Originalwerten; MCP funktional geprüft und Kachel/IPSView durch Eigentümer bestätigt. Alle konfigurierten Quellen bleiben in Prüfung und Aktualisierung. Mehr Achsen erst mit eigenem Layoutkonzept. |

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
| EChartsScatter | Bis zu 16 benannte Vergleiche mit je 2–3 Rollen, insgesamt höchstens 48 Zuordnungen. Pro gemeinsamer Rolle gleiche Einheit, X/Y/Größe untereinander weiterhin verschieden. Abgeschlossene Intervalle werden je Gruppe exakt verbunden. Insgesamt 200–2000 geladene Aggregate über alle Zuordnungen verteilt. | Feste Min/Max-Größenbezüge und Gruppenmodell sind per MCP funktional geprüft und vom Eigentümer in Kachel/IPSView bestätigt. Alte ungruppierte Listen bleiben ohne Migration erhalten. Gemeinsame Bubble-/Farbskalen und flüchtige Legende verändern keine Quellenwerte oder Ladebudgets. |
| EChartsHeatmap | Eine numerische Quelle oder optional 1–4 getrennte Vergleichsansichten; datierte Woche oder Wochentag × Stunde; bisherige Kalenderzeiträume sowie benutzerdefinierte Dauern/feste historische Grenzen bis 366 × 24 Stunden. Abgeschlossene, vollständig enthaltene Stundenaggregate; je Chunk höchstens 2000, insgesamt höchstens sieben 60-Tage-Abfragen. | Umgesetzt und funktional abgenommen: vier Kennzahlen, passende Wochentag-/Stundenreduktion und neue Zeitfenster mit Navigation bei unveränderten Altzeiträumen/Budgets. Optionale 1–4-Vergleichsansichten sind lokal umgesetzt; gemeinsame oder getrennte Farbskalen, eigene Perioden und geteiltes Sieben-Abfragen-Budget. Live-Abnahme nach Modulupdate offen. Ansichten bleiben getrennt, Quellen werden nicht in dieselbe Zelle gemischt. |
| EChartsParallel | 2–16 Achsen; LineBudget 1–1000, Default 750; effektives Ladelimit je Achse höchstens abgerundet 10000/Achsenanzahl. 24 h / 7 d / 30 d, eigene Dauer oder feste historische Grenzen, Stunde/Tag. Auto oder explizit Mittelwert/Minimum/Maximum für Standardarchive, Zählerverbrauch für Zählerarchive; verschiedene Einheiten, nur gemeinsame Intervalle. | 16-Achsen-Ansicht, feste Min-/Max-Skalen und Kennzahlen je Achse funktional per MCP geprüft, Kachel/IPSView durch Eigentümer bestätigt. Automatische Altachsen, Originalwerte und alle Ladebudgets bleiben erhalten, Überschreitungen werden gemeldet. Mindestens zwei Achsen gehört zur Diagrammform. |
| EChartsCalendar | Eine numerische Archivquelle; Jahr, Quartal, Monat oder festes historisches Datumsfenster ab 1971, höchstens 366 Tage/Aggregate. Mittelwert/Minimum/Maximum erfordern Standardarchive, Verbrauch ein Zählerarchiv. Teilarchive und echte Nullwerte bleiben erhalten. | Umgesetzt: passende Navigation, freie Fenster auch über Jahresgrenzen, Beginn eingeschlossen/Ende ausgeschlossen; höchstens 120 Rückwärtsschritte für neue Ansichten, Jahresnavigation unverändert. Alte Designs/Jahre bleiben nutzbar. Funktionale MCP-Abnahme und Kachel/IPSView bestätigt. Mehrere Tageswerte gehören zu CalendarPie oder getrennten Kalenderpanels. |
| EChartsCalendarPie | Je nach Modus 1–5 numerische Quellen oder eine diskrete Zustandsquelle; Tagesmittel, Zählerverbrauch oder Zustandsdauer. Bis 32 Zustände. Optionale Min/Max-Normalisierung samt Originaleinheiten; Monatsmodell höchstens 250000 Bytes. | Umgesetzt: Teilkreise ausdrücklich optional mit fehlenden IDs, Raute und dauerhaftem Hinweis auf nur vorhandene Gewichte. Strenger Default, Zustandsdauer und alle Budgets bleiben erhalten. 1–5 bleibt die ausdrückliche Produktentscheidung. MCP funktional geprüft; Kachel/IPSView durch Eigentümer bestätigt. |
| EChartsBoxplot | 1–32 Standard-Archivquellen gleicher Einheit; 24 h / 7 d / 30 d, Custom oder feste historische Von-/Bis-Daten; bis 1000 abgeschlossene Stundenmittel je Quelle, insgesamt 10000. R7-Quartile, Min/Max-Whisker als Standard oder optional 1,5-IQR-Whisker mit separaten Ausreißern; keine Rohwertverteilung. | Umgesetzt und live bestätigt: Kategoriennavigation, relative und absolute Zeitfenster sowie optionales IQR-Modell. Ausreißer verwenden dieselben geladenen Stundenmittel und bleiben vollständig im Modell und in der Werteskala; Budgetüberschreitung verlangt weiterhin einen kürzeren Zeitraum ohne Teilstatistik. |
| EChartsCandlestick | Eine numerische Standard-Archivquelle oder optional 1–4 benannte Vergleichspanels mit eigenen Originalskalen/-einheiten; 15/30 Minuten, Stunde, Tag oder lokale Kalenderwoche in 24 h / 7 d / 30 d, Custom oder festen historischen Von-/Bis-Daten (höchstens 720 Intervalle). Tatsächliche Rohwerte für ersten/letzten Wert und Minimum/Maximum; leere Intervalle bleiben leer. Bis 63 Abfragen/64000 Rohänderungen pro Aktualisierung. | Relative/feste Zeitfenster, optionaler Zeitzoom und neue Minuten-/Wochenintervalle umgesetzt, per MCP funktional geprüft und vom Eigentümer in Kachel/IPSView bestätigt; auch die optionalen benannten Panels sind per MCP geprüft und vom Eigentümer für Kachel/IPSView bestätigt. Zoom bleibt eine reine Ansicht mit vollständiger Werteskala. Rohwert- und Abfragebudgets bleiben erhalten; Einzelmessungen ergeben flache Kerzen, keine erfundenen Endpunkte. |
| EChartsThemeRiver | 1–16 nichtnegative Standard-Archivmittel oder ausdrücklich gewählter Zählerverbrauch gleicher Einheit; Stunde/Tag, 24 h / 7 d / 30 d, Custom oder feste historische Von-/Bis-Daten; höchstens 720 Intervalle, 1000 geladene Aggregate je Quelle und 16000 gemeinsam. Modell höchstens 900000 Bytes, HTML höchstens 1 MiB. | Umgesetzt: relative und feste historische Zeitfenster, optionale ID-stabile Bandauswahl, gemeinsame Auswahl-/Startlogik, gekennzeichnete Vorschau und auswählbarer Verbrauch über den vorhandenen Gateway-Reducer. Versteckte Quellen schließen keine Lücken und behalten ihre Archivabfragen. Keine negativen Breiten, Nullfüllung oder eigene Zählerdifferenzbildung. |
| EChartsStateTimeline | Jetzt 1–32 Boolean-/Integer-Standardarchive, bis 32 Zustandsdefinitionen je Quelle; 24 h / 7 d / 30 d, Custom oder feste historische Von-/Bis-Daten; bis 2000 Rohwerte je Quelle plus Vorgänger, insgesamt 16000 Rohwerte; Modell höchstens 250000 Bytes. Unbekannte Zustände sichtbar. | Umgesetzt: unabhängige Zeilennavigation ab neun Quellen und Gesamtbudget ohne stille Kürzung, relative Custom-Zeitfenster und sekundengenaue historische Grenzen mit unveränderten Vorgängern. Später nach Lastprüfung bis 100 Quellen. Float-/String-Zustände erst mit ausdrücklichem Gleichheits-/Zuordnungsvertrag. |

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
  16 Quellen, BarCategory, Waterfall, Pie und BarPolar verwenden ausdrücklich 100,
  StateTimeline sowie Boxplot 32. Andere Familien bleiben
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

Boxplot prüft feste historische Grenzen, leere Fenster ohne Archivzugriff,
unveränderte Uhrfortschritts-/Altverträge und getrennte angefragte/effektive
Zeitstempel. Native Datumseingaben werden einschließlich DST streng geprüft.
Die zentralen Methoden verändern die bisherigen Consumer nicht. Beide Ausgaben,
reine Entwurfsvorschauen und die bisherigen 32-/1000-/10000-Grenzen sind geprüft.
Die neue Datumswahl und feste Archive in Kachel/IPSView bleiben nach Update live
zu bestätigen.

Radar prüft 16×10 Zuordnungen, 17×3 abgewiesene Gruppen, unveränderte Altlisten,
Initialauswahl und Gruppenfarben beider Designer, vollständige Lesezugriffe,
Referenzen und Updates einschließlich versteckter Fehler. SVG-Vorschauen prüfen
alle Quellen vor dem höchstens achtteiligen Ausschnitt mit ursprünglicher Palette
und vollständiger Clippingwarnung. Der native Engine-Test sichert Auswahl,
leere Auswahl/Wiederherstellung, Anker, Theme/Resize und ARIA-Anzeigenamen ab.
Die getrennten Modell-/HTML-Guards und das UTF-8-Maximalformular sind geprüft;
die gemeinsame Vorschau-Callback-Erzeugung wird semantisch ausgeführt statt nur
als Text geprüft. Inverse Achsen sind zusätzlich mit Enden/Mitte, Clipping,
unveränderten Originalmetadaten, echten SVG-/ECharts-Koordinaten und ausgeführten
Achsen-Callbacks geprüft. Die vollständige Qualitätssuite ist bestanden;
lokale Canvas- und echte Mausradprüfungen ergänzen die Modelle. Die neue
MCP-Abnahme inverser Achsen ist bestanden; der Eigentümer bestätigt Kachel und
tatsächliches IPSView. Teilgruppen sind mit 1/2 vorhandenen Werten, Nullplätzen,
Fehlermatrizen, echten offenen ECharts-Geometrien und beiden SVGs geprüft.
Ihre eigene neue Live-Abnahme nach Modulupdate bleibt offen.

Funnel prüft 32/33 Quellen, unveränderte Altlisten, gleiche Namen bei getrennten
IDs, Einheiten/Genauigkeit, negative/Null-/Gateway-Fehler auch versteckter Quellen,
vollständige Lesezugriffe/Referenzen/Abonnements und beide Ausgabewege. Die
Vorschau filtert/sortiert vor ihrem Ausschnitt und behält ein verborgenes Maximum.
Native ECharts-Geometrie prüft die Breiten tatsächlicher Stufenpolygone für beide
Ausrichtungen und weitere Seiten; Auswahl, leere Zustände und Anker bleiben bedienbar.
200000 Modellbytes und finales 1-MiB-HTML-Limit sind getrennt abgesichert.
Live-Abnahme nach Modulupdate bleibt beim Eigentümer.

GaugeMulti prüft 32/33 Quellen, unveränderte Altlisten, gleiche Namen mit
verschiedenen IDs, eigene Skalen/Einheiten, Darstellungsübernahme und
vollständige Lesezugriffe/Referenzen/Abonnements einschließlich versteckter
Updates. Alle vier Vorlagen und unabhängiges IPSView-Design werden geprüft,
ebenso leere/späte Anfangsauswahl und korrekte Vorschauzahlen/Geometrie.
Native Layoutprüfungen sichern Seiten, Identitäten und große Designimporte ab.
900000 Modellbytes und 1 MiB finaler HTML-Puffer besitzen getrennte Fehlertests.
Die Live-Abnahme nach Modulupdate bleibt beim Eigentümer.

BarPolar prüft 100/101 Quellen, Altlisten, vollständige Lesezugriffe/Abonnements,
versteckte Aktualisierungen und Fehler, Normalisierung, unabhängige Designer,
vollständige Vorschau-Extrema und Palettenidentität. Native Polar-Tests prüfen
Seiten/ID-Auswahl, alle 100 erreichbaren Quellen, manuelle und vollständige
automatische Skalen sowie Anker bei Sortierung/Resize/Refresh/Entfernung.
Modell- und HTML-Budgets werden unabhängig geprüft. Lokale synthetische
Canvas-Prüfungen bestätigen Auswahl/Seiten; Live-Abnahme nach Update bleibt offen.
Die gemeinsame Navigation bleibt auch im Category-Normalisierungsstresstest
unter 1 MiB (IPSView 1047188 Bytes, 1388 Bytes Reserve).

Pie prüft 100/101 Quellen, vollständige Lesezugriffe und VM-Abonnements,
versteckte Updates/Fehler, Altlisten, Einheiten und Normalisierung. Auswahl-
und Gesamtanteile werden mit unabhängigen Bezugsgrößen einschließlich großer
und subnormaler Zahlen geprüft. Vorschauen behalten den Nenner des gesamten
Anfangskollektivs. Native Tests sichern ID-Auswahl, Legendennavigation,
responsive Textbegrenzung und breite Legendenspalten bei 100/200 % Schrift ab.
Modell- und abschließender HTML-Guard werden separat geprüft. Lokale Canvas-
Prüfungen beider Ausgabewege sind positiv; Live-Abnahme nach Update steht aus.

Waterfall prüft 100/101 Quellen, 101 modellierte Schritte, späte Aktualisierungen,
Referenzen/Abonnements, Altlisten, Originaleinheiten und signed Kumulierung.
Die Vorschau prüft eine echte Gesamtbilanz trotz abweichender Präfixsumme und
ausgelassener Extrema. Native Tests sichern beide Ausgabewege, globale Skala,
ID-Tooltips und Navigation bei Reorder/Resize. Modell- und tatsächlicher finaler
HTML-Guard werden mit übergroßen Metadaten geprüft. BarCategory bleibt auch mit
gemeinsamer Navigation unter 1 MiB im maximalen Normalisierungstest.
Zwischensummen und Neustarts sind einschließlich 100 Quellen/201 Schritten
funktional per MCP geprüft; der Eigentümer bestätigt Kachel und tatsächliches
IPSView. Bestehende Live-Konfigurationen und Designs blieben unverändert;
temporäre Testobjekte wurden entfernt. Die Vorschauhost-Meldung zum
MutationObserver blieb ohne beobachteten Waterfall-Ausfall ungeklärt.

ThemeRiver prüft 16×720 Originalwerte, 16/17 Quellen und das gemeinsame
16000-Ladebudget einschließlich ausgeblendeter Quellen. Native Tests
bestätigen ID-stabile Auswahl, ursprüngliche Lücken und Nullwerte, während
Vorschauen ihre maximal acht gezeichneten Bänder kennzeichnen. Beide
verlustfrei komprimierten Ausgaben passen im gemessenen Szenario unter 1 MiB;
übermäßige Modell-/HTML-Größen werden ausdrücklich abgewiesen. Die lokale
Canvas-Prüfung für Auswahl, Wiederherstellung und Aktualisierung ist positiv.
Die neue Live-Abnahme nach Modulupdate bleibt offen.

Der zusätzliche Verbrauchstest verwendet den echten Gateway-Pfad mit bis zu
16×720 Zähleraggregaten, nicht aktuellen Werten oder Rohwertdifferenzen.
Geprüft sind falsche Archivtypen/Reducer, Null/negative Werte, Trunkierung,
Wiederanlauf, versteckte Lücken, beide Ausgaben sowie lokale Tages-/DST-Grenzen.

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

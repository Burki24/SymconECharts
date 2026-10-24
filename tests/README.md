# Tests

## Radar: inverse Achsen je Messgröße

`php tests/radar_model.php` begann RED an der noch unveränderten Radiusprojektion.
Geprüft sind identische Altachsen/false, unabhängige Umkehr mehrerer Achsen,
negative Wertebereiche, Min/Max/Mitte, Begrenzung außerhalb des Bereichs,
unveränderte Originalwerte/IDs/Zeitstempel und strikt abgewiesene Flags.
`php tests/gateway_gauges.php --radar-many` ergänzt 16×10 gemischte Achsen,
unveränderte Properties und Abonnements, beide HTML-Ausgaben und geometrisch
geprüfte PHP-SVGs. Der tatsächliche Achsen-Callback wird ausgeführt und übergibt
true/false in ungespeicherten Listen; beide Vorschauen bleiben ohne Persistenz,
Statusänderung oder Gatewayzugriff. Fehler auf der letzten Achse werden abgewiesen.
Die bestehenden Modell-, HTML- und Formulargrenztests bleiben aktiv.

`node tests/radar_layout.js` prüft echte Koordinaten der gepinnten ECharts-Engine
in Kreis/Vieleck und beiden Ausgabewegen: keine doppelte Umkehr, Originalwerte
in Tooltip und Punktlabels, sichere Texte und ARIA auch ohne Gruppenauswahl oder
sichtbare Achsennamen. `node tests/radar_browser.mjs` bietet unter `/?inverse=1`
synthetische Gruppen mit tatsächlicher PHP-Projektion. Die lokale Canvas-
Browserprüfung bestätigt inverse Beschriftung/Bereich, ARIA und erhaltene
Gruppenauswahl nach Reihenfolgeänderung in schmaler Kachel und breiter IPSView-
Testeinbettung. Keine Browserwarnungen oder -fehler beobachtet. Echte Mausradaktionen
über beiden Diagrammen scrollen den äußeren Host in beide Richtungen
jeweils 0 → 144 → 0, ohne Diagrammzoom oder Auswahländerung.

Die vollständige Qualitätsprüfung `php tests/quality.php --fix` ist bestanden:
Formatierung, Syntax, Verträge, alle Integrations- und Layouttests.
Lokale Browserfixtures ersetzen keine Symcon-Kachel-/Konsolen- oder tatsächliche
IPSView-Client-Abnahme. Diese bleibt für inverse Achsen nach Modulupdate offen;
keine Live-Konfigurationen wurden geändert.

## CalendarPie: optionale unvollständige Tage

`php tests/calendar_pie_data.php` und `node tests/calendar_pie_layout.js`
begannen RED an fehlender Tagespolicy beziehungsweise fehlendem Teilkreis.
Die familienlokale Policy wird mit striktem Default, Opt-in, Nullgewichten,
fehlenden/laufenden Tagen und unveränderter unbekannter Zustandsdauer geprüft.
`php tests/calendar_pie_integration.php` verwendet echte Module/Gateway mit
synthetischen Kernelarchiven: Tagesmittel und Zählerverbrauch, Originalformate
bei Normalisierung, fehlende IDs, Erhaltung von Designs/Referenzen und beiden
Ausgaben einschließlich WebContent. Gekürzte Antworten bleiben Fehler. Der echte
Checkbox-Entwurfscallback wird ausgeführt; beide SVGs folgen der Auswahl ohne
Property-, Status- oder Archivänderung. Alle vorhandenen Schutztests bleiben aktiv.

Der native Engine-Test prüft Teilkreise auch in der Größen-/Font-/Donutmatrix,
Markierungen, sichere begrenzte Tooltips, explizite Prozentnenner und Warnungen
ohne Tooltip/Legende. Die Innenlabel-Prüfung berücksichtigt den 3-Pixel-Versatz
der gepinnten Pie-Runtime. Ihr vorheriger 500-Pixel-Überlappungsfall ließ nach
dieser korrekten Platzprüfung keine Werte mehr zu; bei 600 Pixeln prüft derselbe
Fall weiterhin echte LabelLayout-Unterdrückung mit tatsächlich sichtbaren Zahlen.

`node tests/calendar_pie_browser.mjs` liefert unter `/incomplete` einen lokalen
synthetischen Vergleich: strenger Kalender, Teilkreise, normalisierte IPSView-
Ausgabe und echte PHP-SVG-Vorschau. Die Browserprüfung bestätigt Kennzeichnung,
fehlende Quellen, Originalwerte, eingeschränkte Anteile und Monatsnavigation.
Echte Mausradaktionen scrollen den äußeren Testhost über Kachel in beide
Richtungen (0 → 144 → 0) sowie über IPSView-Testframe (504 → 648 → 504), ohne
Diagrammzoom oder Monatswechsel. Keine Browserfehler beobachtet. Diese lokalen
Testeinbettungen ersetzen keine Symcon-/native IPSView-App-Abnahme. Die spätere
funktionale MCP-Prüfung ist bestanden; der Eigentümer bestätigt die optionalen
Teilquellen in Kachel und IPSView. Diese Benutzerbestätigung ist vom lokalen
Browsernachweis getrennt. Live-Konfigurationen oder Archive wurden bei der
lokalen Prüfung nicht verändert.

## Waterfall: berechnete Zwischensummen und unabhängige Neustarts

`php tests/waterfall_data.php` begann für Zwischensummen am fehlenden Schrittplan
RED und danach für Neustarts an der falschen Addition. Geprüft sind exakte
Altmodelle, Subtotals ohne erneute Addition, negative/null und maximale endliche
Starts, Überläufe vor späteren Neustarts, fehlerhafte Flags, stabile Quell-/
Subtotal-IDs und maximal 201 Schritte aus 100 Quellen.
`php tests/gateway_gauges.php --waterfall-sections` nutzt echte Module/Gateway
und synthetische Kernelwerte. Beide HTML-Ausgaben und SVGs, unabhängige Designs,
Originalmetadaten/Einheiten, alle Quellabfragen/Abonnements, späte Updates,
ausgeführte Listen-Entwurfscallbacks ohne Persistenz/Statusänderung sowie maximale
UTF-8-Listen und unveränderte Byteguards werden geprüft. Der bestehende
negative SVG-Layouttest erwartet nun den übersetzten Standardtext des gemeinsamen
Modells statt des zuvor fest auf Deutsch gesetzten „Gesamt“ in englischer Testsitzung.
`node tests/bar_waterfall_layout.js` prüft echte gepinnte Geometrie für absolute
Neustarts und Zwischensummen, vollständige Skalen früherer Abschnitte, Tooltips,
dauerhafte Hinweise, letzte Kategorie und Ansichtserhalt beim Resize in beiden
Adaptern. `node tests/bar_waterfall_browser.mjs` bietet unter `/?sections=1`
dieselben echten PHP-Ausgaben mit synthetischen Quellen und ohne Live-Transport.
Die lokale Browserprüfung bestätigt Zwischensumme/Neustart/Endsumme, Tooltip,
Navigation und Ansichtserhalt nach Aktualisierung sowie beide SVG-Rollen.
Echte Mausradaktionen scrollten den äußeren Testhost über Kachel und IPSView-
Testframe abwärts/aufwärts (0 → 451 → 0), ohne Skalenzoom oder ungewollte
Änderung der Kategorienansicht. Die Konsole blieb fehlerfrei.
Die funktionale MCP-Prüfung ist inzwischen bestanden; der Eigentümer bestätigt
Kachel und tatsächliches IPSView. Im Vorschauhost wurde eine ungeklärte
MutationObserver-Meldung ohne beobachteten Waterfall-Ausfall protokolliert.
Alle bestehenden Konfigurationen/Designs blieben erhalten, Testobjekte wurden entfernt.

## Sankey: berechnete Rest-/Bilanzflüsse

`php tests/sankey_balance.php` begann RED am fehlenden Restfluss. Es prüft
Opt-in je Knoten, beide Differenzrichtungen, exakte Altmodelle, Umkehr vor
Bilanz, keine Kaskaden, Rundungsreste und endliche Extrema sowie 100-/200-Grenzen
einschließlich unsichtbarer Konfiguration. `php tests/gateway_gauges.php
--sankey-balance` prüft echte Module/Gateway mit synthetischen Kernelwerten:
keine erfundenen Quellen oder Zeitstempel, erhaltene Designs und Abonnements,
beide HTML-Ausgaben/WebContent und SVGs, Updates und Fehler/Erholung.
Der tatsächliche kompakte Node-Entwurfscallback wird ausgeführt, ohne
Persistenz oder Statusänderung. Auch die größten ergänzten Modelle samt
Formularen halten die bestehenden 1-MiB-Grenzen ein.
`node tests/sankey_layout.js` prüft die echte ECharts-Verzweigungsgeometrie,
Kennzeichnung und sichere Tooltips in beiden Adaptern/Ausrichtungen und bei
ausgeschalteten Labels/Tooltips. `node tests/sankey_units_browser.mjs` bietet
die synthetischen Fälle `balance`, `balance-inflow`, `balance-equal` und
`balance-budget`. Die Fixture spricht keine Symcon-Installation an.
Die lokale Canvas-Browserprüfung bestätigt beide Differenzrichtungen, die
Kennzeichnung in HTML und SVG, keinen Zusatz bei exakter Bilanz und dieselbe
Budgetdiagnose ohne Teilgeometrie. Berechnete Tooltips bleiben durch Begrenzung
und Umbruch auch in schmalen eingebetteten Ansichten lesbar. Echte Mausradaktionen
scrollten den Testhost über beiden Diagrammadaptern in beide Richtungen
(0 → 637 → 0), ohne Diagrammzoom. Die Konsole blieb ohne Warnungen/Fehler.
Die anschließende funktionale MCP-Prüfung auf Symcon 9.1 bestätigt beide
Differenzrichtungen, exakte Bilanz, Nullwerte, Kantenumkehr, unabhängige benachbarte
Bilanzen, Einheitenprüfung einschließlich unsichtbarer Quellen und unveränderte
100-/200-Grenzen. Die Browserprüfung zeigte beide SVGs, Kennzeichnung und echte
IPSView-WebContent-Updates. Alle 32 bestehenden ECharts-Konfigurationen und
Datenflussverbindungen blieben anhand der Prüfsummen unverändert; sämtliche
temporären Testobjekte wurden entfernt. Der Eigentümer bestätigt anschließend
Rest-/Bilanzknoten in tatsächlicher Kachel und IPSView.

## Sankey: optionale Umkehr negativer Flüsse

`php tests/sankey_model.php` begann für den Opt-in-Fall RED an der bisherigen
Negativablehnung. Es prüft Betragsbreiten, Originalwerte, Null/Extrema, alte
Modelle und effektive Kreise einschließlich Nullflüssen.
`php tests/gateway_gauges.php --sankey-reversal` prüft echte Module/Gateway mit
synthetischem Kernel: je Kante explizite Auswahl, unveränderte IDs/Abonnements,
Mehrfachverwendung, beide HTML-Ausgaben, veröffentlichte WebContent-Dokumente,
SVG-Richtungen/-Originale, präzise Fehler und Erholung nach Vorzeichenwechsel.
Der tatsächliche kompakte Formcallback wird ausgeführt; Diagnose und beide
Vorschauen bleiben statusfrei und speichern keine Entwürfe. Maximale
100-/200-Modelle mit langen Schlüsseln sowie der 65536-Byte-Guard sind geprüft;
HTML und Formular bleiben unter 1 MiB. Die kompakte gemeinsame Callback-
Erzeugung ersetzt wiederholte Skalarnamen, nicht die Schutzgrenze.
`node tests/sankey_layout.js` prüft die effektive Richtung und sichere Tooltips
mit Originalwerten in beiden Ausrichtungen/Adaptern mit echter ECharts-Geometrie.
`node tests/sankey_units_browser.mjs` bietet zusätzlich `reverse` und
`reverse-cycle` als ausschließlich synthetische Browserfälle.
Der lokale Canvas-Browserlauf bestätigt den Originalwert mit Vorzeichen im
Tooltip, beide Ausrichtungen und die gleiche Kreisdiagnose in beiden HTML-
Ausgaben und SVG. Echte Mausradaktionen scrollten den Testhost über Kachel
und IPSView-Frame in beide Richtungen (0 → 637 → 0), ohne Diagrammzoom;
die Browserkonsole blieb ohne Warnungen/Fehler. Testtab und lokaler Server
wurden anschließend geschlossen.
Die spätere MCP-Prüfung und Eigentümerbestätigung für tatsächliche Kachel und
IPSView liegen für die Kantenumkehr vor; die Bilanzfunktion ist separat abzunehmen.

## Sankey: Einheitenmodell und Quellenprüfung

`php tests/sankey_units.php` begann RED an der fehlenden Unit-Policy. Es prüft
manuellen Altmodus, automatische/erwartete Einheiten, ID-stabile Auflösung,
exakte Kennzeichen, einheitenlose Mengen und Zeilendiagnosen.
`php tests/gateway_gauges.php --sankey-units` prüft echte Module/Gateway und den
gemeinsamen Metadatenleser mit synthetischem Kernel. Native/Legacy-Vorrang,
Metadatenfehler, Nullflüsse, unveränderte Altmodelle/Quellwerte, Abonnements,
Ausgaben, gespeicherte Designs und reine SVG-Entwürfe sind abgedeckt.
Maximale 100-/200-Modelle bleiben samt Formular unter den bisherigen Budgets.
`node tests/sankey_layout.js` prüft effektive Einheiten, Originalbreiten und
HTML-sichere Tooltips in beiden Adaptern. `node tests/sankey_units_browser.mjs`
stellt synthetische Fälle und beide echten HTML-Ausgaben plus SVG bereit.
Der lokale Browserlauf bestätigt automatische/geprüfte und manuelle Einheiten,
einheitenlose Ausgabe und die präzise Diagnose eines Nullflusses mit kWh statt W.
Die endgültige Fixture liefert das normale Interaktionsasset aus; ihre frische
Browserkonsole blieb fehlerfrei. Echte Mausradaktionen scrollten den äußeren
Testhost über dem Diagramm abwärts/aufwärts (0 → 637 → 0), ohne Sankey-Zoom.
Die lokale Fixture spricht keine Symcon-Installation an. Die spätere MCP-Abnahme
des Einheitenmodells auf Symcon 9.1 prüfte getrennt alle drei Modi, native/Legacy-
Quellen, Nullflüsse und 24-/25-Zeichen-Grenzen mit temporärer Testinstanz.
WebContent aktualisierte sich im Browser live; alle 32 vorhandenen Konfigurationen
blieben unverändert, sämtliche Testobjekte wurden entfernt. Der Eigentümer
bestätigt anschließend tatsächliche Kachel und IPSView. Die neue Umkehrfunktion
ist von diesem älteren Live-Nachweis ausdrücklich nicht umfasst.

## GraphZ2M: Verbindungsauswahl

`php tests/zigbee_graph_model.php` begann RED bei „schwächste zuerst“.
Es prüft die inklusive Auswahl nach höchstem LQI je gerichteter Dublette,
stabile Gleichstände, Gegenrichtungen, Null, nicht endliche Werte, leere
Bereiche und unveränderte Roh-/Anzeigebudgets. Der echte Modul-/Exportpfad
unter `php tests/gateway_gauges.php --zigbee-link-selection` verwendet nur
temporäre synthetische Exportdateien. Geprüft werden Altdefaults, reine
Entwürfe, Feldsichtbarkeit, unabhängige Designer und beide HTML-Ausgaben
auch bei 500 Knoten/10000 Meldungen/2000 ausgewählten Verbindungen.
`node tests/zigbee_graph_layout.js` prüft beide Adapter/Layouts, absolute
Farben und Statusangaben auch bei leeren Treffern. `node tests/graph_browser.mjs`
bietet unter `/?family=z2m` synthetische Exportauswahl über das echte PHP-Modell.
Die lokale verschachtelte Canvas-Prüfung bestätigte beide Layouts/Adapter,
leere Trefferlisten und erhaltenen Zoom nach Auswahlwechsel/Refresh;
die Browserkonsole blieb ohne Warnungen oder Fehler.
Diese lokalen Tests ersetzen keine neue Live-Abnahme in Symcon/IPSView.

## Candlestick: Minuten- und Wochenintervalle

`php tests/candlestick_intervals.php` begann RED an der fehlenden Minutenart.
Es prüft zentrale vollständige Minuten-/ISO-Wochenintervalle, relative und
absolute Grenzen, lokale DST-/Offsetwechsel, ISO-Jahreswechsel, Custom-Dauern
und die unveränderte 720/721-Grenze. Die bisherigen Stunden-/Tagesprüfungen
bleiben unverändert aktiv. `php tests/gateway_gauges.php --candlestick-intervals`
prüft echte Modul-/Gateway-Zusammenarbeit mit synthetischen Kernelarchiven,
OCLH statt Aggregate, Lücken, Cache, unabhängige Designer, reine SVG-Entwürfe,
Zoom und beide begrenzten HTML-Ausgaben. `node tests/candlestick_layout.js`
ergänzt native Geometrie-/Label-/Tooltip-/Zoomprüfungen für alle drei neuen
Intervallarten, beide Ausrichtungen und Adapter. `node tests/candlestick_browser.mjs`
bietet auswählbare synthetische Intervallansichten; lokale verschachtelte
Canvas-Prüfungen bestätigten echtes Mausrad in beide Richtungen bei stabiler
Werteskala und erhaltenen Ansichten nach Refresh. Die neuen Intervalle wurden
nach Modulupdate per MCP funktional geprüft; der Eigentümer bestätigt Kachel
und tatsächliches IPSView. Echte Touchgeräte bleiben ungeprüft.

## Treemap/Sunburst: normalisierte Gewichte und Blattformatierung

`php tests/treemap_model.php` begann RED an der fehlenden Projektion und prüft
Gewichte, Originale, Bereiche und unveränderte Altmodelle.
`php tests/gateway_gauges.php --hierarchy-normalization` prüft den echten
Modul-/Gateway-Pfad mit synthetischem Kernel, native und manuelle Formate,
negative/außerhalb liegende Originale, ungültige späte Quellen, Entwürfe,
wiederholtes ApplyChanges sowie beide Designer, Vorschauen und Ausgaben.
100 Quellen mit acht Ebenen und 800 Knoten bleiben innerhalb der Budgets;
101 Quellen werden abgewiesen. Es werden keine Live-Daten oder Archivregeln geändert.

`node tests/treemap_layout.js` und `node tests/sunburst_layout.js` prüfen
Originalbeschriftungen, escaped Tooltips, Gewichtshinweise, Gruppensummen ohne
falsche Einheiten und die gepinnte native ECharts-Geometrie in beiden Adaptern.
Alte Navigation und Designer bleiben in den bestehenden Prüfungen enthalten.
Live-Abnahme nach Modulupdate und tatsächlicher IPSView-Client bleiben separat.

## Scatter/Bubble: benannte Vergleichsgruppen

`php tests/gateway_gauges.php --scatter-groups` begann RED an den fehlenden
Vergleichsgruppen. Der echte Modul-/Gateway-Pfad prüft bis zu 16 Gruppen und
48 Zuordnungen, unabhängige Joins bei Lücken, mehrfach genutzte Variablen mit
eigenen Kennzahlen, umbenannte/umgeordnete Gruppen, Einheiten, späte Fehler,
alle Quellenreferenzen, Designvererbung und beide Vorschauen. Alte Quellen ohne
`Group` und explizit leere Gruppen erhalten dasselbe unveränderte Modell.
17 Gruppen, 49 Zuordnungen, doppelte Rollen und unpassende Einheiten werden
vor Archiv-I/O abgewiesen. Alle 200/1000/2000-Budgets bleiben gemeinsam.
Der Stressfall 16 × 2 × 62 geladene Aggregate ergibt höchstens 992 Punkte;
beide endgültigen HTML-Ausgaben mit UTF-8-Beschriftungen bleiben unter 1 MiB.
Der 200000-Byte-Guard für neue Gruppenmodelle wird getrennt geprüft.

`node tests/scatter_layout.js` prüft mehrere native Serien, gemischte
Scatter-/Bubble-Gruppen, gleiche Anzeigenamen, escaped Original-Tooltips,
gruppenübergreifende Bereichsauswahl, globale Größen-/Farbskalen und die
gemeinsame ID-stabile Auswahl bei Refresh/Umordnung. Die gepinnte ECharts-Engine
prüft echte Mehrserien-/Legendengeometrie. `node tests/scatter_browser.mjs`
bietet eine rein synthetische lokale Canvas-Prüfansicht für beide Adapter.
Live-Abnahme nach Modulupdate und tatsächlicher IPSView-Client bleiben separat.

## Scatter/Bubble: feste Größenbezüge

`php tests/gateway_gauges.php --scatter-bubble-range` prüft den echten
Modul-/Gateway-Pfad mit synthetischem Kernelarchiv: automatische Altmodelle,
unveränderte Originalwerte und Einheiten, getrennte Designer, Formularcallbacks,
reine Entwurfsvorschauen, Designkopie und die unveränderten 200/1000/2000-Budgets.
Ungültige Modi, gleiche/vertauschte/nichtendliche oder überlaufende Grenzen
werden vor Archiv-I/O abgewiesen. Wiederholung und Refresh verändern keine
Properties. Beide vollständigen HTML-Ausgaben bleiben unter 1 MiB.

`node tests/scatter_layout.js` begann RED an der fehlenden festen Skalierung.
Der Test prüft beide Ausgabeadapter, unveränderte automatische Einzelpunkte,
Begrenzung nur der Geometrie, Originaltooltip und Größenstabilität bei
Aktualisierung sowie anderem Datenfenster. Die gepinnte echte ECharts-Engine
bestätigt die Durchmesser 10/10/25/40/40 für Werte -10/0/50/100/150 bei 0–100.
Die neue Funktion bleibt nach Modulupdate in Kachel und tatsächlichem
IPSView live abzunehmen; lokale Tests sind keine Live-Lastfreigabe.

## Parallel: individuelle feste Min-/Max-Achsenbereiche

`php tests/gateway_gauges.php --parallel-axis-ranges` begann RED an fehlenden
Modellgrenzen. Der isolierte öffentliche Modul-/Gateway-Test prüft automatische
Altzeilen, gemischte und gleichartige Achsen, unabhängige Designs, Originalwerte,
vollständige gemeinsame Zeilen, exakte Grenzwerte und Überschreitungen auf der
16. Achse. Umordnung folgt Variablen-IDs, nicht gleichen Namen. Ungültige Typen,
fehlende Grenzen, null, zusammenfallende große Integer, überlaufende Differenzen
und JSON-Zahlen `1e400` werden vor Archiv-I/O abgewiesen. Beide HTML-Ausgaben,
veröffentlichter IPSView-WebContent und das 10000er-Budget bleiben abgesichert.
Reine gespeicherte/ungespeicherte Vorschauen prüfen auch die ausgelassene 16. Achse;
Wiederholung und Formularaufruf verändern keine Konfiguration.

`node tests/parallel_layout.js` begann RED an fehlenden nativen `min`/`max`.
Die echte gepinnte Runtime prüft tatsächliche feste Skalen, unveränderte native
Werte, ehrliche Achsenmarkierung, Panning und Rückkehr zu automatisch. Die lokale
Canvas-Prüfung zeigte überlaufende Linien in der Titelzone nach Entfernung des
nativen Startclips. Ein dauerhafter Clip an der Serienwurzel über den offiziellen
Post-Update-Hook begrenzt nur Linien, nicht Titel oder Daten. Der echte Runtime-Test
sichert Cliprechteck, Panning und Entfernung beim Wechsel zu automatisch ab.
Alte 16×625- und historische Zeitraumtests bleiben Regressionen;
`php tests/normalization.php` prüft die zentrale numerische Bereichsprüfung samt
unverändertem Normalisierungsvertrag.

Die lokale Fixture `node tests/parallel_browser.mjs` unterstützt `&ranges=fixed`.
Am 21.10.2026 wurden beide Ausgabeadapter bei 400×520/790×440, Warnung,
Titelschutz und Verschieben bis zur letzten festen Achse mit synthetischen
Daten geprüft. Neue Skalen nach Modulupdate und ein tatsächlicher IPSView-Client
sind dabei nicht live geprüft. Keine Installation oder Archivierung verändert.

## Parallel: 16 Achsen mit verschiebbarer Ansicht

`php tests/gateway_gauges.php --parallel-many` prüft echtes Modul, gemeinsame
Query-Planung, Join und Gateway mit Kernel-Doppeln. Der Lasttest läuft über
`tests/run.php` in einem eigenen Prozess innerhalb des bisherigen PHP-Speicherlimits.
Erster Lauf RED wegen der
alten Zehn-Achsen-Grenze. Die Matrix deckt 2/10/11/13/15/16 Achsen, feste,
eigene und bisherige Zeiträume sowie kleinere Linienbudgets ab. 16×625 Aggregate
belegen das unveränderte 10000er-Budget; die Lücke der letzten Achse reduziert
die gemeinsame Linienzahl. Gemischte Einheiten, Zähler, Tagesintervalle,
17/1/doppelte Achsen und eine übergroße sechzehnte Antwort sind enthalten.
Unicode-Labels maximaler Länge und 10000 große endliche Zahlen passen in beide
HTML-Budgets. Reine Vorschauen kennzeichnen den Ausschnitt ohne Archivzugriff;
Refresh/Öffnen verändern weder gespeicherte Quellen noch Designs/Budgets.

`node tests/parallel_layout.js` begann mit fehlender breiter Ansicht RED und
prüft alle Dimensionen/Werte, Steuerknöpfe, Tastatur, Größen-/Schriftänderung,
ID-stabile Umordnung, Rückkehr aus einer voll sichtbaren Ansicht und Fehler.
Der echte gepinnte Renderer sichert native Koordinaten, Skalen, Achsenmarkierung,
sichtbaren Titel und Tooltip-Grenzen für beide Ausgaben ab. Alte absolute
Zeitraumtests behalten die Zehn-Achsen-Budgetmatrix; ihre neue Negativgrenze
liegt bei 17 statt elf Achsen.

`node tests/parallel_browser.mjs` bietet unter Port 8803 ausschließlich
synthetische Canvas-Daten (`?axes=2|3|10|16&font=100|200`). Am 21.10.2026 wurden
400×520 und 790×440, Achsenknöpfe, Pos1/Ende und 200-%-Schrift lokal geprüft.
Keine Installation oder Archivierung bei dieser lokalen Prüfung verändert.
Die spätere funktionale MCP-Prüfung auf Symcon 9.1 mit temporärer Instanz
bestätigt 16 Achsen/42 gemeinsame Stundenlinien mit unabhängigen Originalwerten,
kleine Limits, feste Tagesintervalle, 17-Achsen-Ablehnung und beide Ausgaben.
Der Eigentümer bestätigt Kachel und tatsächliches IPSView. Testobjekte wurden
entfernt, Konfigurationen/Designs und Archivierung blieben unverändert.
Ein voller 10000-Punkte-Live-Lasttest bleibt wegen kurzer Archive offen.

## Heatmap: benutzerdefinierte und feste historische Zeiträume

`php tests/heatmap_window.php` prüft freie Dauern, strikte native Datumsfelder,
angefangene Randstunden, leere Kurzfenster, gleich lange Navigationsschritte,
stabile feste Grenzen, 23-/25-Stunden-Tage und 366/367 Tage. Alte Kalenderpläne
bleiben auch mit ungültigen inaktiven neuen Feldern unverändert. Erster Lauf
RED wegen des noch unbekannten benutzerdefinierten Modus.

`php tests/gateway_gauges.php --heatmap-periods` nutzt echtes Modul/Gateway
mit Kernel-Doppeln und ist auch im normalen Gateway-Test enthalten. Alle vier
Kennzahlen, beide Ausgaben/WebContent, unabhängiges IPSView-Design, Wiederholung,
authentifizierte Navigation/Reset und Refresh bleiben geprüft. Ungültige
Grenzen scheitern vor Archivabfragen, gültige leere Fenster lesen kein Archiv.
Ein dichtes Fenster mit 8784 Stunden benötigt genau sieben 60-Tage-Abfragen mit
je höchstens 2000 Aggregaten; eine abgeschnittene oder übergroße letzte Antwort
verhindert ein irreführendes Teilmodell. Native Formularfelder und beide
Entwurfsvorschauen lesen keine Archive und ändern weder Konfiguration noch Status.

`node tests/heatmap_layout.js` prüft beide neuen Modi und Ausgaben auf schmaler
und breiter Fläche mit dem echten gepinnten ECharts-Renderer, unverfälschte
Wochenzeilen, Uhrzeiten/Offsets und Navigation/Reset. Antworten einer alten
Dauer, Grenze, Quelle oder Gestaltung dürfen die neue Ansicht nicht ersetzen.
`node tests/heatmap_browser.mjs` liefert unter Port 8802 synthetische Daten:
`?range=custom|absolute&metric=average|minimum|maximum|consumption`. Am 21.10.2026
wurden feste Grenzen und freie Verbrauchsfenster in 400×600-/790×440-Canvas-
Ansichten sowie Navigation und Reset lokal geprüft. Lange Zeitraumtexte werden
zugunsten unverdrängter Knöpfe gekürzt und bleiben vollständig im Tooltip.
Keine Symcon-Daten oder Instanzen bei dieser lokalen Prüfung verändert.
Die spätere funktionale MCP-Abnahme auf Symcon 9.1 ist erfolgt; der Eigentümer
bestätigt Kachel und tatsächliches IPSView. Temporäre Testobjekte wurden entfernt,
bestehende Konfigurationen/Designs blieben identisch. Ein Live-Lasttest bleibt offen.

## Calendar: Jahr, Quartal, Monat und feste Datumsfenster

`php tests/calendar_data.php` prüft die unveränderten Jahrespläne sowie Monats-/
Quartalswechsel, Schaltjahre, jahresübergreifende freie Fenster, 366/367 Tage,
strikte native SelectDate-Felder, Zukunft und Navigation über DST. Fehlende
Mitternacht wird zur realen lokalen Tagesgrenze; ein vollständig entferntes
Datum bleibt ungültig. `calendar_pie_data.php` sichert das unveränderte
CalendarPie-Modell nach Zentralisierung der begrenzten Tagesenumeration.

`php tests/gateway_gauges.php` bindet `calendar_views_integration.php` ein:
reales Calendar/Gateway mit Kernel-Doppeln, alle vier Kennzahlen in allen
Ansichten, unveränderte 366-Aggregat-/Ausgabebudgets, Teilarchive, unabhängiges
IPSView-Design, beide Ausgaben/WebContent, wiederholtes ApplyChanges,
authentifizierte lesende Navigation und ungültige Fenster vor Archivzugriff.
Native Formularfelder und beide Vorschauentwürfe verwenden dieselben Grenzen,
ohne Archivzugriff, Property- oder Statusänderung. Der erste neue Datentest
scheiterte vor der Implementierung an der fehlenden View-Methode.

`node tests/calendar_layout.js` prüft mit dem gepinnten echten ECharts-Renderer
kurze und jahresübergreifende Datumsarrays, endliche Zellgeometrie, tatsächliche
Labelgrenzen in beiden Ausrichtungen/Ausgaben, angepasste Beschriftungsdichte,
Navigation/Reset und verworfene Antworten einer alten Konfiguration.
`node tests/calendar_browser.mjs` bietet unter Port 8796 synthetische Daten,
`?period=year|quarter|month|absolute` und `/chart?mode=symcon|ipsview&period=…`.
Am 20.10.2026 wurden kurze und jahresübergreifende Canvas-Ansichten auf schmaler
Fläche sowie Navigation/Reset für Quartal und freie Fenster lokal geprüft;
die breiten Ausrichtungen sind zusätzlich durch echte SVG-Geometrie geprüft.
Keine Symcon-Daten verwendet. Die neuen Calendar-Ansichten wurden inzwischen
per MCP geprüft; der Eigentümer bestätigt anschließend Kachel und tatsächliches
IPSView. Das ist keine Live-Lastfreigabe.

## Abnahmestatus des vorherigen Archivzeiträume-Blocks

Die gemeinsame funktionale MCP-Live-Abnahme von Boxplot, Parallel, Candlestick,
ThemeRiver und StateTimeline ist abgeschlossen. Der gefundene ThemeRiver-
Teilarchivfehler wurde korrigiert und nach Modulupdate erneut live geprüft;
der Eigentümer bestätigt Kachel und tatsächliches IPSView. Bestehende
Konfigurationen, Designs und Archiveinstellungen blieben unverändert;
temporäre Testobjekte wurden entfernt. Die vollständigen Grenz-, Budget-
und Fehlermatrizen bleiben automatisierte Nachweise, keine Live-Lastfreigabe.
Die folgenden Detailabschnitte beschreiben die ursprünglichen Entwicklungs-
und Testschritte; dieser Status ersetzt deren damalige offene Live-Hinweise.

## StateTimeline: feste historische Zeiträume

`php tests/gateway_gauges.php --state-timeline-absolute` verwendet die vorhandene
Testklasse mit steuerbarer Uhr und das reale Modul/Gateway mit Kernel-Doppeln.
Erster Lauf RED wegen fehlendem absoluten Modus. Geprüft werden ungerundete
Sekunden, 1-Sekunden-Fenster, Vorgänger, beide Ränder, Wiederholungen, unbekannte
Anfänge/leere Archive, stabile Grenzen, DST und nicht ganzstündige Offsetwechsel.
Die Date-Fixture bewahrt rohe lokale Uhrfelder, damit PHP nicht schon vor dem
Test nicht existierende Sommerzeitstunden still normalisiert.

Ungültige Dates müssen vor Rohwert-/Vorgängerabfragen scheitern. Beide Ausgaben,
native Date-Felder, tatsächliche Vorschauachsen, Zustandsnamen/-farben und reine
Entwürfe ohne Konfigurations-/Statusänderung bleiben abgesichert. 32×500 Punkte
prüfen das volle 16000er-Budget mit Restlimits 1500/1000/500; gekürzte, übergroße,
konfliktbehaftete oder vorgängerfalsche Antworten der letzten Quelle dürfen kein
Teilmodell liefern. Alte Zeitmodelle und die bestehenden 2000er-/Ausgabeguards
bleiben in `state_timeline_integration.php` aktiv.

`php tests/archive_absolute_windows.php` prüft die gemeinsame exakte Grenzplanung
und unveränderte Vollintervallpläne. Gesamtlauf: `php tests/quality.php --fix`.
Die gemeinsame funktionale Live-Abnahme aller fünf Familien ist inzwischen
erfolgt; siehe aktuellen Abnahmestatus oben (ADR 0081/0082).
Es werden keine laufenden Instanzen, Zustandszuordnungen oder Archive verändert.

## ThemeRiver: feste historische Zeiträume

`php tests/gateway_gauges.php --theme-river-absolute` verwendet die bestehende
Verbrauchs-Testklasse und den realen Modul-/Gateway-Pfad mit Kernel-Doppeln.
Der erste Lauf war RED, weil `absolute` noch nicht unterstützt wurde.
Geprüft sind absolute Mittelwerte und Zählerverbräuche, echte Nullwerte,
gemeinsame Lücken versteckter Quellen, stabile Grenzen, vollständig enthaltene
Stunden/Tage, leere Fenster ohne Query, DST und ungültige Date-Eingaben.
Native Formularfelder und beide SVG-Entwürfe bleiben ohne Gateway-/Archivzugriff,
Property- oder Statusänderung; obere Legenden dürfen die Dates nicht überdecken.

Beide Kennzahlen werden mit 16×720 Werten und unveränderten 1000/16000-Ladegrenzen
geprüft; 721 Intervalle scheitern vor dem ersten Laden. Trunkierte, übergroße,
negative oder archivtypfalsche Antworten einer versteckten Quelle dürfen kein
Teilmodell liefern. Die begrenzten, verlustfrei komprimierten Ausgaben und
unabhängige Designs bleiben abgesichert. Alte Zeitmodelle ignorieren neue
inaktive Dates; der absolute Modus ignoriert inaktive Custom-Felder.

Die zentrale Regression `archive_absolute_windows.php` und die bestehenden
ThemeRiver-Daten-/Verbrauchs-/16-Quellen- und Rendererprüfungen sichern die
unveränderten Verträge. Gesamtlauf: `php tests/quality.php --fix`.
Diese Tests ändern keine Live-Instanzen oder Archive. Die anschließend
durchgeführte gemeinsame Live-Abnahme ist im Status oben dokumentiert.

## Candlestick: feste historische Zeiträume

`php tests/gateway_gauges.php --candlestick-absolute` verwendet die bestehende
Candlestick-Testklasse und den realen Modul-/Gateway-Pfad mit Kernel-Doppeln.
Der erste Lauf war RED, weil der absolute Modus noch abgewiesen wurde.
Die Regression prüft echte OCLH-Rohwerte, partielle Stunden-/Tagesgrenzen,
Lücken, Einzelwerte, Null-/Negativwerte, zeitstabile Fenster, leere Fenster ohne
Abfragen und kontrolliert ungültige/fehlende/zukünftige/mehrdeutige Grenzen.
DST umfasst 23-/25-Stunden-Tage, getrennte Herbststunden, Viertelstunden-Offsets
und die Ablehnung nicht ganzstündiger Offsetwechsel. 720/721 Intervalle sichern
die Planungsgrenze; 10000 Rohpunkte durchlaufen echte Gateway-Halbierung mit
weiterhin höchstens 2000 Punkten je Abfrage und ohne Aggregatersatz.
Bestehende Rohdatentests sichern außerdem die 63-Anfragen-Grenze.

Beide begrenzten Ausgaben, unabhängiges Design, native Date-Felder und reine
SVG-Entwürfe in beiden Ausrichtungen werden geprüft. Die zentrale Regression
`php tests/archive_absolute_windows.php` prüft zusätzlich `AbsoluteIntervals`;
`archive_windows.php` und `theme_river_data.php` sichern bestehende Consumer
der nun gemeinsam enumerierten Intervalle. Die gesamte Qualitätssuite läuft
mit `php tests/quality.php --fix`.

Diese Tests ändern keine Live-Instanz oder Archivierung. Die anschließende
gemeinsame Live-Abnahme von Datumswahl und Ausgaben ist inzwischen erfolgt.

## Parallel: feste historische Zeiträume

`php tests/gateway_gauges.php --parallel-absolute` prüft den echten Modul-/Gateway-
Pfad mit Kernel-Doppeln. Der erste Lauf war RED, weil Parallel `absolute` noch
nicht akzeptierte. Stunden und lokale Kalendertage verwenden die zentrale Planung
aus Boxplot: inklusive/exklusive Grenzen, Auf-/Abrundung, leere Fenster ohne
Abfragen, abgeschlossene Intervalle bis jetzt und zeitstabile historische Daten.
Standardmittel und Zählerverbrauch bleiben per `auto`-Reducer getrennt;
ausschließlich identische Zeitstempel aller Achsen bilden Linien. Nullwerte,
negative Standardwerte und unterschiedliche Einheiten bleiben erhalten.

Die Regression enthält 23-/25-Stunden-Tage, tatsächliche Herbststunden, einen
Viertelstunden-Zonenoffset, kontrolliert abgewiesene nicht ganzstündige Wechsel,
fehlende/ungültige/mehrdeutige Dates, unveränderte Altmodellfelder, unabhängige
Designs und zustandsneutrale native Formular-/SVG-Entwürfe. Zehn Achsen mit
je 1001 vorhandenen Aggregaten liefern maximal 10×1000 geladene Werte und den
bisherigen Kürzungshinweis; 11 Achsen beziehungsweise Budgets 0/1001 scheitern.

Lokale Modul-/Rendererprüfungen laufen weiterhin je Änderung. Diese Tests
allein sind keine Live-Abnahme; der anschließend gemeinsam geprüfte Stand
des Archivzeiträume-Blocks ist oben gesondert dokumentiert.

## Boxplot: kompakte Wertachsenbeschriftung

### Optionales IQR-Modell

`php tests/boxplot_statistics.php` prüft beobachtete 1,5-IQR-Whisker,
R7-Erhalt, Fence-Gleichheit, Null-IQR, leere/einzelne/negative und extreme
endliche Stichproben. `php tests/gateway_gauges.php --boxplot-iqr` prüft mit
echtem Gateway und Kernel-Doppeln unveränderte Min/Max-Altdaten, gemeinsame
Modellauswahl bei unabhängigen Designs, veröffentlichte HTML-Variable,
neutral bleibende Entwürfe ohne Archivzugriff und beide Ausgabe-/Formularbudgets
mit 32 Quellen, 9984 Stundenmitteln und 4864 einzeln erhaltenen Ausreißern.

`node tests/boxplot_layout.js` verwendet die echte ausgelieferte Boxplot-/Scatter-
Runtime für horizontale und vertikale Punkte, doppelte Werte mit separaten
Zeitstempeln, Offscreen-Extrema, IDs nach DataZoom, Refresh/Resize und Entfernung
der Scatter-Serie bei Rückkehr zum Min/Max-Modell. Lokale Canvas-Beispiele:
`node tests/boxplot_browser.mjs`, anschließend `/chart?mode=symcon&whiskers=iqr`
und `/chart?mode=ipsview&whiskers=iqr`. Echte native Kachel und tatsächlicher
IPSView-Client einschließlich Mausradbedienung bleiben nach Update zu prüfen.
Die Fixture bindet denselben zentralen Interaktions-Helper wie die echten
Ausgaben ein und stellt Wheel-Empfang und Kategorienfenster als DOM-Datenattribute
bereit. Der Engine-Test reproduziert den Wheel-Stillstand mit `zoomLock=true`
und prüft die Korrektur mit unveränderlicher Wertespanne.

### Werteachsenformatierung

`node tests/boxplot_layout.js` reproduziert mit synthetischen gebrochenen
Extremwerten die langen Nachkommastellen der internen 5-%-Randgrenzen. Der
ursprüngliche Renderer scheitert an der erwarteten Beschriftung `1.4` statt
`1.4424994468192827`. Die ausgelieferte Runtime prüft formatierte Achsenlabels
für Kachel/IPSView, beide Ausrichtungen, 350/900 Pixel und `de-DE`/`en-US`.
Die volle gepinnte SVG-Runtime prüft zusätzlich tatsächlich gezeichnete Labels.
Exakte vollständige Skala, unveränderte Statistiken, Scrolling, unterschiedliche
Quellpräzision, enge Stundenmittelbereiche und negative Null bleiben abgesichert.
Extrem kleine Zahlen verwenden wissenschaftliche Notation statt Nullwerte.

Die lokale synthetische Canvas-Vorschau startet mit
`node tests/boxplot_browser.mjs`; `/chart?mode=symcon&fractional=1` beziehungsweise
`/chart?mode=ipsview&fractional=1` zeigen die korrigierten Grenzen. Es werden keine
Installation oder Archive angesprochen. Die Live-Abnahme nach Modulupdate in
Kachel und in der tatsächlichen IPSView-App bleibt davon getrennt.

## Boxplot: feste historische Zeiträume

`php tests/archive_absolute_windows.php` prüft die neuen ausdrücklich opt-in
verwendeten Planungsmethoden ohne Symcon-I/O. Native SelectDateTime-Objekte
erfordern sechs Integer-Felder und gültige eindeutige lokale Zeitpunkte.
Ungültige Daten, DST-Lücken und doppeldeutige Herbstgrenzen werden abgewiesen.
Start eingeschlossen/Ende ausgeschlossen, vollständig enthaltene Stunden/Tage,
leere Fenster, Fortschreiten der Uhr, Zukunft, UTC, nicht ganzstündige
Zonenoffsets und 23-/25-Stunden-Tage sind abgedeckt. Das erste RED war die noch
fehlende Methode; `archive_windows.php` erhält die bisherigen Planungsverträge.
Ein zusätzlicher RED-Fall für Lord Howe verhinderte eine fälschliche 02:30-
Stundengrenze. Betroffene Stundenfenster mit nicht ganzstündigem Offsetwechsel
werden jetzt ausdrücklich abgewiesen; übliche DST-Wechsel bleiben unterstützt.

`php tests/gateway_gauges.php --boxplot-absolute` verwendet den echten Gateway
mit Kernel-Doppeln. 08:30–13:30 liefert genau die Stunden ab 09/10/11/12 Uhr;
angefragte und effektive Grenzen werden getrennt geprüft. R7-Statistik,
Nullwerte, Lücken, leere Kategorien, unveränderte Alt-Properties und unabhängige
Ausgabedesigns bleiben erhalten. Leere Fenster lesen keine Archive. Ungültige
aktive Dates scheitern vor Abfragen; inaktive Daten werden ignoriert.
Quellenlimit 32/33, 1000 pro Abfrage, gemeinsames 10000-Budget, Trunkierung und
Wiederherstellung sind separat geprüft. Die Quelldaten der AC-Doppel liegen
wie in Symcon absteigend vor; ein zunächst falsch sortiertes Fixture wurde
berichtigt, ohne die Produktions-Validierung zu lockern.

Formularsichtbarkeit und vollständige Entwurfs-Callbacks enthalten beide
Datumsfelder. Gültige Vorschauen zeigen Entwurfsgrenzen mit Offset/Zeitzone und
gekennzeichneten Beispielen; ungültige Entwürfe melden das Problem ohne Boxen.
Properties, Status und Archivzugriffe bleiben unverändert. Die JavaScript-
Geometrie bleibt unverändert und wird mit `node tests/boxplot_layout.js` geprüft.
Eine neue reale Formular-/Kachel-/IPSView-Prüfung nach Modulupdate bleibt offen.

## Radar: 16 Gruppen mit optionaler Gruppenauswahl

`php tests/radar_model.php` erhält Originalwerte, Variablen-IDs, Achsenreihenfolge
und explizite Bereiche bei 16 Gruppen. Die erste neue Probe scheiterte gezielt
an der bisherigen Acht-Gruppen-Grenze, bevor die Modellgrenze erhöht wurde.

`php tests/gateway_gauges.php --radar-many` prüft isoliert 16×10 Zuordnungen und
17×3 abgewiesene Gruppen, 3–10 Achsen, vollständige Reads/Referenzen/Abonnements
und versteckte Updates/Fehler. Initialsichtbarkeit ist ein optionaler globaler
Gruppenvertrag, kein Farb- oder Designerfeld. Alt-Konfigurationen und unabhängige
IPSView-Designs bleiben erhalten. Vorschauen lesen alle tatsächlichen Werte,
filtern danach und zeichnen höchstens acht anfangs sichtbare Gruppen mit
korrekten Zahlen und vollständiger Palette/Clippingwarnung. Sie schreiben weder
Properties noch Status und kontaktieren weder Gateway noch Archiv.

Die generierten List-Callbacks werden mit sparsamen `ArrayIterator`-Listen
tatsächlich ausgeführt; sämtliche Listeneinträge sowie false, Nullwert und
unabhängige Designparameter müssen im Entwurf ankommen. Das UTF-8-Maximalformular
überschritt zuerst mit 1094274 Bytes den Symcon-Puffer. Die gemeinsame
Callback-Kompaktierung beseitigt die Wiederholung skalarer Feldnamen, nicht
deren Übertragung. Radar aktiviert die Kompaktierung ausdrücklich, während
andere Familien und insbesondere der spezialisierte TreeSystemHealth-Regeladapter
ihre bisherigen Callback-Verträge behalten. Keine synchronisierte Helperdatei
wurde verändert.
Modellbudget 200000 Bytes und finales 1-MiB-HTML-Limit haben getrennte
Fehler-/Wiederherstellungsfälle ohne Quellenkürzung oder Propertyänderung.

`node tests/radar_layout.js` verwendet die gepinnte echte Engine für beide
Ausgaben, 10 Achsen, Alle/Umkehren, leere Auswahl und Wiederherstellung,
stabile Auswahl/Farben/Legendenanker, Initialänderungen und Resize/Themewechsel.
Tooltips und ARIA behalten Originalwerte/Einheiten und zeigen keine technischen
Gruppen-IDs. Mehrzeilige Warnungen überdecken die Auswahl nicht.

`node tests/radar_browser.mjs` bietet auf Port 8797 synthetische Canvas-Beispiele;
`/chart?mode=ipsview&axes=10&font=150` prüft die Maximalachsen mit größerer Schrift,
`legacy=1` erhält das ursprüngliche Beispiel. Lokale Browsernachweise ersetzen
keine neue Live-Abnahme in Symcon-Kachel oder IPSView nach Modulupdate.

## Funnel: 32 Quellen, Auswahl und Stufenseiten

`php tests/gateway_gauges.php --funnel-many` prüft isoliert den echten Modul-/
Gateway-Pfad mit synthetischen Kernel-Daten: 1/32/33 Quellen, Altlisten ohne
Property-Umschreiben, gleiche Namen bei getrennten IDs, Einheiten/Genauigkeit,
Anfangssichtbarkeit und unabhängige Designer. Versteckte Quellen werden weiterhin
gelesen, referenziert und abonniert; ihre Updates und Fehler bleiben sichtbar.
JSON-Budget von 200000 Bytes und tatsächliches finales 1-MiB-HTML-Budget haben
getrennte Fehler-/Wiederherstellungsfälle. Der gemeinsame Resolver bleibt für
andere Familien ohne explizite Freigabe beim bisherigen Quellenbudget.

SVG-Drafts lesen keine Archive oder Gateway-Daten und ändern weder Status noch
Properties. Filterung und Sortierung liegen vor dem höchstens achtteiligen
Vorschauausschnitt; ein verborgenes Maximum bleibt die Bezugsgröße. Getestet
sind ein spät allein sichtbarer Wert mit tatsächlicher 10-%-Breite, Original-
Palette, Null-/Leerauswahl, vollständige Mengenhinweise und gültiges XML.

`node tests/funnel_layout.js` misst echte Polygonbreiten im gepinnten ECharts
für beide Ausgaben und Ausrichtungen und sichert die vollständige Skala auf
allen Seiten ab. Legacy-Geometrie, Sortierung, Farben, ID-Anker, Schriftgröße,
Resize, Entfernen, Nullseiten und Fehlerwiederholung sind abgedeckt. Ein
zusätzlicher Fall setzt die DOM-Maße versteckter Diagramme auf null und sichert
die sofortige Wiederherstellung nach leerer Auswahl ohne ResizeObserver ab.
Die bisherige PHP-/ECharts-Polygonparität bleibt erhalten. Der anfängliche
Frontendtest scheiterte gezielt an der fehlenden Auswahl-/Seitennavigation.

`node tests/funnel_browser.mjs` stellt synthetische Canvas-Beispiele auf Port
8799 bereit: Kachel/IPSView, horizontal/vertikal, Quellenaktionen und Seiten,
`/?narrow=1&font=150` für schmale Ausgaben sowie die vorhandenen Stilvarianten.
Am 20.10.2026 wurden lokal im Canvas-Browser Alle/Umkehren, leere Auswahl und
sofortige Wiederherstellung sowie die letzte Seite 24–32/32 visuell bestätigt.
Die horizontale Ausgabe mit 150-%-Schrift zeigt mehrere lesbare Stufen und
behält beim Einzelzuschalten des Maximalwerts ihre Skala und ihren ID-Anker.
Bei 240 px Breite bleibt der Ladefehlerhinweis über der zweizeiligen Bedienung;
Aktualisieren entfernt den Hinweis und erhält die gewählte Ansicht.
Die neue Funktion erfordert nach Modulupdate noch die Live-Abnahme in Symcon
und der IPSView-App; lokale Tests ersetzen diese nicht.

## Gauge-Runtime: konfigurierte Titel in beiden Ausgabewegen

`node tests/gauge_runtime_titles.js` prüft das ausgelieferte Bundle unverändert
mit seinem nativen CanvasRenderer. Nur die Browser-Zeichenfläche wird ersetzt;
der Test registriert keine zusätzlichen ECharts-Komponenten. Tatsächliche
`fillText`-Aufrufe und Display-Elemente müssen die äußeren Titel von GaugeMulti
(alle vier Vorlagen), Tacho und Chronograph für Kachel und IPSView enthalten.
Schriftgröße/Farbe, Kachelkopfabstand mit/ohne sichtbaren Kopf, Entfernen und
Wiederherstellen eines Titels sowie unveränderte individuelle Skalen und Werte
sind abgedeckt. GaugeSingles instrumenteigener Titel bleibt ebenfalls sichtbar.
Der Test vergleicht zusätzlich den gepinnten Neubau bytegleich mit dem Bundle
und ist in `tests/run.php` und damit in CI und der Gesamtprüfung eingebunden.

Der anfängliche Regressionstest scheiterte gezielt am fehlenden TitleComponent
im ausgelieferten Build. Die Korrektur ergänzt ausschließlich diesen offiziellen
Baustein im Gauge-Einstieg von ECharts 6.1.0, ohne Änderung des Fremdkerns oder
anderer Bundles. Integrität, Lizenzen und HTML-Budgets bleiben separat geprüft.
Live-Abnahme nach Modulupdate in Symcon und IPSView bleibt erforderlich.
Am 20.10.2026 zeigte die lokale Canvas-Browserprüfung mit synthetischen Daten
die GaugeMulti-Diagrammtitel in beiden Ausgabewegen sichtbar nebeneinander;
der Kacheltitel und Vergrößerungsbutton blieben oberhalb der Diagrammüberschrift.

## GaugeMulti: 32 Quellen, Auswahl und Instrumentenseiten

`php tests/gateway_gauges.php --gauge-multi-many` prüft den realen Modul-/Gateway-
Pfad mit synthetischen Kernelgrenzen: 32/33 Quellen, Minimum zwei, individuelle
signed Skalen/Einheiten/Präzision, Darstellungsübernahme, gleiche sichtbare Namen,
Referenzen/Abonnements und vollständige Aktualisierung auch versteckter Quellen.
Altlisten und Designproperties bleiben nach wiederholtem Apply unverändert.
Alle vier Vorlagen und unabhängige IPSView-Designs werden abgeglichen.
Der anfängliche Test schlug ausdrücklich an der alten 16er-Grenze fehl.

Vorschautests prüfen einen spät allein sichtbaren 32. Eintrag mit seiner eigenen
Skala und tatsächlicher Halbkreisgeometrie. Farben folgen derselben vollständigen
16er-Palette wie im Renderer. Anfangsauswahl wird vor dem Vierer-Sample angewandt;
der zunächst rote Zählertest verhindert die falsche Angabe 4/32 bei nur einem
gezeichneten Instrument. Leerzustand, ignorierte Anfangsvorgaben bei deaktivierter
Auswahl und schreib-/gateway-/archivfreie Drafts sind abgedeckt. Eine gültige
230-KiB-SVG-Platte bleibt nutzbar. 900000 Modellbytes und finale 1-MiB-HTML-Grenze
werden getrennt mit begrenzten Fehlerausgaben geprüft. Diese Fälle laufen isoliert
über `tests/run.php`, ohne den großen Monolith-Speicher weiter zu erhöhen.

`node tests/gauge_multi_layout.js` prüft Altgeometrie, Auswahl/Seiten für alle vier
Vorlagen und beide Ausgaben, sämtliche 32 erreichbaren Quellen, individuelle
Originalskalen und Werte, leere/einzelne Auswahl sowie Reorder/Resize/Theme/Removal.
Native Fälle verwenden die ausgelieferte Gauge-Runtime; nur der Headless-
Testadapter ergänzt einen SVGRenderer. Custom-Zeiger und SVG-Platten bleiben
geprüft. `bar_polar_layout.js` und `--polar-many` sichern die Extraktion der
gemeinsamen DOM-/CSS-Bedienung ohne Veränderung des Polar-Modells ab.

`node tests/gauge_multi_browser.mjs` bietet synthetische Canvas-Daten auf Port
8808; die Galerie kombiniert vier Vorlagen und beide Ausgabewege.
`/?narrow=1` zeigt 240-px-Frames und einen Ladefehlerknopf. Am 20.10.2026 wurden
Raster, Ringraster, konzentrische Ringe mit 150 % und Wetterstation visuell
geprüft: Alle/Umkehren, leere Auswahl und Wiederherstellung sowie Seite 17–32
mit Originaleinheiten. Die schmale Warnüberlagerung wurde vor Korrektur
reproduziert und durch einen gemeinsamen 92-px-Abstand über der zweizeiligen
Bedienleiste behoben. Der erste vollständige Lauf fand zudem einen veralteten
Formularindex im alten SVG-Test; die Prüfung adressiert nun den stabilen Feldnamen.
Live-Abnahme nach Modulupdate bleibt separat, keine Live-Konfiguration geändert.

Beim Quellen-/Seitenausbau wurde der fehlende TitleComponent als separater
Bestandsbefund bestätigt. Die anschließende gesonderte Korrektur und ihre
Regressionstests sind im vorhergehenden Abschnitt dokumentiert.

## Pie: 100 Quellen, Auswahl und vollständige Anteilsgrundlagen

`php tests/pie_data.php` prüft reine Prozentrechnung einschließlich echter
Nullen, 100/101 Quellen, ungültiger Werte, subnormaler und sehr großer Gewichte.
`php tests/gateway_gauges.php --pie-many` prüft den tatsächlichen Gateway mit
synthetischen Kernel-Variablen: alle 100 Lesezugriffe, Referenzen und VM-Abonnements,
Altlisten ohne Umschreiben, Quellenauswahl und Anfangsvorgaben, versteckte
Updates/Fehler, gleiche Namen, Einheiten/Normalisierung und Originalmetadaten.
Beide vollständigen Ausgabe-Modelle, realistische UTF-8-Metadaten sowie
Modell- und tatsächlicher finaler HTML-Guard werden separat abgesichert.

Die Vorschau zeichnet höchstens sechs Quellen, doch Nenner und geometrische
Bezugsgrößen stammen aus der vollständigen Anfangsauswahl. Bei 100 gleichen
Gewichten sind das je Quelle 1 %; mit zehn anfangs sichtbaren Quellen 10 %
Auswahlanteil und 1 % Gesamtanteil. Auch nur die letzte ausgewählte Quelle,
keine Auswahl und ignorierte Anfangsvorgaben im ausgeschalteten Modus werden
geprüft. Entwürfe lesen keine Gatewaywerte und verändern keine Properties.

`node tests/pie_layout.js` nutzt zusätzlich die ausgelieferte Pie-Runtime:
beide Ausgabe-/Resize-Wege, 100 Sektoren, Sortierung, doppelte Namen, originale
Tooltipwerte, Auswahl-/Gesamtanteile, Legendenanker, Aktualisierung, Entfernen,
Themewechsel, Alle/Umkehren und Wiederherstellung nach leerer Auswahl/Nullen.
Große Summen sowie winzige sichtbare Werte neben riesigen versteckten Werten
werden mit endlicher nativer Sektorgeometrie geprüft. Eine Regression verhindert
den grauen ECharts-Platzhalterkreis bei leerer Auswahl.

`node tests/pie_browser.mjs` startet eine lokale synthetische Canvas-Fixture
auf Port 8806 mit beiden Ausgabewegen und echter PHP-Vorschau. Am 19.10.2026
wurden beide Donut-Ausgabewege mit 100 Quellen, zehn ausgewählten Quellen,
Auswahl-/Gesamttooltip, Alle/Umkehren, leerer Auswahl und Wiederherstellung
über die nativen Auswahlknöpfe geprüft. Die PHP-Vorschau behält 10 % je
gezeichneter Quelle trotz Ausschnitt. Eine breite normalisierte Nightingale-
Ansicht mit 200 % Schrift bestätigt die Trennung von Außenbeschriftung und
Legendenspalte einschließlich vollständig sichtbarer Auswahlknöpfe.
Die Live-Abnahme
nach Modulupdate in Symcon/IPSView bleibt erforderlich; Live-Konfigurationen
und Werte werden bei dieser Erweiterung nicht verändert.

## Waterfall: 100 Quellen und vollständige Bilanz

`php tests/waterfall_data.php` sichert die gemeinsame geordnete Addition für
Modell und Vorschau: Vorzeichenwechsel, negative Starts, echte Nullen, 100/101
Werte und nichtendliche Einzel-/kumulierte Werte.
`php tests/gateway_gauges.php --waterfall-many` verwendet den tatsächlichen
Gateway mit synthetischen Kernel-Variablen: 100 aktuelle Lesezugriffe, 100
Referenzen/VM-Abonnements und 101 vollständige Schritte samt berechneter Endsumme.
Geprüft sind Alt-Konfigurationen ohne Umschreiben, gleiche Namen, Einheiten,
späte Updates, Entfernen von Quellen, Fehler und Wiederanlauf. Die Vorschau
zeigt 5/100, Auslassungszeichen, vollständige Endsumme und Extrema inklusive
eines ausgelassenen Ausschlags sowie Präzision einer späten Quelle. Entwürfe
lesen keine Gatewaywerte und verändern keine Properties.

Beide Ausgaben werden verlustfrei mit dem vollständigen Modell verglichen.
UTF-8-/HTML-Metadaten, Modellgrenze 200000 Bytes und tatsächlicher finaler
1-MiB-HTML-Guard sind getestet; zu große gültige Metadaten erzeugen ein klares
Fehlerdokument statt verkürzter Summen. Die neue fokussierte Suite läuft separat,
damit die große gemeinsame Gateway-Fixture ihr Speicherlimit beibehält.

`node tests/bar_waterfall_layout.js` nutzt die gepinnte native Cartesian-Runtime:
beide Ausgabearten, 101 Kategorien, Offscreen-Extrema, letzte Endsumme/ID-Tooltips,
Zero-Crossings, responsive Dichte, Schrift-/Balkenbreiten, Reorder/Removal/Empty,
Themewechsel und beide Resize-Wege. `node tests/bar_category_layout.js` prüft
die gemeinsame ID-Anker-/Fensterlogik ohne Änderungen der Kategoriengeometrie.
Das generierte Category-App-Artefakt wird mit dem bestehenden dokumentierten
Minify-Befehl reproduziert. `--current-normalization` prüft zusätzlich die
knappe Ausgabegrenze großer Category-Modelle samt SVG-Muster.

Die lokale synthetische Browser-Fixture `node tests/bar_waterfall_browser.mjs`
nutzt Port 8805, zeigt beide Ausgabewege und die echte PHP-Vorschau. Am
19.10.2026 wurden beide Ausgabewege mit 100 Quellen, Navigation bis zur
vollständigen Endsumme, erhaltener Skala und erhaltener Position nach erneuter
Zustandsübertragung visuell geprüft. Die PHP-Vorschau zeigt dieselbe Endsumme
bei ausdrücklich gekürzter Darstellung. Ein zusätzlicher Regressionstest
sichert den Abstand negativer Wertbeschriftungen zu den Kategoriennamen ab.
Die erneute Live-Abnahme nach Modulupdate in Symcon/IPSView bleibt beim Eigentümer;
Live-Konfigurationen und Variablenwerte werden nicht verändert.

## ThemeRiver: Zählerverbrauch als Datengrundlage

`php tests/gateway_gauges.php --theme-river-consumption` prüft den wirklichen
Gateway-Pfad mit 16 synthetischen Zählerarchiven: `Metric=consumption` fordert
`sum` an und erhält die originalen `Avg`-Verbrauchssummen. Absichtlich abweichende
Roh-/Momentanwerte und ein Rohwert-Reset fließen nicht in die Rechnung ein.
Bis 16×720 Intervalle, gleiche Namen, versteckte Quellen, Nullwerte und strenge
gemeinsame Lücken bleiben erhalten. Fehler bei Archivtyp, Reducer, negativen
Werten, gekürzten/übergroßen Antworten sowie Wiederanlauf sind abgedeckt.
Der alte `average`-Default verändert keine Quellen oder Designs.

Die Tests vergleichen beide Ausgaben verlustfrei und prüfen den abgeschlossenen
25-Stunden-DST-Tag, den Ausschluss des laufenden Tages und ein leeres Custom-Fenster
ohne Archivzugriff. Ungespeicherte Metric-Entwürfe aktualisieren beide
gekennzeichneten SVG-Vorschauen ohne Archivabfrage oder Property-Schreibzugriff.
`tests/theme_river_data.php` prüft zusätzlich die zentrale Metric-Zuordnung
einschließlich ungültiger Werte. Native Tests in `tests/theme_river_layout.js`
prüfen beide Ausgabearten, Verbrauchs-Aria/Nullhinweise, Auswahl und originale
kWh-Tooltips samt Dezimalstellen; Modelle ohne Metric behalten die alte Semantik.
Die lokale Browser-Fixture unterstützt `?metric=consumption`. Am 19.10.2026
wurden beide Canvas-Ausgaben mit 16 Quellen/720 Intervallen visuell geprüft:
Verbrauchstitel/Aria, originale kWh-Tooltips, Auswahl mit erhaltenem ID-Anker
nach Umsortierung, vollständiges Ausblenden/Wiederherstellen und die gekennzeichnete
PHP-Vorschau. Es traten keine Überlappungen mit Kachelkopf oder Fußbereich auf.
Live-Konfigurationen/Archive bleiben unverändert; die Abnahme nach Modulupdate
in Symcon und IPSView bleibt dem Eigentümer vorbehalten.

## ThemeRiver: 16 Quellen und optionale Bandauswahl

`php tests/theme_river_data.php` prüft 16/17 Reihen und den zentralen
Ladezähler exakt bei 16000/16001 Aggregaten und 1000/1001 je Quelle.
`php tests/gateway_gauges.php --theme-river-many` nutzt echte Gateway-Operationen
mit synthetischen Kernel-Archiven: 16×720 Originalwerte, unveränderte 8er-Listen,
deaktivierter Default, boolesche Anfangssichtbarkeit und IDs trotz gleicher
Namen. Versteckte Quellen bleiben in Referenzen, Abfragen, Ladezähler und
vollständigem gemeinsamen Join. Eine Lücke der versteckten Quelle bleibt
`null`; echte Nullwerte werden nicht als fehlend behandelt. Negative Werte,
Zählerarchive im Mittelwertmodus und übergroße Antworten werden ohne Teilmodell abgewiesen.

Die Prüfung entpackt beide großen Startzustände und vergleicht alle Quellen,
Zeilen und Originalwerte exakt. Gemessen am 19.10.2026 einschließlich Metric-Feld:
Modell 352339 Bytes, Kachel 608584 Bytes und IPSView 611871 Bytes. Getrennte Regressionen prüfen
900000-Byte-Modellgrenze und finalen HTML-Guard, klare Fehler und Wiederanlauf.
Das Test-Speicherlimit bleibt 128 MiB. Vorschauen decken 4/16, 0/16, spät
sichtbare Originalfarben und deaktivierte Auswahl mit 8/16 ab; Entwürfe lösen
keine Archivabfragen oder gespeicherten Konfigurationsänderungen aus.

`node tests/theme_river_layout.js` verwendet die echte gepinnte ECharts-Runtime
für beide Ausgabearten mit 16 Quellen und 720 Intervallen. Geprüft werden
Einzel-/Alle-/Umkehrauswahl, vollständiges Ausblenden/Wiederherstellen,
Legendenanker, Update/Reorder/Removal/Resize, gleiche Namen und Originaltooltips.
Die ursprünglichen Lücken bleiben bei jeder Auswahl erhalten, einschließlich
360 getrennter Abschnitte und gemeinsamem ausgewähltem Darstellungsmaßstab.
Nullarchive, ausgewählte Nullbänder und keine Auswahl sind unterscheidbar.
Verzögerte komprimierte Starts, neuere Vollzustände und Unload sind abgesichert.

`node tests/theme_river_browser.mjs` bietet eine lokale synthetische
Canvas-Fixture auf Port 8802. Am 19.10.2026 wurden beide Ausgabearten mit
16 Quellen/720 Intervallen visuell geprüft: native Legende, Auswahl, Refresh
und Umsortierung sowie vollständiges Ausblenden und Wiederherstellen.
Die neue Live-Abnahme in Symcon/IPSView nach Modulupdate bleibt offen;
Live-Konfigurationen und Archive wurden nicht verändert.

## BarHistory: 32 Reihen mit gemeinsamer Reihenauswahl

`php tests/gateway_gauges.php --bar-history-many` prüft echte Gateway-
Operationen mit synthetischen Kernel-Archiven: 32/33 Quellen, 200/32000/32001
Budget, Default 1000 und bestehende 16er-Listen ohne Konfigurationsänderung,
16/17 Einheitengruppen, versteckte Quellen mit unverändertem Budgetanteil,
Rohzeitstempel/Lücken, Aggregationsmodi und Reducer. Beide komprimierten
HTML-Startzustände werden exakt entpackt und mit dem vollständigen Modell
verglichen. Gleichnamige Quellen bleiben getrennt. Große gültige SVGs,
900000-Byte-Modellfehler und finaler HTML-Guard werden separat geprüft.
Die Vorschauprüfungen umfassen 0/32 und spät sichtbare Quellen mit erhaltenen
Farben, kompakten sichtbaren Achsen und gekennzeichnetem Ausschnitt.

Messung am 19.10.2026: Modell mit 32000 Punkten 588488 Bytes, Kachel 891877
Bytes, IPSView 895164 Bytes. Ein fast 256 KiB großes SVG mit 32 langen Namen
und kleinerem Punktbudget ergibt 714789/718076 Bytes HTML und 321397 Bytes
Formular. Ein großes Muster plus maximales Punktbudget kann das Modellbudget
überschreiten; dies ist ausdrücklich kein versprochenes Kombinationsmaximum.
Das Gesamttest-Speicherlimit bleibt 128 MiB. Abgeschlossene historische
Testobjekte und HTML-/Formular-Snapshots werden vor den nachfolgenden Familien
freigegeben; die Lastprüfung läuft separat mit demselben Limit.

`node tests/bar_history_layout.js` und `node tests/time_series_layout.js`
prüfen den gemeinsam extrahierten Auswahlbaustein mit der echten gepinnten
ECharts-Runtime: 32×1000 Punkte, Auswahl/Alle/Umkehren/keine Reihe,
gleiche Namen und ID-Identität, Legendenanker, Update/Reorder/Removal/Resize,
Zeitzoom, sichtbare Achsen und Balkenbreiten sowie Start-Dekomprimierung mit
neueren Vollzuständen und Unload. Die vollständigen Daten bleiben erhalten.

`node tests/bar_history_browser.mjs` bietet eine synthetische lokale
Canvas-Fixture auf Port 8817. Am 19.10.2026 wurden schmale Kachel und breite
IPSView-Ausgabe mit 32 Reihen/32000 Punkten visuell geprüft. Native
Legendenwahl und Zeitzoom blieben nach Refresh und Umsortierung erhalten.
Die Live-Abnahme dieser Erweiterung nach Modulupdate steht noch aus;
Symcon-Konfigurationen und Archive wurden nicht verändert.

## Größere manuelle Graphen

`php tests/graph_model.php` prüft 300 Knoten/1000 Kanten, N+1, gleiche Namen
mit eigenen IDs und die unveränderten Schutzregeln. `php tests/gateway_gauges.php
--graph-large` prüft über echte Module mit synthetischen Kernelgrenzen wiederholtes
Apply, unveränderte gespeicherte Listen, beide unabhängigen Designer, begrenzte
und sicher maskierte Vorschauausschnitte sowie komplette HTML-Ausgaben unter
1 MiB. Der Stressfall kombiniert 100 HTML-sensible Zeichen je Name und 300 je
formatiertem Wert. `graph_output.php` sichert die HTML-sichere Kompressionsschwelle
ab; die Modulprüfung entpackt beide Dokumente und vergleicht alle Knoten/Kanten
und unveränderten vollständigen Werte. Kleine Zustände bleiben direkt eingebettet.
`zigbee_graph_model.php` sichert die getrennten Z2M-Roh-/Link-/LQI-Budgets ab.

`node tests/graph_layout.js` und `node tests/zigbee_graph_layout.js` prüfen
die Renderer und gemeinsame Ansichtsverwaltung: Kräfte-/Kreislayout,
Kachel-/IPSView-Ausgabe, ID-stabile Aktualisierung, Resize, entfernte Knoten,
Layoutwechsel, sichere Tooltips und dichte Beschriftungen. Die Runtime bleibt
lokal festgeschrieben. `node tests/graph_browser.mjs` stellt synthetische
300/1000-Canvas-Ansichten auf `127.0.0.1:8814` bereit. Die echte Abnahme in
Symcon und IPSView nach Modulupdate ist dadurch nicht ersetzt.

Am 19.10.2026 wurden beide Layouts des manuellen Graphen lokal mit komprimiertem
Startzustand als 400-px-Kachel und 720-px-IPSView-Variante visuell geprüft.
Pan/Zoom blieb nach Werteaktualisierungen erhalten. GraphZ2M wurde mit
synthetischen Rollen und LQI-Farben gegengeprüft. Zunächst rote Regressionen
sichern das 1-MiB-Problem längerer Werte und den zusätzlichen Z2M-Fußbereich ab.
Die tatsächliche ausgelieferte Canvas-Runtime wird zusätzlich mit einem
Zeichenkontext-Doppel geprüft; das volle Entwicklungspaket kann fehlende
Bundle-Komponenten dadurch nicht verdecken. Die vollständigen Originaltexte
bleiben nach Entpacken im sicheren Tooltip.

## Zwölf Nachkommensebenen für Tree und SystemHealth

`php tests/tree_hierarchy.php` prüft zwölf Nachkommensebenen ab Wurzel 0,
gleiche Namen mit eigenen IDs, Filter, zwölf/dreizehn sowie unveränderte
Knoten- und Suchbudgets. `php tests/tree_preview.php` prüft die gemeinsame
SVG-Geometrie: echte Eltern-Kind-Kanten, Anfangstiefe, alle fünf Anordnungen,
80-Knoten-Grenze, sichere Texte und normale/Zustandsfarben. Mit `--svg LR`
beziehungsweise RL/TB/BT/radial liefert dieser Test zusätzlich eine synthetische
tiefe Vorschau zur lokalen Sichtprüfung.

`php tests/gateway_gauges.php --tree-depth` und `--tree-health-depth` verwenden
die realen Modulpfade mit synthetischen Kernelgrenzen. Defaults, Properties und
StatusRules bleiben bei wiederholtem Apply beziehungsweise Vorschau-Drafts
unverändert. Ebene zwölf, Ablehnung von dreizehn, unabhängige Designer,
Namens-/Regelfilter, ausgeblendete und tiefere Quellen sowie Statussuche werden
geprüft. Fehlende Regelquellen sind absichtlich nicht numerisch: Ein unerlaubtes
Lesen für die Bewertung würde den Test fehlschlagen lassen. Kompressions-
und Ausgabetests sichern 3000 Knoten ab; 200000 Bytes bleiben eine Schwelle
für Startkompression und werden nicht zu einer neuen Ablehnungsgrenze.

Die erweiterte `tree_layout.js` verwendet echte native Tree-Aktionen für beide
Renderer, fünf Anordnungen und Ausgabearten. Aufklappen, Pan/Zoom, Aktualisierung,
Resize, neue Wurzel/Anfangstiefe und entfernte Objekt-IDs werden überprüft.
Die zusätzliche gemeinsame Tree-JavaScriptdatei wird nur in diesen Familien
geladen. Die Live-Abnahme nach Modulupdate in Symcon und IPSView bleibt separat.

`node tests/tree_browser.mjs` bietet auf `127.0.0.1:8812` synthetische Canvas-
Ansichten und SVG-Vorschauen. Am 19.10.2026 wurden Tree horizontal/vertikal
und Health horizontal/radial als 400-px-Kachel und 720-px-IPSView-Variante
visuell geprüft. Aufklappen, Pan/Zoom und Werteaktualisierung behielten ihre
Ansicht; der Health-Bewertungsumfang blieb sichtbar. Dabei entdeckte
Überschneidungen tiefer Radialbeschriftungen mit dem Hinweis und vertikaler
Beschriftungen mit dem Titel sind durch zusätzliche Layoutabstände korrigiert
und mit zunächst roten echten Textgeometrie-Regressionen abgesichert.
Keine Live-Konfiguration oder Quellwerte wurden geändert.

## Flexible Treemap- und Sunburst-Hierarchien

`php tests/treemap_model.php` und `php tests/hierarchy_sources.php` prüfen
das gemeinsame rekursive Modell und die Quellenvalidierung: acht Ebenen
einschließlich Blatt, stabile alte IDs, wörtliche alte Schrägstriche,
explizite Pfad-Escapes, Reihenfolge/Summen, ungültige Pfade und Bytebudgets.
Vorschauwerte sind synthetisch; weder aktuelle Werte noch Archive werden gelesen.

`php tests/gateway_gauges.php --treemap-hierarchy` und
`php tests/gateway_gauges.php --sunburst-hierarchy` prüfen die jeweiligen
Modul-/Gatewaypfade, unveränderte gespeicherte Altquellen, beide unabhängigen
Designer sowie vollständige SVG-Vorschauen. Stressfälle mit 100 Quellen,
800 Knoten und langen Namen bleiben innerhalb der bestehenden Ausgabepuffer.
Die beiden Layoutsuiten verwenden die festgeschriebene echte ECharts-Runtime:
tiefe Navigation, Rückweg, Aktualisierung/Resize und entfernte Gruppen.
Treemap hält bei tiefer Hierarchie den Navigationspfad auch kompakt sichtbar.

`node tests/hierarchy_browser.mjs` stellt auf `127.0.0.1:8804` rein synthetische
Vergleiche bereit; `/?family=treemap|sunburst` wählt die Familie. Nach Modulupdate
bleibt die tatsächliche Symcon-/IPSView-Abnahme separat erforderlich.

Am 19.10.2026 wurden beide Familien lokal mit nativer Canvas-Ausgabe als
400-px-Kachel und 650-px-IPSView-Variante visuell geprüft: tiefe Gruppen öffnen,
Werte aktualisieren und zurücknavigieren. Ein dabei entdeckter leerer Treemap-
Ausschnitt nach Wiederherstellung einer tiefen Gruppe ist durch den Aufbau des
Vorfahrenpfads korrigiert und mit einer zunächst roten echten Runtime-Regression
abgesichert. Tiefe Sunburst-Beschriftungen werden anhand der tatsächlichen
Ringgeometrie und Schrift gekürzt statt über benachbarte Ringe zu laufen;
vollständige Namen bleiben im Tooltip. Die echten Installationen wurden nicht geändert.

## Normalisierte aktuelle Vergleiche

`php tests/normalization.php` prüft die reine Projektion auf 0–100 anhand
expliziter Quellbereiche, Begrenzung nur der Geometrie, negative und echte
Nullwerte, große/kleine Float-Bereiche und ungültige nichtendliche oder nicht
darstellbare Grenzen. Der gemeinsame Quellenresolver lässt gemischte Einheiten
nur nach ausdrücklicher Aktivierung zu; Funnel und inaktive Altmodelle bleiben
unverändert. Die erste Prüfung war vor Einführung des Vertrags rot.

`php tests/gateway_gauges.php --current-normalization` führt die neuen
Integrationsfälle für Pie, BarPolar, BarCategory und PictorialBar mit echtem
Gateway und synthetischen Kernel-Doppeln aus. Die Gesamtsuite ruft diesen
eigenen Prozess auf, statt alle HTML-Fixtures gleichzeitig im monolithischen
Speicher zu behalten. Originalzahlen, Einheiten/Präzision, Bereiche, beide Ausgaben,
schreibfreie Entwürfe, Aktivierung/Deaktivierung und Konfigurationsfehler
werden geprüft. Die bestehenden Quellen-, SVG- und Ausgabebudgets bleiben aktiv.
Die vier vorhandenen Layoutsuiten prüfen normalisierte Geometrie getrennt
von Originalwertbeschriftungen und HTML-sicheren Tooltips.

BarCategory kombiniert zusätzlich 100 normalisierte Quellen mit langen Namen
und einem fast maximalen SVG-Muster und prüft beide Ausgaben unter 1 MiB.
Der vorhandene modulbezogene esbuild-Schritt minifiziert nun vollständig;
der Layouttest sichert die exakte Reproduzierbarkeit des ausgelieferten Appscripts.
Quellen-/SVG-Limits und das PHP-Speicherlimit werden dafür nicht erhöht.

`node tests/current_normalization_browser.mjs` bietet rein lokale Pie-/Polar-
Ansichten auf `127.0.0.1:8810`. Die bestehenden Browserfixtures von BarCategory
und PictorialBar unterstützen `normalize=yes`; PictorialBar kombiniert dies
mit `color=gradient&shape=svg` oder `shape=icon`. Gemischte °C-/Prozent-/LQI-
Originalwerte bleiben in Tooltip und Beschriftung erhalten.

Am 19.10.2026 wurden normalisierte Pie-/Polar-Ansichten sowie PictorialBar
mit eigenem SVG und Farbverlauf lokal visuell geprüft. BarCategory wurde mit
normalisierten Stapeln und gemischten Einheiten geprüft. Ein dabei sichtbar
abgeschnittener langer Tooltip ist durch Begrenzung auf das Widget und
Textumbruch korrigiert und als zunächst roter Layoutfall abgesichert.
Normalisierte Null-/Kleinstsegmente gruppierter/gestapelter Balken unterdrücken
unlesbare Labels; ihre Originale bleiben im Tooltip. Direkter PictorialBar-
Aufruf blieb ohne Konsolenwarnung/-fehler; Frame-Vergleichsseiten können den
bereits bekannten, nicht dem Modul zugeordneten MutationObserver-Fehler zeigen.

Nach Modulupdate bleiben die neue Formularbedienung und die neuen Vergleiche
in tatsächlichen Symcon-/IPSView-Clients separat abzunehmen. Bei diesen
Entwicklungsprüfungen werden keine Live-Einstellungen oder Variablenwerte geändert.

## BarPolar: 100 Quellen, Auswahl und Seiten

`php tests/polar_data.php` prüft Obergrenze, signed vollständige Skalen,
Gesamt-Null, ungültige Werte und overflow-sichere Vorschauprojektion.
`php tests/gateway_gauges.php --polar-many` läuft isoliert mit realem Gateway
und synthetischen Kernel-Grenzen: 100/101 Quellen, Altlisten, vollständige
Referenzen/Abonnements und Lesezugriffe, versteckte Updates und Fehler,
unabhängige IPSView-Skalen, Originalmetadaten, Normalisierung und echte Nullen.
Der zunächst rote Cap-Test schlug ausdrücklich an der alten Grenze fehl.
Drafts bleiben schreib-/gateway-/archivfrei; SVG-Ausschnitte behalten versteckte
späte Extrema, IDs und vollständige Palettenpositionen. Die JSON- und finalen
HTML-Guards werden separat überschritten und auf begrenzte Fehler geprüft.

`node tests/chart_view_state.js` charakterisiert direkte und native Auswahl
sowie Kategorieanker der beiden geteilten Bausteine. `bar_polar_layout.js`
prüft die ausgelieferte native Polar-Runtime, alle 100 erreichbaren Quellen,
leere Auswahl und Wiederherstellung, beide Modi/Ausgaben, ID-Tooltips/Farben,
Fixierung, Theme/Sort/Refresh/Resize/Entfernung sowie ganze versteckte Extrema.
Die neuen nativen Layoutfälle verwenden einen zusätzlichen SVGRenderer nur als
Headless-Testoberfläche; das ausgelieferte Canvas-Bundle wird nicht verändert.
Alle neuen Tests laufen über `tests/run.php` und den vollständigen Qualitätslauf.

`node tests/bar_polar_browser.mjs` bietet auf `127.0.0.1:8807` ausschließlich
synthetische Canvas-Beispiele. Die Galerie kombiniert beide Modi und Ausgaben;
`/chart?mode=symcon|ipsview&shape=radial|tangential&font=24` öffnet eine Ansicht.
Am 19.10.2026 wurden radiale Kachel-/IPSView-Ansichten sowie konzentrische Bögen
mit 24 px, Quellen-Popup, Alle/Umkehren, leere Auswahl, Wiederherstellung und
Seitenwechsel lokal visuell geprüft. Die letzte radiale Seite erschloss Quelle
100; versteckte Extrema blieben im Maßstab. Keine Live-Konfiguration geändert.
Live-Abnahme nach Modulupdate in Kachel und IPSView bleibt beim Eigentümer.

## Calendar- und Heatmap-Kennzahlen

`calendar_metrics.php` und `heatmap_metrics.php` ergänzen die Gateway-Suite um
die explizite Kennzahlenauswahl: unterschiedliche `Avg`/`Min`/`Max`, Zähler-
`Avg` als Verbrauchssumme statt Zählerstand, gültige Nullen, negative
Standardwerte und abgewiesene negative Verbräuche. Standard-/Zählerrollen,
Reducer, Antwortmetadaten, Trunkierung, Navigation und Fehlererholung werden
über die realen Modul-/Gatewaypfade mit synthetischen Kernel-Doppeln geprüft.
Beide HTML-Ausgaben bleiben begrenzt; Entwürfe und Vorschauen persistieren
nichts und fragen kein Archiv ab.

`calendar_data.php` und `heatmap_aggregation.php` prüfen zusätzlich die
Kennzahlenreduktion. Bei der Heatmap bleiben Mittelwert, Minimum, Maximum
und Verbrauchssumme auch über mehrere gleiche Wochentag-Stunden erhalten;
beide realen Herbststunden zählen, fehlende Stunden werden nicht erzeugt.
`calendar_layout.js` und `heatmap_layout.js` prüfen Kennzahlentexte, Tooltips,
ARIA, alte Modelle ohne Kennzahl sowie verspätete Navigationsantworten nach
Kennzahlenwechsel. Die Runtimes und der Gatewayvertrag bleiben unverändert.

Die lokalen Browserfixtures `calendar_browser.mjs` (Port 8796) und
`heatmap_browser.mjs` (Port 8802) verwenden ausschließlich synthetische Daten.
`/chart?mode=symcon|ipsview&metric=average|minimum|maximum|consumption` wählt
Ausgabe und Kennzahl; Navigation behält die Kennzahl bei. Die neuen
Formularfelder und Kennzahlen benötigen nach Modulupdate noch eine echte
Symcon-/IPSView-Abnahme; Live-Einstellungen werden nicht geändert.

Am 19.10.2026 wurden Calendar-Tagesminima und -verbrauch sowie Heatmap-
Stundenmaxima und -verbrauch lokal im Canvas-Renderer visuell geprüft.
Jahres- und Zeitraumwechsel bewahrten die Kennzahl; die Heatmap wurde auch
als schmale Kachel neben breitem IPSView geprüft. Direkte Rendereraufrufe
blieben ohne Konsolenwarnung/-fehler. Die Heatmap-Vergleichsseite mit Frames
zeigte einmal einen nicht dem Modul zugeordneten MutationObserver-Fehler;
beide eingebetteten Diagramme und ihre Navigation funktionierten dennoch.

## Benutzerdefinierte Archivzeiträume

`php tests/archive_windows.php` prüft die neue vollständige Intervallplanung:
90 Minuten, leere Minutenfenster, lokale Tage, Sommer-/Winterzeit, eindeutige
Herbststunden, unveränderte Tagesvorgaben sowie 720/721 und sehr große Dauern.
Der Test läuft auch über `tests/run.php` und den vollständigen Qualitätslauf.

`parallel_custom_ranges.php` und `boxplot_custom_ranges.php` laufen über die
Gateway-Suite; `archive_custom_interval_integration.php` über Candlestick und
die getrennte ThemeRiver-Integration. StateTimeline erweitert seine vorhandenen
Daten-/Integrationstests. Geprüft werden echte Modul-/Gatewaypfade mit
Kernel-Doppeln, gemeinsame Zeitbereiche beider HTML-Ausgaben, abgeschlossene
Aggregate bzw. rohe Zustandsvorgänger, Cacheidentität, Grenzwerte, unveränderte
Budgets und Statistiken. Formulare prüfen Custom-Auswahl, Grenzen/Einheiten,
anfängliche und unmittelbare Sichtbarkeit sowie archiv- und schreibfreie Drafts.
Die erste neue Prüfung jedes Familienpfads schlug vor der Implementierung
wegen fehlender Custom-Unterstützung fehl.

Die ECharts-Runtimes und Renderer bleiben unverändert; vorhandene Layouttests
werden erneut ausgeführt. Ein realer Symcon-Formulartest und die Live-Abnahme
der neuen Zeitbereiche in Kachel/IPSView nach Modulupdate bleiben offen.

## Breitenabhängige Kategorienabstände in BarCategory

`bar_category_layout.js` führt das ausgelieferte `app.min.js` aus und vergleicht
es mit der reproduzierbaren esbuild-Transformation von `app.js`. Beide
Ausrichtungen und Ausgaben sowie einfache, gruppierte und gestapelte Balken
werden bei 20/100 Prozent Breite verglichen. Native ECharts-Geometrie belegt
engere Kategoriezentren und mehr sichtbare Kategorien (auch mehr als 16),
Font-Mindestabstände, Navigation bei kleinen Modellen, Resize/Update mit
Quell-ID-Anker und Erreichbarkeit des letzten Eintrags. Die erste neue
Dichte-Assertion scheiterte vor der Änderung an der unveränderten Fenstergröße.

`bar_category_large_sources.php` prüft dichteabhängige SVG-Ausschnitte,
sichtbare Kategorienabstände, Schriftgrenzen, unabhängige IPSView-Breiten und
unveränderte gespeicherte Entwürfe. Alle SVGs bleiben parsbar; Anzahlhinweise
stimmen mit dem Ausschnitt überein. Der bestehende Stressfall mit 100 langen
Quellbezeichnungen, Themes und fast 256 KiB SVG bleibt unter 1 MiB je Ausgabe.
Der gemeinsame Vorschautest erzwingt für BarCategory nun einen dichten
horizontalen Grenzfall statt pauschal sechs Vorschauquellen vorauszusetzen.

Die lokale Browserfixture unterstützt `compare=1`, `width=20|100`, `font=200`
und `/preview.svg?width=20&orientation=vertical`. Am 18.10.2026 wurden synthetische
72-Quellen-Vergleiche in Kachel-/IPSView-Varianten, beide Ausrichtungen und
SVG-Vorschauen visuell geprüft, einschließlich der Dichtebegrenzung durch
große Schrift. Live-Symcon-/IPSView-Tests nach Modulupdate bleiben offen.

## BarCategory-Verlauf und StateTimeline bis 32 Quellen

`bar_category_gradient.php` ergänzt die Gateway-Integration um optionale
Verläufe, unveränderte Vollfarben, unabhängige Designs, Fließkomma-Grenzen,
Kopie/Vererbung, Feldsichtbarkeit, Vorschau-Callbacks, gültige SVG-Vorschauen
aller Modi/Ausrichtungen und ungültige Bereiche/Farben. Der 16%-Fall verhindert
einen auf jeden kurzen Balken neu gestreckten Gesamtverlauf. 100 Quellen mit
Verlauf bleiben in beiden Ausgaben unter 1 MiB. Abgeschlossene BarCategory-
Fixtures werden vor weiteren Familien freigegeben, statt das PHP-Speicherlimit
für die gemeinsame Integrationssuite anzuheben.

`bar_category_layout.js` prüft absolute Min-/Max-Farbstopps, LQI, Teilbereiche,
Null/fehlende und signierte Werte, gestapelte Segmente, Legenden-Auswahl,
Kontrast und feste Farbskalen bei Navigation/Sortierung/Updates mit 72/100
Quellen. Das ausgelieferte Subset prüft native Modelle, Geometrie und
Gradient-Fills. Der separat geladene SVGRenderer ist nicht klassenidentisch
mit den Path-Klassen des kompilierten Subsets: Seine SVG-Ausgabe allein ist
kein Bildnachweis. Tatsächliche SVG-Gradienten werden deshalb zusätzlich mit
der offiziellen vollständigen Runtime geprüft, native Canvas-Ausgaben lokal
im Browser. `bar_category_browser.mjs` bietet `fill=gradient`, `min`, `max`
und `signed=1`. Horizontale 100er-Kachel sowie vertikale gestapelte IPSView-
Ansicht mit 72 synthetischen Quellen wurden am 18.10.2026 visuell geprüft.

Die StateTimeline-Integration prüft 32 gültige/33 abgewiesene Quellen,
Quell-IDs bei gleichen Namen, unbekannte/teilweise Archive, Ausgabegrößen,
gekennzeichnete Vorschauausschnitte und das gemeinsame 16000-Rohwerte-Budget.
`state_timeline_layout.js` prüft den nativen Zeilenfilter bis Quelle 32,
gleichzeitigen Zeitzoom und Zeilennavigation, ID-Anker bei Aktualisierung,
Umordnung und Resize. Die Browserfixture `rows=32` wurde in Kachel-/IPSView-
Varianten bis zur letzten Quelle visuell geprüft. Das ersetzt keine Live-
Abnahme im tatsächlichen Symcon-/IPSView-Client nach dem Modulupdate.

## PictorialBar-Spaltenbreite und BarCategory bis 100 Quellen

Die PictorialBar-Integrationsprüfung sichert die neue Spaltenbreite (0 =
automatisch, 1–80 Prozent), unabhängige Ausgaben, Designkopie, Vererbung,
ungespeicherte Vorschauen und die Ablehnung ungültiger Breiten ab.
`pictorial_bar_layout.js` prüft den Einfluss langer Namen, manuelle Anteile,
Platzschutz in schmalen Kacheln und unveränderte vertikale Geometrie.
Die lokale Browserfixture unterstützt `labels=long`, `column=45` und
`orientation=horizontal`; native 624×414- sowie breite IPSView-Ansichten wurden
am 18.10.2026 mit synthetischen 72/100 Quellen visuell geprüft.

`bar_category_large_sources.php` ist Teil von `gateway_gauges.php` und prüft
72/100 gültige Quellen, 101 abgewiesene Quellen, unveränderte Identitäten und
Referenzen, signierte Werte, gekennzeichnete echte Vorschauausschnitte und das
1-MiB-Ausgabelimit. `bar_category_layout.js` lädt das ausgelieferte BarCategory-
Subset plus ausschließlich den SVGRenderer als Headless-Testadapter. Für
beide Ausgaben, Ausrichtungen und alle drei Modi werden Navigation bis zur
letzten Kategorie, Achsentexte, vollständige signierte Skala, Tooltips und
Fenstererhaltung nach Aktualisierung geprüft. Gleichlautende Kategorien
verwerfen keine Variablen; Sortierung bewahrt den Anker anhand der Quell-ID.
Ein zusätzlicher Integrationstest kombiniert 100 lange Bezeichnungen mit
einem fast 256-KiB-großen SVG-Muster in beiden Designern samt Themes und
prüft jede Ausgabe gegen das 1-MiB-Limit.

`node tests/bar_category_browser.mjs` stellt synthetische Canvas-Beispiele auf
`http://127.0.0.1:8802` bereit. Parameter: `count=100`, `bar=simple|grouped|stacked`,
`orientation=horizontal|vertical`, `mode=symcon|ipsview`.
Diese lokalen Nachweise ersetzen nicht die Live-Abnahme nach dem Modulupdate
in den tatsächlichen Symcon- und IPSView-Clients. Live-Einstellungen werden
von den Entwicklungsprüfungen nicht geändert.

## PictorialBar: große Quellenlisten

`php tests/gateway_gauges.php` prüft zusätzlich 72/100/101 Quellen, unveränderte
IDs/Referenzen/Abonnements, keine Änderung der Funnel-Grenze, beide HTML-Ausgaben
und Formularvorschauen unter 1 MiB. Auch 100 Quellen mit großem SVG und Verlauf
werden geprüft; die SVG-Vorschauen kennzeichnen ihren echten Acht-Quellen-
Ausschnitt statt auf Beispieldaten zurückzufallen.

`node tests/pictorial_bar_layout.js` prüft die vollständigen 72-/100-Quellen-
Modelle, native DataZoom-Navigation bis zum letzten Eintrag, Originaltexte und
die Labelgeometrie nach Scrollen. Beide Ausgaben, beide Ausrichtungen sowie
Standardform, Icon und eigenes SVG mit Verlauf sind enthalten. Der Ausschnitt
muss bei einem Datenupdate erhalten bleiben. Diese Tests verwenden die echte
gepinnt installierte ECharts-Engine; der Assettest prüft das ausgelieferte Subset
mit DataZoom und neuer Prüfsumme.

`node tests/pictorial_bar_browser.mjs` bietet unter `http://127.0.0.1:8801`
synthetische Beispiele mit 72 Quellen in der Kachel und 100 in IPSView.
`/chart?mode=ipsview&count=100&color=gradient&shape=svg` öffnet den großen Fall
direkt. Ein echter Symcon-/IPSView-Live-Test nach Modulupdate bleibt erforderlich;
die Entwicklungsprüfungen füllen keine Live-Instanz und ändern keine Quellwerte.

## CalendarPie

`php tests/calendar_pie_data.php` prüft abgeschlossene lokale Monatsfenster,
Schaltjahre, Sommerzeit und Tagesgrenzen auch bei Übergängen um Mitternacht
(São Paulo und Havanna). Samoas übersprungenes Datum wird ohne doppelte
Datumsschlüssel ausgelassen. Alle Zustandsdauern einschließlich unbekannter
Zeit ergeben exakt die reale Monats- beziehungsweise Tageslänge. Numerische
Mittelwerte/Zählersummen bleiben unverändert; fehlende Quellen, Nulltage,
negative Werte, Punkt-/Zustandslimits und ungültige Daten sind abgesichert.
Jede numerische Quellenanzahl von eins bis fünf bleibt vollständig erhalten;
eine sechste Quelle wird ausdrücklich abgewiesen.
Die optionale Normalisierung prüft feste Min/Max-Bereiche, unterschiedliche
Einheiten, negative Originale, Begrenzung nur der Gewichte, Originalpräzision,
Null-/Teiltage, inaktive Grenzen und nichtendliche/ungültige Bereiche.

`php tests/calendar_pie_integration.php` verwendet den echten Gateway mit den
vorhandenen Kernel-Doubles in einem eigenen Prozess. Alle drei Modi prüfen
Archivart/Reducer, Metadaten, Vorgänger, Quellenidentität, aktive Referenzen,
unabhängige Designer, Vererbung/Kopie, deutsche Formular- und Renderertexte,
schreibfreie Entwürfe, Vorschauzustände/-farben, Monatsnavigation, Fehlererholung
und die komplette HTML-Ausgabe unter 1 MiB. Vollständige Kreise/Donuts nutzen
die gemeinsame SVG-Sektorgeometrie ohne Rundungsfehler oder künstliche Naht.
SVG-Intervalle, Konturen, Labelunterdrückung, automatische Unbekannt-Farbe und
die Zellposition nach einem übersprungenen lokalen Datum werden geprüft.
Beide numerischen Modi werden zusätzlich mit je einer bis fünf Quellen durch
Gateway, Kachel-/IPSView-Bootstrap und beide SVG-Vorschauen geprüft. Ein Entwurf
mit sechs Quellen zeigt einen Hinweis statt einer gekürzten Vorschau.
Gemischte Einheiten bleiben ohne Normalisierung ungültig und werden nach
explizitem Einschalten in beiden numerischen Modi korrekt publiziert. Native
Einheiten/Präzision je Quelle, gleichnamige IDs, inaktive globale Formatierung,
schreibfreie SVG-Entwürfe, Bereichsfehler und unveränderte Zustandsdauern sind
zusätzlich abgesichert.

`node tests/calendar_pie_layout.js` prüft die gepinnte echte ECharts-Engine in
316 Geometrievarianten: Kachelkopf, Titel, Navigation, Wochentage, Legende,
Kreis-/Donutgrenzen, große Schriften sowie vollständige Textgrenzen ohne
Überlappung oder Eintritt in das Donut-Loch. Ein echter Kollisionsfall mit
acht Kategorien als defensiver Renderer-Stresstest muss Beschriftungen
unterdrücken, ohne alle Texte zu verlieren. Der reguläre Quellenvertrag bleibt
eins bis fünf; jede Anzahl wird in beiden numerischen Modi und Ausgaben geprüft.
ID-basierte Legenden erhalten gleiche sichtbare Namen. Sichere URL-Auflösung
für opake Frames, veraltete Navigationsantworten und Timer-Nachrichten während
des Monatswechsels sind ebenfalls abgesichert.
Normalisierte Winkel verwenden Gewichte statt Originalzahlen. Tooltip und
Innenlabels bewahren Originalwerte; Quelleneinheit/-präzision, Bereich,
Kreisanteil, negative/überhöhte Werte, Nullgewichte, Teiltage und HTML-Escaping
der Originaleinheiten werden in den Rendereroptionen geprüft.

`node tests/calendar_pie_browser.mjs` stellt ausschließlich unter
`http://127.0.0.1:8805` synthetische Kalenderdaten bereit. `/` vergleicht
schmale Kachel, breites IPSView und hellen Verbrauch. `/chart?mode=ipsview&data=states&ring=yes`
zeigt Zustandsdauern als Ringe; `/chart?mode=ipsview&theme=light&data=counter&font=200&values=yes`
prüft große Schrift und Innenwerte. `/preview.svg?data=states` beziehungsweise
`data=average` oder `data=counter` erzeugen die echte PHP-Formularvorschau.
Mit `sources=1` bis `sources=5` lässt sich die Anzahl numerischer Quellen sowohl
unter `/chart` als auch unter `/preview.svg` variieren.
`/chart?normalize=yes&sources=2` zeigt einen normalisierten Temperatur-/Feuchte-
Vergleich mit Originalwerten im Tooltip. `mode=ipsview` wählt den zweiten
Ausgabeweg; `normalize=yes` ist auch für `/preview.svg` verfügbar.
`/normalized` vergleicht eine schmale dunkle Kachel, helles IPSView und die
PHP-Vorschau. Diese normalisierten Ansichten sowie Originaleinheiten,
getrennte Kreisanteile und der Tooltip-Zeilenumbruch bei 400 Pixel Breite wurden
am 18.10.2026 zusätzlich lokal visuell geprüft.
Diese Ansichten, dunkle Kachel, helles IPSView, unbekannte Anteile,
Monatsnavigation und die korrigierten Innenwerte wurden am 18.10.2026 lokal
visuell geprüft. Ein direkt geöffneter Renderer blieb ohne Konsolenwarnung
oder -fehler. Die Live-Abnahme aller drei Modi sowie der neuen Formularfelder
in Symcon 9.0/9.1 und IPSView steht nach Modulupdate noch aus.
Es wurden keine Live-Konfigurationen, Variablenwerte oder Archive verändert.

StateTimeline wird durch `php tests/state_timeline_data.php`,
`php tests/archive_states_gateway.php` und `php tests/state_timeline_integration.php`
abgesichert. Der Integrationstest nutzt den echten Gateway mit den vorhandenen
Kernel-Doubles über einen eigenen Prozessmodus, damit HTML-Fixtures nicht
alle gleichzeitig im monolithischen Speicher verbleiben. Geprüft werden
rohe Boolean-/Integer-Zustände, Vorgänger, Epochengrenze, unbekannte Anfänge,
gleiche Quellnamen, Quellwechsel, halboffene Intervalle, Dubletten, Wertebereiche,
Zähler-/Float-Ausschluss, fehlender Parent, Fehlererholung, vollständige deutsche
Formularbeschriftungen einschließlich dynamischer Felder, Entwürfe ohne
Persistenz/Archivabfrage, getrennte Designer und IPSView-Kopie. Gekürzte und
zu große Modelle ergeben einen sichtbaren Fehler. Ein adversarieller gültiger
Zustandsname mit 80 Ampersands sichert die HTML-JSON-Expansion und das
1-MiB-Ausgabelimit ab; der Test scheiterte vor dieser Größenkorrektur.

`node tests/state_timeline_layout.js` prüft native ECharts-6.1.0-Geometrie,
exakte Endpunkte, winzige Abschnitte, unbekannte Zustände, Zoom-Clipping,
Titel-/Fußabstände, doppelte Namen, große Schrift und eindeutige Datumslabels.
Browserfixture: `node tests/state_timeline_browser.mjs`, nur `127.0.0.1:8804`
und synthetische Daten. `/` vergleicht schmale Kachel, breites IPSView und eine
helle Ansicht mit unbekannten Zuständen. `/chart?mode=ipsview&theme=light&font=200&range=30d`
prüft große Achsenschrift und Tooltips; `/preview.svg` zeigt die echte PHP-
Formularvorschau, optional mit `?mode=ipsview&independent=yes`.
Am 17.10.2026 wurden diese Ansichten und beide PHP-Vorschauen lokal visuell
geprüft; ein direkter Rendereraufruf blieb ohne Konsolenwarnung/-fehler.
Lokale Browserprüfung und vollständige Qualitätssuite ersetzen keine Live-
Abnahme des neuen Moduls nach Update in Symcon 9.0/9.1 und IPSView.
Es wurden keine Live-Konfigurationen oder Archive verändert.

Die Designer-Erweiterung vom 18.10.2026 sichert alle neuen Felder in beiden
Formularen, deutsche Übersetzungen, Validierung, unabhängige IPSView-Ausgabe,
Kopieren und schreibfreie SVG-Entwürfe ab. Geprüft werden Rundung/Kontur,
Textausrichtung, Schriftfamilie/-stile, Achsen-/Rastergestaltung und Zeitformate.
Der echte Custom-Renderer wird zusätzlich über native Mouseover/-out-Ereignisse
auf Hervorhebung, Fokus, relative Restdeckkraft und Wiederherstellung geprüft.
Geometrieprüfungen begrenzen Rundungen auch bei Ein-Sekunden-Intervallen und
sichern Fuß-/Zoomabstände bei zweizeiliger Zeitbeschriftung, 200 % Schrift und
maximalem Labelabstand in beiden Ausgabewegen ab.
`/chart?design=extended` und `/preview.svg?design=extended` zeigen die neuen
Stile mit synthetischen Daten. Am 18.10.2026 wurden dunkle Kachel, breite
IPSView-Ansicht, helles IPSView mit rechtsbündigen Labels, 200 % Achsenschrift
und die erweiterte PHP-SVG-Vorschau lokal visuell geprüft. Ein frisch direkt
geöffneter Renderer blieb ohne Konsolenwarnungen/-fehler; die Vergleichsseite
mit Frames zeigte einen nicht dem Modul zugeordneten MutationObserver-Fehler.
Die neue Gestaltung und ihre Formularbedienung sind nach Modulupdate noch
in der echten Symcon-Kachel und IPSView abzunehmen; keine Live-Schreibzugriffe.

`node tests/ipsview_transport.js` prüft die zentrale Adressauflösung für
opake `data:`-/`about:`-Einbettungen bei verbotenem Top-Zugriff: Basisadresse,
Elternadresse, Referrer, Ancestor-Ursprung und bisheriger Top-Rückfall,
HTTP/HTTPS, Ports und IPv6. Ungültige, relative oder mit Zugangsdaten
versehene Adressen werden nicht als Endpunkt verwendet. Frischer Zustand
vor wartenden Live-Nachrichten, unveränderte Bootstrap-Konfiguration,
Credential-Policy und Abschalten der Wiederverbindung sind abgesichert.
Der neue Einbettungsfall scheiterte vor der Korrektur mit `ws:///hook/...`.

Browserfixture: `node tests/ipsview_transport_browser.mjs` bindet ausschließlich
`127.0.0.1:8803` und verwendet synthetische Werte, die echte ECharts-Runtime
und den unveränderten Time-Series-Renderer. Das opake Base64-`data:`-Frame
enthält standardmäßig kein Basis-Element. Ein HTTP-Zustand mit zwei Punkten
und drei WebSocket-Nachrichten müssen zur sichtbaren Meldung
`Live-Verbindung: 5 Punkte` und einer Linie mit fünf Punkten führen; die erste
Nachricht trifft während der Zustandssynchronisierung ein.
`?encoding=uri`, `?referrer=none` und `?base=yes` prüfen weitere Einbettungen.
Diese Varianten wurden am 17.10.2026 lokal im Chromium-Browser geprüft.
Die Live-Abnahme nach Modulupdate in Kachel und IPSView bleibt separat offen;
Live-Konfigurationen und Archivdaten wurden nicht verändert.

`pictorial_bar_svg.php` prüft eigene SVG-Formen über den tatsächlichen
Uploadadapter: Rohdaten/Base64/Data-URL, nichtnullige viewBox, relative und
mehrteilige Pfade, alle Pfadbefehle, kompakte Arc-Flags, XML-Struktur sowie
abgewiesene aktive/fehlerhafte Inhalte und Größenlimits. Die Integration
sichert native Dateifelder beider Designer, Entwürfe ohne Persistenz,
eigenständige Vorschauen, Kopie/Vererbung, Fehlererholung und den Ausschluss
der Originaldatei aus dem Transport ab. Große gültige Uploads mit 16 Quellen
bleiben einschließlich Formular unter 1 MiB. Abgeschlossene Formular-Snapshots
werden im monolithischen Test freigegeben, statt das Speicherlimit zu erhöhen.
Der echte Renderer prüft eigene SVGs in beiden Ausgaben, Ausrichtungen,
Symbolmodi und Farbmodi; verschobene und nullbasierte Geometrien sind gleich.
Browserfixture: `?shape=svg`, auch für `/preview.svg`; `&symbols=single`
prüft Einzelformen. Am 17.10.2026 wurden hohe/breite dunkle Ansichten,
helle Einzelformen sowie horizontale und vertikale PHP-Vorschauen lokal
visuell geprüft. Neue native Dateiauswahl und Live-Ausgaben nach Modulupdate
bleiben offen. Es wurden keine Live-Konfigurationen geändert.

`module_contracts.php` sichert für alle Module das Namensschema ohne
Leerzeichen und eine leere Aliasliste ab. Die fünf früheren Anzeige-Aliase
von Boxplot, Candlestick, Funnel, PictorialBar und Radar wurden damit zunächst
als Regression nachgewiesen; nach ihrer Entfernung besteht der Test.
Instanznamen werden nicht durch Modulcode oder Tests automatisch geändert.

Die Symcon-Icon-Erweiterung wird mit `php tests/pictorial_bar_icons.php`
gegen synthetische Light-/Brands-/Kit-Tupel, Priorität, mehrere Teilpfade,
ungültige Namen, Nicht-Ausführung von JavaScript und Ressourcenlimits geprüft.
Der Integrationstest verwendet den unveränderten Produktionsleser gegen eine
synthetische installierte `console/icons.js`: native Picker beider Designer,
Entwurfsänderungen ohne Persistenz, unabhängige Pfade, Kopie, Fehlererholung
und HTML-Ausgabegrenzen bei 16 Quellen. Der echte ECharts-Renderer prüft
Icons in beiden Ausrichtungen, allen drei Farbmodi und Einzel-/Wiederholung.
SVG-Pfadtransformationen und die Lastbegrenzung komplexer Formen sind abgedeckt.
Die Browserfixture ergänzt `?icon=cell`; `/preview.svg` liefert die tatsächliche
PHP-Vorschau mit rein synthetischen Beispielen (über die lokale Integrationssuite).
Am 17.10.2026 wurden hohe Kachel, breite IPSView-Ansicht sowie horizontale und
vertikale SVG-Vorschau lokal visuell geprüft. Ausgewählte Light-/Brands-Pfade
der installierten Symcon-Bibliothek wurden zusätzlich rein lesend aufgelöst.
Native Iconauswahl und neue Live-Ausgaben nach Modulupdate sowie andere
Installationslayouts/Versionen bleiben ausdrücklich Testlücken.

PictorialBar: `pictorial_bar_integration.php` wird in der Gateway-Suite
ausgeführt und prüft Quellen, Nullwerte, Einheiten, ID-Identität, negative
Werte, Abonnements, Aktualisierung, Fehlererholung, Vorschauen, Designs und
beide Ausgaben. `node tests/pictorial_bar_layout.js` prüft Optionen,
Originalwert-Erhaltung, Clipping und alle fünf Formen mit der echten Engine
in beiden Ausrichtungen. Die Funnel-Suite sichert den gemeinsam verwendeten
Quellenvertrag weiter ab. Lokale Browserfixture:
`node tests/pictorial_bar_browser.mjs` (127.0.0.1:8801), ausschließlich
synthetische Werte. Live-Kachel und IPSView sind nach Modulupdate separat offen.

Die PictorialBar-Designerweiterung prüft kontinuierliche RGB-Interpolation an
den Rand-/Zwischenwerten, unabhängige Farbbereiche, Nachkommastellen,
Originalwerte oberhalb des Zeichenmaximums, Whitelists und Grenzen beider
Designer. Neue Optionen, Kopieren/Vererbung, gültiges und schreibfreies SVG
sowie deutsche Formulare sind abgesichert. Die echte ECharts-Engine prüft
die vier Wertepositionen in beiden Ausrichtungen und Symbolmodi.
Die Browserfixture `?design=extended` zeigt die wertabhängigen Farben und
neuen Text-/Raster-/Schattenstile, `?symbols=single` einzelne Formen und
`?theme=light` einen hellen Hintergrund. Diese lokale Prüfung ersetzt keine
Live-Abnahme der Erweiterung nach Modulupdate.

Der zusätzliche räumliche Verlauf wird für 0–100 %, 0–255 LQI, nichtnullige
Minima, automatische Zeichenskalen und optisch begrenzte Werte geprüft.
Alle fünf Formen, Einzel-/Wiederholung und beide Ausrichtungen laufen gegen
die echte Engine. Dabei werden auch die tatsächlichen Texttransformationen
am Messwertende statt an den Compound-Pfadgrenzen geprüft. Der intern
erzeugte Pfad bleibt bei sehr breiten Ansichten auf 128 Teilformen begrenzt.
SVG-Vorschau, unabhängige Modi, Kopieren und unveränderte Entwurfs-Properties
sind im Integrationstest abgedeckt. Browserfixture: `?color=gradient`,
optional `&range=lqi`, `&symbols=single` oder `&theme=light`. Dunkle hohe und
breite Wiederholungsansichten sowie helle LQI-Einzelformen wurden am
17.10.2026 lokal visuell geprüft. Die neue Live-Kachel-/IPSView-Abnahme nach
Modulupdate bleibt offen.

Candlestick-Zeitzoom: `node tests/candlestick_layout.js` prüft zusätzlich den
ausgelieferten Candlestick-Build mit nativen DataZoom-Komponenten, beide
Ausrichtungen und inverse Zeitachsen, Zoom per zentralem Wheel-Router,
unveränderte Skalen und Grids, passende Kerzenbreiten, Update/Resize und
Abschalten. Die PHP-Integration prüft ausgeschaltete Defaults, getrennte
Designer, Vorschauentwürfe ohne Archivzugriff und unveränderte Daten/Budgets.
`node tests/candlestick_browser.mjs` stellt zwei lokale verschachtelte Frames
mit 168 synthetischen Stunden, sichtbarem Zoomnachweis und Refresh-Schaltfläche
bereit. Echte lokale Mausradaktionen und Regler werden dort geprüft;
die Abnahme in Symcon und dem tatsächlichen IPSView-Client nach Update sowie
auf Touchgeräten bleibt separat offen.

Candlestick: `php tests/candlestick_data.php` prüft abgeschlossene Stunden/
Tage, Sommerzeit, erste/letzte Rohpunkte, Extrema, Lücken, Einzelwerte,
Trunkierung und Abfragelimits. `candlestick_integration.php` wird im
Gateway-Suite-Kontext ausgeführt und prüft Quellen, Cache, Designs,
Vorschauen, Fehler und beide begrenzten HTML-Ausgaben.
`node tests/candlestick_layout.js` prüft Optionen und echte ECharts-Geometrie
für beide Ausrichtungen. Die installierte Kachel und das IPSView-Widget
bleiben nach dem Modulupdate separat zu prüfen.
Für synthetische lokale Browserdaten kann
`node tests/candlestick_browser.mjs` gestartet werden (127.0.0.1:8800).
Es werden keine echten Symcon-Daten gelesen oder geschrieben.

Die Candlestick-Designprüfung umfasst außerdem alle 16 ergänzten Felder,
ungespeicherte Vorschauen, beide Ausgaben, Kopieren/Vererbung, Grenzen und
Schrift-Whitelist. Die echte Engine prüft hohle Kerzen, getrennte Rahmen,
Hover-Zustände, feste Breite, umgekehrte Zeitachse und Nullpunkteinbindung
sowie enge Layouts. Browserfixture `?design=extended` verwendet die neuen
Einstellungen, `?theme=light` ein helles Layout. Die Archivtests bleiben unverändert.

Boxplot wird durch `php tests/boxplot_statistics.php`,
`php tests/gateway_gauges.php` (einschließlich `boxplot_integration.php`)
und `node tests/boxplot_layout.js` geprüft: R7-Quartile, endliche Extremwerte,
abgeschlossene Stunden, Lücken und leere Archive, einzelne Stichproben,
Einheiten-/Zähler-Abweisung, strikte Gateway-Antworten, unabhängige Designer,
entkoppelte Beispielvorschauen, Ausgabegrenzen und echte ECharts-Geometrie.
Die lokale synthetische Browserfixture startet mit `node tests/boxplot_browser.mjs`.
Sie ersetzt keine Live-Prüfung in Symcon oder IPSView.

`boxplot_large_sources.php` (über die Gateway-Suite) prüft zusätzlich 32/33
Quellen, doppelte Namen mit eigener ID, leere und negative Quellen, R7-Erhalt,
beide Ausgaben und große Bezeichnungen unter 1 MiB sowie Vorschauen ohne
Archivzugriff mit gekennzeichnetem Ausschnitt. Ein gemeinsames 10000-Punkte-
Budget bricht dichte 32×30-Tage-Modelle ohne Teilstatistik ab; 32 vollständige
Sieben-Tage-Archive und Erholung nach Budgetfehler werden geprüft.
Die echte ausgelieferte Boxplot-Runtime wird für Slider-/Inside-Navigation,
vollständige signierte Skala, Tooltips nach Filterung und ID-stabile Ansichten
bei Refresh, Umordnung und Resize in beiden Ausrichtungen getestet.
Die Browserfixture bietet `count=32`, `orientation=horizontal|vertical`,
`tail=1`, `font=200` und `box=10` für ergänzende Canvas-Sichtprüfungen.
Die 32-Quellen-Erweiterung benötigt nach Modulupdate noch die Live-Abnahme.

Sankey wird durch `php tests/sankey_model.php` und
`node tests/sankey_layout.js` geprüft. Abgedeckt sind manuelle Knotenschlüssel,
aktuelle Verbindungsgewichte, Zyklen, Null- und Negativwerte sowie das Layout
mit der echten ECharts-Engine. Die Gateway-Suite prüft zusätzlich
Quellenabonnements, Wertänderungen, Formularauswahl und getrennte Ausgabewege.
Die Designer-Tests prüfen zusätzlich individuelle Farben nach Knotenschlüssel,
Rahmen und Rundungen, Beschriftungs- und Hover-Optionen, Grenzen beider Designs,
Kopieren, Draft-Vorschau und deren unveränderte gespeicherte Konfiguration.
Regressionsfälle mit einer Quelle und 24 ungleichen Verbrauchern sichern
vollständige Vorschauen, aktuelle Größenverhältnisse, geschlossene Flussbänder
innerhalb der Zeichenfläche sowie Fehler-/Nullzustände ohne Konfigurations-
oder Statusänderung ab. ECharts-Engine-Tests prüfen begrenzte und kollisionsfreie
Knotenlabels in beiden Ausrichtungen auch ohne aktiviertes `HideOverlappingLabels`.
Der Anwender hat am 16.10.2026 auch die ergänzten Designeroptionen und die
korrigierte Vorschau/Beschriftung in Symcon-Kachel und IPSView live bestätigt.
Dieser Nachweis gilt für die vorhandene Testinstallation; andere Plattform-
und Symcon-Versionsvarianten sind dadurch nicht zusätzlich geprüft.

Die lokale Testsuite wird aus dem Repository-Stamm gestartet:

```text
git submodule update --init --recursive
php tests/run.php
```

Für den vollständigen lokalen Qualitätslauf einschließlich automatischer,
rein mechanischer PHP- und JSON-Formatkorrekturen wird verwendet:

```text
php tests/quality.php --fix
```

Ohne `--fix` arbeitet der Qualitätslauf schreibgeschützt. Beide Modi werten
jeden Teilprozess einzeln aus, damit ein später erfolgreicher Befehl keinen
vorherigen Fehlerstatus verdeckt. Vor einer JSON-Korrektur wird die Syntax
aller versionierten JSON-Dateien separat validiert; ungültiges JSON wird nicht
automatisch überschrieben. Zusätzlich zur Basissuite laufen PHP-Lint,
PHP-CS-Fixer, JSON-Style, `git diff --check` und alle Gauge-Layouttests.

Der optionale versionierte Pre-Push-Hook wird einmalig mit
`git config core.hooksPath .githooks` aktiviert. Wenn sein Qualitätslauf eine
Formatkorrektur erzeugt, wird der Push gestoppt, bis die Korrektur committet
wurde.

Die Gauge-Layouttests benötigen zusätzlich Node.js und werden separat mit
`node tests/gauge_single_layout.js`, `node tests/gauge_multi_layout.js`,
`node tests/gauge_tacho_layout.js` und `node tests/gauge_chronograph_layout.js`
ausgeführt. Der Single-Test prüft die vergrößerte Darstellung und die Grenzen
der Zifferblattplatte in Kachel und IPSView. Der Multi-Test prüft die freigehaltene
Kachelkopfzeile sowie die Layouts beider Ausgabewege anhand gerenderter
ECharts-Optionen. Er deckt auch Ringraster und konzentrische Ringe mit 2 bzw.
16 Quellen und das Wetterstationspanel ab. Eigene Renderer-Suiten prüfen die
Tacho- und Chronograph-Module einschließlich quellbezogener Designwerte und
SVG-Verarbeitung für ihre maximal fünf Quellen. Der Tacho-Test prüft außerdem,
dass breite Kacheln mit vier oder fünf Quellen alle Nebeninstrumente gleich
groß, überlappungsfrei und innerhalb der verfügbaren Fläche anordnen. Die
Die übrigen PHP-Vertragstests benötigen kein Node.js; der vollständige
Qualitätslauf führt zusätzlich die Renderer-Tests mit Node.js aus.

`node tests/echarts_design.js` prüft die gemeinsame Deckkraftumrechnung und
die optionale Balkenkontur;
`node tests/time_series_layout.js` prüft unter anderem Flächen und
Annotationen. Beide laufen auch im vollständigen Qualitätslauf.
`node tests/pie_layout.js` prüft Kreis, Donut und Nightingale-Rose,
ID-getrennte gleichnamige Quellen, Textsicherheit, Animation und Nullsummen.
Bei installierter festgeschriebener ECharts-Buildabhängigkeit prüft es die
Sektorgeometrien zusätzlich gegen die echte ECharts-Engine im SVG-SSR-Modus.
`php tests/heatmap_aggregation.php` prüft Wochentag-/Stundenbildung,
fehlende und gemessene Nullwerte sowie beide Sommerzeitwechsel.
`node tests/heatmap_layout.js` prüft ECharts-Optionen, responsive
Zellenbeschriftungen und den Erhalt der letzten Grafik bei Lesefehlern.

`tests/fixtures/tacho-browser.html` und
`tests/fixtures/chronograph-browser.html` sind manuelle Browser-Fixierungen
für die Tacho- und Chronograph-Vorlagen. Sie laden die lokal gebündelte
ECharts-Runtime und den jeweiligen echten Renderer mit drei synthetischen
Quellen, ohne eine Symcon-Instanz zu benötigen. Sie ersetzen keinen Test in
der nativen Kachel oder in IPSView.

Der GitHub-Workflow `.github/workflows/tests.yml` verwendet zusätzlich
`Burki24/Symcon_ModuleCI/php-tests@v1.0.0`. Die gemeinsame Action prüft unter
PHP 8.5 alle PHP-Dateien auf Syntax, validiert die JSON-Dateien und startet
anschließend denselben lokalen Testeinstieg. Der Workflow
`.github/workflows/style.yml` verwendet
`Burki24/Symcon_ModuleCI/style@v1.0.0` für die offiziellen Symcon-Prüfungen mit
StylePHP und PHP CS Fixer.

## Enthaltene Prüfungen

- `validate_structure.php` prüft die erwarteten Projekt-, Modul-, Test- und
  Workflowdateien, die von Symcon erlaubten Verzeichnisse im Library-Stamm,
  JSON-Grundstrukturen und die Einbindung der gemeinsamen CI-Basis
  einschließlich des Style-Workflows.
- `module_contracts.php` charakterisiert die vorhandenen Library- und
  Modulidentitäten, die Symcon-9.0-Mindestversion sowie die beiden
  Datenflussrichtungen.
- `symcon_strict.php` prüft Strict-Modulbasis, typisierte öffentliche
  Verträge, wiederverwendbare Gateway-Verbindung, die minimale
  Gauge-Konfiguration und den HTML-SDK-Vertrag von Gauge Single.
- `data_protocol.php` prüft Versionierung sowie Erfolgs- und Fehlerantworten
  des internen Gateway-Protokolls.
- `gateway_gauges.php` führt den vollständigen Momentanwertabruf für Gauge
  Single und Gauge Multi mit Symcon-Test-Doppeln aus. Für Multi werden außerdem
  geordnete Quellen, Referenzwechsel, stabile Item-IDs, Beschriftungen,
  Duplikate, Wertebereiche und fehlerhafte Konfigurationen geprüft. Für Gauge
  Single werden außerdem Kacheldesigner-Presets, Theme-Auswahl, Zeigerformen
  einschließlich des abgesicherten SVG-Pfadimports,
  Skalenbögen, Farbrollen, SVG-Vorschau, HTML-Erzeugung, lokale
  ECharts-Einbettung und die Zustandsaktualisierung nach
  `VM_UPDATE` charakterisiert. Das erzeugte
  HTML-Dokument muss unter dem Symcon-Output-Buffer-Limit von 1.048.576 Byte
  bleiben. Für Gauge Multi werden außerdem die gemeinsame Zeiger-, Naben-,
  Skalen-, Farb- und Plattengestaltung, deren Validierung sowie getrennte
  Kachel- und IPSView-Einstellungen geprüft. Der JavaScript-Layouttest sichert
  die Anwendung dieser Designschicht auf Raster-, Ring- und Chronographenserie
  einschließlich der Zeichenreihenfolge ab. Die Formularintegration prüft,
  dass ungespeicherte Änderungen beide SVG-Vorschauen sofort und bei
  unabhängigem IPSView-Design getrennt aktualisieren.
- Für TimeSeries prüft derselbe Integrationstest zusätzlich die optionale
  WebContent-Variable, die Vererbung des Kacheldesigns, ein unabhängiges
  IPSView-Theme und -Design sowie die sofortige Aktualisierung beider
  Formularvorschauen. Quellenbezogene Referenzlinien und Wertebereiche werden
  einschließlich Normalisierung, ungültiger Bereichsgrenzen, SVG-Vorschau und
  ECharts-Optionen geprüft.
- Für Category Bar prüft der Integrationstest den aktuellen Gateway-Datenweg,
  die gemeinsame Einheit, vollständige Gruppierungs-/Stapelmatrizen,
  getrennte Kachel-/IPSView-Designs und Live-Updates.
  `bar_category_layout.js` sichert Orientierung, Sortierung, einfache,
  gruppierte und gestapelte Reihen, Farben, Balkenbreite, Beschriftung und
  abgerundete Balken im Renderer ab.
- Für Historical Bar prüft der Integrationstest den Archivdatenweg mit
  automatischer Verdichtung und ausdrücklich unveränderten Rohwerten sowie
  die Ein-Quellen-Validierung. `bar_history_layout.js` sichert Zeitachse,
  Werteformatierung, Raster, Balkenform, Farbe und Kürzungswarnung im nativen
  Renderer ab.
- `echarts_assets.php` prüft Version, SHA-256-Integrität, Lizenz und NOTICE der
  lokal gebündelten Apache-ECharts-Runtime und der sechs offiziellen Themes.
  Zusätzlich werden der Gauge-spezifische Browser-Export, das gemeinsame
  512-KiB-Budget und der tolerierte Windows-Zeilenendenfall geprüft.
- `svg_path.php` prüft Rohtext-, Base64- und Data-URI-Importe sowie die
  Ablehnung nicht unterstützter oder aktiver SVG-Inhalte. Die ornamentale
  Beispielnadel unter `fixtures/gauge-pointer-ornate.svg` wird zusätzlich als
  direkt importierbare Testdatei einschließlich ihres optionalen Drehpunkts
  geprüft. Die Gauge-Integration prüft außerdem, dass die prozentuale
  Pivot-Justierung die native ECharts-Nabe sichtbar in ihrem Zeigerring
  zentriert. Kreis, Ring, ausgeblendete und eigene SVG-Naben werden über
  denselben Vorschau- und Datenmodellvertrag abgesichert; aktiver Inhalt in
  einem Naben-SVG wird abgewiesen.
  Runde und skalenbogenabhängige Zifferblattplatten werden einschließlich
  Theme- oder eigener Farben, transparenter Füllung, Rand, Größe und Schatten
  in Datenmodell, SVG-Vorschau und nativem ECharts-Renderer geprüft.
- `gauge_design.php` prüft den ausschließlich für ECharts bestimmten,
  gemeinsamen Gauge-Designvertrag für Farben, SVG-Zeiger, Ankerpunkte und
  bereinigte SVG-Zifferblatthintergründe.
- `helper_integrity.py` stellt sicher, dass Subscription, Manifest,
  Helper-Dokumentation und alle abonnierten Datenfluss- und
  Visualisierungshelper vollständig übereinstimmen.
- `test_update_library_metadata.py` prüft Versionsfortschreibung, Build- und
  Datumsableitung sowie den Schutz vor einer Versionsrückstufung.

## ThemeRiver

- `php tests/theme_river_data.php` prüft abgeschlossene Stunden und lokale
  Kalendertage einschließlich beider DST-Wechsel, echte Nullwerte, negative/
  nichtendliche Daten, exakte Zeitstempel und Lücken ohne Nullauffüllung.
- `php tests/gateway_gauges.php --theme-river` nutzt denselben echten Gateway
  und dieselben Kernel-Doppel wie die bisherige Integration, läuft aber wegen
  deren angesammelter HTML-Fixtures in einem eigenen Prozess. Geprüft werden
  Quellenidentität und gleiche Namen, Einheiten, explizite Mittelwerte,
  Zählerablehnung, Quellenreferenzen, Vorschauentwürfe ohne Archivzugriff,
  unabhängige Designer, Kopie/Vererbung, deutsche Texte, Transientfehler und
  das 1.048.576-Byte-Limit bei acht Quellen und 720 vollständigen Intervallen.
- `node tests/theme_river_layout.js` prüft den produktiven Optionsaufbau und
  die echte gepinnte ECharts-Engine: Abschnitte ohne Lückenüberbrückung,
  gemeinsame Pixelmaßstäbe, Nullausgaben ohne Division durch Null, sichere
  Tooltip-Zuordnung an der Zeigerposition und Anzeigenamen statt interner IDs.
  Stunden-/Datumsbeschriftungen und geometrischer Achsen-/Legendenabstand
  werden bei 320/630/2048 Pixeln und 50/100/200 % Schriftgröße geprüft;
  Entwurfsänderungen der SVG-Vorschau verändern keine gespeicherte Konfiguration.
  Die erweiterten Designer werden einschließlich Property-Validierung,
  vollständiger Kopie/Vererbung und deutscher Texte geprüft. Native Bandpolygone
  müssen Schatten, Stricharten und Blur-Deckkraft tatsächlich übernehmen;
  obere/untere und links/mittig/rechts ausgerichtete Legenden bleiben bei
  großen Symbolen und zweizeiligen Datum-/Uhrzeitlabels überlappungsfrei.
- `node tests/theme_river_browser.mjs` bietet die rein lokale Canvas-Fixture
  unter `http://127.0.0.1:8802`. `/chart?mode=symcon` und
  `/chart?mode=ipsview&theme=light&design=extended` prüfen dunkle Kachel und
  helle IPSView mit acht Quellen; `case=zero`, `case=empty` und `case=dense`
  ergänzen Grenzfälle. `/preview.svg` erzeugt die tatsächliche PHP-Vorschau
  über den vorhandenen Bootstrap mit `--theme-river-preview`.
  `range=7d` beziehungsweise `range=30d`, `case=complete` und `font=200`
  ergänzen die Prüfung von Datumsangaben und dem vergrößerten Legendenabstand.
  `design=extended` zeigt Schatten, gestrichelte Bänder, Raster, Georgia,
  Rautensymbole und zweizeilige Daten; `legend=bottom` setzt die ansonsten
  oben gezeigte erweiterte Legende nach unten. `/preview.svg?design=extended`
  prüft denselben statischen Designumfang der PHP-Vorschau.

Am 17.10.2026 wurden lokale Kachel-/IPSView-Darstellung, Titelabstände,
Lücken und Tooltipausblendung sowie die Nullanzeige visuell geprüft.
Die Grundfunktion wurde anschließend vom Eigentümer in Kachel und IPSView
bestätigt. Die überarbeitete Zeitachse und der Legendenabstand wurden lokal
im Canvas-Renderer und in der SVG-Vorschau auch bei 200 % Schrift geprüft;
ihre erneute Live-Abnahme steht nach dem Modulupdate noch aus.
Die erweiterten Designoptionen wurden zusätzlich im dunklen Kachelformat,
in breiter IPSView-Ausgabe mit 200 % Schrift, im hellen Design sowie in der
SVG-Vorschau visuell geprüft. Bandhover mit reduzierter Restdeckkraft wurde
im Canvas-Renderer bestätigt. Die Live-Abnahme dieser Erweiterung und der
neuen Formularfelder in Symcon/IPSView bleibt Aufgabe des Eigentümers.

## Funnel

Die Designer-Erweiterung prüft optische Größenbegrenzung mit unveränderten
Nullwerten, Rand-/Führungslinien, Schatten, Schriftfamilie, Beschriftungsseite,
Überlappungsschalter und Hover-/Blur-Einstellungen. Modell und unabhängige
IPSView-Ausgabe, Kopie, deutsche Formulare, ungültige Bereiche/Enums und
invertierte Größen werden abgesichert. Neue PHP-SVG-Punkte werden in beiden
Ausrichtungen und drei Reihenfolgen mit dem echten ECharts-Renderer verglichen.
Die Browserfixture ergänzt `design=polished` für lokale Stilprüfungen
mit Linien, Schatten, Außenbeschriftung und Schriftfamilie.

`funnel_integration.php` ist in `gateway_gauges.php` eingebunden und prüft
aktuelle Werte über den echten Gateway mit Kernel-Doppeln, ID-Identität,
gleiche Einheiten, Null-/Negativwerte, Quellenentfernung, VM_UPDATE, Fehler-
wiederholung, unabhängige Designer, Kopie, deutsche Beschriftungen und das
1.048.576-Byte-Limit bei 16 Quellen. Vorschauen lesen nur begrenzt aktuelle
Quellen und verändern weder Status noch gespeicherte Konfiguration.

`node tests/funnel_layout.js` prüft beide Ausrichtungen, Querausrichtung,
stabile Sortierung, Farben, explizite Null-Styles, sichere Tooltips, Leerzustand
und Transientfehler. Die echte gepinnte ECharts-Engine prüft die Geometrie bei
100/350/900 Pixel Breite; PHP-Vorschaupolygone werden direkt mit den
ECharts-Punkten bei gleicher Zeichenfläche verglichen.

`node tests/funnel_browser.mjs` bietet eine ausschließlich lokale synthetische
Canvas-Fixture unter `http://127.0.0.1:8799`. Hohe Kachel und breite IPSView-
Darstellung wurden am 17.10.2026 visuell geprüft. `/chart?mode=symcon&design=extended`
zeigt 16 Stufen mit großen Schriften/Abständen; `/chart?mode=ipsview&theme=light`
prüft den hellen Hintergrund. Die reale neue MCP-Kachelvorschau und Live-Tests
in Symcon beziehungsweise IPSView bleiben nach Installation erforderlich.

## TimeSeries: 32 Reihen und Reihenauswahl

`php tests/gateway_gauges.php --time-series-many` verwendet echte Gateway-
Operationen mit synthetischen Kernel-Archiven. Geprüft werden 32/33 Quellen,
32000/32001 Budget, acht/neun Einheitengruppen, unveränderte Altlisten und
Defaults, versteckte Abonnements/Append-Punkte, Rohwerte und Reducer,
ID-bezogene Annotationen und vollständig ausgeblendete Modelle. Beide
Startdokumente werden entpackt und mit dem vollständigen Modell verglichen.
Große gültige SVG-Muster bleiben im Live-Modell, während beide Vorschauen
ihre Vereinfachung ausdrücklich kennzeichnen. Modell- und HTML-Byteguards
werden getrennt geprüft; die gespeicherte Konfiguration bleibt unverändert.
Der Test bleibt beim gemeinsamen 128-MiB-Speicherlimit und protokolliert nur
Archiv-/Current-Anfragen statt sämtliche vollständigen Renderer-Pushes.

Gemessen am 19.10.2026: 32000-Punkte-Modell 590044 Bytes, Kachel 897755 Bytes,
IPSView 901043 Bytes. Ein fast 256 KiB großes SVG-Muster ergibt 724159/727447
Bytes HTML und 343137 Bytes Formular einschließlich beider Vorschauen.
Diese Beispiele sind keine Zusage für beliebig komplexe Modelle am Bytebudget.

`node tests/time_series_layout.js` prüft native ECharts-Legenden und Auswahl
in beiden Ausgabearten, gleiche sichtbare Namen bei verschiedenen IDs,
Alle/Umkehren/keine Reihe, Legendenanker, Reorder/Entfernung/Resize und
unabhängigen Zeitzoom. Weitere Regressionen sichern komprimierte Starts,
fehlende/defekte gzip-Unterstützung, neuere Vollzustände, Unload und während
der Dekomprimierung eintreffende Append-Punkte ab. Graph-/Z2M-Layouttests
sichern die extrahierte gemeinsame Startlogik zusätzlich ab.

`node tests/time_series_browser.mjs` startet eine lokale synthetische
Canvas-Fixture auf Port 8816. Am 19.10.2026 wurden 32 Reihen mit 32000 Punkten
im schmalen Kachel- und breiten IPSView-Ausgabeweg visuell geprüft, einschließlich
gleichnamiger Legendenwahl und erhaltener Auswahl/Zoom nach Umsortierung.
Die neue Live-Abnahme in Symcon und IPSView nach Modulupdate steht noch aus;
Live-Konfigurationen und Archive wurden bei dieser Erweiterung nicht geändert.

## Calendar

`calendar_data.php` prüft Jahresgrenzen, Schaltjahre, Zeitumstellungen,
abgeschlossene Tage und die Ablehnung ungültiger Tagesdaten. Die im
Tagesverlauf beginnende Aufzeichnung wird als gültiges Tagesaggregat
beibehalten; doppelte lokale Datumswerte bleiben unzulässig. Die im
Gateway-Integrationstest eingebundene `calendar_integration.php` prüft den
realen Gateway-Datenweg mit Kernel-Doppeln, negative/Nullwerte, Lücken,
Zählerablehnung, schreibfreie Vorschauen, Designkopie, getrennte Ausgabewege,
WebContent und das 1-MB-Limit. `calendar_layout.js` prüft Ausrichtung,
Kopfbereich, Titel, Farben, Labelanpassung, echte ECharts-Geometrie und die
lesende Jahresnavigation.
Die Designerweiterung sichert feste Farbgrenzen, umgekehrte Farben,
Monatstrennlinien, Zellstile, Positions-/Formatoptionen und alle vier
Jahreszahl-Positionen mit dem realen ECharts-Renderer ab. Die Modultests
prüfen unabhängige Vorschauen, Float-Grenzen beim Kopieren und die
Validierung beider Designer.

Mit `node tests/calendar_browser.mjs` startet eine rein lokale Fixture unter
`http://127.0.0.1:8796` mit synthetischen Jahresdaten und der ausgelieferten
Calendar-Runtime. Am 16.10.2026 wurden hohe native und breite IPSView-Layouts,
dunkler Hintergrund, Titel/Kopfbereich, Farbskala und Jahresnavigation im
Browser geprüft. Das ersetzt keinen Live-Test in Symcon oder IPSView.

Die Suite belegt die PHP-seitigen Verträge und den Datenweg nur gegen lokale
Symcon-Test-Doppel. Sie ersetzt keinen Laufzeittest in Symcon und keinen
visuellen Browsertest. Insbesondere die tatsächliche native Kacheldarstellung,
Größenwechsel in den Symcon-Clients und IPSView bleiben auf der erreichbaren
Testebene offen.

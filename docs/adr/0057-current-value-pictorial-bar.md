# ADR 0057: PictorialBar mit aktuellen Variablenwerten

- Status: Angenommen
- Datum: 2026-10-17
- Ergänzt: ADR 0001, ADR 0002, ADR 0005, ADR 0017, ADR 0036 und ADR 0037

## Entscheidung

`EChartsPictorialBar` erhält Präfix `ECPB` (Pictorial Bar) und GUID `{CF6B1F8C-7219-4D59-94EA-19CF7570637F}`.
Es ist eine eigene Diagrammfamilie, kein Modus von Category Bar oder Polar Bar.
1–16 manuell gewählte numerische Quellen derselben Einheit werden über ihre
IDs identifiziert. Labels und Farben sind optional. Doppelte Namen sind
zulässig, doppelte IDs nicht. Alle Werte müssen endlich und nichtnegativ
sein; Null bleibt ein gültiger sichtbarer Wert.

## Daten und Wiederverwendung

Funnel belegt denselben aktuellen Quellenvertrag. `EChartsCurrentSources`,
`EChartsSourceIdentity` und `EChartsVariablePresentation` übernehmen unverändert
Gateway-Zugriff, Referenzen, VM_UPDATE-Abonnements, Identität und Formatierung.
Formular-, SVG-, Theme-, Animations- und IPSView-Bausteine werden wie bei den
anderen Familien verwendet. Geometrie, Vorschau und Validierung bleiben in
der neuen Familie. Es gibt keine Archive, Aggregate, Skriptausführung oder
Zustandsbewertung und keine Änderung bestehender Quellwerte.

Die Listenreihenfolge kann beibehalten oder stabil nach Wert sortiert werden.
Jede Quelle behält dabei ihre Identität und Farbe. Ein gemeinsames Maximum
von 0 bedeutet automatische Skalierung; positive Maxima sind eine rein
optische Begrenzung. Oberhalb davon bleibt der Originalwert in Modell,
Wertbeschriftung und Tooltip erhalten. Nullwerte zeichnen kein Symbol,
behalten aber Kategorie und Wertbeschriftung. Bei ausschließlich Nullen
verwendet die Skala 1 als technische Obergrenze.

## Darstellung und Ressourcen

Die erste Version bietet rect, roundRect, circle, diamond und triangle.
Externe Bilder, freie Pfade und SVG-Importe benötigen einen eigenen
abgesicherten Vertrag und sind hier ausgeschlossen. Ein Symbol kann an die
Balkengeometrie angepasst oder entlang der Werteachse wiederholt werden.
Wiederholungen verwenden ein gemeinsames BoundingData-Maximum und Clipping.
Die Zahl der Symbole ist eine proportionale Darstellung und bedeutet nicht
„ein Symbol = eine Einheit“. Bis zu 128 Symbole je Quelle begrenzen die
Zeichenlast; bei großen Ansichten vergrößert sich nötigenfalls die Symbolhöhe.

Die Ausgabe verwendet den unveränderten gepinnten Apache ECharts 6.1.0 mit
PictorialBarChart, Grid, Title, Tooltip, Aria und CanvasRenderer als separates
Subset mit LabelLayout für den Beschriftungsschutz. Andere Runtimes und das Gateway werden nicht vergrößert.
Die nativen Kacheln schützen den 58-px-Kopfbereich; Diagrammtitel stehen
darunter. Beide Designer erhalten eigene Live-Vorschauen und Vererbung.
Vorschauen lesen höchstens 16 aktuelle Werte; ungültige Entwürfe erzeugen
keine erfundenen Ersatzwerte. Nur die leere Konfiguration zeigt Beispiele.

## Prüfung

### Erweiterung auf 100 Quellen vom 18.10.2026

PictorialBar erlaubt auf ausdrücklichen Auftrag 1–100 Quellen; die frühere
Obergrenze 16 ist abgelöst. Der gemeinsame CurrentComparisonSources-Resolver
erhält einen expliziten Maximalparameter, dessen Default 16 bleibt. Nur
PictorialBar wählt 100, Funnel wird nicht beiläufig erweitert. Namen, GUID,
Properties, Quellenidentität, Einheiten-/Vorzeichenvertrag und Archivfreiheit
bleiben unverändert; eine Migration gespeicherter Quellen ist nicht nötig.

Über 16 Quellen verwendet der Renderer native Slider-/Inside-DataZoom-
Komponenten auf der Kategorienachse. Der Ausschnitt enthält abhängig von
Platz und Schrift höchstens 16 Werte. Das vollständige Modell mit bis zu
100 IDs bleibt erhalten; Skala und Farbbereiche ändern sich beim Scrollen nicht.
Vor Neuaufbau bei Wertaktualisierung/Resize wird der sichtbare Anfang anhand
der Quell-ID gesichert. Gradient-/Icon-/SVG-Werttexte werden an der tatsächlichen
Zeichenzeile statt am vollständigen Rohindex positioniert.

Die SVG-Vorschau validiert/liest alle bis zu 100 Quellen, zeigt bei großen
Listen aber ausdrücklich die ersten acht mit Gesamtzahlhinweis. Das schützt
die Formgröße auch bei komplexen Formen und verhindert Ersatz-Beispieldaten.
Die gepinnte Runtime bleibt ECharts 6.1.0/Apache-2.0; nur das PictorialBar-Subset
ergänzt DataZoom. Herkunft, Integrität und Größe werden neu dokumentiert und
geprüft. Output-/SVG-Budgets und Wiederholungslimits bleiben aktiv.

Regressionen prüfen 72/100/101 Quellen, alle Referenzen, beide Ausgaben und
Vorschauen unter 1 MiB, große SVGs mit Verläufen, unveränderte Funnel-Grenze,
native Navigation bis zur letzten Kategorie sowie Fenstererhaltung bei Updates.
Die historische Entscheidung oben beschreibt weiterhin den Ausgangsstand.

### Designer-Ergänzung vom 17.10.2026

Die offiziellen PictorialBar-, Label- und ItemStyle-Optionen wurden erneut
gegen die unverändert gepinnte Engine geprüft. Ergänzt werden lokale
Schriftfamilien, Werteposition/-abstand/-gewicht, Überlappungsschutz,
Kategorienrotation, Achsenmarkierungen/-werte, Rasterbreite/-linienart/-deckkraft,
Rahmenlinienart, Schatten und abschaltbare Hervorhebung mit Fokus/Blur.

Ein familienbezogener Farbmodus interpoliert RGB linear über drei Farben
bei Minimum, Mitte und Maximum eines expliziten nichtnegativen Float-Bereichs.
Der Bereich ist unabhängig von der geometrischen Skala. Werte außerhalb
erhalten Randfarben, Originalwerte und gespeicherte Quellfarben bleiben erhalten.
Der neue Modus übersteuert Quellfarben nur bei der Ausgabe. Standard bleibt
`category`, ohne Migration oder Änderung bestehender Properties. Alle neuen
Properties werden symmetrisch für beide Designer registriert und kopiert.

Der Modus `value` färbt weiterhin jeden Balken einheitlich. Freie Bilder/SVG,
Texturen, Callback-Code, Marker und zusätzliche Koordinatensysteme bleiben
außerhalb dieses Vertrags. Die Runtime und das Gateway bleiben unverändert.

### Skalenbezogener räumlicher Verlauf vom 17.10.2026

Auf ausdrücklichen Anwenderwunsch ergänzt `ColorMode=gradient` einen räumlichen
Verlauf; `category`, `value`, ihre Defaults und alle gespeicherten Properties
bleiben unverändert. Minimum und Maximum beziehen sich immer auf den festen
Farbbereich, nicht auf den aktuellen Balkenendwert oder die aktuelle Verteilung.
Bei 0–100 % und 16 % wird nur der entsprechende Anfang sichtbar; bei 0–255 LQI
liegt die mittlere Farbe bei 127,5. Randfarben gelten außerhalb der Grenzen.

Bei wiederholten Symbolen erzeugt der Browser aus den fünf bereits zugelassenen
Grundformen einen begrenzten zusammengesetzten `path://`-Symbolpfad. Dessen
unsichtbare Null-Linien fixieren den vollständigen Koordinatenrahmen. Alle
Teilformen teilen dadurch einen ECharts-Lineargradienten; BoundingData und
Clipping begrenzen die sichtbare Länge am Originalmesswert beziehungsweise
Zeichenmaximum. Die vier Wertepositionen werden über den öffentlichen
`labelLayout`-Callback am tatsächlichen Balkenende ausgerichtet, nicht an den
vollen Pfadgrenzen. Einzelformen behalten ihre wertabhängige Geometrie und
erhalten den auf diesen sichtbaren Ausschnitt begrenzten Farbverlauf.

Die Pfade bestehen ausschließlich aus intern erzeugten endlichen Koordinaten
mit höchstens 128 Teilformen. Benutzereingaben für Pfade, externe Bilder,
zusätzliche Ressourcen oder Änderungen an ECharts sind dafür nicht erforderlich.
ECharts-Gradienten verwenden normierte Endpunkte und nur die relevanten
Farbgrenzen im sichtbaren Bereich, um extreme Gradientkoordinaten zu vermeiden.
Die PHP-Vorschau zeichnet direkt in einem gemeinsamen SVG-Koordinatensystem
und clippt wiederholte Formen am aktuellen Wert, statt Verläufe in Patterns
zu wiederholen. Beide Designer, Vererbung und Kopieren verwenden denselben Modus.

Grundlage ist das offizielle [Symbol-/Pfadformat](https://github.com/apache/echarts-doc/blob/master/en/option/partial/icon.md).

Grundlagen: [offizielle PictorialBar-Dokumentation](https://github.com/apache/echarts-doc/blob/master/en/option/series/pictorialBar.md)
und [ItemStyle](https://github.com/apache/echarts-doc/blob/master/en/option/partial/item-style.md).

Vertrags- und Gateway-Integration prüfen positive/Null-/negative/ungültige
Werte, Quellidentität, Einheiten, Abonnements, Aktualisierung, Fehlererholung,
Designvererbung und Output-Limit. Renderer-Tests verwenden die echte
ECharts-Engine für Formen, Wiederholung, beide Ausrichtungen, Nullwerte,
Sortierung und optische Begrenzung. Lokale Browserprüfungen verwenden
synthetische Daten. Die nachfolgende Live-Abnahme in Kachel und IPSView ist durch den Eigentümer
bestätigt.

### Installierte Symcon-Icons vom 17.10.2026

`Symbol=icon` und `SymbolIcon` ergänzen die bestehenden Formen ohne Migration
oder Änderung der Defaults. Beide Designer registrieren und kopieren den
Iconnamen als String. Das dokumentierte native Formularfeld `SelectIcon`
liefert die grafische Auswahl, keine eigene duplizierte Icon-Galerie.

Das öffentliche PHP-SDK liefert dafür keine SVG-Pfaddaten. Der lokale,
familienbezogene Adapter `PictorialBarIcon` liest deshalb ausschließlich
`console/icons.js` unter `IPS_GetKernelDirEx()`, ohne JavaScript auszuführen.
Das ist ausdrücklich ein **interner Ressourcenformat-Adapter**, kein stabiler
SDK-Vertrag. Nach Symcon-Änderungen oder auf abweichenden Installationen kann
er eine Anpassung benötigen; fehlende oder inkompatible Ressourcen führen zu
einem erklärten Konfigurationsfehler, nicht zu erfundenen Ersatzformen.
Die Standardformen benötigen diese Ressource nicht.

Die Prüfung verwendet die aktuelle lokal installierte Bibliothek (Light,
Brands und Symcon-Kit). Minifier-Variablennamen werden aus Pack-Registrierungen
ermittelt, nicht festgeschrieben. Begrenzte JSON-Tupel werden ausgewertet:
maximal 8 MiB Bibliothek, 128 KiB Datensatz, Verschachtelung 8, 32 Teilpfade,
16 KiB kombinierter Pfad und Dimensionen 1–16384. Der vorhandene
`EChartsSvgPath`-Validator prüft die extrahierten Pfade. Ein begrenzter
Anfragecache hält nur die installierte Ressource und bis zu 32 Ergebnisse.
URL-, Datei-, CSS- und freie SVG-Eingaben sind nicht zugelassen.

Nur der gewählte Pfad und seine Dimensionen gelangen ins Modell. Die
komplette Bibliothek, kommerzielle Iconbestände oder Fonts werden weder ins
Repository kopiert noch mit dem Modul ausgeliefert. ECharts und Gateway
bleiben unverändert. Zur Laufzeit besteht keine CDN- oder Font-Abhängigkeit.

Wiederholte Icons werden proportional in ihre Glyphenbox eingepasst und
als begrenzter Compound-Pfad gezeichnet. Einzelformen passen sich wie bisher
an die Balkengeometrie an. Alle Farbmodi bleiben verfügbar. Maximal 64
Icon-Wiederholungen und ein konservatives 256-KiB-Pfadbudget begrenzen die
Zeichenlast; komplexe Icons ergeben weniger, größere Wiederholungen.
Werte-Clipping, Rohwerte und Labelpositionen bleiben unverändert. Die
SVG-Vorschau referenziert die einmal eingebettete Form intern mit `use`.
Inverse Paint-Transformationen halten den Verlauf in Diagrammkoordinaten.

Grundlagen: [SelectIcon](https://www.symcon.de/en/service/documentation/developer-area/sdk-tools/sdk-php/configuration-forms/selecticon/),
[HTML-SDK](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/html-sdk/),
[Symcon-Icons](https://www.symcon.de/de/service/dokumentation/komponenten/icons/).
Der interne Adapter wurde lesend an der vorhandenen Windows-Installation
geprüft; andere Installationslayouts und Symcon-Versionen bleiben Testlücken.
Die native Iconauswahl und die neue Live-Kachel-/IPSView-Ausgabe wurden nach
dem Modulupdate durch den Eigentümer bestätigt.

### Eigene SVG-Pfadformen vom 17.10.2026

`Symbol=svg` und die String-Property `CustomSymbolSVG` ergänzen beide
Designer ohne Änderung der bestehenden Defaults oder Konfigurationen.
Das native `SelectFile` speichert den Dateiinhalt gemäß SDK als Base64.
Entwürfe aktualisieren ausschließlich die Vorschauen. Vererbung und
Designkopie folgen dem vorhandenen Vertrag; ungenutzte Dateien werden nicht validiert.

Der familienbezogene Adapter `PictorialBarSvg` verwendet `EChartsSvgPath::Import`
für Uploadformat, Dateigrenzen, erlaubte Elemente und Sicherheitsprüfung.
Anschließend prüft DOM mit deaktiviertem Netzwerk die XML-Struktur. Es gibt
weder Dateipfad-/URL-Lesen noch Skriptausführung oder Rückgabe des Original-Markups.
Die zusätzliche Pfadgrammatikprüfung validiert Argumentgruppen aller
SVG-Pfadbefehle einschließlich kompakter Arc-Flags. Jeder Teilpfad erhält
einen absoluten initialen Move, damit unabhängige relative Ursprünge beim
Zusammenfügen erhalten bleiben. Die gemeinsam verwendeten Validatoren und
die ECharts-Runtime bleiben unverändert.

Es gelten 128 KiB dekodierte Datei, 192 KiB Uploadhülle, 32 Pfade und
16 KiB normalisierte Pfaddaten. Absolute Koordinatenbeträge sind auf 10⁶,
positive viewBox-Dimensionen auf 10⁻⁶ bis 10⁶ begrenzt. Abgewiesene Uploads
ergeben eine feste lokalisierte Meldung und Konfigurationsstatus 202;
Details aus fremden Dateien werden nicht in Fehlermeldungen gespiegelt.
Der Vertrag ist eine gefüllte Nichtnull-Pfadsilhouette, kein vollständiger
SVG-Bildrenderer. Eingebettete Farben, Konturlinien, CSS und Layoutattribute
werden nicht übernommen. Inhalte müssen für diesen Vertrag aufbereitet sein.

Nur Pfad und numerische viewBox-Geometrie erreichen das Modell; die neue
Datei-Property wird ausdrücklich aus dem Transportstil ausgeschlossen.
Icons und eigene SVGs teilen sich Compound-Pfad, Clipping, alle Farbmodi und
die Vorschau mit internem `use`. Zusätzliche Ursprungskoordinaten halten die
Geometrie auch bei nichtnulliger viewBox deckungsgleich. Ressourcenlimit,
proportionale Wiederholung und gestreckte Einzelform bleiben wie bei Icons.

Nachweise umfassen Uploadformate, XML-/Pfadfehler, abgelehnte aktive Inhalte,
Größenlimits, unabhängige relative Teilpfade, beide Designer, schreibfreie
Entwürfe, Kopie/Vererbung, Rohdaten-Ausschluss und das 1-MiB-Ausgabelimit.
Die echte ECharts-Engine prüft beide Orientierungen, Ausgabearten, Symbolmodi
und Farbmodi; verschobene Ursprünge werden mit unabhängig nullbasierten
Geometrien verglichen. Browserprüfungen mit eigener synthetischer SVG-Form
ergänzen den Nachweis. Native Dateiauswahl, neue Live-Kachel und IPSView
bleiben nach dem Modulupdate durch den Eigentümer zu prüfen.

Grundlage: [Symcon SelectFile](https://www.symcon.de/en/service/documentation/developer-area/sdk-tools/sdk-php/configuration-forms/selectfile/).

## Anpassbare Bezeichnungsspalte vom 18.10.2026

`LabelColumnWidthPercent` und `IPSViewLabelColumnWidthPercent` ergänzen die
Designer additiv mit Default 0 (automatisch). Im horizontalen Layout wird die
Spalte anhand aller Bezeichnungen und der effektiven Schrift gemessen, nicht
anhand des sichtbaren DataZoom-Ausschnitts. Automatisch werden höchstens 60,
manuell bis 80 Prozent der
Diagrammbreite möglich. Die verbleibende Wertefläche und deren Text-/Navigations-
Reserve begrenzen die Spalte in schmalen Ansichten. Vertikale Layouts und
Quellwerte bleiben unverändert; eine Migration vorhandener Properties ist
nicht nötig. Unabhängige Ausgabe, Vererbung, Designkopie und SVG-Entwurf
verwenden denselben Property-Vertrag. Der Browser misst Text mit Canvas,
die schematische PHP-Vorschau approximiert anhand von Zeichenbreite und Schriftgröße.

## Grundlagen

Am 17.10.2026 wurden die offiziellen Einstiege
[Startseite](https://echarts.apache.org/en/index.html),
[llms.txt](https://echarts.apache.org/en/llms.txt) und
[PictorialBar](https://echarts.apache.org/en/option.html#series-pictorialBar)
sowie die unveränderte npm-Runtime geprüft. Lizenz und Herkunft bleiben
Apache-2.0 gemäß THIRD_PARTY_NOTICES.md.

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
synthetische Daten. Live-Kachel und IPSView bleiben nach dem Modulupdate
durch den Eigentümer separat abzunehmen.

## Grundlagen

Am 17.10.2026 wurden die offiziellen Einstiege
[Startseite](https://echarts.apache.org/en/index.html),
[llms.txt](https://echarts.apache.org/en/llms.txt) und
[PictorialBar](https://echarts.apache.org/en/option.html#series-pictorialBar)
sowie die unveränderte npm-Runtime geprüft. Lizenz und Herkunft bleiben
Apache-2.0 gemäß THIRD_PARTY_NOTICES.md.

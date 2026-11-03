# ADR 0059: Zustandszeitdiagramm aus rohen Archivwechseln

- Status: Angenommen
- Datum: 2026-10-17
- Entscheider: Burki24
- Ergänzt: ADR 0001, 0002, 0005, 0017, 0036 und 0037

## Vertrag und Abgrenzung

`EChartsStateTimeline`, Präfix `ECST`, GUID
`{581AD0EF-4E14-4796-902B-08381E299232}`, zeigt ein bis acht unterschiedliche
Boolean-/Integer-Archivvariablen als Zeilen. Die Variablen-ID ist die Identität;
gleiche Anzeigenamen sind erlaubt. Pro Quelle lassen sich bis zu 32 ganzzahlige
Zustände mit Beschriftung und Farbe zuordnen. Unzugeordnete Werte bleiben als
Zahlen sichtbar; Boolean wird technisch auf 0/1 normalisiert. Float-, String-
und Zählerarchive sind ausgeschlossen. Das Modul beobachtet nur und schreibt
weder Variablen noch Archive oder Originalinstanzen.

`Range` wählt 24 Stunden, sieben oder 30 verstrichene Tage bis zum aktuellen
Abfragezeitpunkt. Ein Minutentimer aktualisiert die Ansicht. Pro Quelle werden
höchstens 2000 Rohpunkte und ein Vorgänger vor dem Fenster gelesen. Der neue
additive Protokoll-v1-Aufruf `archive.states.read` ist generische
Gateway-Infrastruktur für diskrete Roharchive mit Vorgänger, nicht für
Diagrammfarben oder Zustandsbeschriftungen. `archive.read` und alle bestehenden
Operationen bleiben unverändert. Anfrage: `VariableID`, `StartTimestamp`,
`EndTimestamp`, `Limit`. Antwort: VariableID/-Type, ArchiveAggregationType,
Start-/EndTimestamp, aufsteigende `Points`, `PreviousPoint` oder null sowie
`Truncated`. Booleanwerte kommen als ganze 0/1 zurück. Bestehende begrenzte
Gateway-Cachelogik darf mit getrenntem Operationsschlüssel wiederverwendet werden.

## Zeitsemantik und Grenzen

Abschnitte sind halboffen `[Beginn, Ende)` und werden am sichtbaren Fenster
abgeschnitten. Der letzte bekannte Zustand gilt bis zum nächsten Wechsel oder
Fensterende. Das ist eine diskrete Zustandsfortschreibung, keine numerische
Interpolation. Gleiche aufeinanderfolgende Werte werden zusammengefasst.
Ein Punkt exakt am rechten Rand erzeugt keinen Abschnitt. Ein Vorgänger
initialisiert den Zustand nur vor dem Fenster; ein Punkt am linken Rand hat
Vorrang. Fehlt beides, bleibt der Anfang bis zur ersten Messung ausdrücklich
unbekannt. Eine vollständig leere Quelle bleibt vollständig unbekannt.

Archive können ausgeschaltete Aufzeichnung, verlorene Zwischenwerte oder
gelöschte Abschnitte nicht zuverlässig als Ausfall kennzeichnen. Die Ausgabe
behauptet deshalb keinen lückenlosen Gerätenachweis. Bekannte Zustände werden
weitergeführt, bis ein protokollierter Wechsel vorliegt; unbekannte Anfänge
werden nicht durch den aktuellen Variablenwert ersetzt. Gekürzte Antworten,
widersprüchliche Doppelzeitstempel und ungültige Daten führen zu einem
sichtbaren Fehler statt zu erfundenen langen Abschnitten. Automatische
Aggregation, Sampling und unbegrenztes Nachladen sind ausgeschlossen.

Zusätzlich begrenzen 250000 Bytes HTML-sicher kodiertes Diagramm-JSON den
gesamten Bootstrap. Die Größe wird nach dem vorhandenen HTML-JSON-Encoder
gemessen, nicht vor der Expansion von Zeichen wie `&`. Ein Überschreiten
erzeugt dieselbe Aufforderung zu einem kürzeren Zeitraum, keine gekürzte
Darstellung. Die vollständige Seite bleibt einschließlich gepinnter Runtime
und offizieller Themes unter dem Symcon-Ausgabepuffer von 1 MiB.

## Wiederverwendung, Ausgabe und Nachweise

Verglichen wurden ThemeRiver, Candlestick und TimeSeries. Wiederverwendet
werden Gateway-Parent, Quellnamenauflösung, Animation, Themes, Formular-
Vorschauverdrahtung, IPSView-Vererbung, Hintergrund, Titelabstände, Zoom und
persistenter Transport. Fachliche Intervallbildung und Zustandszuordnung
bleiben im neuen Modul. Synchronisierte Helper werden nicht lokal verändert.

Ein eigener reproduzierbarer Build der unveränderten ECharts-6.1.0-Runtime
enthält CustomChart und die benötigten Komponenten. Native `renderItem`-
Rechtecke werden auf den Diagrammbereich begrenzt. Es werden weder externe
Formatter noch JavaScript-Importe oder neue Laufzeit-CDNs ermöglicht.
Die Zustandsnamen und Tooltiptexte werden escaped. Schriftgrößen/-farben,
Zeilenhöhe, Deckkraft, Kontur, Raster, Labels, Tooltip, Zoom und Animation
besitzen getrennte Designer für Kachel und IPSView. Historische Vorschauen
verwenden gekennzeichnete synthetische Daten ohne Archivzugriff.

Prüfungen umfassen Datenfenster und Zeitzonen, Vorgänger, unbekannte Anfänge,
Werteidentität, doppelte Namen, Limit-/Fehlerfälle, Gateway, beide Designer,
Vorschauentwürfe ohne Persistenz, Transport, Ausgabegrößen und tatsächliche
ECharts-Geometrie. Lokale Browserprüfungen ersetzen keine Live-Abnahme nach
Modulupdate in Symcon und IPSView. Die anschließende funktionale Abnahme
ist durch den Eigentümer für beide Ausgaben bestätigt.

## Erweiterte Gestaltung (18.10.2026)

Der Abgleich erfolgt mit der offiziellen
[Custom-Serien-Dokumentation](https://echarts.apache.org/en/option.html#series-custom),
den [grafischen Elementen](https://echarts.apache.org/en/option.html#graphic)
und [Zeitachsen](https://echarts.apache.org/en/option.html#xAxis).
Die gepinnte ECharts-Runtime bleibt unverändert. Properties und Validierung
bleiben im Gerät, gemeinsame Vorschau-/Designer-/Animationshelfer werden
wiederverwendet. Es entstehen keine neuen Gateway-Aufgaben.

Additiv kommen Konturart, begrenzte Rechteckrundung, innere Textausrichtung,
Schriftfamilie und getrennte Fett-/Kursivstile hinzu. Zeit- und Quellenlabels,
Achsenlinie und Ticks sind separat schaltbar; Format, Labelabstand,
Linienart/-breite sowie Rasterfarbe/-deckkraft/-kontur sind einstellbar.
Custom-Grafiken erhalten die Eigenschaften ausdrücklich in `renderItem`;
generische Serien-Labels allein würden hier nicht wirken. Hervorhebung und
optionaler Selbstfokus gelten pro Abschnitt, nicht für die gesamte Custom-Serie.
Die Restdeckkraft ist nur bei aktivem Fokus relevant.

Alle Felder gelten ebenso für unabhängiges IPSView, Vererbung, Kopieren und
Entwurfsvorschauen. Statische SVGs bilden die optischen Einstellungen ab,
nicht Mausinteraktion oder Animation. Bestehende Defaults sowie Quellen,
Zeitgrenzen, Archivvertrag, Zustandsidentitäten und Farben bleiben erhalten.
Frei ausführbare Formatter, externe Schriften und eine irreführende gemeinsame
Zustandslegende für unterschiedliche Quellen sind nicht Teil dieser Erweiterung.

## 32 Quellen und unabhängige Zeilennavigation (18.10.2026)

Die Quellenobergrenze steigt additiv von acht auf 32. Variablen-ID,
Zustandsmodell, unbekannte Anfänge, Properties und bisherige Konfigurationen
bleiben erhalten. Bei mehr als acht Quellen ergänzt eine native vertikale
DataZoom-Leiste einen responsiven Ausschnitt von höchstens acht Zeilen.
Zeitzoom und Zeilenausschnitt werden getrennt erfasst und wiederhergestellt;
der Zeilenanker folgt der Variablen-ID, nicht Namen oder Arraypositionen.
Die Zeilenleiste bleibt ohne Zeitzoom verfügbar. Beide Ausgaben verwenden
denselben Renderer; die statische Vorschau zeigt ausdrücklich die ersten
acht von insgesamt bis zu 32 Quellen mit synthetischen Zuständen.

Die vorhandene Custom/DataZoom-Runtime genügt; ein neuer Build ist nicht
erforderlich. Pro Quelle bleiben höchstens 2000 Rohpunkte plus Vorgänger
zulässig. Der gemeinsame Abfragehaushalt beträgt weiterhin maximal die
bisherige Acht-Quellen-Kapazität von 16000 Rohpunkten, nun über bis zu 32
Anfragen verteilt. Ein kleineres verbleibendes Budget wird als begrenztes
Limit angefragt; volle/gemeldete gekürzte Archive oder ein erschöpfter
Haushalt führen zu einem sichtbaren Fehler, nicht Sampling. Die Größe
HTML-sicher kodierter Zeilen wird bereits während des Aufbaus begrenzt;
der abschließende 250000-Byte-Modellvertrag und der 1-MiB-Seitenpuffer bleiben.

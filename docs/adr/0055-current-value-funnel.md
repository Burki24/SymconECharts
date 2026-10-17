# ADR 0055: Funnel mit aktuellen Variablenwerten

- Status: Angenommen
- Datum: 2026-10-17
- Entscheider: Burki24
- Ergänzt: ADR 0001, 0002, 0005, 0017, 0036 und 0037

`EChartsFunnel` erhält die GUID `{491282D5-3E26-4601-AEA7-ABD7404642B2}`
und das Präfix `ECFU`. 1–16 unterschiedliche numerische Variablen derselben
effektiven Einheit bilden manuell benannte Stufen. Anzeigename und Farbe sind
optional; technische Identität und Updates bleiben an die Variablen-ID gebunden.

Aktuelle Werte kommen über das bestehende `current.read`. Negative, nichtendliche
oder fehlende Werte werden nicht als Null ersetzt. Null ist zulässig; bei nur
Nullwerten erscheint ein Leerhinweis. Es werden weder Archive noch Gerätewerte
geändert. Breiten beziehen sich linear auf den größten aktuellen Wert, nicht auf
die Summe. Gleiche Stufenhöhen und die trapezförmige Verbindung sind die
Funnel-Geometrie von ECharts; Flächen sind keine Anteilsberechnung. Prozentwerte
und eine monotone Prozess- oder Konversionssemantik werden nicht unterstellt.

Standard ist die konfigurierte Listenreihenfolge. Optional wird stabil auf- oder
absteigend nach Wert sortiert; Farben bleiben an der Quelle. Ausrichtung,
Ausrichtung am Rand, Stufenabstand, Beschriftungen, Schriftgrößen/Farben,
Rahmen, Deckkraft, Tooltip und Animation sind je Ausgabe unabhängig gestaltbar.
Die sortierte Liste wird mit `sort: none` an ECharts übergeben, damit Reihenfolge
und Polygonverbindungen in Vorschau und Ausgabe denselben Vertrag besitzen.

Wiederverwendung vor Neuentwicklung: `EChartsCurrentSources` übernimmt Gateway-
Lesen sowie Referenzen und VM_UPDATE-Abonnements. Vorhandene Komponenten für
Variablenpräsentation, Identität, Themes, Animation, Vorschauverdrahtung,
Designerabschnitte, IPSView-Transport, Hintergrund und Fehlerwiederholung bleiben
kanonisch. Familiengeometrie und Validierung liegen im Gerätemodul; keine neue
Gateway-Operation und keine Änderung synchronisierter Helper.

Die statische SVG-Vorschau liest bei gültigen Quellen begrenzt deren aktuelle
Werte, niemals Gateway oder Archive. Ohne Quellen zeigt sie ausdrücklich
Beispieldaten, ungültige Quellen/Werte einen Hinweis. Ungespeicherte Änderungen
bleiben Vorschauzustand und ändern weder Properties noch Status.

Die neue SDK-Dokumentation über MCP wurde für Listenspeicherung, Referenzen
und Nachrichtenabonnements geprüft. Der lokale, separat gebaute Funnel-Subset
bleibt auf ECharts 6.1.0 mit Lizenz und Integritätsprüfung festgeschrieben.
Grundlage: [offizielle Funnel-Serie](https://echarts.apache.org/en/option.html#series-funnel)
und der mitgelieferte unveränderte `funnelLayout`-Quelltext der gepinnten Runtime.

Prüfziel: Kernel-Doppel mit echtem Gateway, Grenzen und Fehlerfälle, Formulare,
Dokumentbudget, Vorschau ohne Seiteneffekte, reale ECharts-Polygongeometrie und
lokale Canvas-Browserfixture. Neue reale MCP-Kachelvorschau und Live-Tests in
Symcon 9.0/9.1 beziehungsweise IPSView benötigen Installation und eine Instanz;
sie werden nicht durch lokale Tests ersetzt.

## Lokale Nachweise vom 17.10.2026

- `php tests/quality.php --fix`: Formatierung, Syntax, Struktur (25 Module),
  öffentliche Verträge, Integrationssuite, Assets, Helper-Integrität und Layouttests.
- `node tests/funnel_layout.js`: reale ECharts-Polygonpunkte in beiden
  Ausrichtungen und drei Reihenfolgen, drei Querausrichtungen und bei
  100/350/900 Pixel Breite; direkter Vergleich mit PHP-SVG-Punkten.
- `funnel_integration.php`: 16 Quellen unter dem Formular-/Dokumentlimit,
  gleiche Namen mit verschiedenen IDs, Einheiten, Null-/Negativwerte,
  unabhängige Designer, Live-Updates, Referenzentfernung, Fehlerwiederholung
  einschließlich Transportfehler nach fachlichem Fehler und deutsche Texte.
- Lokale Canvas-Browserfixture: hohe native Kachel mit Titel/Kopfbereich,
  breite horizontale IPSView-Ausgabe und Maximalzahl mit großen Abständen
  visuell kontrolliert. Bei zu wenig Platz werden Texte unterdrückt, nicht
  Werte oder Quellen entfernt.

Keine realen Instanzen oder Konfigurationen wurden geschrieben. Die
MCP-SDK-Abfragen sind lesende Entwicklungsnachweise, keine Bestätigung des
neuen Moduls in einer Symcon-/IPSView-Laufzeit.

## Designer-Ergänzung vom 17.10.2026

Der Anwender bestätigt die erste Version inzwischen in nativer Kachel und
IPSView. Die neue reale MCP-Kachelvorschau wurde vor dieser Erweiterung mit
installierter Instanz erfolgreich geprüft; dieser Nachweis gilt nicht
automatisch für die folgenden neuen Optionen.

Abgleich mit der offiziellen [Funnel-Serie](https://echarts.apache.org/en/option.html#series-funnel),
den [Item-Styles](https://github.com/apache/echarts-doc/blob/master/en/option/partial/item-style.md),
[Schatten](https://github.com/apache/echarts-doc/blob/master/en/option/partial/style-shadow-opacity.md)
und [Linien-Styles](https://github.com/apache/echarts-doc/blob/master/en/option/partial/line-style.md).
Alle Ergänzungen werden von der vorhandenen unveränderten 6.1.0-Runtime
unterstützt; kein Runtime-Neubau oder Abhängigkeitswechsel.

| Bereich | Ergänzungen |
| --- | --- |
| Größen | `minSize`, `maxSize` als Prozentwerte, Minimum nicht größer als Maximum |
| Stufen | `borderType`, `shadowBlur/Color/OffsetX/OffsetY` |
| Text | `fontFamily`, begrenzte Labelbreite, optionales `labelLayout.hideOverlap`, Außenbeschriftung an beiden Rändern |
| Führung | `labelLine.show/length/lineStyle.width/type/color/opacity` |
| Hover | `emphasis.focus/disabled`, optional explizite `blur.itemStyle.opacity` |

Alle 19 neuen Properties erhalten unabhängige IPSView-Pendants und werden
von den vorhandenen Schleifen für Registrierung, Kopie, Vererbung, Vorschau
und Modellserialisierung erfasst. Quellen, Sortierung, Farben nach ID,
Einheiten, aktuelle Werte, Transport und Titel-/Kachelkopfbereich bleiben
unverändert. Defaults entsprechen den bisherigen festen Optionen; -1 bei
Blur-Deckkraft lässt ECharts seine automatische Abdunklung beibehalten.
Eine positive optische Mindestgröße bedeutet ausdrücklich, dass auch eine
Nullquelle eine Breite erhält; Originalwert und Leerzustand bleiben erhalten.

SVG und Renderer verwenden dieselbe Größenabbildung und seitenabhängige
Platzreservierung. Statische Vorschauen bilden Schatten, Linien und Typografie
ab; Hover und Animation bleiben ausdrücklich Laufzeitverhalten. Kleine
Ausgaben dürfen Führungslängen und Textbreiten begrenzen. Versteckte
Überlappungen dürfen ausgeschaltet werden, Quellen werden nie entfernt.

Nicht enthalten: freie JavaScript-Formatter, Theme-/Gradientenimporte,
individuelle Stufenhöhen, Auswahlzustände, Dataset-Umbau und manuell
abschneidende Wertebereiche. Sie ändern Sicherheits-, Interaktions- oder
Datenverträge und werden nicht als bloße Stildeklarationen ergänzt.
Eine Beschriftungsdrehung wird nicht angeboten, da der gepinnte Funnel-
Renderer sie nicht allein durch die allgemeine Text-Property umsetzt.

Nachweise: Gateway-/Formular-/Budgettests, Validierung beider Designer,
Übernahme/Kopie aller Optionen, schreibfreie ungespeicherte Vorschau,
echte ECharts-Styles und Polygonpunkte sowie direkter PHP-SVG-Vergleich.
Der erneute Live-Test nach Installation bleibt für diese Erweiterung offen.

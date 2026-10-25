# ADR 0102: Optionale gerichtete Graph-Beziehungen

- Status: Angenommen
- Datum: 2026-10-24
- Entscheider: Burki24
- Ergänzt: ADR 0047 und ADR 0066

## Vertrag und Kompatibilität

`EChartsGraph` registriert `DirectedEdges` als Boolean mit Default false.
Dies ist eine fachliche Instanzeinstellung, kein unabhängiges Designfeld.
Alte Listen, IDs, Designer, Modus ohne Property, Statuscodes und lesende
60-Sekunden-Aktualisierung bleiben nutzbar. Es gibt keine Konfigurationsmigration
oder automatischen Änderungen vorhandener Kanten. Zurückschalten auf ungerichtet
weist vorhandene Gegenrichtungen als Dubletten ab statt sie zu löschen.

Im Standard gelten unverändert ungeordnete, eindeutige Knotenpaare. Gerichtete
Verbindungen folgen `SourceObjectID` nach `TargetObjectID`: Gegenrichtungen
sind verschiedene Beziehungen, dieselbe geordnete Verbindung bleibt ungültig.
Selbstkanten, unbekannte Endpunkte und doppelte Knoten bleiben unzulässig.
Isolierte Knoten, getrennte Komponenten und gerichtete Kreise sind erlaubt.
Dies ist kein Sankey-Flussmodell; Gewichte und Breitenskalen folgen separat.

## Gemeinsame Modellbildung und Ausgabe

`GraphModel::ValidateRows` und `Build` erhalten einen optionalen letzten
Boolean-Parameter. Bestehende Aufrufer bleiben ungerichtet. Das additive
Modellfeld `directed` und je gerichteter Kante `reciprocal` versorgen Live-Ausgaben
und beide SVGs. Gegenrichtungen werden anhand der vollständigen ID-Menge
ermittelt, nicht anhand von Namen oder eines Vorschauausschnitts.
Knoten-/Kantenreihenfolge, Originalwerte und Quellenidentitäten bleiben erhalten.

Beide Live-Adapter verwenden offizielle native Graph-Pfeile am Ziel,
HTML-escaped Richtungstooltips und eine ARIA-Kennzeichnung. Gegenrichtungen
erhalten getrennte Kurven. Eine einzelne Richtung bleibt gerade. Im Kreislayout
biegt die festgeschriebene ECharts-Runtime radiale Kurven auf dieselbe Seite;
bei antipodischen Endpunkten hilft auch ein Vorzeichenwechsel nicht. Daher
liefert ausschließlich das gerichtete Kreislayout explizite Kreispositionen
an die native Graph-Serie mit `layout=none` und erhaltener Kreisgeometrie.
Die native einfache Kantenprojektion trennt Gegenrichtungen auch bei zwei
Knoten. Ungerichtete Layouts bleiben unverändert. Gemeinsame Ansichtsverwaltung,
Beschriftungs-, Titel-, HTML-/IPSView-, Formular- und SVG-Helper werden
weiterverwendet; es entsteht kein neuer Gateway- oder Upstream-Helper-Vertrag.
SVG-Pfeile erben die jeweilige Kantenfarbe und enden vor dem Zielknoten.

GraphZ2M behält seine gerichteten Exportpaare, LQI-Reduktion, Auswahl und Budgets.
Es wird keine neue generische Topologieabstraktion für unterschiedliche
Dublettenregeln eingeführt. ECharts bleibt bei 6.1.0 ohne Runtime-Neubau.
Referenzen: [Graph-Optionen](https://echarts.apache.org/en/option.html#series-graph.edgeSymbol),
lokal festgeschriebene `GraphSeries`, `simpleLayoutHelper` und `circularLayoutHelper`
unter `.tools/echarts-runtime/node_modules/echarts/lib/chart/graph`.

## Schutzgrenzen und Nachweise

Unverändert 2–300 Knoten, 1–1000 Kanten, 64 KiB je gespeicherter Liste,
1 MiB je Symcon-Ausgabe sowie 40 Knoten/80 Kanten im echten Vorschauausschnitt.
Die bestehende vollständige komprimierte Startübertragung bleibt erhalten.

Modell- und Renderer-Tests begannen RED an bisher abgewiesenen Gegenrichtungen
und fehlenden Pfeilen. Tests prüfen beide Dublettenmodelle, Rückkehr zum Standard,
Zyklen, N/N+1, IDs, unabhängige Designs, echte Vorschauen, beide Ausgabeadapter
und die ausgelieferte native Runtime einschließlich geometrisch getrennter
Gegenrichtungen bei zwei Knoten. Lokale Browser- und Qualitätsnachweise stehen
im Testdokument. Die funktionale MCP-Prüfung ist bestanden; der Eigentümer bestätigt gerichtete
Beziehungen in Kachel und tatsächlichem IPSView. Bestehende Konfigurationen und
Designs wurden bei der MCP-Prüfung unverändert belassen. Ein laufender Live-Push
war in der statischen MCP-Vorschau separat nicht nachweisbar.

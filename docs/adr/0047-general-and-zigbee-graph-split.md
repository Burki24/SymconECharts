# ADR 0047: Allgemeinen Graph und Zigbee2MQTT-Topologie trennen

- Status: Angenommen
- Datum: 2026-10-14
- Entscheider: Burki24
- Ergänzt: ADR 0001, ADR 0017, ADR 0036 und ADR 0037

## Kontext

Eine frei vom Anwender festgelegte Verbindung zwischen Symcon-Objekten und
die von Zigbee2MQTT gemessene Mesh-Topologie besitzen unterschiedliche
Datenquellen, Identitäten und Aktualisierungsregeln. Der allgemeine Graph
benötigt keine Netzwerkanalyse; ein Zigbee-Scan soll umgekehrt nicht beim
Öffnen einer Diagrammkachel ausgelöst werden. Das verfügbare
Zigbee2MQTT-Network-Map-Modul kann die zuletzt gespeicherte RAW-Topologie
lesend mit Knoten, gerichteten Links, LQI und Aufnahmezeit exportieren.

## Entscheidung

`EChartsGraph` verwendet die Modul-GUID
`{BD3C8351-82EA-4B6B-BD82-1E034B7E68C6}` und das Präfix `ECGR`.
`ECGA` bleibt als früheres Gauge-Kürzel gesperrt. Die eigenständige spätere
Instanz `EChartsGraphZ2M` erhält das reservierte Präfix `ECGZ`; ihre
Modul-GUID und ihr detaillierter Vertrag werden erst bei ihrer Umsetzung
festgelegt.

Der allgemeine Graph speichert zwei Listen: 2–80 Knoten mit Symcon-Objekt-ID
und optionalem Anzeigenamen sowie 1–200 ungerichtete Kanten zwischen diesen
IDs. Kategorien, Instanzen und Variablen sind zulässige Knoten. Doppelte
Objekt-IDs, Selbstkanten, doppelte Kanten und unbekannte Endpunkte sind
ungültig. Objekt-IDs sind alleinige technische Identität; Namen und Pfade
sind Anzeigeinhalt. Variable Werte werden nur formatiert angezeigt. Die
Knoten und Kanten werden weder aus der Symcon-Elternhierarchie noch aus
Zigbee2MQTT automatisch abgeleitet.

Der Graph verwendet den bestehenden Gateway-Parent, die gemeinsame
HTML-/IPSView-Ausgabe, Theme-, Animations- und Vorschau-Bausteine. Ein
getrennter, lokal festgeschriebener ECharts-6.1.0-Build enthält Graph Chart,
Titel, Tooltip, Aria und Canvas. Kachel und IPSView besitzen getrennte
Designer mit Kräfte- oder Kreislayout, Pan/Zoom, Beschriftung und Farben.
Die Daten werden im normalen Betrieb mindestens alle 60 Sekunden lesend
erneuert; Graph-Objekte werden nicht verändert.

`EChartsGraphZ2M` soll später ausschließlich eine gespeicherte
Netzwerkkarten-Momentaufnahme lesen. Es startet keinen automatischen Scan.
Geräteidentität, gerichtete Links, Qualitätswerte und Zeitpunkt der
Momentaufnahme bleiben im Z2M-spezifischen Datenvertrag und werden nicht in
die manuellen Symcon-Objektlisten des allgemeinen Graphen gepresst.
Gemeinsamer Renderer-Code wird erst bei belegter semantischer Gleichheit
beider Graph-Module zentralisiert.

## Nachweise und Grenzen

Tests prüfen Knoten-/Kantenvalidierung, gleichnamige Objekte, Modell,
Renderer, Formularvererbung und beide HTML-Ausgabewege. Eine tatsächliche
Symcon-Kachel- und IPSView-Abnahme folgt nach dem Modulupdate und ist durch
Repositorytests nicht ersetzt. Der Z2M-Graph ist nicht Teil dieser
Implementierung.

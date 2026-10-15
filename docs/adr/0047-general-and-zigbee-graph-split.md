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
`ECGA` bleibt als früheres Gauge-Kürzel gesperrt. Die eigenständige Instanz
`EChartsGraphZ2M` verwendet das Präfix `ECGZ` und die Modul-GUID
`{A9C67877-1CF7-4E9E-9B17-C9AC2C200483}`.

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

`EChartsGraphZ2M` liest ausschließlich die zuletzt exportierte RAW-Topologie
der gewählten `Zigbee2MQTT Network Map`-Instanz. Es startet keinen Scan.
Die öffentliche RAW-Rückgabefunktion überschreitet mit großen Netzen das
Symcon-Ausgabelimit von 1 MiB. Deshalb liest das Modul die neueste, vom
Zigbee2MQTT-Modul serverseitig erstellte JSON-Exportdatei. Der gemeinsame
Exportordner ist nur bei genau einer Netzwerkkarteninstanz eindeutig; bei
mehreren wird die Konfiguration abgewiesen. Nach einem neuen Scan ist ein
erneuter Export nötig. Unvollständige und übergroße Dateien werden verworfen.
IEEE-Adressen identifizieren Geräte intern; gleichnamige Geräte bleiben
getrennt. Gerichtete Links, LQI und Aufnahmezeit bleiben im Z2M-spezifischen
Datenvertrag und werden nicht in die manuellen Symcon-Objektlisten des
allgemeinen Graphen gepresst. Doppelte gerichtete Paare werden mit dem
höchsten LQI zusammengeführt, die Gegenrichtung bleibt erhalten. Standardmäßig
werden die 300 stärksten Links angezeigt; Mindest-LQI (0–255) und Linkbudget
(1–2000) sind Instanzeinstellungen. Alle Geräte bleiben als Knoten sichtbar.
Die Exportdatei wird nach fünf Minuten oder ausdrücklich per
Aktualisierungsaktion erneut gelesen.
Der Z2M-Renderer verwendet das lokal mitgelieferte ECharts Label Layout, um
überlappende Namen auszublenden und sie beim Zoomen neu zu prüfen. Verbindungslinien
bleiben vollständig erhalten, erscheinen im Überblick aber zurückhaltender;
beim Hervorheben eines Geräts werden Nachbarschaft und Beschriftung betont.
Gemeinsamer Renderer-Code wird erst bei belegter semantischer Gleichheit
beider Graph-Module zentralisiert.

## Nachweise und Grenzen

Tests prüfen Knoten-/Kantenvalidierung, gleichnamige Objekte, das allgemeine
Graph-Modell sowie die Z2M-Projektion und den Z2M-Renderer. Eine tatsächliche
Symcon-Kachel- und IPSView-Abnahme des neuen Z2M-Moduls folgt nach einem
Modulupdate und ist durch Repositorytests nicht ersetzt. Der Scan ohne Routen
lieferte am 15.10.2026 eine gespeicherte Momentaufnahme mit 98 Geräten und
1.907 Link-Einträgen; diese installationsbezogenen Rohdaten sind kein
Repository-Testfixture.

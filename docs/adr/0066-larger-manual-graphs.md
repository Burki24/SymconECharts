# ADR 0066: Größere manuelle Graphen und gemeinsame Ansichtsverwaltung

- Status: Angenommen
- Datum: 2026-10-19
- Entscheider: Burki24
- Ergänzt: ADR 0047

## Entscheidung

EChartsGraph erlaubt 2–300 ausgewählte Objektknoten und 1–1000 ungerichtete
Kanten. Kategorien, Instanzen und Variablen bleiben zulässig; unbekannte
Endpunkte, doppelte IDs, Selbstkanten und doppelte ungeordnete Paare bleiben
ungültig. Gleiche Namen bleiben anhand ihrer Objekt-IDs getrennt. Isolierte
Knoten sind weiterhin zulässig; es wird kein zusammenhängender Graph erzwungen.
Properties, Modulidentitäten, Defaults und gespeicherte Listen werden nicht
umgeschrieben. Die 60-Sekunden-Aktualisierung bleibt lesend.

## Gemeinsame und getrennte Verantwortung

`GraphModel` besitzt die manuellen Höchstzahlen. `echarts-graph.js` wird nur
von Graph und GraphZ2M eingebunden und verwaltet kompatible native Ansichten:
Pan/Zoom sowie ID-bezogene Ausgangskoordinaten für das Kräfte-Layout. Entfernte
Knoten werden verworfen, neue IDs erhalten normale Layoutpositionen. Ein
Layout-/Bedienungswechsel oder vollständig ausgetauschte IDs setzen die Ansicht
zurück. Kräfte dürfen nach der Wiederherstellung weiter simulieren; das
Kreislayout bleibt automatisch. Keine Ansicht wird persistent gespeichert.

Gemeinsam sind außerdem Tooltip-Begrenzung, kurze Beschriftungsgeometrie und
Schutz des Kachelkopfs. Familienbezogene Farben, aktuelle Variablenwerte,
Z2M-Geräterollen, gerichtete Links, LQI-Skala und Aufnahmezeit bleiben getrennt.
Die vorhandene lokal festgeschriebene ECharts-Runtime wird nicht verändert.
Kachelkopf und Diagrammtitel sind physisch vom Canvas getrennt. Nach Ende
der asynchronen Kräfteberechnung werden Beschriftungskollisionen erneut durch
die native Runtime geprüft. Große Kräftegraphen erhalten einmalig eine
Gesamtansicht innerhalb des familienbezogenen Plotbereichs, sofern der Benutzer
noch nicht eingegriffen hat. Z2M behält damit auch Platz für Legende und
Aufnahmezeit. Es gibt keine synchrone Vollsimulation im produktiven Renderer.

## Budgets und Vorschau

Die manuelle SVG-Vorschau prüft den gesamten Entwurf, zeichnet aber höchstens
40 konfigurierte Knoten und 80 echte Verbindungen zwischen enthaltenen
Endpunkten. Ein Mengenhinweis nennt Ausschnitt und Gesamtzahlen. Der Ausschnitt
verändert niemals das Live-Modell. Ungültige Entwürfe bleiben ausdrücklich
gekennzeichnete Beispiele, Z2M behält seine synthetische Vorschau.

Der Symcon-Ausgabepuffer von 1 MiB bleibt maßgeblich. Stresstests kombinieren
300 Knoten, 1000 Kanten, lange HTML-sensible Namen und formatierte Werte sowie
beide unabhängigen Ausgaben. `EChartsGraphOutput` komprimiert gemeinsame
Startzustände oberhalb von 200000 HTML-sicher serialisierten Bytes mit gzip;
beide Renderer entpacken sie im Browser wie bereits die Tree-Familien.
Kleine Dokumente behalten ihre direkte Darstellung, Daten werden nicht gekürzt.
Die bisherigen 64 KiB je gespeicherter manueller Liste bleiben unverändert.
Es werden keine Gateway- oder Exportbudgets angehoben. Z2M bleibt bei
500 Rohknoten, 10000 Rohkanten, 1–2000 sichtbaren
Verbindungen (Default 300), LQI 0–255 und höchstens 16 MiB Exportdateigröße.
Ein Netzwerkscan wird nicht ausgelöst.

## Nachweise und Grenzen

Modell- und Modultests sichern N/N+1, unveränderte Altlisten, gleiche Namen,
geschützte Kantenregeln, sichere Vorschauen und Ausgabegrößen ab. Native
ECharts-Tests prüfen beide Layouts und Ausgabearten, Aktualisierungen,
Größenänderung und entfernte IDs. Browserprüfungen verwenden ausschließlich
synthetische Daten. Eine lokale Lastmessung ist keine Freigabe für beliebige
Clienthardware; die nachfolgende echte Symcon-/IPSView-Abnahme ist durch den Eigentümer bestätigt.

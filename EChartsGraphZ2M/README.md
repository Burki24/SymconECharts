# EChartsGraphZ2M

`EChartsGraphZ2M` zeigt die zuletzt exportierte Zigbee2MQTT-Netzwerkkarte als
interaktiven Apache-ECharts-Graphen. Benötigt werden IP-Symcon 9.0/9.1, ein
verbundenes `EChartsGateway` und eine Instanz des Moduls
`Zigbee2MQTT Network Map`, die bereits eine Netzwerkanalyse abgeschlossen und
die Exportdateien erstellt hat.

Wählen Sie die Netzwerkkarteninstanz im Formular aus und erstellen Sie in
Zigbee2MQTT über „Exporte“ die Exportdateien. Der Graph liest die neueste
JSON-Datei aus dem Netzwerkkarten-Exportverzeichnis auf demselben Symcon-Server;
er startet **keinen** Netzwerkscan und verändert weder Geräte noch
Zigbee2MQTT-Einstellungen. Nach jedem neuen Scan muss erneut exportiert werden.
Danach können Sie die Anzeige über „Exportierte Netzwerkkarte aktualisieren“
sofort erneuern. Andernfalls prüft der Graph alle fünf Minuten, ob ein neuerer
Export vorliegt. Bei mehreren Netzwerkkarteninstanzen ist der gemeinsame
Exportordner nicht eindeutig zuordenbar; diese Konfiguration wird abgewiesen.

Die Knoten entsprechen Zigbee-Geräten; ihre IEEE-Adressen dienen intern als
stabile technische Identität. Gleichnamige Geräte bleiben getrennt. Die
Verbindungen bleiben gerichtet und zeigen ihren LQI-Wert im Tooltip. Mehrfach
gemeldete Verbindungen in derselben Richtung werden mit dem höchsten LQI
zusammengefasst; die Gegenrichtung bleibt eigenständig. Die Anzeige nennt den
Zeitpunkt der gespeicherten Momentaufnahme und die Zahl der dargestellten
gegenüber allen gemeldeten Verbindungen.

Koordinator, Router und Endgeräte erhalten unterschiedliche Knotenfarben.
Die Zuordnung steht in der Legende; bei unbekanntem Gerätetyp wird die
Koordinatorfarbe verwendet. Die Farben richten sich standardmäßig nach dem
gewählten ECharts-Theme und können im Kachel- sowie im unabhängigen
IPSView-Designer einzeln angepasst werden. Die bisherige Einstellung
„Knotenfarbe“ heißt jetzt „Koordinatorfarbe“ und behält ihren gespeicherten
Wert; Router und Endgeräte nutzen zunächst ihre eigenen Theme-Farben.

Verbindungen werden anhand ihres LQI eingefärbt: standardmäßig Rot bei 0,
Gelb bei 128 und Grün bei 255; Zwischenwerte erhalten stufenlos berechnete
Farben. Alle drei Stützfarben lassen sich im Kachel- und unabhängigen
IPSView-Designer frei wählen. Die Legende zeigt die gewählte Skala. Eine
zuvor eingestellte einheitliche Verbindungsfarbe wird nicht mehr verwendet.
Der LQI stammt aus dem letzten Export und ist keine Live-Messung.

Mit Mindest-LQI und maximal angezeigten Verbindungen lässt sich ein großes
Netz übersichtlich halten. Standardmäßig werden die 300 stärksten Verbindungen
angezeigt; Knoten ohne ausgewählte Verbindung bleiben sichtbar. Der Grenzwert
kann bis 2.000 erhöht werden. Der kleine Kachelmodus blendet Beschriftungen
bei großen Netzen aus; über den Tooltip sind die Gerätenamen weiterhin
abrufbar. In größeren Ansichten werden überlappende Knotennamen automatisch
ausgedünnt und beim Zoomen erneut angeordnet. Berührte Knoten heben ihre
Verbindungen und Beschriftung hervor; im Überblick treten die Verbindungslinien
zurück. Kachel und optionales IPSView-HTML-Widget besitzen getrennte
Designer für Layout, Zoom, Beschriftung, Farben und Animation. IPSView kann
das Kacheldesign übernehmen oder unabhängig gestaltet werden.
In der Symcon-Kachel bleibt die Kopfzeile mit Kachelname und
Vergrößerungsbutton frei; Knoten und Linien werden darunter angezeigt.

Der Graph zeigt eine gespeicherte Momentaufnahme, keine Live-Funkstrecken.
Die Existenz einer Verbindung beweist nicht, dass sie aktuell als Route genutzt
wird. Ein Export ohne Routen ist vollständig als Verbindungsgraph verwendbar.

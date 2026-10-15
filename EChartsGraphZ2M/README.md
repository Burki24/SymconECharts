# EChartsGraphZ2M

`EChartsGraphZ2M` zeigt die zuletzt gespeicherte Zigbee2MQTT-Netzwerkkarte als
interaktiven Apache-ECharts-Graphen. Benötigt werden IP-Symcon 9.0/9.1, ein
verbundenes `EChartsGateway` und eine Instanz des Moduls
`Zigbee2MQTT Network Map`, die bereits eine Netzwerkanalyse abgeschlossen hat.

Wählen Sie die Netzwerkkarteninstanz im Formular aus. Der Graph liest deren
gespeicherten RAW-Export; er startet **keinen** Netzwerkscan und verändert
weder Geräte noch Zigbee2MQTT-Einstellungen. Nach einem neuen Scan können Sie
die Anzeige über „Gespeicherte Netzwerkkarte aktualisieren“ sofort erneuern.
Andernfalls wird die Momentaufnahme alle fünf Minuten erneut gelesen.

Die Knoten entsprechen Zigbee-Geräten; ihre IEEE-Adressen dienen intern als
stabile technische Identität. Gleichnamige Geräte bleiben getrennt. Die
Verbindungen bleiben gerichtet und zeigen ihren LQI-Wert im Tooltip. Mehrfach
gemeldete Verbindungen in derselben Richtung werden mit dem höchsten LQI
zusammengefasst; die Gegenrichtung bleibt eigenständig. Die Anzeige nennt den
Zeitpunkt der gespeicherten Momentaufnahme und die Zahl der dargestellten
gegenüber allen gemeldeten Verbindungen.

Mit Mindest-LQI und maximal angezeigten Verbindungen lässt sich ein großes
Netz übersichtlich halten. Standardmäßig werden die 300 stärksten Verbindungen
angezeigt; Knoten ohne ausgewählte Verbindung bleiben sichtbar. Der Grenzwert
kann bis 2.000 erhöht werden. Der kleine Kachelmodus blendet Beschriftungen
bei großen Netzen aus; über den Tooltip sind die Gerätenamen weiterhin
abrufbar. Kachel und optionales IPSView-HTML-Widget besitzen getrennte
Designer für Layout, Zoom, Beschriftung, Farben und Animation. IPSView kann
das Kacheldesign übernehmen oder unabhängig gestaltet werden.

Der Graph zeigt eine gespeicherte Momentaufnahme, keine Live-Funkstrecken.
Die Existenz einer Verbindung beweist nicht, dass sie aktuell als Route genutzt
wird. Ein Export ohne Routen ist vollständig als Verbindungsgraph verwendbar.

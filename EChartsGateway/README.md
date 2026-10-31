# EChartsGateway

Gemeinsame Zentrale der SymconECharts-Library. Das Modul ist als Splitter
angelegt und soll mehrere Geräteinstanzen verschiedener Chartfamilien mit
gemeinsamen Diensten versorgen. Eine eigene I/O-Instanz ist nicht vorgesehen.

Das Gateway liest für die verbundenen Diagramme numerische Momentanwerte sowie
rohe und aggregierte Archivwerte. Identische erfolgreiche Archivabfragen
werden bis zu 15 Sekunden zwischengespeichert. Außerdem stellt das Gateway die
Live-Verbindung für IPSView-Diagramme bereit.

Für Zustandszeitdiagramme liest es zusätzlich rohe Boolean- und
Integer-Archivzustände einschließlich eines vorhandenen Vorgängers vor dem
Zeitfenster. Diese Zustände werden nicht zu Mittelwerten verdichtet.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in Symcon](#4-einrichten-der-instanzen-in-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [Visualisierung](#6-visualisierung)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

Momentanwerte werden mit Typ und Änderungszeitpunkt bereitgestellt.
Archivabfragen berücksichtigen Variable, Zeitraum, Datenmodus,
Verdichtungsstufe, Reduzierer und Punktlimit. Rohwerte sowie aggregierte
Durchschnitts-, Summen-, Minimum- und Maximumwerte werden zeitlich aufsteigend
geliefert; gekürzte Antworten sind ausdrücklich gekennzeichnet.

Die reine Kachelnutzung bleibt unabhängig von einer optionalen IPSView-Ausgabe.

### 2. Voraussetzungen

Erforderlich sind **Symcon 9.0 oder 9.1 und PHP 8.5**. Eine Kompatibilität zu
älteren Symcon-Versionen wird nicht versprochen.

Historische Diagramme sollen das vorhandene Symcon-Archiv verwenden. Eine
zusätzliche Archivinstanz oder eigene Datenbank ist nicht vorgesehen.

### 3. Software-Installation

Das Gateway ist Bestandteil der gemeinsamen Library **SymconECharts** und
wird nicht als getrennte Library installiert.

Die Library wird in der Modulverwaltung über folgende Repository-URL
installiert:

```text
https://github.com/Burki24/SymconECharts
```

### 4. Einrichten der Instanzen in Symcon

Das vorgesehene Modell ist eine gemeinsame EChartsGateway-Instanz mit einer
oder mehreren Gauge-Single-, Gauge-Multi-, Gauge-Tacho- und
Gauge-Chronograph-Instanzen sowie
EChartsTimeSeries und späteren weiteren Chartfamilien-Geräten. Das Gateway hat
keinen übergeordneten Datenfluss; die Geräteinstanzen werden mit ihm verbunden.

Im regulären Betrieb genügt eine Gateway-Instanz für alle ECharts-Geräte. Sie
wird beim Anlegen des ersten Diagramms erstellt; bei weiteren Diagrammen wird
das vorhandene Gateway ausgewählt. Symcon bietet im Parent-Dialog technisch
weiterhin die Erstellung eines zusätzlichen Gateways an. Mehrere Gateways sind
zulässig, bilden aber getrennte Infrastruktur und sind nicht der vorgesehene
Standard.

Der gemeinsame HTTP-Zugang bleibt auch bei mehreren Gateways derselbe.
Zustandsabfragen erreichen das Gateway, mit dem das jeweilige Diagramm
verbunden ist. Beim Entfernen des zuständigen Gateways übernimmt ein
verbleibendes Gateway automatisch; während der kurzen Übergabe kann eine
Verbindung vorübergehend neu aufgebaut werden. Bestehende Diagramm-URLs,
Quellen und Designs müssen dafür nicht angepasst werden.

Beide Gauge-Module bieten über `GetCompatibleParents()` vorhandene kompatible
Gateway-Instanzen zur Verbindung an. Mehrere Chart-Instanzen können dadurch
bewusst dasselbe Gateway verwenden; die Geräte erzeugen nicht über
`RequireParent()` automatisch ein weiteres Gateway.

**Konfigurationsseite:** Das Gateway benötigt keine eigenen dauerhaften
Einstellungen. Unter **JSLive-Importvorschau** lässt sich ein JSLive-Exportpaket
im Format 1.0 prüfen:

1. Die Datei mit der Endung `.jslive-echarts.json` auswählen.
2. Quellen und mögliche Zielfamilien prüfen. Bei fremder oder unbestätigter
   Installation vorhandene Variablen und Archive ausdrücklich per ID zuordnen.
3. Nach Zuordnungsänderungen **Paket und Zuordnungen erneut prüfen** wählen.
4. Mit Diagrammnummer und Berichtsseite die vollständige Feldbilanz sowie
   Abweichungen durchsehen; **Vollständigen Bericht herunterladen** sichert den
   gesamten Konfigurationsplan als JSON.

Die Vorschau legt keine Diagramme an und verändert keine bestehenden
Instanzen, Quellen, Darstellungen oder Archivwerte. Dateien und Zuordnungen
werden nur im geöffneten Formular gehalten. Die Tabellen zeigen 100 Einträge
je Seite; der Download enthält den vollständigen Bericht.

Zielfamilien und einzelne Zieleigenschaften sind zunächst ein **Teilentwurf**.
Quellen-/Archivprüfung, native Formatierung und Chartgestaltung werden getrennt
bewertet. Fehlende Modelle, ungeprüfte Altberechnungen oder Designabweichungen
bleiben sichtbar gesperrt. Ein vollständiger Import und ein gerenderter
Original-/Zielvergleich sind noch nicht verfügbar. Eigene Vorlagen und SVGs
werden dabei nicht ausgeführt oder installiert.

Pakete sind auf 16 MiB begrenzt. Überschreitet die Analyse 100000 JSON-Werte,
32 Verschachtelungsebenen oder 8 MiB vollständigen Bericht, wird sie mit einer
Grenzmeldung abgebrochen. Große Bestände auf mehrere Pakete verteilen.

### 5. Statusvariablen und Profile

#### Statusvariablen

Das Modul legt keine eigenen Statusvariablen an.

#### Profile

Das Modul legt keine Variablenprofile oder eigenen
Variablendarstellungen an.

### 6. Visualisierung

Das Gateway ist ein Hintergrunddienst. Es soll weder eine eigene Chart-Kachel
noch eine große HTML-Dashboard-Seite erzeugen.

Die native Symcon-Kachel und das optionale IPSView-HTML-Widget gehören zur
jeweiligen Geräteinstanz
([Gauge Single](../EChartsGaugeSingle), [Gauge Multi](../EChartsGaugeMulti),
[Gauge Tacho](../EChartsGaugeTacho),
[Gauge Chronograph](../EChartsGaugeChronograph)).
Beide Ausgabewege sind in den Gerätemodulen implementiert. Der Gateway liefert
dabei ausschließlich die gemeinsame Laufzeitkommunikation; das jeweilige
Diagrammmodell und seine Darstellung verbleiben im Gerät.

### 7. PHP-Befehlsreferenz

`ECGW_PreviewJSLiveImport(int $InstanzID, string $Package, string $Options = '{}'): string`
prüft ein JSLive-Paket 1.0 und liefert den vollständigen JSON-Bericht.
`$Package` ist der UTF-8-Dateiinhalt, kein Dateipfad und kein Base64-String.
Ungültige Pakete werden mit einer Ausnahme abgelehnt. Der Bericht enthält
technische Regelkennungen und englische Detailtexte; das Formular ist lokalisiert.

`$Options` enthält bei Bedarf ausdrückliche ID-Zuordnungen, zum Beispiel für
ein synthetisches Paket:

```json
{"sourceMapping":{"variable:13000":14000},"archiveMapping":{"archive:20000":21000}}
```

Der interne Formularadapter `ECGW_PreviewJSLiveImportFromForm` verarbeitet die
Dateiauswahl und aktualisiert ausschließlich die Formularansicht.

Lizenz der eigenen Beiträge: [PolyForm Noncommercial License 1.0.0](../LICENSE).

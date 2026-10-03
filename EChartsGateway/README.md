# EChartsGateway

Gemeinsame Zentrale der SymconECharts-Library. Das Modul ist als Splitter
angelegt und soll mehrere Geräteinstanzen verschiedener Chartfamilien mit
gemeinsamen Diensten versorgen. Eine eigene I/O-Instanz ist nicht vorgesehen.

**Entwicklungsstand:** Modulgerüst. Die unten als geplant beschriebenen Dienste
stehen noch nicht zur Verfügung.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in Symcon](#4-einrichten-der-instanzen-in-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [Visualisierung](#6-visualisierung)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

**Vorhanden:** Moduldefinition als Splitter (`type: 2`), festgelegte
Datenfluss-IDs für Chartfamilien-Geräte und ein PHP-Grundgerüst. Die
Konfigurationsform ist noch leer. Eine eigene `ForwardData()`-Verarbeitung
ist noch nicht implementiert.

**Geplant:** Gemeinsame Daten- und Archivdienste, begrenzte Zwischenspeicherung,
zentrale technische Vorgaben, Diagnose und die bei IPSView-Nutzung benötigten
abgesicherten HTTP-Endpunkte. Die reine Kachelnutzung soll ohne aktivierte
IPSView-Ausgabe und ohne deren WebHook-Zugriff funktionieren.

### 2. Voraussetzungen

Das Entwicklungsziel und die deklarierte Mindestversion sind
**Symcon 9.0 / PHP 8.5**. Eine Kompatibilität zu älteren Symcon-Versionen wird
nicht versprochen. Die Laufzeitfähigkeit des aktuellen Modulgerüsts ist damit
noch nicht belegt.

Historische Diagramme sollen das vorhandene Symcon-Archiv verwenden. Eine
zusätzliche Archivinstanz oder eigene Datenbank ist nicht vorgesehen.

### 3. Software-Installation

Das Gateway ist Bestandteil der gemeinsamen Library **SymconECharts** und
wird nicht als getrennte Library installiert.

Für Entwicklungsarbeiten die folgende Repository-URL im Module Control
verwenden und den Branch `dev` auswählen:

```text
https://github.com/Burki24/SymconECharts
```

Diese Anleitung bezieht sich auf das Entwicklungsgerüst. Sie setzt keine
Veröffentlichung im Module Store voraus und stellt keine Freigabe für den
produktiven Einsatz dar.

### 4. Einrichten der Instanzen in Symcon

Das vorgesehene Modell ist eine gemeinsame EChartsGateway-Instanz mit einer
oder mehreren EChartsGauge-Instanzen sowie späteren weiteren
Chartfamilien-Geräten. Das Gateway hat keinen übergeordneten Datenfluss; die
Geräteinstanzen werden mit ihm verbunden.

**Hinweis zum aktuellen Gerüst:** EChartsGauge verwendet noch
`RequireParent()`. Bei fehlender Verbindung wird damit eine neue Gateway-Instanz
angelegt, auch wenn bereits ein kompatibles Gateway existiert. Die
Wiederverwendung eines gemeinsamen Gateways muss im nächsten technischen
Schritt berücksichtigt werden. Siehe [offizielle RequireParent-Dokumentation](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/requireparent/).

**Konfigurationsseite:** Noch keine konfigurierbaren Felder oder Aktionen.

### 5. Statusvariablen und Profile

#### Statusvariablen

Das Modulgerüst legt noch keine eigenen Statusvariablen an.

#### Profile

Das Modulgerüst legt noch keine Variablenprofile oder eigenen
Variablendarstellungen an.

### 6. Visualisierung

Das Gateway ist ein Hintergrunddienst. Es soll weder eine eigene Chart-Kachel
noch eine große HTML-Dashboard-Seite erzeugen.

Die native Symcon-Kachel und das optionale IPSView-HTML-Widget gehören zur
jeweiligen [EChartsGauge-Instanz](../EChartsGauge). Beide Ausgabewege sind
noch zu implementieren.

### 7. PHP-Befehlsreferenz

Für das Gateway ist noch keine fachliche PHP-Befehlsschnittstelle freigegeben.
Das festgelegte Funktionspräfix lautet `ECGW`.

Die Lebenszyklusmethoden des Gerüsts sind keine dokumentierte
Anwenderschnittstelle. Beispielbefehle ohne Implementierung werden nicht als
verfügbare Funktionen ausgewiesen.

Weitere Projektgrundsätze: [Entwicklung](../docs/ENTWICKLUNG.md).  
Lizenz der eigenen Beiträge: [PolyForm Noncommercial License 1.0.0](../LICENSE).

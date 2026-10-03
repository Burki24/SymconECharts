# EChartsGateway

Gemeinsame Zentrale der SymconECharts-Library. Das Modul ist als Splitter
angelegt und soll mehrere Geräteinstanzen verschiedener Chartfamilien mit
gemeinsamen Diensten versorgen. Eine eigene I/O-Instanz ist nicht vorgesehen.

**Entwicklungsstand:** Technische Datenbasis. Das Gateway verarbeitet den
versionierten `current.read`-Vertrag für numerische Symcon-Variablen. Archiv-,
Cache-, Renderer- und HTTP-Dienste sind noch nicht implementiert.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in Symcon](#4-einrichten-der-instanzen-in-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [Visualisierung](#6-visualisierung)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

**Vorhanden:** Moduldefinition als Splitter (`type: 2`) auf Basis von
`IPSModuleStrict`, festgelegte Datenfluss-IDs für Chartfamilien-Geräte und ein
versionierter Gateway-Vertrag. `ForwardData()` verarbeitet `current.read`,
prüft Variablen-ID und numerischen Variablentyp und liefert Wert, Typ und
Änderungszeitpunkt als strukturierte Antwort. Die Transporthülle nutzt den
zentralen `DataFlowHelper`.

**Geplant:** Gemeinsame Daten- und Archivdienste, begrenzte Zwischenspeicherung,
zentrale technische Vorgaben, Diagnose und die bei IPSView-Nutzung benötigten
abgesicherten HTTP-Endpunkte. Die reine Kachelnutzung soll ohne aktivierte
IPSView-Ausgabe und ohne deren WebHook-Zugriff funktionieren.

### 2. Voraussetzungen

Das Entwicklungsziel und die deklarierte Mindestversion sind
**Symcon 9.0 / PHP 8.5**. Eine Kompatibilität zu älteren Symcon-Versionen wird
nicht versprochen. Die Modulverträge werden lokal mit Test-Doppeln geprüft;
ein Laufzeitnachweis in einer realen Symcon-Installation steht noch aus.

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

EChartsGauge bietet über `GetCompatibleParents()` vorhandene kompatible
Gateway-Instanzen zur Verbindung an. Mehrere Chart-Instanzen können dadurch
bewusst dasselbe Gateway verwenden; das Gauge erzeugt nicht über
`RequireParent()` automatisch ein weiteres Gateway.

**Konfigurationsseite:** Das Gateway benötigt aktuell keine eigenen
Einstellungen. Die Seite zeigt seinen Bereitschaftsstatus.

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
jeweiligen [EChartsGauge-Instanz](../EChartsGauge). Beide Ausgabewege sind
noch zu implementieren.

### 7. PHP-Befehlsreferenz

Für das Gateway ist keine direkte fachliche Anwenderschnittstelle freigegeben.
Das festgelegte Funktionspräfix lautet `ECGW`. Der synchrone Datenfluss über
`ForwardData()` ist ein interner Vertrag für verbundene Chartfamilien-Module.

Lebenszyklus- und Datenflussmethoden sind keine PHP-Befehle für Anwender.
Beispielbefehle ohne Implementierung werden nicht als verfügbare Funktionen
ausgewiesen.

Weitere Projektgrundsätze: [Entwicklung](../docs/ENTWICKLUNG.md).  
Lizenz der eigenen Beiträge: [PolyForm Noncommercial License 1.0.0](../LICENSE).

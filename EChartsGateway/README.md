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

**Konfigurationsseite:** Unter **JSLive-Export und Metadatenprüfung** die Datei
`.jslive-echarts.json` auswählen. Die erste Ansicht prüft nur Paket und Metadaten;
ihre Zielfamilien sind technische Kandidaten. Maßgeblich für eine Neuanlage ist
anschließend der **vollständige native Übernahmeplan** im nächsten Abschnitt.

1. Bei fremder oder unbestätigter Installation vorhandene Variablen und Archive
   ausdrücklich per ID zuordnen. Es gibt keine automatische Namenszuordnung.
2. Im Abschnitt zur bestätigten Übernahme eine bestehende Zielkategorie wählen
   (0 bedeutet Wurzel), die Diagrammnummer auswählen und den Plan vorbereiten.
3. Den vollständigen Bericht herunterladen und die nativen Vorschauen für
   Kachel und IPSView mit dem Original vergleichen. Der Bericht enthält für
   jedes Feld eine Zuordnung, Abweichung oder begründete Nichtanwendbarkeit.
4. Die konkret aufgeführten Abweichungen **einzeln** bestätigen. Fehlerhafte
   Quellen, nicht unterstützte Darstellungen, eigene ausführbare Vorlagen oder
   überschrittene Budgets bleiben gesperrt.
5. **Neue Instanz anlegen** ausdrücklich bestätigen und die Übernahme ausführen.
   Bei mehreren Diagrammen wird jeweils das ausgewählte Diagramm bestätigt.
   Paket oder Zuordnungen nach Änderungen erneut vorbereiten.
6. Ergebnis und Importjournal prüfen. Eine Wiederholung desselben Plans liefert
   die bereits angelegte Instanz, ohne deren Konfiguration erneut anzuwenden.

TimeSeries/BarHistory übernehmen Linien, Balken und Mischbilder; Pie übernimmt
auch mehrere unabhängige Ringe. GaugeSingle bildet radiale, lineare und
Kompass-Instrumente ab. Historische Radar- und Fortschrittsmodelle liegen in
[EChartsRadarHistory](../EChartsRadarHistory/README.md) und
[EChartsProgress](../EChartsProgress/README.md). Eigene sichere SVG-Füllformen
und Pfade werden geprüft; Skripte und fremde Vorlagen werden nicht ausgeführt.
Progressbar-Verläufe und SVG-Füllbilder verwenden einen begrenzten lokalen
Farbadapter. Zyklische Verlaufsbewegung wird als statisches Anfangsbild
übernommen und gesondert gemeldet; externe Bildadressen bleiben gesperrt.
Die unterstützten Formate und Grenzen stehen bei
[EChartsProgress](../EChartsProgress/README.md#verlaufsfarben-und-svg-füllbilder-aus-jslive).
Es wird keine beliebige oder pixelgleiche 1:1-Migration versprochen.

Nur neue, durch das Importjournal eindeutig zugeordnete Ziele werden geschrieben.
Originalinstanzen, Quellwerte, Actions, Darstellungen und Archive bleiben
unverändert. Native Formatierung wird aus dem Export übernommen und benötigt
keine Legacy-Profile. Zeitsteuerungen werden als Einstellungen der neuen Instanz
übernommen; vorhandene JSLive-Steuervariablen werden nicht weitergesteuert.

### Unterbrechungen und geänderte Exporte

Datei und Zuordnungen erneut auswählen und denselben Plan vorbereiten. Das
Gateway setzt einen eindeutig dokumentierten Teilimport fort. Eine geänderte
Zielinstanz wird nicht überschrieben. Für eine neue Exportrevision oder eine
bewusst getrennte Kopie den zugehörigen bisherigen Journaleintrag ausdrücklich
als Ausgangspunkt angeben; die alte Instanz bleibt erhalten.

Auch vor dem Anwenden unterbrochene Konfigurationsschritte werden mit derselben
Zielinstanz fortgesetzt. Datei erneut auswählen, Plan prüfen und bestätigen;
das Journal muss dafür nicht zurückgesetzt werden. Bereits vorgemerkte
Importeinstellungen werden beim Fortsetzen nicht blind neu geschrieben. Der
aktivierte Stand wird vollständig mit dem bestätigten Ziel verglichen. Bei
einer Abweichung stoppt der Import und schreibt keine Korrektur darüber.
Ein eigener neuer Einstellungsentwurf auf einem bereits fertigen Ziel bleibt
unverändert und wird durch eine Importwiederholung nicht angewendet.

Modulupdates erhalten den Erstellungsnachweis. Bereits durch einen früheren
Modulstand neu gesiegelte Nachweise werden ausschließlich beim bestätigten
Wiederanlauf abgeglichen: Eigentumsmarker und vollständiger Zielzustand müssen
weiterhin stimmen. Der ursprüngliche Nachweis bleibt im Journal dokumentiert.
Es ist kein Zurücksetzen des Journals oder Neuerzeugen des Ziels nötig.

Importierte Diagramme behalten ihren Aktualisierungstakt auch dann bei, wenn
das Gateway nach einem Modulupdate vorübergehend noch nicht bereit ist. Sobald
Gateway und Quellen wieder verfügbar sind, setzt der nächste Aktualisierungs-
aufruf das Diagramm automatisch fort; erneutes Speichern oder Anwenden ist nicht
nötig. Bis zur erfolgreichen Initialisierung wird ein Fehlerzustand angezeigt.
Ungültige oder entfernte Importmodelle erhalten keinen laufenden Timer.

Die Journalansicht zeigt jeweils bis zu 25 Einträge und bietet **Vorherige
Journalseite** und **Nächste Journalseite**. Wird das Journal während des
Blätterns geändert, die Ansicht mit **Importjournal aktualisieren** neu laden.

War ein Absturz genau während der Erzeugung möglich, wird keine weitere Instanz
automatisch erzeugt. Die Wiederanlaufaktion verlangt den Journaleintrag, seine
Revision und eine ausdrücklich bestätigte Ziel-ID. Nur eine unveränderte,
passende Neuinstanz kann zugeordnet werden. Ziel-ID 0 bestätigt ausschließlich
eine vom Gateway nachgeprüfte Abwesenheit. Unklare oder nachträglich veränderte
Ausgaben stoppen den Import. Es gibt keine automatische Löschung oder Bereinigung.

Pakete bleiben auf 16 MiB, 100000 JSON-Werte und 32 Ebenen begrenzt. Der
vollständige Bericht ist auf 8 MiB begrenzt und wird niemals still gekürzt;
große Bestände auf kleinere Pakete verteilen. Das Journal enthält höchstens
1000 Einträge und 4 MiB. Ressourcen werden lokal aus dem geprüften Paket
übernommen; eigene Skripte und Templates sind keine ausführbaren Importziele.
Technische Regeln und Detailtexte des Berichts sind englisch, das Formular ist
lokalisiert. Dateien und Zuordnungen bleiben im geöffneten Formular; das Journal
speichert Zuordnung und Prüfnachweise, nicht noch einmal das gesamte Exportpaket.

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

`ECGW_PreviewJSLiveImport(int $InstanzID, string $Package, string $Options): string`
prüft ein JSLive-Paket 1.0 und liefert den vollständigen JSON-Bericht.
`$Package` ist der UTF-8-Dateiinhalt, kein Dateipfad und kein Base64-String.
Ungültige Pakete werden mit einer Ausnahme abgelehnt. Der Bericht enthält
technische Regelkennungen und englische Detailtexte; das Formular ist lokalisiert.
Alle drei Argumente sind beim Symcon-Funktionsaufruf erforderlich. Ohne eigene
Zuordnungen wird für `$Options` ausdrücklich `'{}'` übergeben.

`$Options` enthält bei Bedarf ausdrückliche ID-Zuordnungen, zum Beispiel für
ein synthetisches Paket:

```json
{"sourceMapping":{"variable:13000":14000},"archiveMapping":{"archive:20000":21000}}
```

Der interne Formularadapter `ECGW_PreviewJSLiveImportFromForm` verarbeitet die
Dateiauswahl und aktualisiert ausschließlich die Formularansicht. Seine sechs
Argumente (Instanz-ID, Base64-Dateiinhalt, Zuordnungen, Diagrammindex,
Seitenindex und Download-Schalter) werden von allen Formularaktionen
ausdrücklich übergeben. PHP-Standardwerte der Modulmethoden ersetzen keine
Argumente der von Symcon bereitgestellten Funktionen.

Lizenz der eigenen Beiträge: [PolyForm Noncommercial License 1.0.0](../LICENSE).

### Bestätigte Übernahme per PHP

`ECGW_PrepareJSLiveImport(int $InstanzID, string $Package, string $Options,
int $ParentID): string` liefert den vollständigen nativen Plan als JSON.
`$Package` enthält die UTF-8-Datei, `$Options` ohne Zuordnungen ausdrücklich
`'{}'`, `$ParentID` die vorhandene Kategorie oder 0.

`ECGW_ExecuteJSLiveImport(int $InstanzID, string $Package, string $Options,
int $ParentID, string $Confirmation): string` führt ausschließlich bestätigte
Neuanlagen aus. Die Bestätigung bindet den aktuellen `planHash`, die ausgewählten
`chartKey`-Einträge und ihre einzelnen Abweichungs-IDs:

```json
{"confirmedNewInstances":true,"planHash":"<aus dem vorbereiteten Plan>","charts":[{"chartKey":"<aus dem Plan>","accept":["<jede einzeln geprüfte Abweichungs-ID>"]}]}
```

Für eine getrennte Neuanlage kann der jeweilige Chart-Eintrag zusätzlich
`"separateFrom":"<bisherige Journal-ID>"` enthalten.
`ECGW_GetJSLiveImportJournalPage(int $InstanzID, int $Offset, int $Limit,
string $Revision): string` liest eine chronologisch geordnete Metadatenseite.
Alle vier Argumente sind erforderlich. Mit `0, 25, ''` beginnen; weitere Seiten
verwenden exakt `nextOffset` und dieselbe zurückgegebene `revision`. `nextOffset`
gleich `null` bedeutet Ende. `Limit` muss zwischen 1 und 50 liegen. Bei
`JOURNAL_CHANGED` wieder auf der ersten Seite beginnen.

Das JSON enthält `version`, `view: "summary"`, `revision`, `total`, `offset`,
`limit`, `nextOffset` sowie die geordnete Liste `entries`. Jeder Eintrag enthält
`entryID`, Sequenz, Herkunftsidentität, Revision, Ziel-ID, Status, Phase und
Prüfnachweise. Konfigurations-, Ressourcen- und Ausgabeinhalte werden nicht in
die Seiten kopiert. Jede Antwort bleibt unter 128 KiB; das persistente Journal
behält unverändert seine 1000-Einträge-/4-MiB-Grenzen.

`ECGW_GetJSLiveImportJournal(int $InstanzID): string` erhält das bisherige
Vollformat bis 256 KiB. Darüber meldet es `LIMIT_EXCEEDED` mit Verweis auf die
Seitenfunktion, statt an der Symcon-Ausgabegrenze abzubrechen. Diese Funktion
wird nicht entfernt; größere Journale werden niemals still gekürzt.

`ECGW_ResolveJSLiveImport(int $InstanzID, string $EntryID, int $TargetID,
string $Revision, bool $Confirmed): void` ordnet einen mehrdeutigen
Erzeugungsvorgang ausdrücklich zu oder bestätigt seine nachgeprüfte Abwesenheit.
Keine dieser Aktionen ist über einen Import-HTTP-Endpunkt verfügbar.

Der neue Formularadapter `ECGW_JSLiveImportFromForm` verlangt alle acht
Argumente: Instanz-ID, Base64-Dateiinhalt, Zuordnungen, Zielkategorie, Aktion,
Bestätigungs-JSON, Diagrammindex und IPSView-Schalter. Der ältere lesende
Sechs-Argumente-Adapter bleibt unverändert verfügbar.

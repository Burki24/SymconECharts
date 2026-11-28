# EChartsBarHistory

Stellt Archivwerte von einer bis 32 numerischen Symcon-Variablen als Balken
entlang einer Zeitachse dar. Für aktuelle Kategorienvergleiche verwenden Sie
[EChartsBarCategory](../EChartsBarCategory/README.md).

Installation: Im **Symcon-Modulstore** nach **eCharts** suchen.
[Voraussetzungen und Einrichtung](../README.md#installation-und-erstes-diagramm)
(IP-Symcon 9.0/9.1, PHP 8.5, gemeinsames Gateway).
Alle technischen Felder, Standardwerte und Quellenparameter stehen in der
[Konfigurationsreferenz](../docs/KONFIGURATION.md#echartsbarhistory).

## Einrichtung und Datenquellen

Die [Library installieren](../README.md#installation-und-erstes-diagramm), eine
BarHistory-Instanz anlegen und mit dem gemeinsamen EChartsGateway verbinden.
Archivierte Integer-/Float-Variablen hinzufügen, Zeitraum und Datenmodus
festlegen, das Design einstellen und die Änderungen übernehmen.
Für IPSView bei Bedarf die zusätzliche HTML-Ausgabe einschalten.

Jede Variablen-ID darf nur einmal vorkommen. Eigene Beschriftung, Farbe,
Formatierung und Reduzierer sind je Quelle wählbar. Ohne eigene Beschriftung
erscheint der aktuelle Variablenname; gleiche Namen bleiben getrennte Reihen.
Standardmäßig stehen gleichzeitige Balken nebeneinander. Quellen mit derselben
Einheit teilen sich eine Wertachse. Für zwei Temperaturen mit unterschiedlichen
Skalen öffnen Sie die zweite Quelle und aktivieren unter **Wertachsenbereich**
die **Eigene Wertachse**. Wählen Sie die **Achsenseite** und stellen Sie den
**Achsenbereich** auf **Manuell**, um Minimum und Maximum vorzugeben. So lassen
sich etwa Außentemperatur links von −20 bis 40 °C und Warmwasser rechts von
20 bis 60 °C ablesen. Bei horizontalen Balken entsprechen die beiden Seiten
unten und oben. **Variablendarstellung** übernimmt den dort hinterlegten Bereich
und verwendet die eingetragenen Grenzen als Rückfall.

Bis zu 16 Wertachsen sind möglich. Die Quellenreihenfolge bestimmt die
Achsenreihenfolge. Auf einer gemeinsamen Achse müssen alle ausdrücklich
gewählten Seiten und Grenzen zusammenpassen. Gestapelte Quellen benötigen
die gemeinsame Achse ihrer Einheit.

### PV-Leistungen als gestapelte Balken anzeigen

1. Die archivierten Leistungsvariablen der PV-Flächen hinzufügen, etwa **Ostdach**
   und **Westdach**, beide in kW.
2. Für beide Quellen **Stapelgruppe (0 = einzeln)** auf **1** stellen und
   **Mittelwert** wählen. Als Datenmodus zum Beispiel **1 Stunde** einstellen.
3. Änderungen übernehmen. Liefert das Ostdach im Stundenmittel 2 kW und das
   Westdach 3 kW, zeigen die beiden Farbsegmente zusammen 5 kW an.
   Der Tooltip zeigt die einzelnen Beiträge. Die Legende blendet sie einzeln aus.

Die Gruppen 1–32 bilden jeweils einen eigenen Stapel. Gruppe 0 behält einzelne
Balken bei. Jede Gruppe benötigt dieselbe wirksame Einheit und eine automatische
oder feste Verdichtung. Fehlt für ein Intervall der Archivwert einer konfigurierten
Gruppenquelle, bleibt der gesamte Stapel dort leer, auch wenn die Quelle in der
Legende ausgeblendet ist. Ein gemessener Nullwert zählt als vorhandener Beitrag.
Positive und negative Beiträge stapeln sich auf gegenüberliegenden Seiten der
Nulllinie. Die Legende bestimmt, welche Beiträge im Stapel sichtbar sind.

## Zeiträume, Auswahl und Ladebudget

Wählbar sind Rohwerte, automatische oder ausdrücklich festgelegte Aggregation
sowie feste, kalendergebundene und benutzerdefinierte Zeitspannen. Rohdaten
werden bei überschrittenem Budget als gekürzt gemeldet und nicht still durch
Aggregate ersetzt. Eigene Zeitstempel bleiben erhalten; verschiedene Quellen
werden nicht künstlich auf dieselben Messzeitpunkte gesetzt.

Das gemeinsame Punktbudget beträgt 200–32000, Standard 1000, und wird auf
**alle konfigurierten Quellen** verteilt: abgerundet und höchstens 2000 je
Quelle. Auch versteckte Reihen behalten ihre Abfragen und ihr Budget.
Bei 32 Quellen ermöglichen 1000 Gesamtpunkte höchstens 31 je Reihe, 32000
Gesamtpunkte höchstens 1000. Ein höheres Budget garantiert keine lückenlose
Archivabdeckung. Bestehende kleine Quellenlisten bleiben unverändert nutzbar.

Die scrollbare Legende bietet Einzelauswahl, **Alle**, **Umkehren** und je
Quelle **Anfangs sichtbar**; eine fehlende Angabe bedeutet sichtbar.
**Alle**, dann **Umkehren** blendet alles aus. Eine anfangs verborgene Einzelreihe
behält eine Legende zum Einschalten; eine sichtbare Einzelreihe benötigt sie nicht.
Vollständige Namen und Einheiten bleiben in Legendentooltips verfügbar.

Auswahl, Legendenposition und Zeitzoom bleiben bei Aktualisierung, Umsortierung
und Größenänderung anhand der Variablen-IDs erhalten. Neuladen stellt die
konfigurierte Anfangsauswahl wieder her. Diese flüchtige Bedienung schreibt
keine Einstellungen. Nicht benötigte Achsen verschwinden mit versteckten Reihen;
die übrigen Achsen rücken zusammen. Viele Einheiten können den Zeichenbereich
einer schmalen Kachel trotzdem stark verkleinern.

Der optionale Zeitzoom mit Schieber und Mausrad ist standardmäßig aktiv.
Er verändert nur den Ausschnitt geladener Daten, lädt keine weiteren Punkte
und bleibt bei regulären Aktualisierungen erhalten. Ein geänderter konfigurierter
Zeitraum setzt ihn zurück. Kleine Kacheln erhalten einen schmaleren Schieber,
weniger Achsenmarken und ausgeblendete überlappende Beschriftungen.

Das Modell ist auf 900000 Bytes, die vollständige HTML-Ausgabe auf 1 MiB
begrenzt. Lange Namen, komplexe SVGs oder hochpräzise Zahlen können diese Grenze
vor dem Punktbudget erreichen. Dann Budget oder Muster vereinfachen; Werte
werden nicht still gerundet oder entfernt. Große Startzustände werden verlustfrei
komprimiert und benötigen im Browser `DecompressionStream`.

## Gestaltung und IPSView

Im **Kacheldesigner** unter **Achsen und Anzeige** wählen Sie die **Ausrichtung**.
**Vertikal** ist der Standard. Bei **Horizontal** stehen die ältesten Intervalle
oben und die neuesten unten. Die Balken verlaufen waagerecht, der Zeitzoom liegt
rechts und folgt der Zeitachse. Die Vorschau zeigt die gewählte Ausrichtung.
Für eine andere Ausrichtung in IPSView deaktivieren Sie dort **Kacheldesign verwenden**
und wählen die Ausrichtung im IPSView-Designer.

Unter **Achsen und Anzeige → Reglerdicke (0 = automatisch)** stellen Sie bei
aktivem Zeitzoom die Höhe der Zeitleiste ein. Bei horizontalen Balken bestimmt
der Wert die Breite der Leiste rechts. Auf dem Handy sind beispielsweise **32 px**
ein Ausgangspunkt. Möglich sind 1 bis 64 px. **0** verwendet die bisherige
automatische Größe. Bei sehr geringer Höhe wird die Leiste unter den Balken
begrenzt. Kachel und unabhängiger IPSView-Designer haben jeweils eine Vorschau.

Unter **Abstand der Wertachsen (0 = automatisch)** legen Sie fest, wie weit
zwei sichtbare Wertachsen auf derselben Seite auseinanderliegen. **0** verwendet
die bisherige Automatik. Für engere Skalen auf dem Handy können Sie beispielsweise
**24 px** ausprobieren. Wählbar sind 1 bis 120 px. Bei horizontalen Balken
gilt der Abstand für die Achsen unten bzw. oben. Beide Designer zeigen die
Änderung in der Vorschau. Bei knappem Platz wird der gewählte Abstand verkleinert,
damit für die Balken Platz bleibt.

Einstellbar sind Theme, Balkenbreite, Deckkraft, Rundung samt Radius, Gitter,
Wertbeschriftung und getrennte Schriftgrößen/-farben für Titel, Legende, Achsen
und Werte. Die Achsenschrift gilt auch für die Einheitenüberschrift.
Wertbeschriftungen bleiben standardmäßig ausgeblendet; ihre Standardposition
liegt bei vertikalen Säulen oben und bei horizontalen Balken rechts. Andere
Seiten oder Innenpositionen sind mit 0–100 px Abstand wählbar.

**Balkenfüllung: Farbverlauf** aktiviert die Endfarbe. Bei automatischer Endfarbe
läuft der Verlauf zum Theme-Hintergrund aus. **SVG-Muster** wiederholt eine
eigenständige geprüfte Datei bis 256 KiB innerhalb der Balken. Es ändert nicht
deren Form. Bei Ladeproblemen bleibt die Quellenfarbe erhalten. Für dichte
Archive hineinzoomen, um das Motiv erkennen zu können.

IPSView übernimmt standardmäßig das Kacheldesign oder erhält ein unabhängiges
Design. Vor der eigenen Bearbeitung **Kacheldesign verwenden** ausschalten.
Der Kopierknopf übernimmt die aktuellen Kachelfelder in den IPSView-Designer.
Die Vorschau zeigt auch ungespeicherte Änderungen sofort. Mit **Übernehmen** speicherst Du das Design. Quellen und Datenmodus bleiben gemeinsam;
IPSView-Zeitraum und Zeitachsenbeschriftung liegen separat bei den Zeiteinstellungen.
Archivaktualisierungen ersetzen nicht das gesamte WebContent-Dokument.

Start- und Aktualisierungsanimation besitzen getrennte Zeiten von 0–3000 ms,
Bewegungskurven und Verzögerungen von 0–3000 ms. Standard ist cubicInOut ohne
Verzögerung. Diese gilt für den gesamten Übergang; 0 ms unterdrückt ihn und die
Systemeinstellung für reduzierte Bewegung schaltet Animationen ab.

### Zeitraum direkt im Diagramm wechseln

Aktiviere im Kacheldesigner **Zeitraumauswahl anzeigen**. Unter dem Diagramm erscheint die aktuelle Auswahl. Ein Tipp öffnet die Zeiträume, etwa **6 Stunden**, **24 Stunden**, **Heute**, **Diese Woche**, **Dieser Monat** oder **Dieses Jahr**. Bei **24 Stunden** reicht das Fenster vom aktuellen Zeitpunkt aus zurück. **Heute** beginnt um Mitternacht in der Symcon-Zeitzone.

Beim Wechsel lädt das Diagramm die Archivwerte für den gewählten Zeitraum mit der eingestellten Verdichtung und dem vorhandenen Punktbudget. Die Zoomleiste dient weiterhin zum Vergrößern eines Ausschnitts. Die Auswahl gilt nur für die geöffnete Ansicht. Beim erneuten Öffnen gilt wieder der konfigurierte Zeitraum. Für IPSView kannst Du **Kacheldesign verwenden** deaktivieren und die Auswahl getrennt einblenden.

## Vorschau und Verfügbarkeit

Die SVG-Vorschauen folgen auch ungespeicherten Änderungen an Titel, Quellen,
Zeitraum, Theme, Rundung und Deckkraft bis 0 %. Sie verwenden gekennzeichnete
Beispieldaten und lösen keine Archivabfrage aus. Bis zu drei anfangs sichtbare
Quellen mit passenden Einheitenachsen werden gezeigt; ein Ausschnitt nennt die
angezeigte und gesamte Anzahl. Sind alle verborgen, erscheinen keine Beispielbalken.
Die ursprüngliche Quellenreihenfolge bestimmt weiterhin die Farben.
Einheiten und Dezimalstellen folgen der wirksamen Quellenformatierung.
SVG-Motive werden schematisch dargestellt. Echte Werte, numerische Skalen,
Zoom und Muster beurteilen Sie in der Kachel beziehungsweise im IPSView-Widget.

Bei vorübergehendem Archivfehler bleiben die letzte gültige Grafik und ihr
Zoom erhalten. Wiederholte Fehler zeigen einen Hinweis bis zum nächsten
erfolgreichen Laden; ohne frühere Daten erscheint eine Fehlermeldung.

## Übernommene JSLive-Modelle

Das [EChartsGateway](../EChartsGateway/README.md) kann geprüfte JSLive-Pakete
nach ausdrücklicher Bestätigung als **neue** Instanzen übernehmen. Bestehende
Diagramme und Quellen bleiben erhalten. Die importierte Variante erhält ein
eigenes, aus dem Export gebildetes Modell; die reguläre Einrichtung dieses
Moduls bleibt unverändert. Quellen- und Archiv-IDs sowie native Formatierungen
werden erhalten, ohne Legacy-Profile anzulegen. Der Importbericht nennt
Darstellungs- und Verarbeitungsunterschiede einzeln.

Importierte Modelle bieten Titel, Zeitsteuerung bei historischen Modellen,
Hintergrund-/Schriftanpassungen und Sichtbarkeitseinstellungen für Kachel und
IPSView. Die Farbkarte ist schematisch; **Native Vorschau herunterladen** zeigt
die tatsächliche ECharts-Ausgabe mit noch nicht gespeicherten Entwurfswerten.
Quellen und vollständige ursprüngliche Stile stammen aus dem Importmodell;
sie werden nicht in die reguläre Quellen-/Designerstruktur zurückübersetzt.
Bei einer neuen Übernahme eines geänderten Exports ist ausdrücklich eine
separate Neuanlage erforderlich. Das bisherige Ziel wird nicht überschrieben.

## Konfigurationsreferenz und Skriptzugriff

Die [Feldreferenz](../docs/KONFIGURATION.md#echartsbarhistory) enthält
Formularbeschriftungen, technische Namen, Typen, Standardwerte und Auswahllisten.
Für Skriptänderungen gelten die dort erläuterten JSON- und Anwenderegeln.

### Ausgabevariable

| Ident | Typ / Darstellung | Bedeutung | Einheit | Bedienbar |
| --- | --- | --- | --- | --- |
| `IPSViewBarHistory` | String / WebContent | HTML-Dokument für das optionale IPSView-Widget | keine | Nein |

Die Variable entsteht bei aktivierter IPSView-Ausgabe. Nach deren Abschalten
bleibt sie erhalten, wird aber nicht weiter aktualisiert. Die ausdrückliche
Löschaktion im Formular entfernt sie samt ihrer bisherigen Verknüpfbarkeit.
Quellvariablen und deren Archive bleiben unverändert. Weitere Statusvariablen
werden für das reguläre Diagramm nicht angelegt.

### Öffentliche PHP-Funktionen

`$InstanzID` bezeichnet immer diese Modulinstanz, nicht eine Quellvariable
oder das Gateway. Alle aufgeführten Argumente beim Symcon-Aufruf übergeben.

| Signatur | Rückgabe und Wirkung |
| --- | --- |
| `ECBH_CopyTileDesignToIPSView(int $InstanzID): void` | Überträgt die gespeicherten Kachelwerte in den geöffneten IPSView-Formularentwurf. Erst **Übernehmen** speichert die Kopie. |
| `ECBH_GetBarHistoryData(int $InstanzID): string` | Liest das reguläre konfigurierte Diagrammmodell als JSON; keine Änderung von Quellen oder Archivwerten. |
| `ECBH_GetBarHistoryDiagnostic(int $InstanzID): string` | JSON-Momentaufnahme der Konfigurationsprüfung und letzten Fehlermeldung; kein Nachweis einer erfolgreichen Datenabfrage. |
| `ECBH_GetIPSViewHTML(int $InstanzID): string` | Vollständiges HTML-Dokument für IPSView; der Rückgabewert bestätigt keine Anzeige in einem Client. |
| `ECBH_RefreshArchive(int $InstanzID): void` | Aktualisiert die Diagrammausgaben aus den konfigurierten Quellen. Kein Erfolgswert und keine Änderung von Quellen/Archiven. |
| `ECBH_GetJSLiveImportHTML(int $InstanzID, bool $IPSView): string` | `$IPSView=false`: Kachelausgabe; `true`: IPSView-Ausgabe eines übernommenen JSLive-Modells als vollständiges HTML. |
| `ECBH_RefreshJSLiveImport(int $InstanzID): void` | Aktualisiert die Diagrammausgaben aus den konfigurierten Quellen. Kein Erfolgswert und keine Änderung von Quellen/Archiven. Nur für importierte Modelle. |
| `ECBH_GetJSLiveImportData(int $InstanzID): string` | JSON-Zustand eines übernommenen JSLive-Modells einschließlich Verfügbarkeit; nur für importierte Varianten. |

Beispiel mit synthetischer Instanz-ID (durch die eigene ID ersetzen):

```php
$instanceID = 12000;
$model = json_decode(ECBH_GetBarHistoryData($instanceID), true, 512, JSON_THROW_ON_ERROR);
```

Die fachlichen Grenzen dieser Anleitung gelten auch bei Skriptaufrufen.
Ungültige Konfigurationen, fehlende Quellen oder ein nicht verfügbares Gateway
können eine Ausnahme beziehungsweise einen ausdrücklich nicht verfügbaren
Zustand liefern. Solche Rückgaben nicht als leeren oder mit null aufgefüllten
Messbestand auswerten. Archivabfragen können den kurzzeitigen Gateway-Cache
nutzen; ein Aufruf garantiert keinen neu protokollierten Messwert.

## Lizenz

Die eigenen Beiträge stehen unter der
[PolyForm Noncommercial License 1.0.0](../LICENSE).
Für Fremdkomponenten gelten deren [Originallizenzen](../THIRD_PARTY_NOTICES.md).

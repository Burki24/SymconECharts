# EChartsBarHistory

Stellt Archivwerte von einer bis 32 numerischen Symcon-Variablen als Balken
entlang einer Zeitachse dar. Für aktuelle Kategorienvergleiche verwenden Sie
[EChartsBarCategory](../EChartsBarCategory/README.md).

Installation: Im **Symcon-Modulstore** nach **SymconECharts** suchen.
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
Gleichzeitige Balken stehen nebeneinander. Jede wirksame Einheit erhält eine
eigene Wertachse. Quellen derselben Einheit teilen diese Achse; widersprüchliche
explizite Seitenwahlen für dieselbe Einheit sind ungültig. Die Listenreihenfolge
bestimmt die Reihen- und Achsenreihenfolge. Unterstützt sind bis zu 16 Einheiten.

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

Einstellbar sind Theme, Balkenbreite, Deckkraft, Rundung samt Radius, Gitter,
Wertbeschriftung und getrennte Schriftgrößen/-farben für Titel, Legende, Achsen
und Werte. Die Achsenschrift gilt auch für die Einheitenüberschrift.
Wertbeschriftungen bleiben standardmäßig ausgeblendet; ihre Standardposition
liegt oberhalb der Balken. Alternativ sind andere Seiten oder Innenpositionen
mit 0–100 px Abstand wählbar.

**Balkenfüllung: Farbverlauf** aktiviert die Endfarbe. Bei automatischer Endfarbe
läuft der Verlauf zum Theme-Hintergrund aus. **SVG-Muster** wiederholt eine
eigenständige geprüfte Datei bis 256 KiB innerhalb der Balken. Es ändert nicht
deren Form. Bei Ladeproblemen bleibt die Quellenfarbe erhalten. Für dichte
Archive hineinzoomen, um das Motiv erkennen zu können.

IPSView übernimmt standardmäßig das Kacheldesign oder erhält ein unabhängiges
Design. Vor der eigenen Bearbeitung **Kacheldesign verwenden** ausschalten.
Der Kopierknopf übernimmt das gespeicherte Kacheldesign einmalig und ersetzt
vorhandene unabhängige Werte. Quellen und Datenmodus bleiben gemeinsam;
IPSView-Zeitraum und Zeitachsenbeschriftung liegen separat bei den Zeiteinstellungen.
Archivaktualisierungen ersetzen nicht das gesamte WebContent-Dokument.

Start- und Aktualisierungsanimation besitzen getrennte Zeiten von 0–3000 ms,
Bewegungskurven und Verzögerungen von 0–3000 ms. Standard ist cubicInOut ohne
Verzögerung. Diese gilt für den gesamten Übergang; 0 ms unterdrückt ihn und die
Systemeinstellung für reduzierte Bewegung schaltet Animationen ab.

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
| `ECBH_CopyTileDesignToIPSView(int $InstanzID): void` | Kopiert das gespeicherte Kacheldesign, ersetzt die unabhängigen IPSView-Designwerte und wendet sie an; aktiviert unabhängiges Design. |
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

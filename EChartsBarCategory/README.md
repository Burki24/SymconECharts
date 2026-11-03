# EChartsBarCategory

Vergleicht aktuelle Werte von einer bis 100 numerischen Symcon-Variablen als
Kategorienbalken. Die Darstellung ist vertikal oder horizontal sowie einfach,
gruppiert oder gestapelt möglich. Ohne Normalisierung müssen alle Quellen
dieselbe wirksame Einheit besitzen. Historische Balken gehören zu
[EChartsBarHistory](../EChartsBarHistory/README.md).

Voraussetzungen und Installation: [SymconECharts](../README.md#voraussetzungen)
(IP-Symcon 9.0/9.1, PHP 8.5, gemeinsames Gateway).
Alle technischen Felder, Standardwerte und Quellenparameter stehen in der
[Konfigurationsreferenz](../docs/KONFIGURATION.md#echartsbarcategory).

## Einrichtung und Quellen

Die [Library installieren](../README.md#installation-und-erstes-diagramm), eine
BarCategory-Instanz anlegen und mit dem gemeinsamen EChartsGateway verbinden.
Integer- oder Float-Variablen auswählen, Titel und Design festlegen und übernehmen.
Ein Archiv ist nicht erforderlich. Für IPSView die optionale HTML-Ausgabe aktivieren.

Jede Variable darf nur einmal vorkommen. In jeder Quellenzeile stehen eine
**Kategorie** (`Category`) und ein optionaler **Datenreihenname** (`Series`).
Die Kategorie beschriftet die Kategorienachse, die Reihe den Legendeneintrag.
Eine leere Kategorie verwendet im einfachen Modus den Variablennamen, sonst
die gemeinsame Gruppe. Eine leere Reihe verwendet den Variablennamen.
Gleiche Beschriftungen bleiben getrennte Quellen, ohne IDs in der Grafik anzuzeigen.
Fehlende Kategorie-/Reihenkombinationen bleiben leer. Bei Sortierung nach Werten
zählt die Summe der verfügbaren Werte je Kategorie. Standard ist **Einfach**.

## Optionale Min/Max-Normalisierung

**Numerische Quellen anhand ihrer Min/Max normalisieren** erlaubt relative
Vergleiche unterschiedlicher Einheiten, etwa Temperatur, Feuchte und LQI.
Je Quelle eigene Grenzen setzen: Standard 0 und 100, beide endlich und Minimum
kleiner als Maximum. Sie stammen nicht automatisch aus der Variablendarstellung.
Bei ausgeschalteter Normalisierung werden diese Grenzen nicht ausgewertet.

Die Balkenlänge entspricht `(Wert − Min) / (Max − Min) × 100`, begrenzt auf 0–100.
Auch negative Originalwerte sind mit passenden Grenzen zulässig. Beschriftung
und Tooltip behalten Originalwert, Einheit und Präzision; der Tooltip ergänzt
die normalisierte Position und den Originalbereich. Die Vergleichsachse ist
ausdrücklich als **Normalisiert 0–100 je Quelle** gekennzeichnet.

Sortiert wird nach normalisierten Werten. Gruppierte Balken bleiben getrennt;
gestapelte Balken addieren ihre Gewichte und können über 100 hinausgehen.
Das ist keine 100-%-Anteilsberechnung. Zu kleine normalisierte Stapelsegmente
blenden ihre Wertbeschriftung aus; die Originalwerte bleiben im Tooltip.
Farbverläufe folgen im normalisierten Modus der normalisierten Wertachse.
Gespeicherte Farb- und Designwerte werden nicht automatisch geändert.

## Kategoriennavigation und Abstände

Die Grenze von **100 Quellen** gilt insgesamt über alle Kategorien und Reihen.
Passen sie nicht in den verfügbaren Raum, erscheinen Kategorienschieber und
Mausradnavigation. Die Zahl sichtbarer Kategorien folgt der Ausgabegröße,
**Balkenbreite** sowie Achsen- und Wertschriftgröße; es gibt kein festes
16-Kategorien-Fenster. Das gilt horizontal und vertikal in Kachel und IPSView.

Dünnere Balken rücken die Kategorien enger zusammen. Die Schriftgröße schützt
einen Mindestabstand; darunter ändert sich nur die Balkendicke. Kurze Listen
bleiben kompakt. Gruppierte Balken reservieren Platz je sichtbarer Reihe.
Überlappende Werte werden ausgeblendet, vertikale Kategorienamen bei Bedarf
gekürzt; vollständige Namen und Werte bleiben über Tooltip/Legende verfügbar.
Viele Reihen erhalten eine scrollbare Legende.

Alle Quellen bleiben im Modell. Sortierung und vorzeichenbehaftete Werteskala
verwenden den vollständigen Bestand einschließlich positiver und negativer
Stapelsummen. Navigation verändert keine Skala oder Quelle. Aktualisierungen
erhalten die sichtbare Kategorie, im einfachen Modus anhand der Variablen-ID
auch bei gleichen Namen oder einer geänderten Sortierung.

## Füllung, Farben und Beschriftungen

**Balkenfüllung: Farbverlauf entlang der Wertachse** färbt jeden Balken entlang
eines festen Bereichs mit Minimum-, Mittel- und Maximumfarbe. Standardfarben
sind Rot, Gelb und Grün. Für Batterien passt 0–100, für LQI 0–255; auch
gebrochene oder negative Grenzen sind möglich. Außerhalb des Farbbereichs gilt
die nächste Endfarbe, ohne die tatsächlichen Werte zu begrenzen.

Ein 16-%-Balken nutzt nur die ersten 16 % eines Verlaufs von 0–100. Der Verlauf
wird nicht vollständig auf den kurzen Balken gestreckt. Er verläuft horizontal
von links nach rechts und vertikal von unten nach oben. Gestapelte Segmente
setzen den Verlauf an ihrer Position auf der gemeinsamen Wertachse fort;
positive und negative Stapel bleiben getrennt. Eine geänderte Legendenauswahl
berechnet diese Positionen neu. Legendenfarben kennzeichnen weiterhin die Reihe.
**Einfarbig** bleibt die Standardfüllung.

**SVG-Muster** wiederholt ein geprüftes SVG innerhalb der rechteckigen Balken;
es verändert nicht deren Form. Zulässig sind eigenständige Dateien bis 256 KiB
ohne Skripte oder externe Referenzen. Die Mustergröße ist einstellbar.
Kann ein Browser das SVG nicht laden, bleibt die Quellenfarbe als Rückfall.

Wertbeschriftungen können automatisch, oberhalb, unterhalb, links, rechts oder
innen mit 0–100 px Abstand stehen. Innenbeschriftungen erhalten automatischen
Kontrast, sofern keine eigene Wertfarbe gewählt ist. Titel, Legende, Achsen und
Werte besitzen getrennte Schriftgrößen von 50–200 % und eigene Farben;
automatische Farben folgen dem Theme. Balkenbreite, Gitter und Rundung sind
ebenfalls einstellbar.

## Kachel, IPSView und Vorschau

IPSView verwendet standardmäßig das Kacheldesign. Für eigene Werte zuerst
**Kacheldesign verwenden** ausschalten. Der Kopierknopf übernimmt das zuletzt
gespeicherte Kacheldesign einmalig und überschreibt die bisherigen unabhängigen
IPSView-Designwerte. Die Datenquellen bleiben gemeinsam.

Die SVG-Vorschau reagiert auf ungespeicherte Änderungen. Sie verwendet aktuelle
Quellen oder deutlich gekennzeichnete Beispieldaten. Der begrenzte Ausschnitt
folgt Größe, Orientierung und Schrift; sortiert wird zuvor der vollständige
Bestand. Angezeigte und gesamte Quellenzahl werden bei einem Ausschnitt genannt.
Ein SVG-Motiv wird schematisch schraffiert. Die tatsächliche Kachel beziehungsweise
das IPSView-Widget bleibt maßgeblich für Motiv, Navigation und responsive Geometrie.

Animationen für Start und Aktualisierung lassen sich getrennt von 0–3000 ms
einstellen, zusätzlich Bewegungskurve und Verzögerung von 0–3000 ms.
Standard ist cubicInOut ohne Verzögerung. Die Verzögerung gilt für den ganzen
Übergang, nicht nacheinander für einzelne Werte. 0 ms unterdrückt den Übergang;
reduzierte Bewegung des Systems schaltet Animationen ab.

Bei vorübergehendem Ladefehler bleibt eine zuletzt gültige Grafik sichtbar.
Wiederholte Fehler zeigen einen Hinweis; nach 60 Sekunden wird erneut geladen.
Ohne vorherige Daten oder bei ungültiger Konfiguration erscheint eine Fehlermeldung.

## Konfigurationsreferenz und Skriptzugriff

Die [Feldreferenz](../docs/KONFIGURATION.md#echartsbarcategory) enthält
Formularbeschriftungen, technische Namen, Typen, Standardwerte und Auswahllisten.
Für Skriptänderungen gelten die dort erläuterten JSON- und Anwenderegeln.

### Ausgabevariable

| Ident | Typ / Darstellung | Bedeutung | Einheit | Bedienbar |
| --- | --- | --- | --- | --- |
| `IPSViewBarCategory` | String / WebContent | HTML-Dokument für das optionale IPSView-Widget | keine | Nein |

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
| `ECBC_CopyTileDesignToIPSView(int $InstanzID): void` | Kopiert das gespeicherte Kacheldesign, ersetzt die unabhängigen IPSView-Designwerte und wendet sie an; aktiviert unabhängiges Design. |
| `ECBC_GetBarData(int $InstanzID): string` | Liest das reguläre konfigurierte Diagrammmodell als JSON; keine Änderung von Quellen oder Archivwerten. |
| `ECBC_GetBarDiagnostic(int $InstanzID): string` | JSON-Momentaufnahme der Konfigurationsprüfung und letzten Fehlermeldung; kein Nachweis einer erfolgreichen Datenabfrage. |
| `ECBC_GetIPSViewHTML(int $InstanzID): string` | Vollständiges HTML-Dokument für IPSView; der Rückgabewert bestätigt keine Anzeige in einem Client. |

Beispiel mit synthetischer Instanz-ID (durch die eigene ID ersetzen):

```php
$instanceID = 12000;
$model = json_decode(ECBC_GetBarData($instanceID), true, 512, JSON_THROW_ON_ERROR);
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

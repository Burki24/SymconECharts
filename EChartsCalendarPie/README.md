# EChartsCalendarPie

Zeigt einen Monatskalender mit einem Kreisdiagramm je abgeschlossenem Tag.
Die Anteile können Tagesmittelwerte, Tagesverbrauch oder Zustandsdauern sein.
Das Modul liest ausschließlich: Quellen, Archivaufzeichnung und Archivwerte
werden weder verändert noch automatisch ergänzt.

Installation: Im **Symcon-Modulstore** nach **SymconECharts** suchen.
[Voraussetzungen und Einrichtung](../README.md#installation-und-erstes-diagramm)
(IP-Symcon 9.0/9.1, PHP 8.5, gemeinsames Gateway).
Alle technischen Felder, Standardwerte und Quellenparameter stehen in der
[Konfigurationsreferenz](../docs/KONFIGURATION.md#echartscalendarpie).

## Einrichtung

1. Eine Instanz `EChartsCalendarPie` anlegen und mit dem vorhandenen
   `EChartsGateway` verbinden. Ein gemeinsames Gateway genügt für alle Diagramme.
2. Unter **Datengrundlage** einen der drei Modi wählen und die dazugehörigen
   Quellen konfigurieren. Beim Wechsel bleiben die Einstellungen der anderen
   Modi gespeichert; abgefragt werden nur die aktiven Quellen.
3. **Monat** leer lassen, um dem aktuellen Monat zu folgen, oder einen Monat
   im Format `JJJJ-MM` eintragen. Erlaubt sind Januar 1971 bis zum aktuellen
   Monat. Titel und gewünschtes Design einstellen.

Die Pfeile laden frühere Monate beziehungsweise kehren zum ausgewählten
Monat zurück. Bis zu 120 Monate vor der konfigurierten Auswahl sind erreichbar.
Diese Navigation verändert die gespeicherte Konfiguration nicht. Weiter
zurückliegende Monate können direkt in der Konfiguration ausgewählt werden.

## Datengrundlagen

### Tagesmittelwerte

Eine bis fünf numerische Integer-/Float-Variablen aus Standardarchiven wählen.
Die Anzahl wird über Hinzufügen und Entfernen in der Quellenliste bestimmt;
es sind keine drei festen Quellen vorgegeben. Die Vorschau folgt der Auswahl.
Jede Quelle bildet einen Kreisanteil mit optionalem Namen und eigener Farbe.
Ohne eigenen Namen folgt die Beschriftung dem aktuellen Variablennamen.
Gleichnamige Quellen bleiben getrennt erhalten.

Verwendet werden die unveränderten Tagesmittelwerte des Symcon-Archivs,
nicht neu berechnete Mittelwerte von Stundenmitteln. Ohne Normalisierung müssen
alle Quellen dieselbe wirksame Einheit besitzen; Einheit und Nachkommastellen können aus
der Variablendarstellung übernommen oder gemeinsam vorgegeben werden.
Für Anteile sind nur nichtnegative Werte geeignet. Beispielsweise lassen sich
die durchschnittlichen Leistungen mehrerer Verbraucher vergleichen; daraus
wird keine Energieverbrauchsmessung abgeleitet.

### Tagesverbrauch

Hier werden ebenfalls eine bis fünf numerische Quellen (ohne Normalisierung derselben Einheit)
gewählt, jedoch ausschließlich mit **Zähleraggregation** im Symcon-Archiv.
Die Kreisanteile zeigen dessen Tagesverbrauch: die vom Archiv berechnete Summe
positiver Zählerdifferenzen. Kumulative Zählerstände werden nicht addiert.
Zählerrücksetzungen behandelt das Archiv nach seiner eigenen Zählerlogik;
das Modul schätzt keine fehlenden Verbräuche. Ein Standardarchiv wird nicht
automatisch in einen Zähler umgedeutet.

### Zustandsdauer

Eine archivierte Boolean- oder Integer-Variable mit Standardaggregation wählen.
Unter **Zustandszuordnung** können bis zu 32 verschiedene Werte mit Namen und
Farben hinterlegt werden. Boolean verwendet `0` für falsch und `1` für wahr.
Nicht zugeordnete Werte bleiben als Zahlen sichtbar.

Jeder protokollierte Zustand gilt bis zum nächsten Wechsel. Ein vorhandener
Archivwert vor Monatsbeginn bestimmt den Anfangszustand. Fehlt er, bleibt die
Zeit bis zum ersten Wert **Unbekannt**. Diese Zeit erhält einen eigenen Anteil
und wird nicht aus der Prozentberechnung entfernt. Der Tooltip zeigt Stunden
und Anteile an der tatsächlichen Tageslänge; Sommerzeitwechsel ergeben deshalb
23 oder 25 statt 24 Stunden. Aktuelle Variablenwerte ersetzen keine Archivwerte.
Historisch vollständig übersprungene lokale Datumswerte, etwa bei einem
Wechsel der Datumsgrenze, erhalten keinen erfundenen Zeitanteil.

Pro Monat werden höchstens 2000 Rohwechsel gelesen. Zu viele Wechsel oder
ungültige Archivantworten erzeugen eine sichtbare Meldung statt einer
ausgedünnten oder geschätzten Darstellung. Ein Zustandsarchiv kann verlorene
Zwischenwerte oder deaktivierte Aufzeichnung nicht zuverlässig als Ausfall
erkennen: Ein bekannter Zustand wird bis zum nächsten protokollierten Wechsel
fortgeführt.

Bei mehreren Zustandswechseln derselben Sekunde gilt dieselbe zentrale Regel
wie bei StateTimeline: Genau ein Eintrag mit positiver ganzzahliger Archiv-Dauer
und sonst ausschließlich Dauer 0 bestimmen den danach geltenden Zustand.
Wechsel mit Dauer 0 erhalten keinen Zeitanteil. Mehrdeutige Dauerangaben bleiben
ein Fehler; das Archiv wird nicht verändert.

### Optionale Normalisierung für unterschiedliche Messgrößen

Für **Tagesmittelwerte** und **Tagesverbrauch** kann **Numerische Quellen anhand
ihres Min/Max normalisieren** aktiviert werden. Dann dürfen die ein bis fünf
Quellen unterschiedliche Einheiten haben. Je Quelle werden **Min** und **Max**
festgelegt; ohne Eintrag gelten 0 und 100. Max muss größer als Min sein.
Die Grenzen gelten für alle angezeigten Tage und Monate, nicht nur für die
aktuell beobachteten Messwerte.

Das Kreisgewicht ist `100 × (Wert − Min) / (Max − Min)`, begrenzt auf 0–100.
Beispiel: 20 °C im Bereich 0–40 °C ergeben Gewicht 50; 60 % Feuchte im Bereich
0–100 % ergeben Gewicht 60. Die Kreisflächen betragen damit rund 45,5 % und
54,5 %. Das sind relative Vergleichsgewichte, keine Anteile einer gemeinsamen
physikalischen Gesamtmenge.

Negative Temperaturen sind bei aktiver Normalisierung erlaubt. Werte außerhalb
der Grenzen werden nur für die Kreisfläche begrenzt. Der Tooltip bewahrt den
Original-Tageswert mit seiner eigenen Einheit und Nachkommastellenzahl; zusätzlich
zeigt er normalisiertes Gewicht, Bereich und gegebenenfalls Kreisanteil.
Auch optionale Segmentzahlen zeigen die Originalwerte, nicht die Gewichte.
Einheiten und Nachkommastellen werden je Quelle aus der Symcon-Darstellung
beziehungsweise dem Legacy-Profil gelesen. Ohne solche Angaben gelten keine
Einheit und eine Nachkommastelle. Die globalen Formatierungsfelder sind während
der Normalisierung inaktiv; ihre gespeicherten Einstellungen bleiben erhalten.

Fehlende Quellen bleiben fehlend, und ein vollständig auf null begrenzter Tag
erhält keinen künstlichen Kreis. Ohne Normalisierung bleiben unveränderte,
nichtnegative Werte derselben Einheit erforderlich. Der Modus **Zustandsdauer**
ignoriert die Normalisierung und die numerischen Min/Max-Eingaben.

## Fehlende Daten und Kalendergrenzen

Es werden nur abgeschlossene lokale Tage in der Symcon-Zeitzone ausgewertet.
Der laufende Tag und zukünftige Tage bleiben als nicht abgeschlossen markiert.
Ein vollständiges Monatsarchiv ist nicht erforderlich.

Bei Mittelwerten und Verbrauch wird ein Kreis nur gezeichnet, wenn alle
konfigurierten Quellen für diesen Tag vorliegen (unveränderter Standard). Eine Raute markiert einen
unvollständigen Tag; verfügbare Werte und fehlende Quellen stehen im Tooltip.
Ein Quadrat kennzeichnet vollständig fehlende Daten, ein leerer Kreis einen
vollständigen Tag mit ausschließlich echten Nullwerten. Es werden keine
gleich großen Ersatzsegmente erzeugt. Nicht abgeschlossene Tage tragen ein
abgeschwächtes Dreieck.

Mit **Vorhandene Quellen an unvollständigen Tagen anzeigen** können Sie für
Mittelwerte und Verbrauch ausdrücklich auch Teilkreise aktivieren. Gezeichnet
werden nur vorhandene positive Werte beziehungsweise normalisierte Gewichte.
Die fehlenden Quellen bleiben im Tooltip genannt; sie erhalten keinen
erfundenen Nullwert oder Ersatzanteil. Eine orange Raute am Zellrand und ein
dauerhafter Hinweis kennzeichnen unvollständige Tage auch bei ausgeschalteter
Legende, Wertanzeige oder Tooltip.

Die Kreisanteile beziehen sich dann **nur auf die an diesem Tag vorhandenen
Quellen**, nicht auf eine vollständige Gesamtmenge. Eine allein verfügbare
positive Quelle ergibt daher 100 % des gezeigten Kreises. Bei aktiver
Normalisierung bleiben Min/Max unverändert: Nur der Kreisanteil bezieht sich
auf die vorhandenen Gewichte; Originalwerte, Einheiten und Präzision bleiben
im Tooltip erhalten. Sind alle vorhandenen Gewichte null, bleibt die Raute
ohne Kreis. Vollständig fehlende und nicht abgeschlossene Tage werden auch
mit dieser Option nicht als Kreise dargestellt.

Die Auswahl gilt gemeinsam für Kachel, IPSView und beide Vorschauen. Sie ist
standardmäßig ausgeschaltet und wird im Zustandsmodus nicht ausgewertet;
dessen unbekannter Zeitanteil und tatsächliche Tageslänge bleiben erhalten.

Ein vorhandenes Tagesaggregat kann sich auch auf einen erst im Tagesverlauf
begonnenen Aufzeichnungszeitraum beziehen. Es wird unverändert übernommen;
das Vorhandensein aller Quellen beweist keine lückenlose Tagesaufzeichnung.
Im Zustandsmodus erscheinen fehlende oder teilweise bekannte Tage als Kreise
mit ausdrücklich unbekanntem Zeitanteil.

## Kachel, IPSView und Vorschau

Beide Designer bieten Theme, Tooltip, Legende, Wochenbeginn und Tagesnummern.
Kreisradius (20–90 %), Donut-Innenradius (0–80 % des Außenradius), Startwinkel,
Drehrichtung, Segment- und Zellrahmen sowie Zelldeckkraft sind einstellbar.
Titel, Kalenderbeschriftung und Werte besitzen eigene Schriftgrößen und Farben.
Kategorienfarben stehen bei den Quellen beziehungsweise Zustandszuordnungen;
unbekannte Zustandszeit besitzt eine eigene Designfarbe.

Der Diagrammtitel hält Abstand zum nativen Kachelnamen und Vergrößerungsbutton.
Bei kleinen Zellen entfallen Tagesnummern oder Segmentwerte zugunsten einer
überlappungsfreien Darstellung. Werte bleiben im Tooltip erreichbar. Die
Segmentbeschriftung zeigt nur die Zahl; die Einheit steht im Tooltip. Texte,
die nicht vollständig in ihren Kreis beziehungsweise Ring passen, entfallen.
Die Legende ist bei vielen Kategorien blätterbar; ihre Reihenfolge und Farben
bleiben im gesamten Monat gleich. Animation einschließlich Dauer und
Bewegungskurve ist einstellbar.

IPSView erbt zunächst das Kacheldesign. Nach Deaktivieren der Vererbung lässt
es sich unabhängig gestalten; ein Kopierknopf übernimmt das gespeicherte
Kacheldesign als Ausgangspunkt. Die optionale Stringvariable
`IPSViewCalendarPie` enthält das WebContent-Dokument für ein IPSView-HTML-Widget
und wird nicht bedient. Die Daten werden minütlich aktualisiert.

Beide Formularvorschauen reagieren auf ungespeicherte Änderungen, einschließlich
Datengrundlage und Zustandszuordnung. Sie verwenden gekennzeichnete synthetische
Beispiele, keine tatsächlichen Archivwerte. Mausinteraktion und Animation
sind in der statischen Vorschau nicht enthalten. Gespeichert wird erst beim
Übernehmen der Konfiguration.

## Konfigurationsreferenz und Skriptzugriff

Die [Feldreferenz](../docs/KONFIGURATION.md#echartscalendarpie) enthält
Formularbeschriftungen, technische Namen, Typen, Standardwerte und Auswahllisten.
Für Skriptänderungen gelten die dort erläuterten JSON- und Anwenderegeln.

### Ausgabevariable

| Ident | Typ / Darstellung | Bedeutung | Einheit | Bedienbar |
| --- | --- | --- | --- | --- |
| `IPSViewCalendarPie` | String / WebContent | HTML-Dokument für das optionale IPSView-Widget | keine | Nein |

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
| `ECCP_CopyTileDesignToIPSView(int $InstanzID): void` | Kopiert das gespeicherte Kacheldesign, ersetzt die unabhängigen IPSView-Designwerte und wendet sie an; aktiviert unabhängiges Design. |
| `ECCP_GetCalendarPieData(int $InstanzID): string` | Liest das reguläre konfigurierte Diagrammmodell als JSON; keine Änderung von Quellen oder Archivwerten. |
| `ECCP_GetCalendarPieDiagnostic(int $InstanzID): string` | JSON-Momentaufnahme der Konfigurationsprüfung und letzten Fehlermeldung; kein Nachweis einer erfolgreichen Datenabfrage. |
| `ECCP_GetIPSViewHTML(int $InstanzID): string` | Vollständiges HTML-Dokument für IPSView; der Rückgabewert bestätigt keine Anzeige in einem Client. |
| `ECCP_RefreshArchive(int $InstanzID): void` | Aktualisiert die Diagrammausgaben aus den konfigurierten Quellen. Kein Erfolgswert und keine Änderung von Quellen/Archiven. |

Beispiel mit synthetischer Instanz-ID (durch die eigene ID ersetzen):

```php
$instanceID = 12000;
$model = json_decode(ECCP_GetCalendarPieData($instanceID), true, 512, JSON_THROW_ON_ERROR);
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

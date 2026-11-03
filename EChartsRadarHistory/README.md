# EChartsRadarHistory

Stellt archivierte JSLive-Radarwerte in einer nativen Symcon-Kachel und optional
als IPSView-WebContent dar. Diese Familie erhält die historischen Klassen- und
Referenzregeln eines übernommenen JSLive-Radars. Für aktuelle Vergleiche mit
eigenen Messachsen verwenden Sie [EChartsRadar](../EChartsRadar/README.md).

Installation: Im **Symcon-Modulstore** nach **SymconECharts** suchen.
[Voraussetzungen und Einrichtung](../README.md#installation-und-erstes-diagramm)
(IP-Symcon 9.0/9.1, PHP 8.5, gemeinsames Gateway).
Alle technischen Felder, Standardwerte und Quellenparameter stehen in der
[Konfigurationsreferenz](../docs/KONFIGURATION.md#echartsradarhistory).

## Einrichtung

1. Im gemeinsamen [EChartsGateway](../EChartsGateway/README.md) das JSLive-
   Exportpaket auswählen und die vollständige Übernahme vorbereiten.
2. Quellen, Archive, Darstellung und einzeln aufgeführte Unterschiede prüfen.
3. Die Neuanlage ausdrücklich bestätigen. Das Gateway erzeugt und verbindet
   die neue Instanz; die ursprüngliche JSLive-Instanz bleibt erhalten.
4. In der neuen Instanz Titel, Zeitraum und die Kachel-/IPSView-Anpassungen
   prüfen und bei Bedarf ändern.

Eine leere, manuell angelegte Instanz besitzt noch kein Diagrammmodell. Das
Modul wird in dieser Version über die bestätigte JSLive-Übernahme eingerichtet.

## Werte und Darstellung

Klassen, Referenzquellen, Sortierung, Rundung, Zeitversatz und die ausgewählte
Zeitperiode stammen aus dem Export. Es werden die bestehenden Variablen- und
Archiv-IDs gelesen; das Modul aktiviert keine Archivierung und schreibt keine
Quellwerte. Fehlende oder gekürzte Archivantworten werden als Fehler gemeldet.
Die erste Klasse liegt oben, weitere Klassen folgen wie im Original im
Uhrzeigersinn. Eine Windrose zeigt damit Osten rechts und Westen links.
Alle Reihen teilen höchstens 63 Archivabfragen und 32000 Zeilen, eine Abfrage
höchstens 2000 Zeilen. Ein zu großer Zeitraum muss verkleinert werden.

Die übernommenen Schriften, Farben und Reihenstile werden mit Apache ECharts
gerendert. Ein Original-/Zielvergleich bleibt sinnvoll: Die native Geometrie,
Textanordnung und Animation können vom alten Chart.js-Bild abweichen. Der
Importbericht nennt diese Unterschiede; fremde Vorlagen werden nicht ausgeführt.
Die native Formatierung wird aus dem Export übernommen und benötigt keine
Legacy-Profile.

## Kachel, IPSView und Vorschau

Die native Kachel berücksichtigt den Platz für Kachelnamen und Vergrößerung.
Für IPSView die Ausgabe aktivieren und die Stringvariable **Diagramm für
IPSView** als WebContent verwenden. Kachel und IPSView können eigene
Hintergrund-/Schriftanpassungen und Sichtbarkeit von Titel, Legende und Tooltip
erhalten. Standardmäßig bleibt der Hintergrund transparent.

Die Farbkarte im Formular ist als schematische Vorschau gekennzeichnet.
**Native Vorschau herunterladen** öffnet die tatsächliche ECharts-Ausgabe mit
den noch nicht gespeicherten Formularanpassungen und aktuellen Archivabfragen.
Sie speichert diese Änderungen nicht. Zeitsteuerungen gehören zur neuen
Instanz und verändern keine Steuerungsvariablen der JSLive-Instanz.

## Status und Verfügbarkeit

Eine leere Instanz meldet eine fehlende Importkonfiguration. Bei nicht
verfügbaren Quellen oder überschrittenen Archivgrenzen wird das importierte
Modell als nicht verfügbar gemeldet. Die Quellen-IDs im Formular helfen bei der
Zuordnung. Es werden keine Profile angelegt. Die optionale IPSView-Variable
verwendet die native WebContent-Darstellung.

## Konfigurationsreferenz und Skriptzugriff

Die [Feldreferenz](../docs/KONFIGURATION.md#echartsradarhistory) enthält
Formularbeschriftungen, technische Namen, Typen, Standardwerte und Auswahllisten.
Für Skriptänderungen gelten die dort erläuterten JSON- und Anwenderegeln.

### Ausgabevariable

| Ident | Typ / Darstellung | Bedeutung | Einheit | Bedienbar |
| --- | --- | --- | --- | --- |
| `IPSViewRadarHistory` | String / WebContent | HTML-Dokument für das optionale IPSView-Widget | keine | Nein |

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
| `ECRH_GetIPSViewHTML(int $InstanzID): string` | Vollständiges HTML-Dokument für IPSView; der Rückgabewert bestätigt keine Anzeige in einem Client. |
| `ECRH_GetJSLiveImportHTML(int $InstanzID, bool $IPSView): string` | `$IPSView=false`: Kachelausgabe; `true`: IPSView-Ausgabe eines übernommenen JSLive-Modells als vollständiges HTML. |
| `ECRH_RefreshJSLiveImport(int $InstanzID): void` | Aktualisiert die Diagrammausgaben aus den konfigurierten Quellen. Kein Erfolgswert und keine Änderung von Quellen/Archiven. Nur für importierte Modelle. |
| `ECRH_GetJSLiveImportData(int $InstanzID): string` | JSON-Zustand eines übernommenen JSLive-Modells einschließlich Verfügbarkeit; nur für importierte Varianten. |

Beispiel mit synthetischer Instanz-ID (durch die eigene ID ersetzen):

```php
$instanceID = 12000;
$model = json_decode(ECRH_GetJSLiveImportData($instanceID), true, 512, JSON_THROW_ON_ERROR);
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

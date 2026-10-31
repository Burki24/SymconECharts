# EChartsRadarHistory

Stellt archivierte JSLive-Radarwerte in einer nativen Symcon-Kachel und optional
als IPSView-WebContent dar. Diese Familie erhält die historischen Klassen- und
Referenzregeln eines übernommenen JSLive-Radars. Für aktuelle Vergleiche mit
eigenen Messachsen verwenden Sie [EChartsRadar](../EChartsRadar/README.md).

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

## Status und PHP-Befehle

Eine leere Instanz meldet eine fehlende Importkonfiguration. Bei nicht
verfügbaren Quellen oder überschrittenen Archivgrenzen wird das importierte
Modell als nicht verfügbar gemeldet. Die Quellen-IDs im Formular helfen bei der
Zuordnung. Es werden keine Profile angelegt. Die optionale IPSView-Variable
verwendet die native WebContent-Darstellung.

`ECRH_GetJSLiveImportData(int $InstanzID): string` liefert das aktuelle
Diagrammmodell als JSON. `ECRH_GetJSLiveImportHTML(int $InstanzID, bool $IPSView):
string` liefert das vollständige HTML für die gewählte Ausgabe.

Lizenz der eigenen Beiträge: [PolyForm Noncommercial 1.0.0](../LICENSE).

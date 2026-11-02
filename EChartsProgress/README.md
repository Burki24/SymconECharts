# EChartsProgress

Stellt einen aktuellen Zahlenwert als Pfadfortschritt oder gefüllte Form in
einer nativen Symcon-Kachel und optional in IPSView dar. Das Modul übernimmt
JSLive-Progressbar-Modelle. Für wiederholte Symbolbalken mit mehreren Quellen
verwenden Sie [EChartsPictorialBar](../EChartsPictorialBar/README.md).

## Einrichtung

1. Im gemeinsamen [EChartsGateway](../EChartsGateway/README.md) das JSLive-
   Exportpaket auswählen und die vollständige Übernahme vorbereiten.
2. Quelle, Wertebereich, Form und einzeln aufgeführte Unterschiede prüfen.
3. Die Neuanlage ausdrücklich bestätigen. Die ursprüngliche Instanz und ihre
   Quellvariable bleiben erhalten.
4. In der neuen Instanz Titel und Kachel-/IPSView-Anpassungen einstellen.

Eine leere, manuell angelegte Instanz besitzt noch kein Fortschrittsmodell.
Diese Version wird über die bestätigte JSLive-Übernahme eingerichtet.

## Wertebereich und Formen

Der Export liefert Minimal-/Maximalwert, Rundung, Schrittweite, Farben und
Form. Auch umgekehrte Bereiche bleiben erhalten. Die Füllung wird an den
Bereich begrenzt; der Originalwert samt nativer Formatierung bleibt erhalten.
Bei Strichformen entspricht die farbige Strecke dem Anteil an der gesamten
Pfadlänge, auch bei mehreren Bögen und eigenen Strichmustern.
Es wird genau eine numerische Quellvariable gelesen. Quellen, Darstellungen,
Actions und Archivierung werden nicht verändert. Legacy-Profile sind nach der
Übernahme nicht erforderlich.

Unterstützt werden die geprüften Standardformen, eigene SVG-Pfade und sichere
SVG-Füllformen aus dem Export. Aktive SVG-Inhalte, fremde Skripte, eigene
ausführbare Vorlagen und ungeprüfte Bild-/Farbeffekte werden abgewiesen. Eine
Zustimmung zu Designabweichungen hebt solche Sperren nicht auf.

SVG-Füllformen behalten ihre eigenen Farben, Verläufe und Transparenzen. Die
Füllfarbe färbt diese Bilder nicht um; der Wert bestimmt den sichtbaren
Ausschnitt. Auch die Textvorlage behält ihre ursprüngliche schwarze Schrift.
Die separate Hintergrundfarbe und ihre Verbreiterung bleiben wirksam.

### Verlaufsfarben und SVG-Füllbilder aus JSLive

Zusätzlich werden die JSLive-Felder `override_fill`, `override_stroke` und
`fill_backgroundFile` übernommen. Unterstützt sind explizite Hex-Farben,
SVG-Bilder als Base64-Daten-URI in den Overrides und
`data:ldbar/res,gradient(Winkel,Dauer,Farbe,...)`
mit zwei bis 16 Hex-Farben, Winkel 0–360° und Dauer 0–3600 Sekunden. Der
Verlauf behält Richtung und wiederholte Palette als statisches Anfangsbild.
Die zyklische Bewegung dieser alten Bildressource wird im Importbericht
gesondert als Abweichung ausgewiesen. Wertanimationen bleiben einstellbar.

Das SVG-Füllbild muss als `image/svg+xml` eingebettet sein; ein explizites
`override_fill` hat Vorrang. Eigene SVG-Formen behalten weiterhin ihre eigenen
Farben. Die Bildgröße und Musterperiode folgen der Quelle und ändern sich
nicht mit dem Wert. Die Rasterauflösung passt sich dem verfügbaren Platz und
der Pixeldichte an, innerhalb von maximal 4096 × 4096 Bildpixeln.

SVG-Muster dürfen nur lokale Farbverweise verwenden. Verschachtelte oder
rekursive Muster, externe Bilder, Skripte und Animationselemente bleiben
gesperrt. Muster unterstützen Drehung und Verschiebung; ihre Dichte ist auf
höchstens 64 Wiederholungen je Achse begrenzt. Prozentuale Bildgrößen und
externe Bildadressen werden nicht nachgeladen. Die vorhandenen Datei-,
Komplexitäts-, Modell- und Ausgabegrenzen gelten gemeinsam weiter.

Bei JSLive kann der native SVG-Dateipfad schwarz erscheinen, weil die alte
Engine ihn nicht als Bildfarbe erkennt. ECharts verwendet die geprüften
eingebetteten SVG-Daten. Diesen sichtbaren Unterschied nennt der Importbericht
ausdrücklich, bevor eine neue Instanz angelegt werden kann.

Einpassung und Ränder folgen dem verfügbaren Bereich der Quellvorlage. Die
untere Wertanzeige sitzt unter diesem Bereich; die mittige Anzeige bleibt in
seiner Mitte. Linienenden entsprechen der tatsächlich sichtbaren Quellform.
Eine dort wirkungslose gespeicherte Auswahl wird bei der Übernahme nicht
nachträglich aktiviert.

Pfadfüllungen übernehmen auch die Hintergrundränder der Quellform. Pfade ohne
Breite oder Höhe, darunter `line` und `rainbow`, bleiben im Füllmodus leer;
eine aktivierte Wertanzeige bleibt sichtbar. Es wird kein zusätzlicher grauer
Strich erzeugt.

Apache ECharts zeichnet Pfade, Füllung, Verlauf und Text nativ. Schatten,
Strichmuster, Textkonturen und Animationen können von den alten Engines
abweichen; der Importbericht nennt die betroffenen Einstellungen einzeln.
Eine Textvorlage ohne Strichpfad wird als füllbarer Text dargestellt.

## Kachel, IPSView und Vorschau

Die Kachel reserviert Platz für Kachelnamen und Vergrößerung. Für IPSView die
Ausgabe aktivieren und **Diagramm für IPSView** als WebContent einbinden.
Hintergrund, Schriftvergrößerung und Sichtbarkeit von Titel, Legende, Tooltip
und Animation sind für beide Ausgaben einstellbar. IPSView kann das Kacheldesign
übernehmen. Der Standardhintergrund ist transparent.

Die Farbkarte ist eine gekennzeichnete schematische Vorschau. **Native Vorschau
herunterladen** liefert die tatsächliche ECharts-Ausgabe einschließlich noch
nicht gespeicherter Formularanpassungen, ohne sie zu übernehmen.

## Status und PHP-Befehle

Eine leere Instanz meldet die fehlende Importkonfiguration. Ungültige Modelle
oder fehlende Quellen werden als nicht verfügbar angezeigt. Das Modul legt
keine Profile an; die optionale Stringvariable für IPSView verwendet die native
WebContent-Darstellung.

`ECPG_GetJSLiveImportData(int $InstanzID): string` liefert das aktuelle Modell
als JSON. `ECPG_GetJSLiveImportHTML(int $InstanzID, bool $IPSView): string`
liefert das vollständige HTML für die gewählte Ausgabe.

Lizenz der eigenen Beiträge: [PolyForm Noncommercial 1.0.0](../LICENSE).

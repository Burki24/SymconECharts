# EChartsHeatmap

`EChartsHeatmap` zeigt eine numerische Archivvariable als 7 × 24-Heatmap:
lokale Stunden von 0 bis 23 Uhr und je nach Zeitraum konkrete Tage oder
gemittelte Wochentage. Eine
gemeinsame `EChartsGateway`-Instanz genügt auch für diese Diagrammfamilie.

## Voraussetzungen und Daten

Benötigt werden IP-Symcon 9.0/9.1, eine numerische Integer- oder
Float-Variable mit aktiviertem Archiv und das verbundene EChartsGateway.
Die Variable wird über ihre ID zugeordnet; ihr Name ist kein technischer
Schlüssel.

Wählbar sind sieben oder dreißig lokale Kalendertage einschließlich heute.
Sieben Tage zeigen jeden Tag mit Wochentag und Datum. Bei dreißig Tagen
werden alle verfügbaren Stundenwerte des gewählten 30-Tage-Zeitraums
berücksichtigt: Jede Zelle zeigt den Durchschnitt für genau ihren Wochentag
und ihre Stunde, etwa aus den vorhandenen Montagen um 08:00 Uhr. Die sieben
Mo–So-Zeilen sind also keine Beschränkung auf sieben Tage. Der Datumsbereich
und die Kennzeichnung „30-Tage-Durchschnitt“ stehen über dem Diagramm.
Mit den Pfeilen wechseln Sie um sieben bzw. dreißig
Tage in frühere oder spätere, lückenlos anschließende Zeiträume; „Heute“ führt
zur aktuellen Ansicht zurück. Der Wechsel gilt für die gerade geöffnete
Kachel beziehungsweise IPSView-Seite und wird nicht als Instanzeinstellung
gespeichert. Nach dem erneuten Öffnen erscheint wieder der aktuelle Zeitraum.
Das Gateway liefert ausschließlich abgeschlossene stündliche Durchschnittswerte.
Werte derselben lokalen Wochentag-Stunde-Kombination werden bei dreißig Tagen
erneut arithmetisch gemittelt; der Tooltip nennt die Anzahl der tatsächlich
berücksichtigten Stundenintervalle. Eine fehlende Stunde bleibt leer, nicht
null. Beim Wechsel von Sommer- auf
Winterzeit gehen beide tatsächlich vorhandenen 02-Uhr-Intervalle in dieselbe
Zelle ein. Beim umgekehrten Wechsel wird keine 02-Uhr-Zelle erfunden.
Tooltip und optional sichtbare Zellenwerte verwenden Einheit und
Nachkommastellen aus der Variablendarstellung oder den manuellen Angaben.

## Design und Ausgabe

Der Kacheldesigner enthält Theme, Farben der Werteskala, sichtbare Farbskala,
optionale Zellenwerte, Textgrößen und -farben sowie Animation. „Individuell“
verläuft zwischen Start- und Endfarbe; bis zu drei optionale Zwischenfarben
ergeben insgesamt zwei bis fünf gleichmäßig verteilte Farbstützpunkte. Ohne
Zwischenfarben bleibt das bisherige Aussehen erhalten. Die individuelle
Skala zeigt ihren kleinsten und größten Archivwert mit der gewählten Einheit.
Neben dieser individuellen Skala stehen Tempera-12 und
Tempera-6 sowie acht historische Farbreihen aus der Untersuchung von
[Schiewe (2023)](https://kartdok.staatsbibliothek-berlin.de/servlets/MCRFileNodeServlet/kartdok_derivate_00000704/Schiewe_Farbschemata%20f%C3%BCr%20Temperaturkarten_Was%20liegt%20zwischen%20Blau%20und%20Rot_KN-IP_2023.pdf)
zur Wahl. Die als NOAA, SAWS, DWD, tagesschau, FAZ, USA Today, China Daily
und ABC.net bezeichneten Beispiele stammen aus Karten vom April 2023. Ihre
Farbtöne wurden aus den Abbildungen näherungsweise rekonstruiert: Es sind
**keine aktuellen, offiziell veröffentlichten Anbieterpaletten**. Die
Vorlagen verwenden feste Celsius-Klassen statt den beobachteten Wertebereich
immer neu auf die gesamte Palette zu verteilen. NOAA reicht gemäß Quelle von
−50 bis +50 °C in 20 Klassen zu je 5 °C. Die Bereiche der anderen historischen
Beispiele sind näherungsweise aus der Abbildung abgelesen, nicht als exakte
Anbietergrenzen zu verstehen. Für Tempera nennt die Quelle keine Grenzen;
hier verwendet das Modul einen eigenen Bereich von −20 bis +40 °C. Werte
außerhalb einer Vorlage erhalten die erste beziehungsweise letzte Klassenfarbe.
Die kompakte Legende zeigt alle Farbfelder und die festen Endwerte in °C,
auch wenn die aktuellen Archivwerte nur einen Ausschnitt belegen. Für diese
Vorlagen müssen die Daten bereits als °C vorliegen; eine Umrechnung erfolgt
nicht. Die eigenen Farben greifen nur bei „Individuell“; bestehende Instanzen
behalten diese Voreinstellung. Bei aktivierten Zellenwerten wählt
„Wertbeschriftungsfarbe: Automatischer Kontrast“ für jede Zelle eine helle oder
dunkle Schrift passend zu ihrer Füllfarbe. Eine manuell gewählte Schriftfarbe
hat Vorrang.
Die Formularvorschau verwendet ausdrücklich Beispieldaten und liest beim
Bearbeiten kein Archiv. Das Diagramm selbst zeigt leere Felder ohne
Interpolation.

Bei bestehenden Instanzen kann die 7-Tage-Ansicht nach dem Modulupdate andere
Zellenwerte zeigen: Bisher wurden 168 Stunden rückwärts ab der aktuellen
Uhrzeit betrachtet, einschließlich zweier angebrochener Tage. Jetzt zählen
sieben vollständige lokale Datumszeilen, wobei heute nur bis zur letzten
abgeschlossenen Stunde reicht. Die 30-Tage-Mittelung bleibt erhalten.

Die native Symcon-Kachel und ein optionales IPSView-WebContent-Widget verwenden
denselben Archivwert. Das IPSView-Design kann das Kacheldesign erben oder
unabhängig eingestellt werden. Bei einem vorübergehenden Ladefehler bleibt
eine zuletzt gültige Grafik sichtbar und die Archivabfrage wird beim nächsten
Aktualisierungsintervall wiederholt.

## Funktionen

```php
$json = ECHM_GetHeatmapData($InstanceID);
$html = ECHM_GetIPSViewHTML($InstanceID);
```

## Lizenz

Lizenz der eigenen Beiträge:
[PolyForm Noncommercial License 1.0.0](../LICENSE).

# EChartsBoxplot

Vergleicht die Verteilung archivierter Messwerte in einer nativen Symcon-Kachel
und optional als eigenes IPSView-WebContent-Widget.

Installation: Im **Symcon-Modulstore** nach **SymconECharts** suchen.
[Voraussetzungen und Einrichtung](../README.md#installation-und-erstes-diagramm)
(IP-Symcon 9.0/9.1, PHP 8.5, gemeinsames Gateway).
Alle technischen Felder, Standardwerte und Quellenparameter stehen in der
[Konfigurationsreferenz](../docs/KONFIGURATION.md#echartsboxplot).

## Einrichtung

1. Das vorhandene gemeinsame EChartsGateway auswählen.
2. Unter „Boxplot-Variablen“ 1–32 numerische Standard-Archivvariablen derselben
   Einheit hinzufügen, beispielsweise Raumtemperaturen. Anzeigename und
   Boxfarbe sind optional. Eine Variable darf nur einmal vorkommen;
   unterschiedliche Variablen dürfen denselben Namen haben.
3. Den Zeitraum wählen: letzte 24 Stunden, 7 Tage oder 30 Tage. Alternativ
   „Benutzerdefiniert“ wählen und 1–1000 Minuten, Stunden, Tage oder Wochen angeben.
   Für feste Anfangs-/Enddaten „Fester historischer Zeitraum“ wählen.
4. Optional das „Whisker-Modell“ wählen: Minimum/Maximum (Standard) oder
   „1,5 IQR mit separaten Ausreißern“.
5. Optional Titel und Design einstellen und Änderungen übernehmen.

Einheit und Nachkommastellen stammen aus der Variablendarstellung beziehungsweise
einem kompatiblen Profil. Zählervariablen werden nicht unterstützt.

## Bedeutung der Boxen

Jede Box gehört einer Variable. Ihre Stichproben sind die gleich gewichteten,
abgeschlossenen **Stundenmittelwerte** des Archivs im gewählten Zeitraum,
nicht die einzelnen Rohwert-Ereignisse. Die laufende Stunde bleibt unberücksichtigt.
Die Darstellung wird minütlich aktualisiert.

Benutzerdefinierte Zeiträume sind rollende Zeitfenster bis zum Ende der letzten
abgeschlossenen Stunde, keine frei gewählten Anfangs-/Enddaten. Auch bei einer
Angabe in Minuten bleiben die Stichproben Stundenmittelwerte. Beispielsweise
reichen 90 Minuten bei letzter abgeschlossener Stunde um 12:00 Uhr von 10:30 bis
11:59:59 Uhr; verwendet wird darin nur der Stundenwert ab 11:00 Uhr. Sehr kurze
Zeiträume können deshalb ohne Stichproben bleiben. Ein Tag entspricht 24 Stunden,
eine Woche sieben solchen Tagen, auch bei einer Zeitumstellung.

### Fester historischer Zeitraum

Unter **Von (einschließlich)** und **Bis (ausschließlich)** Datum und Uhrzeit
auswählen. Es gilt die im Formular angezeigte Symcon-Zeitzone, nicht die
Zeitzone des Browsers. Beginn und Ende müssen gültig sein; der Beginn muss
vor dem Ende liegen und das Ende darf nicht in der Zukunft liegen.

Beispiel: **01.10.2026 00:00 bis 08.10.2026 00:00** umfasst den 1. bis
einschließlich 7. Oktober. Der Zeitraum wandert beim Aktualisieren nicht mit.
Nur vollständig enthaltene, abgeschlossene Stunden werden ausgewertet:
**08:30 bis 13:30** verwendet die vier Stunden ab 09:00, 10:00, 11:00 und
12:00. Eine Auswahl ohne vollständige Stunde bleibt ohne Daten; sie erzeugt
keine Archivabfrage und keine Nullwerte. Die laufende Stunde bleibt auch dann
ausgeschlossen, wenn das gewählte Ende dem aktuellen Zeitpunkt entspricht.

Bei der Umstellung auf Winterzeit werden beide tatsächlich vorhandenen
Stunden berücksichtigt, wenn sie vollständig im Zeitraum liegen. Ein
ausgewählter Grenzzeitpunkt innerhalb einer doppeldeutigen Stunde wird dagegen
mit Hinweis abgewiesen, ebenso eine bei der Sommerzeitumstellung nicht
existierende Uhrzeit. Wählen Sie dann einen eindeutigen Grenzzeitpunkt,
beispielsweise Mitternacht.
Seltene Zeitzonenwechsel mit einer nicht ganzstündigen Verschiebung, etwa
30 Minuten, werden für betroffene historische Stundenfenster ausdrücklich
abgewiesen. Zeiträume davor oder danach sowie die üblichen ganzstündigen
Sommer-/Winterzeitwechsel bleiben unterstützt.

Kachel und IPSView verwenden denselben festen Zeitraum; ihre Designs bleiben
unabhängig. Die Formularvorschau zeigt die gewählten Grenzen samt UTC-Offset
und Zeitzone, aber weiterhin ausdrücklich Beispieldaten. Ungültige Datumsentwürfe
werden in der Vorschau erklärt, ohne Archive zu lesen. Die bisherigen
Voreinstellungen und rollierenden benutzerdefinierten Zeiträume bleiben erhalten.

Die Box reicht vom ersten zum dritten Quartil; die Linie darin ist der Median.
Quartile werden linear interpoliert (R7). Das Whisker-Modell gilt gemeinsam
für Kachel und IPSView und verändert keine Gestaltungseinstellungen:

- **Minimum und Maximum (Standard):** Die Enden zeigen wie bisher das kleinste
  und größte Stundenmittel. Bestehende Instanzen behalten dieses Modell.
- **1,5 IQR mit separaten Ausreißern:** Der Interquartilsabstand ist `IQR = Q3 − Q1`.
  Die Enden reichen bis zum kleinsten beziehungsweise größten tatsächlich
  vorhandenen Stundenmittel innerhalb von `Q1 − 1,5 × IQR` und `Q3 + 1,5 × IQR`.
  Werte genau auf den Grenzen gehören dazu. Werte außerhalb erscheinen als
  einzelne Punkte in der jeweiligen Quellfarbe. Auch bei gleichem Wert bleiben
  unterschiedliche Stunden als eigene Stichproben erhalten; Punkte können
  optisch übereinanderliegen. Bei IQR = 0 sind vom Quartil abweichende Werte Ausreißer.

Das neue Modell klassifiziert **Stundenmittelwerte**, keine kurzzeitigen
Rohwertspitzen. Quartile, Median und Stichprobenanzahl beziehen sich weiterhin
auf sämtliche geladenen Stunden, einschließlich der Ausreißer. Im IQR-Modus
zeigt der Box-Tooltip zusätzlich echte Minimum-/Maximumwerte und die
Ausreißeranzahl. Ein Punkt-Tooltip zeigt Originalwert, Einheit und Zeitpunkt
des Stundenintervalls. Ausreißer sind eine statistische Einordnung, keine
automatische Fehler- oder Alarmbewertung.

Fehlende Stunden bleiben fehlend und werden weder interpoliert noch durch Null
ersetzt. Die Quellen müssen keine gemeinsamen Stunden besitzen. Eine einzelne
Stichprobe ergibt eine zusammengefallene Box, eine leere Quelle bleibt ohne
Box. Sind alle Archive leer, erscheint ein Hinweis. Der Tooltip zeigt die fünf
Kennzahlen, Stichprobenanzahl sowie ersten und letzten erfassten Zeitpunkt.
Teilarchive lassen sich damit erkennen; sie sind nicht automatisch vollständig.

Pro Aktualisierung werden höchstens 10000 Stundenmittelwerte insgesamt und
1000 je Quelle geladen. 32 vollständige Quellen über sieben Tage passen in
dieses Budget. Bei vielen dicht aufgezeichneten 30-Tage-Archiven kann ein
kürzerer Zeitraum erforderlich sein; das Diagramm meldet dies ausdrücklich,
statt Quartile aus abgeschnittenen Daten zu berechnen.
Dieselbe Begrenzung gilt auch bei benutzerdefinierten langen Zeiträumen;
eine größere Zeiteinheit erhöht das Archivbudget nicht.
Auch ein fester historischer Zeitraum erhöht diese Budgets nicht.
Das IQR-Modell verwendet dieselben bereits geladenen Stundenmittelwerte,
ohne zusätzliche Archivabfragen, Ausdünnung oder ein gesondertes Ausreißerbudget.

## Gestaltung und IPSView

Kachel und IPSView besitzen getrennte Designer mit gekennzeichneten synthetischen
Beispielvorschauen. Einstellbar sind Ausrichtung, ECharts-Theme, Animation,
maximale Boxbreite, Randbreite und -farbe, Boxdeckkraft, Raster, Tooltip,
Achsenbeschriftung sowie Schriftgrößen und Farben für Titel und Achsen.
Die maximale Boxbreite wird auf schmalen Flächen automatisch begrenzt.
Lange Kategorienamen werden bei Platzmangel gekürzt; im Tooltip bleiben sie vollständig.

Bei vielen Kategorien erscheint automatisch eine Navigation per Schieberegler
oder Mausrad. Das gilt horizontal und vertikal. Boxbreite und Schriftgröße
bestimmen, wie viele Kategorien gleichzeitig lesbar sind. Der Ausschnitt bleibt
bei Aktualisierungen und Größenänderungen an derselben Quelle verankert.
Die Werteskala berücksichtigt weiterhin alle Quellen, nicht nur die sichtbaren.
Im IQR-Modus umfasst sie auch sämtliche Ausreißer außerhalb des Ausschnitts.
Leere Archive behalten ihren Kategorieplatz, ohne eine Null-Box zu erzeugen.
Zahlen auf der Wertachse werden sprachgerecht und ohne lange Rechen-Nachkommastellen
angezeigt. Die Genauigkeit richtet sich nach den Quellvariablen und wird bei
sehr kleinen Wertebereichen erhöht, damit Teilstriche unterscheidbar bleiben.
Skalengrenzen, Statistik und die quellenspezifische Formatierung im Tooltip
bleiben davon unverändert.

Der Designer bietet außerdem:

- minimale und maximale Boxbreite sowie durchgezogene, gestrichelte oder
  gepunktete Konturen für Boxen, Median und Enden;
- Schatten mit Farbe, Unschärfe und X-/Y-Versatz;
- abschaltbare Hover-Hervorhebung und die Deckkraft der übrigen Boxen;
- Kategorien-Teilstriche, Kategorien-Achsenbreite, Drehung und maximale
  Textbreite der Kategoriennamen (0 = automatisch);
- gewünschte Unterteilungsanzahl der Werteachse sowie Rasterlinienart,
  -breite, -farbe und -deckkraft; ECharts bestimmt die tatsächlichen
  Skalenabstände anhand der Werte;
- Fettschrift für Titel und Achsenbeschriftungen.

Die minimale Boxbreite darf nicht größer als die maximale sein. Beide Breiten
und die Textbreite werden bei Platzmangel responsiv begrenzt. Die Vorschau
zeigt statische Gestaltung mit Beispieldaten; Hover-Effekte sind erst im
Diagramm sichtbar. Die Vorschau zeigt je nach Schriftgröße und Ausrichtung
höchstens zehn Quellen und kennzeichnet die gezeigte Anzahl gegenüber der
Gesamtzahl. Datenfenster und Statistik werden durch diese Optionen
nicht verändert.

IPSView übernimmt standardmäßig das Kacheldesign. Für ein unabhängiges Design
die Übernahme ausschalten oder das Kacheldesign ausdrücklich kopieren und
anschließend separat bearbeiten. Die Datenquellen und der Zeitraum bleiben
für beide Ausgaben gemeinsam. Optional kann der IPSView-Hintergrund durchscheinen.

Der Diagrammtitel steht unter dem Symcon-Kachelkopf. Eine Änderung der
Gestaltung verändert weder Variablenwerte noch Archivdaten.

## Konfigurationsreferenz und Skriptzugriff

Die [Feldreferenz](../docs/KONFIGURATION.md#echartsboxplot) enthält
Formularbeschriftungen, technische Namen, Typen, Standardwerte und Auswahllisten.
Für Skriptänderungen gelten die dort erläuterten JSON- und Anwenderegeln.

### Ausgabevariable

| Ident | Typ / Darstellung | Bedeutung | Einheit | Bedienbar |
| --- | --- | --- | --- | --- |
| `IPSViewBoxplot` | String / WebContent | HTML-Dokument für das optionale IPSView-Widget | keine | Nein |

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
| `ECBX_CopyTileDesignToIPSView(int $InstanzID): void` | Kopiert das gespeicherte Kacheldesign, ersetzt die unabhängigen IPSView-Designwerte und wendet sie an; aktiviert unabhängiges Design. |
| `ECBX_GetBoxplotData(int $InstanzID): string` | Liest das reguläre konfigurierte Diagrammmodell als JSON; keine Änderung von Quellen oder Archivwerten. |
| `ECBX_GetBoxplotDiagnostic(int $InstanzID): string` | JSON-Momentaufnahme der Konfigurationsprüfung und letzten Fehlermeldung; kein Nachweis einer erfolgreichen Datenabfrage. |
| `ECBX_GetIPSViewHTML(int $InstanzID): string` | Vollständiges HTML-Dokument für IPSView; der Rückgabewert bestätigt keine Anzeige in einem Client. |
| `ECBX_RefreshArchive(int $InstanzID): void` | Aktualisiert die Diagrammausgaben aus den konfigurierten Quellen. Kein Erfolgswert und keine Änderung von Quellen/Archiven. |

Beispiel mit synthetischer Instanz-ID (durch die eigene ID ersetzen):

```php
$instanceID = 12000;
$model = json_decode(ECBX_GetBoxplotData($instanceID), true, 512, JSON_THROW_ON_ERROR);
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

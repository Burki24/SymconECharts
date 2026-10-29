# Gemeinsame Systemprüfung am 26.10.2026

Nachtrag 28.10.2026: Der hier historisch beschriebene Mehr-Gateway-Hookfehler
ist mit Regressionstest korrigiert und live nachgeprüft; siehe
[gemeinsame Beta-Abnahme](BETA_ABNAHME-2026-10-28.md). Der Archivdatenkonflikt und
die tatsächlich nicht geprüften Varianten bleiben davon unberührt. Die bereits
bestätigten IPSView-Darstellungen wurden vom Eigentümer nochmals bestätigt.

## Auftrag und Urteil

Zusammenhängende Prüfung der erreichbaren Testlücken mit einem Gesamt-Fazit,
ohne einzelne PRs, Commits oder Pushes. Bestehende Quellen, Archivdaten,
Konfigurationen und Designs sollten erhalten bleiben. Geprüft wurde die
freigegebene Entwicklungs-/Testinstallation, nicht die Produktion.

Der reguläre gemeinsame Gateway-Datenweg und die geprüften Last-, Fehler- und
Wiederanlauffälle funktionieren. Die Prüfung ist jedoch **keine uneingeschränkte
Freigabe**: Ein zusätzlich angelegtes Gateway übernahm den gemeinsamen HTTP-Hook.
Dieser Mehr-Gateway-Fall benötigt eine Korrektur. Daneben besteht ein realer
Archivdatenkonflikt bei StateTimeline; er wurde nicht durch Datenänderungen
verdeckt. Die Funktionsroadmap bleibt abgeschlossen, diese Befunde und die unten
genannten Testgrenzen bleiben davon getrennt.

Lokaler Ausgangsstand: `dev`, `3a1323e`, Library-Metadaten `1.346`.
Live-Umgebung: Symcon 9.1, PHP 8.5.8, Windows, Zeitzone Europe/Berlin.
Der Bericht enthält keine realen Quellen-IDs, Kanal-Token oder Zugriffspfade.
Produktionscode und Repositorytests wurden bei dieser Prüfung nicht geändert.

## Ergebnisse

| Prüfbereich | Beobachtung | Bewertung |
| --- | --- | --- |
| Vollständige lokale Qualitätssuite | `php tests/quality.php --fix` endet mit Exit 0; Style, Syntax, Verträge, Integration und Layout bestehen. | Bestanden auf lokaler Testebene. |
| Bestehende Diagramme | Datenaufrufe aller 29 Familien über 31 Diagramminstanzen: 30 gültige Modelle und Status 102; StateTimeline weist widersprüchliche Archivdaten mit Status 204 ab. | Datenweg grundsätzlich funktionsfähig; konkreter Datenfehler bleibt sichtbar. |
| Kachelausgaben | MCP erzeugt alle 31 HTML-Dokumente unter 1 MiB. Im Browser besitzen 30 Ansichten Canvas-Flächen; StateTimeline zeigt die deutsche Fehlermeldung statt eines Diagramms. | Initiale Kachelvorschau geprüft, kein Nachweis nativer Live-Pushs. |
| IPSView-Dokumente | Alle 31 öffentlichen HTML-Ausgaben liefern nichtleere Dokumente unter 1 MiB, einschließlich der Fehleransicht. | Ausgabeerzeugung geprüft; kein erneuter vollständiger Test der nativen IPSView-App. |
| TimeSeries-Last | 32 synthetische Archivquellen, zunächst genau 32000 Punkte ohne Kürzung; Modell 583631 Bytes, IPSView 887420 Bytes. | Volles Punktbudget auf echtem Archive Control geprüft. |
| TimeSeries-Bytegrenze | Eine zunächst verwendete Variante mit langen Nachkommastellen überschreitet die Modellgrenze und wird kontrolliert abgelehnt. Mit auf eine Nachkommastelle begrenzten synthetischen Werten passt dasselbe Punktbudget. | Punkt- und Bytebudgets gelten weiterhin unabhängig. |
| Parallel-Last | 16 Achsen × 625 gemeinsame Stundenintervalle = 10000 Aggregate; Modell 209803 Bytes, IPSView 780281 Bytes, Status 102. | Volles gemeinsames Ladebudget geprüft. |
| Heatmap-Last | 366 Tage mit 8784 echten Stundenaggregaten; alle 8784 gehen in die 168 Wochentag-/Stundenzellen ein, `truncated=false`, Status 102. | Vollständiges Stundenfenster auf echtem Archiv geprüft; keine zusätzliche maximale Mehrpanel-Lastfreigabe. |
| Aktuelle Werte | Änderungen ausschließlich synthetischer Variablen erscheinen im eingebetteten PictorialBar-Dokument über den echten Transport. | VM_UPDATE-/Push-Datenweg nachgewiesen. |
| Verbindungsabbruch | Lokaler Relay schließt nur drei Testverbindungen und weist 18 Wiederverbindungsversuche ab. Der letzte gültige Wert bleibt sichtbar. | Ausfall tatsächlich erzeugt, reguläre Clients bleiben angebunden. |
| Wiederanlauf | Nach Freigabe drei neue Socket-Verbindungen und drei erfolgreiche HTTP-Zustandsabrufe; ein während der Sperre von 55,5 auf 77,7 geänderter Wert erscheint ohne Neuladen. | Wiederanlauf nachgewiesen, nicht nur erhaltene Altanzeige. |
| Ansichtserhalt | Abgewählte TimeSeries-Reihe bleibt nach Wiederanlauf und Größenwechsel abgewählt. Parallel-Achsenknopf verschiebt auf Achsen 2–5; diese Position bleibt nach weiteren Fehler-/Wiederherstellungsaufrufen erhalten. | Gezielte Auswahl-/Achsenmatrix bestanden. Kein zusätzlicher vollständiger Zeitzoomnachweis in diesem Lauf. |
| Größenwechsel | Drei echte generierte IPSView-Dokumente wechseln ohne Neuladen zwischen 430×500 und 900×650 Pixeln. Die Diagramme werden neu gezeichnet. | Browser-Einbettung geprüft, kein Geräte-/Touch-Nachweis. |
| Quellenprüfung | Falscher Variablentyp und fehlende Quelle: Status 201; nach Rücksetzen Status 102 und gültiges Modell. | Fehler und Erholung bestanden. |
| Gatewayfehler | Nur Testgerät getrennt: Status 203; nach Wiederverbinden Status 102 und gültiges Modell. | Fehler und Erholung bestanden. |
| Archivtypfehler | Parallel-Verbrauchskennzahl an Standardarchiv: Status 204; Rückkehr zum gültigen Modell nach Wiederherstellung. | Keine erfundenen Ersatzwerte. |
| HTTP-Prüfung | Gültiger Kanal 200, falscher Kanal 404, ungültiger Offset/Ausgabeparameter 400, falsche Methode 404; deaktivierter Testtransport 404, nach Aktivierung wieder 200. | Negative Transportfälle bestanden. |

Nach zusätzlichen aktuellen Testwertänderungen muss TimeSeries bei weiterhin
32000 erlaubten Punkten ältere Rohwerte abschneiden. Der danach beobachtete
Kürzungshinweis und `truncated=true` sind erwartetes Budgetverhalten; sie
widersprechen dem vorherigen ungekürzten 32000-Punkte-Nachweis nicht.

Fünf weitere Datenaufrufe je Lastmodell ergaben folgende Laufzeiten. Gemessen
wurde die öffentliche Modellfunktion innerhalb Symcons, einschließlich des
jeweils wirksamen Caches, ohne Netzwerk- oder Browser-Zeichenzeit:

| Modell | Minimum | Median | Maximum |
| --- | ---: | ---: | ---: |
| TimeSeries, 32000 Punkte | 964,34 ms | 1005,57 ms | 1114,25 ms |
| Parallel, 10000 Aggregate | 373,12 ms | 387,86 ms | 488,15 ms |
| Heatmap, 8784 Stunden | 227,64 ms | 250,65 ms | 400,81 ms |

Diese kurzen Wiederholungen sind kein mehrstündiger Last- oder Speichertest.

## Debug-Praxisnachweis

Der gemeinsame flüchtige Diagnosepuffer verlor bei aktivierter Gateway-Diagnose
auf der laufenden Installation Meldungen. Daraus lässt sich kein vollständiger
Ablaufnachweis ableiten. Die Gateway-Diagnose wurde wieder deaktiviert.

Für zwei ausschließlich temporäre Diagramminstanzen wurde deshalb die
dateibasierte Diagnose verwendet und nach dem Test wieder beendet. Der aktuelle
Quellen-/Gatewayfehlerlauf enthält 25 START-/25 ENDE-Meldungen und fünf
Fehlermeldungen; der historische Archivtyp-/Erholungslauf 82 START-/82
ENDE-Meldungen und 16 Fehlermeldungen. Die Fehlermeldung benennt beim Archivtyp
ausdrücklich „Auswertung passt nicht zum Archivtyp.“ Dokumente bleiben als
geschützte Ergebnisse beschrieben. In den erfassten Nachrichten wurden keine
HTML-/SVG-Dokumente, Testtitel oder Transportpfade gefunden. Die längsten
erfassten Zeilen umfassen 386 beziehungsweise 468 Zeichen.

Die Menge der Gateway-Meldungen und die Rotation des gemeinsamen Puffers sind
eine praktische Diagnosegrenze. Vollständige Langzeit- und Gateway-Mengenmessung
über alle Module sowie die gesamte Debug-Matrix in den nativen Hosts sind damit
weiterhin nicht belegt. Die beiden selbst erzeugten Debugdateien wurden nach
Auswertung entfernt.

## Bestätigte Befunde

### Gemeinsamer HTTP-Hook bei mehreren Gateways

`EChartsGateway::Create()` registriert für jede neue Instanz denselben Hook.
Ein zusätzlich zur bestehenden gemeinsamen Instanz angelegtes Test-Gateway
übernahm dadurch den Handler. Der bisherige Zustandspfad eines unveränderten
Bestandsgeräts lieferte anschließend HTTP 404. Die Entfernung des zusätzlichen
Gateways stellte den ursprünglichen Handler nicht automatisch wieder her.

Der Test wurde abgebrochen, die Testgeräte mit dem ursprünglichen Gateway
verbunden und ausschließlich dessen flüchtige Hookregistrierung wiederhergestellt.
Der bestehende Zustandspfad antwortete danach und bei der Abschlusskontrolle
wieder mit HTTP 200 und gültigem JSON. Persistente Bestandskonfigurationen wurden
nicht geändert. Der Befund widerspricht dem dokumentierten zulässigen Betrieb
zusätzlicher Gateways und muss vor dessen Freigabe korrigiert und abgesichert
werden. Im regulären Betrieb mit einem gemeinsamen Gateway besteht dieses
konkret nachgewiesene Auslöseszenario nicht.

### StateTimeline: widersprüchliche Archiveinträge

Nachtrag 29.10.2026: Die genauere Prüfung zeigt schnelle Wechsel mit eindeutigem
positivem Dauereintrag. Die pauschale Einordnung als Datenfehler ist damit
korrigiert; die gemeinsame Auswertung und alle neun Fälle sind nachgeprüft,
ohne Archive zu verändern. Siehe [aktueller Nachweis](STATE_TIMELINE_ARCHIVPRUEFUNG-2026-10-29.md).
Der folgende Absatz bleibt als historischer Befund des damaligen Laufs erhalten.

Eine bestehende Boolean-Quelle besitzt an einem Sekundenzeitpunkt unterschiedliche
archivierte Zustände. Die gemeinsame Archivprüfung verwirft solche Daten
absichtlich, statt eine Reihenfolge zu erfinden. Die Instanz meldet Status 204
und zeigt eine Fehleransicht. Andere geprüfte Kontaktarchive enthalten diesen
Konflikt nicht. Dies ist ein bestätigter Datenkonflikt, kein nachgewiesener
Rendererfehler. Das reale Archiv wurde nicht korrigiert oder gelöscht.

### Browserbeobachtungen

Im lokalen Relay-Tab trat einmal `MutationObserver.observe` mit ungültigem
Node-Argument ohne zuordenbaren Modul-Stack auf. Der direkte zweite
MCP-Kachelgruppentab lieferte keine erfassten Browserfehler. Der einzelne Fehler
wird entsprechend dem bereits dokumentierten IAB-/Vorschau-Befund nicht ohne
weiteren Nachweis als Moduldefekt klassifiziert. Ein insgesamt fehlerfreies
Browserprotokoll wird nicht behauptet.

Ein vertikaler Radversuch über PictorialBar bewegte die äußere Testseite; eine
interne Kategorienbewegung wurde dadurch nicht nachgewiesen. Daraus wird keine
zusätzliche Mausradfreigabe abgeleitet. Die bereits bestätigte native
WoIst-/Helper-Mausradprüfung bleibt erledigt und wurde nicht erneut verlangt.

## Wiederherstellung und verbleibende Grenzen

- SHA-256-Vergleich aller 32 ursprünglichen ECharts-Konfigurationen: keine
  Änderung. Bestehende Quellen, Designs und reale Archivdaten blieben erhalten.
- Alle fünf im Verlauf erzeugten Testinstanzen einschließlich des früh entfernten
  Zusatzgateways sind gelöscht. Die vier abschließenden Diagrammtestinstanzen,
  32 synthetischen Variablen, deren 59591 verbliebenen Rohdatensätze, Ausgabe-
  variablen, Testkategorie und Testmanifest wurden entfernt.
- Debugsitzungen sind beendet, der Relay ist gestoppt und die drei neu angelegten
  Browsertabs sind geschlossen. Der originale HTTP-Zustandsabruf ist wieder 200.
- Die automatische Freigabeprüfung blockierte das Löschen des lokalen
  temporären Ordners auch mit einzeln angegebenen Dateien. Vier inaktive
  Testdateien bleiben außerhalb des Repositorys im temporären Benutzerordner.
  Sie enthalten ausschließlich Testdokumente und Relay-Code; die zugehörigen
  Symcon-Testinstanzen existieren nicht mehr.
- Echte Touchgeräte, gesonderte horizontale Mausradbedienung, andere Symcon-
  Versionen/Installationslayouts und mehrstündige Last-/Speicherprüfungen sind
  weiterhin offen. Die drei nachgewiesenen vollen Budgets ersetzen keine
  Grenzlastprüfung jeder Diagrammfamilie oder aller gleichzeitigen Clients.
- Die Vorschauen und der eingebettete Browserlauf ersetzen keine erneute
  vollständige native Kachel-/IPSView-Fehlermatrix. Bereits vorliegende
  Eigentümerabnahmen bleiben davon unabhängig gültig.

Es wurden keine Commits, Pushes, Pull Requests oder Veröffentlichungen erzeugt.

# JSLive-Migrationsabnahme

Stand: 03.11.2026. Die vereinbarte technische Migrationsabnahme einschließlich
J01 ist unter 1.386 live bestanden. Dieser Bericht trennt bestätigte Funktionen,
lokale Regressionen und tatsächliche Testlücken. Er ersetzt veraltete
Offen-Vermerke früherer Zwischenstände, nicht deren historischen Prüfumfang.
Eine beliebige oder pixelgleiche Chart.js-Migration wird nicht zugesagt.

## Bestätigter Stand

| Bereich | Nachweis und Status |
| --- | --- |
| Ursprüngliche 40 Exportvarianten | Export vom Eigentümer bestätigt; MCP-Import, Wiederholung und paginierter Journalabruf unter 1.372 bestanden. |
| Automatischer Wiederanlauf | Acht Gauge- und vier Pie-Ziele unter 1.373 ohne zusätzliches ApplyChanges per MCP bestätigt. |
| Formularcallbacks | Korrigierte vollständige Symcon-Aufrufsignaturen per MCP live bestätigt; keine pauschale Gleichsetzung mit realer Dateiauswahl-/Download-Bedienung. |
| Darstellungsbefunde F01–F06 | Korrigiert und an den betroffenen Original-/Zielpaaren live bestätigt; die späteren F06-Restkorrekturen unter 1.378. |
| Zusätzliches Farb-/SVG-Testfeld | 13 Varianten übernommen; insgesamt 53 abgeschlossene Journalziele und Vergleichspaare vorhanden. Originale und Quellen erhalten. |
| Zyklische Palette und Zahlenbeschriftung | Unter 1.382 vom Eigentümer nach Modulupdate positiv bestätigt. Diese Live-Abnahme ist erledigt. |
| Neue Quellpakete | Alle 53 Testcharts frisch per MCP mit JSLive 0.113 exportiert, ohne Exportprobleme. |
| Aktuelle native Modelle | Alle 53 frischen Pakete lokal geprüft und jeweils als Kachel-/IPSView-HTML erzeugt; größte Ausgabe 737869 Bytes. Die Quellen-/Archivleser dieser lokalen Modellprüfung sind Testdoppel, kein erneuter Live-Archivnachweis. |
| Herkunftserkennung 0.113 | Lokal korrigiert. 80 Pläne prüfen die bisherigen 40 Varianten mit LF/CRLF-Vorlagenbytes und unveränderten nativen Revisionen. Der Kerneladapter prüft auch mit aktuellen Vorlagen bestätigte Neuanlage, Wiederanlauf und Wiederholung ohne Schreibzugriffe. |
| Live-Planung und Neuanlage 1.383 | Alle 53 Pakete frisch aus JSLive 0.113 exportiert, ohne Blocker geplant und mit einzeln bestätigten Unterschieden in einer separaten Testkategorie übernommen. |
| Live-Ausgaben 1.383 | Alle 53 Ziele aktiv und Datenstatus ready; 106 vollständige Kachel-/IPSView-HTML-Dokumente, größte Ausgabe 760280 Bytes. Das ist ein Daten-/HTML-Nachweis, kein erneuter Bildvergleich aller Paare. |
| Live-Wiederanlauf 1.383 | Je ein neues Ziel aller sechs Familien wurde ohne Gateway-Verbindung initialisiert (Status 298). Nach Wiederverbinden erfolgte die Rückkehr über den bestehenden Timer zu Status 102/ready, ohne weiteres ApplyChanges. |
| Normaler Live-Wiederaufruf | Alle 53 Importe liefern ohne den Neuanlagezusatz separateFrom dieselben Ziel-IDs; deren Konfigurationen bleiben gleich. |
| Journal und Bestandsschutz 1.383 | 106 vollständige Einträge in drei Metadatenseiten, 106 eindeutige Ziele; die vorherigen 53 Einträge sind unverändert. 108 geschützte Konfigurationen, zwölf Quellvariablen samt Werten/Darstellungen und vier Profile unverändert; enthalten ist auch die Archivkonfiguration. Die Testziele bleiben erhalten. |
| J01 – Live-Abnahme 1.386 | Bestanden: 53 vorhandene Testziele aus sechs Familien jeweils zweimal mit aktuell bestätigtem Plan und `separateFrom` aufgerufen. Alle 106 Ausführungen liefern dieselben Ziel-/Journal-IDs. Alte Bestätigungen und ungültige Vorgänger werden korrekt abgewiesen. |
| Bestandsschutz 1.386 | Alle 161 Konfigurationen einschließlich Designs, 53 Instanz-/Objektmetadaten, zwölf Quellen samt Werten/Darstellungen und vier Profile unverändert. Vollständiger Journal-Fingerabdruck identisch; 106 vollständige Einträge, 106 eindeutige Ziele, drei Metadatenseiten. Keine zusätzlichen Instanzen und keine Archivschreibzugriffe. Alle 53 Testziele bleiben aktiv. |

## Geprüfte Vorlagenänderung

Von den acht unterstützten Standardvorlagen hat nur Progressbar neue fachliche
Bytes. JSLive-Commit `909f0aea28a86a78ecfdc01e681a1fe354610790` ergänzt den
Füllmodus zur Laufzeit für die feste Textform ohne eigenen Pfad. Die gespeicherte
Property `Type`, eigene Pfade und Datenverwertung bleiben gleich. ECharts
behandelt Text-/SVG-Formen bereits so; ein weiterer Rendereradapter ist unnötig.

Die folgenden unveränderten Originalbytes sind ausschließlich inerte Referenz-
Fixtures unter JSLives Lizenz GPL-3.0. Sie werden nicht ausgeführt oder in
Zielvisualisierungen eingebaut:

| Vorlage | SHA-256 |
| --- | --- |
| Progressbar 0.113, LF | `6de5f11322b58a5a316941b020fccdd73b4f32beb92dfcc88e39970b49105fd1` |
| Progressbar 0.113, CRLF | `dcbda0b9487f1afdc689091de5c4542f6c37a5032a4483684103f40c5f36cc66` |

Die übrigen sieben Vorlagen behalten ihre bekannten Hashes. Alte Prüfsummen
bleiben zugelassen. Weder eine Versionsnummer noch ein gültiger Paket-Hash
ersetzt die konkrete Vorlagenprüfung. Andere veränderte Templates bleiben
gesperrt, auch mit korrekt neu berechneten Integritätsdaten.

## J01: Wiederholung einer getrennten Neuanlage

### Historischer Befund unter 1.383

Die frühere Vorlagensperre unter 1.382 war mit 1.383 beseitigt. Der damalige
vollständige Live-Lauf zeigte jedoch einen eigenständigen Journalfehler:

1. Ein bereits vorhandenes Original erhält einen neuen Plan mit separater
   Zielkategorie. Die Bestätigung enthält `separateFrom` mit dem bisherigen
   Journaleintrag; alle Planunterschiede sind einzeln akzeptiert.
2. Die reguläre Ausführung legt genau ein neues Ziel an und meldet `complete`.
3. Dieselbe Bestätigung wird unverändert erneut gesendet. Statt dasselbe Ziel
   zurückzugeben, meldet der Journalpfad `PLAN_CHANGED: separate creation must
   identify the reviewed previous target.`
4. Wird ausschließlich `separateFrom` entfernt, liefert derselbe Plan das eben
   erstellte Ziel ohne Konfigurationsänderung zurück. Dies wurde zur Abgrenzung
   geprüft und ist keine Korrektur der identischen Bestätigungswiederholung.

Der Befund ist mit Progress und Gauge unabhängig von der Vorlagenfamilie
reproduziert. In `libs/EChartsJSLiveJournal.php` wird der Neuanlagezusatz beim
Wiederaufruf gegen den inzwischen gefundenen neuen Eintrag geprüft, bevor dessen
vorhandener Zielnachweis wiederverwendet wird. Es entstanden keine zusätzlichen
Ziele; alle 106 Journaleinträge blieben vollständig.

Die lokale Korrektur trennt die Wiederaufnahme einer getrennten Anlage von
einer weiteren Neuanlage. Neue Journaleinträge speichern den ausdrücklich
bestätigten Vorgänger; alte Version-1-Einträge ohne das Feld verwenden nur den
direkten zeitlichen Vorgänger derselben Herkunft als kompatiblen Nachweis.
Der aktuell bestätigte Plan, Zieldefinition, Eigentum und Snapshots bleiben strikt geprüft.
Eine unveränderte vollständige Anlage wird ohne native Schreibaufrufe erneut
zurückgegeben; unterbrochene Anlagen verwenden dieselbe Journal-/Ziel-ID.

`php tests/jslive_journal.php` reproduziert den alten Fehler und prüft jetzt
zusätzlich 184 Abbruchszenarien getrennter Anlagen: gleiche/neue Revision,
neue/alte Journale und unveränderte/aktualisierte Regeln an jeweils 23 dauerhaften
Schritten. Ungültige, fremde und veraltete Vorgänger, unbestätigte Pläne,
abweichende Zieldefinitionen sowie Ziel-/Entwurfs-/Eigentumsänderungen
bleiben gesperrt. Der öffentliche Gateway-Test in `tests/jslive_execution.php`
prüft auch den konkreten Kerneladapter mit unterbrochener getrennter Anlage,
altem Journal und identischer Bestätigungswiederholung ohne native Schreibaufrufe.
Beide Vorlagenläufe (`php tests/jslive_execution.php` mit und ohne
`--current-templates`), der paginierte Journaltest und die vollständige lokale
Qualitätssuite `php tests/quality.php --fix` sind bestanden. Schutzbudgets und
Versionsmetadaten wurden nicht erhöht.

### Historischer Restbefund nach Modulupdate unter 1.385

Die gezielte MCP-Nachprüfung hat alle 53 erhaltenen Testkopien geprüft. Gegenüber
ihren früheren Plänen haben sich ausschließlich `ruleFingerprint` und `planHash`
geändert; Zielrevisionen, Zieldefinitionen, Quellen und Abweichungen sind gleich.
Die alten Bestätigungen scheitern korrekt an der aktuellen Planprüfung.
Auch neu bestätigte aktuelle Pläne mit dem bestehenden `separateFrom` werden
jedoch in allen 53 Fällen mit `PLAN_CHANGED` abgewiesen: Die zusätzliche
Journalprüfung verlangt fälschlich den historischen Plan-Hash als aktuellen Hash.

Der diagnostische Aufruf ohne `separateFrom` liefert in allen 53 Fällen dieselben
Ziel-/Journal-IDs und gilt ausdrücklich nicht als bestandene J01-Abnahme.
Alle 161 erfassten Konfigurationen, 53 Instanz-/Objektmetadaten, zwölf
Quellvariablen samt Werten/Darstellungen und vier Profile bleiben gleich.
Das vollständige Journal hat denselben Fingerabdruck, weiterhin 106 vollständige
Einträge und 106 eindeutige Ziele in drei Metadatenseiten. Die Testkategorie
enthält genau die erwarteten 53 Kopien; alle sind aktiv mit dem Gateway verbunden.
Es wurden keine Archivschreibfunktionen aufgerufen.

Die Restkorrektur verwendet bei frisch geprüftem und bestätigtem aktuellem Plan
die unveränderte Zielrevision, die vollständige Zieldefinition sowie den gültigen
Vorgänger zur Wiederaufnahme. Der historische Plan-Hash bleibt als Nachweis
erhalten. Aktuelle Plan-/Quellenprüfung unter Sperre und Eigentums-/Snapshot-
Prüfungen werden nicht gelockert. Der öffentliche Regressionstest bildet ein
altes Journal mit einem aus anderem Regel-Fingerabdruck berechneten Plan-Hash ab;
die aktuelle Bestätigung setzt dasselbe Ziel fort, alte Regeln und Bestätigungen
bleiben gesperrt. Abgeschlossene Wiederaufrufe schreiben keine nativen Einstellungen.

### Bestandene Live-Nachprüfung unter 1.386

Die Restkorrektur wurde nach dem Eigentümer-Update auf der verbundenen
Testinstallation per MCP geprüft. Verwendet wurden ausschließlich die 53
erhaltenen Testziele aus sechs Familien: Progress (27), Gauge Single (12),
TimeSeries (4), Pie (4), RadarHistory (4) und BarHistory (2).

Für jede Quelle wurde der aktuelle Plan neu geprüft. Gegenüber dem früheren
Plan änderten sich ausschließlich Regel-Fingerabdruck und Plan-Hash;
Zieldefinitionen, Revisionen, Quellen und akzeptierte Abweichungen blieben gleich.
Jede aktuelle Bestätigung mit unverändertem `separateFrom` wurde zweimal
identisch gesendet. Alle 106 Ausführungen meldeten `complete` und dieselben
Ziel-/Journal-IDs. Die zuvor fehlgeschlagenen Progress- und Gauge-Fälle bestanden
ebenfalls. Alte Bestätigungen und ungültige Vorgänger wurden gezielt geprüft
und korrekt mit `PLAN_CHANGED` abgewiesen.

Der Vorher-/Nachher-Vergleich umfasst 161 Konfigurationen einschließlich Designs
und Archivkonfiguration, 53 Instanz-/Objektmetadaten, zwölf Quellvariablen mit
Werten und Darstellungen sowie vier Profile. Alle sind unverändert, ebenso der
Fingerabdruck des vollständigen Journals und seine drei Metadatenseiten mit
106 vollständigen Einträgen und 106 eindeutigen Zielen. Die Testkategorie enthält
weiterhin genau die erwarteten 53 Ziele; alle sind aktiv mit dem Gateway
verbunden. Keine zusätzlichen Instanzen und keine Archivschreibzugriffe.

Damit ist J01 auf dieser Testebene abgeschlossen. Die 184 gezielten
Abbruchszenarien sind lokale Regressionen; der Live-Lauf verwendete vorhandene
abgeschlossene Journalziele nach dem tatsächlichen Modulupdate. Die bestehenden
Kachel-/IPSView-Darstellungsabnahmen bleiben gültig; dieser Lauf ist kein neuer
Bildvergleich oder Touch-/Langzeitnachweis.

## Beta-Entscheidung

Die Nutzungsgrenzen-Roadmap bleibt abgeschlossen. Familienmapper, Importjournal
und die oben bestätigten Bildvergleiche sind erledigt. Die aktuelle
0.113-Herkunftserkennung und Neuanlage sind live bestätigt. Mit der bestandenen
J01-Abnahme unter 1.386 ist die vereinbarte technische Migrationsabnahme auf
der erreichbaren Testebene abgeschlossen.

Echte Restlücken bleiben sichtbar: nicht separat belegte native Dateiauswahl-,
Tabellen-/Download-Grenzbedienungen, weitere Touch-/Gerätevarianten, längere Last-
und Speicherläufe sowie nicht erreichte Plattformvarianten. Die Observer-Meldung
der Vergleichsumgebung ist auch ohne Chart reproduziert und kein nachgewiesener
ECharts-Fehler; sie wird nicht als behoben ausgegeben.

Die erste öffentliche Beta wurde durch den Eigentümer veröffentlicht und am
03.11.2026 in der
[Community angekündigt](https://community.symcon.de/t/beta-modul-symconecharts-nachfolger-von-jslive-charts/144844).
Die damalige Einreichung ist kein offener Arbeitsschritt mehr. Dieser Bericht
belegt seine konkrete Migrationstestebene und ersetzt keine Abnahme späterer
Änderungen. Aktueller Status: [Beta-Unterlagen](BETA_STORE.md). Tatsächliche
Testlücken bleiben erhalten. Für weitere Releases sind der gewählte Commit,
dessen CI/Metadaten und Store-Texte erneut abzugleichen. Commit, Push, Merge
und weitere Einreichungen verbleiben beim Eigentümer.

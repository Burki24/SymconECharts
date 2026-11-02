# JSLive-Migrationsabnahme

Stand: 02.11.2026. Dieser Bericht trennt bestätigte Funktionen, lokale Prüfungen
und den offenen Wiederholungsfehler aus der Live-Abnahme aktueller Quellpakete. Er ersetzt veraltete
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
| Journal und Bestandsschutz | 106 vollständige Einträge in drei Metadatenseiten, 106 eindeutige Ziele; die vorherigen 53 Einträge sind unverändert. 108 geschützte Konfigurationen, zwölf Quellvariablen samt Werten/Darstellungen und vier Profile unverändert; enthalten ist auch die Archivkonfiguration. Die neuen Testziele bleiben zur Nachprüfung erhalten. |
| J01 – exakte Bestätigungswiederholung | Die unveränderte erneute Übermittlung einer erfolgreichen getrennten Neuanlage mit separateFrom wird mit PLAN_CHANGED abgewiesen. In Progress und Gauge reproduziert; keine Duplikate oder Bestandsänderungen. Korrektur noch offen. |

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

Die frühere Vorlagensperre unter 1.382 ist mit 1.383 beseitigt. Der vollständige
Live-Lauf zeigt jedoch einen eigenständigen Journalfehler:

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

Erwartet ist eine idempotente Wiederholung bei gleichem gültigem Plan und
unverändert nachgewiesenem Ziel. Eine Korrektur muss die Bestands-, Identitäts-
und Eigentumsprüfungen erhalten und vollständige sowie unterbrochene getrennte
Importe mit Regressionen abdecken. Produktivcode wurde in dieser Live-Prüfung
nicht verändert. Die anschließende gezielte Live-Nachprüfung bleibt offen.

## Beta-Entscheidung

Die Nutzungsgrenzen-Roadmap bleibt abgeschlossen. Familienmapper, Importjournal
und die oben bestätigten Bildvergleiche sind erledigt. Die aktuelle
0.113-Herkunftserkennung und Neuanlage sind live bestätigt. Für den technischen
Migrationsabschluss fehlen die Korrektur und gezielte Live-Nachprüfung von J01.

Echte Restlücken bleiben sichtbar: nicht separat belegte native Dateiauswahl-,
Tabellen-/Download-Grenzbedienungen, weitere Touch-/Gerätevarianten, längere Last-
und Speicherläufe sowie nicht erreichte Plattformvarianten. Die Observer-Meldung
der Vergleichsumgebung ist auch ohne Chart reproduziert und kein nachgewiesener
ECharts-Fehler; sie wird nicht als behoben ausgegeben.

Eine Beta-Freigabe oder Store-Einreichung wird hier nicht erteilt. Vor Einreichung
sind tatsächlicher Release-Commit, dessen CI und Metadaten sowie Store-Texte und
Termin erneut abzugleichen. Frühere vorbereitete Angaben bleiben historisch
nachvollziehbar; Version oder Datum werden nicht erfunden. Commit, Push, Merge
und Einreichung verbleiben beim Eigentümer.

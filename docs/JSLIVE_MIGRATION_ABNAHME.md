# JSLive-Migrationsabnahme

Stand: 02.11.2026. Dieser Bericht trennt bestätigte Funktionen, lokale Prüfungen
und die noch ausstehende Live-Abnahme aktueller Quellpakete. Er ersetzt veraltete
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

## Noch offen vor dem technischen Migrationsabschluss

Die Testinstallation verwendet beim letzten MCP-Lauf 1.382. Von 53 frischen
Paketen bestehen dort 26 die vollständige lesende Planung; 27 Progressbar-Pakete
werden ausschließlich wegen der unbekannten 0.113-Vorlagenkennung abgewiesen.
Das ist der reproduzierte Ausgangszustand, keine Live-Abnahme der neuen Freigabe.

Nach Eigentümer-Commit/Push und regulärem Modulupdate sind die frischen Pakete
erneut zu prüfen: Paketprüfung, vollständiger Plan, bestätigte Neuanlage in
separaten Testzielen, Wiederanlauf, Wiederholung ohne Duplikate und Journal.
Bestehende Originale, Zielinstanzen, Quellen, Darstellungen und Archive bleiben
erhalten. Bestätigte optische Prüfungen werden nicht pauschal erneut verlangt;
geprüft werden die Herkunftserkennung und der reguläre Importablauf.

## Beta-Entscheidung

Die Nutzungsgrenzen-Roadmap bleibt abgeschlossen. Familienmapper, Importjournal
und die oben bestätigten Bildvergleiche sind erledigt. Für den technischen
Migrationsabschluss fehlt aktuell die Live-Abnahme der frischen 0.113-Pakete
nach dem nächsten Modulupdate.

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

# Debug-Ausgaben

## Einheitlicher Aufbau

Alle 30 Module (Gateway und 29 Diagrammfamilien) verwenden das abgenommene OpenHomeAlarm-Schema: `Modul | Ereignis | Funktion:Zeile` mit `Instanz`, `Vorgang`, `Aufruf`, `Meldung`, `Bezug`. Meldungen sind deutsch. Verschachtelte Aufrufe teilen eine Vorgangskennung, neue äußere Aufrufe erhalten eine neue Kennung. Asynchrone Browser-/Timeraufrufe bilden eigene Vorgänge.

Öffentliche Modul-Einstiege einschließlich Lebenszyklus, Formularaktionen, Exporte, Timer und Empfang erhalten `START` und `ENDE`, auch bei früher Rückgabe und Ausnahmen. Dasselbe gilt für öffentliche projektspezifische Designer-Traits und wesentliche interne Grenzen: Quellen-/Archivabfrage, Veröffentlichung, IPSView-Transport und HTTP-Antwort. Kleine reine Berechnungen bleiben im Ablauf ihrer Aufrufer enthalten.

Ergebnisse nennen tatsächliche Zahlen/Boolean oder Art und Umfang geschützter Text-/Strukturergebnisse. Void-Funktionen behaupten keinen fachlichen Erfolg. Gateway-Ablehnungen erhalten eine eigene deutsche Fehlermeldung, bekannte Fehlercodes eine feste deutsche Ursache. Statusübergaben nennen den tatsächlichen Statuscode; fehlerhafte Konfigurationen und Daten werden nicht als erfolgreiche Diagramme dargestellt.

## Datenfluss und Datenschutz

`SENDEN` erklärt Gateway-, Archive-Control-, WebHook- und HTTP-Übergaben. `EMPFANG` erklärt geprüfte Gateway-Anfragen, tatsächlich gelesene numerische Quellwerte und Protokollantworten. Bezüge enthalten freigegebene Quell-/Ziel-IDs, Operation, Zeitraum, Wert und Punktzahl. Die erfolgreiche Gateway-Verarbeitung bestätigt keine Browserdarstellung. Bestehende API-Aufrufe werden genau einmal ausgeführt; Debug ergänzt keine Quellabfragen oder Aktionen.

Komplette Diagramme, Objektbäume, Netzwerkkarten, Freitext, JSON, SVG, HTML, Bilder, Zugriffspfade und Kanal-Token bleiben geschützt. Ausnahmeinhalte werden nicht protokolliert. Der projektspezifische Adapter schützt auch ältere Diagnosen aus unveränderten zentralen Helpern und nennt stattdessen eine sichere deutsche Fehlerhülle. Die zentrale Begrenzung beträgt 4096 Bytes, ein zusätzlicher Diagnosespeicher entsteht nicht.

## Nachweise und offene Praxisabnahme

`tests/debug_output.php` läuft innerhalb der bestehenden Gateway-/Diagrammintegration. Es prüft für alle 30 Module ausgeglichene Lebenszyklus-Aufrufe, automatische Quellstellen, neue Vorgangskennungen, Gateway-Ablehnungen, Schutz synthetischer Dokumentinhalte und unverändertes Verhalten bei ausgefallenem Debug-Kanal. Bestehende Daten-, Formular-, Ausgabe-, Layout- und Integrationsprüfungen bleiben Bestandteil von `php tests/quality.php --fix`.

Die spezielle Debug-Praxismatrix bleibt als eigener Testnachweis offen: je eine aktuelle und historische Quelle ändern, ungültige/fehlende Quellen und Gateway-/Archivfehler auslösen, Kachel und IPSView aktualisieren, HTTP-Ablehnung und deaktivierten Transport prüfen. Meldungsmenge, Verständlichkeit und Laufzeit müssen in Symcon geprüft werden. Die bestätigte funktionale Kachel-/IPSView-Abnahme der Diagramme belegt diese spezielle Debug-Fehlermatrix nicht; die frühere allgemeine Lizenzlücke ist dagegen überholt. Bei den beschriebenen lokalen Tests wurden keine Live-Systeme geändert.

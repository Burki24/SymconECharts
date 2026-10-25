# ADR 0104: Mehrfachübernahme von PictorialBar-Quellen

Status: angenommen, funktional per MCP geprüft; Live-Abnahme der korrigierten
Formularrückmeldung ausstehend.

## Kontext

PictorialBar erlaubt 100 Quellen, deren Einzelkonfiguration bei etwa 72
Batterievariablen aufwendig ist. Die Roadmap sieht eine explizite Auswahl nach
Objektzweig oder Filter vor. Bestehende Quellen und Designs dürfen dabei nicht
ersetzt oder eigenmächtig gespeichert werden.

## Entscheidung

Der bereits abonnierte unveränderte `VariableSearchHelper` übernimmt Baumsuche,
Namens-/Typprüfung, verdeckte Zweige, deterministische Pfadsortierung und die
erneute Prüfung übermittelter IDs gegen frische Suchergebnisse. Der gemeinsame
Library-Baustein `EChartsSourceImport` ergänzt strikte native/Legacy-Einheiten,
einen optionalen exakten Einheitenfilter und append-only Quellenlisten nach ID.
Es entsteht keine zweite Objektsuche und keine lokale Kopie des Helpers.

`EChartsSourceImportForm` liegt direkt unter `libs`, weil nur diese Library den
Adapter benötigt. Es stellt ein eingeklapptes Panel, flüchtige Filter und eine
editierbare Checkbox-Trefferliste bereit. Einheiten werden nicht umgerechnet.
Suche startet ohne Auswahl, optional werden alle neuen Treffer ausgewählt.
Bestehende IDs bleiben sichtbar markiert und werden beim Anhängen übersprungen.
Andere Namen/Pfade sind Anzeigeinformationen, keine Identitäten. Verknüpfungen
werden nicht aufgelöst. Unterobjekte umfassen maximal 100 Ebenen, ohne Rekursion
werden nur unmittelbare Kinder untersucht.

Beide Aktionen erhalten den aktuellen Formularentwurf, einschließlich
iterierbarer Quellen-/Trefferlisten und aller unabhängigen Designfelder. Die
Übernahme wiederholt dieselbe Suche und validiert das vollständige gemeinsame
Quellenmodell mit `EChartsCurrentComparisonSources` im Gerätemodul. Alte Zeilen
behalten Felder und Reihenfolge; neue erhalten Label leer, Farbe -1 und
Min/Max 0/100. Die Grenzen müssen bei anderen Normalisierungsbereichen vom
Anwender angepasst werden. Erst nach gültiger Gesamtprüfung werden beide
SVG-Vorschauen und die Quellenliste im Formular aktualisiert. Persistenz erfolgt
weiterhin ausschließlich durch die normale Formularübernahme des Anwenders.
Keine Properties, Attribute, Variablen, Archive, Referenzen, Abonnements oder
Instanzstatus werden durch den Import verändert; kein `ApplyChanges`.

Schutzbudgets: 5000 untersuchte Objekte, 500 numerische Namenstreffer vor dem
Einheitenfilter, 250000 Bytes für Treffer; Eingabepayload höchstens 2 MiB.
Gekürzte Suchergebnisse werden abgewiesen. Fachliche 100 Quellen/64 KiB sowie
Modell-/HTML-/SVG-Grenzen bleiben erhalten. Eine Überschreitung der Gesamtgrenzen
übernimmt keine Teilmenge. Suchfehler und Filteränderungen verwerfen alte Suchergebnisse;
ein Fehler beim Anhängen verändert keine Quellenliste. Wiederholte Übermittlung
derselben IDs fügt keine Dubletten hinzu.

## Folgen und Nachweis

Keine Migration, keine zusätzliche Runtime oder Gateway-Operation. Der bestehende
Renderer erhält ausschließlich das bekannte Quellenmodell und bleibt unverändert.
Die Formularkommunikation folgt dem vorhandenen TreeSystemHealth-Muster. Symcon
verteilt `UpdateFormField` an alle geöffneten Formulare derselben Instanz; dies
ist keine isolierte Mehrbenutzersitzung und wird in der Benutzeranleitung benannt.

`php tests/gateway_gauges.php --pictorial-source-import` prüft Modulaktionen,
erzeugte Callbacks mit echten Iteratoren, Entwurfserhalt, zwei Vorschauen,
IDs/gleiche Namen, Einheiten, Normalisierung, veraltete Auswahl und alle Budgets.
Die initiale Prüfung begann RED an der fehlenden Modulaktion. Die vollständige
Qualitätssuite `php tests/quality.php --fix` ist bestanden. Die funktionale
MCP-Prüfung auf Symcon 9.1 bestätigte Speicherung von genau 100 Quellen, Bestand,
Designs, Vorschauen und beide HTML-Ausgaben; temporäre Testobjekte wurden entfernt.
Die dabei fehlende Formularrückmeldung wird im Callback mit `echo` statt `return`
ausgegeben. Der Modulfunktionsvertrag bleibt ein stiller String-Rückgabewert,
erfolgreiche Such-/Auswahlaktionen liefern weiterhin einen leeren Text. Der
Regressionstest prüft tatsächliche Skriptausgabe einschließlich Fehlern ohne
Teilübernahme. Die neue Rückmeldung bleibt nach Update live zu prüfen; diese
funktionale Abnahme ersetzt keinen separaten Test im tatsächlichen IPSView-Client.

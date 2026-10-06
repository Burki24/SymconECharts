# ADR 0017: Vor Neuentwicklung vorhandene Chart-Funktionen wiederverwenden

- Status: Angenommen
- Datum: 2026-10-06
- Entscheider: Burki24
- Ergänzt: ADR 0001, ADR 0005, ADR 0015 und ADR 0016
- Ersetzt durch: –

## Kontext

Mit Gauge Single, Gauge Multi, Gauge Tacho und Gauge Chronograph existieren
mehrere fertige Renderer und Konfigurationswege. Dabei wurden unter anderem
responsive Geometrie, Theme- und Farbauflösung, Vorschauaktualisierung,
SVG-Importe, Werteformatierung und getrennte Ausgabeadapter bereits praktisch
gelöst.

Neue Chartfamilien können ähnliche Funktionen benötigen. Würden diese ohne
vorherige Bestandsaufnahme erneut implementiert, entstünden voneinander
abweichende Varianten derselben Fähigkeit. Eine vorschnelle universelle
Abstraktion wäre jedoch ebenfalls problematisch, wenn nur die Oberfläche
ähnlich ist, die fachlichen Verträge aber verschieden sind.

Außerdem muss zwischen projektspezifischem ECharts-Code und Fähigkeiten
unterschieden werden, die auch für unabhängige Symcon-Module nützlich sind.
Synchronisierte Dateien unter `libs/helper` stammen aus
`Symcon_ModuleHelper` und dürfen nicht als lokale Kopien weiterentwickelt
werden.

## Entscheidung

Vor der Programmierung einer neuen Funktion oder Chartfamilie wird zuerst in
den bereits fertiggestellten Charts, den projektspezifischen Bibliotheken unter
`libs` und den eingebundenen zentralen Helpern nach einer vergleichbaren
Funktion gesucht.

Für den gefundenen beziehungsweise neu entstehenden Code gelten folgende
Grenzen:

1. Ist die Fähigkeit an genau einen fachlichen Chartvertrag gebunden, bleibt
   sie im zuständigen Gerätemodul.
2. Ist eine vorhandene Funktion semantisch kompatibel und innerhalb von
   SymconECharts wiederverwendbar, wird sie vor oder zusammen mit ihrer
   Wiederverwendung in einen klar abgegrenzten projektspezifischen Baustein
   direkt unter `libs` zentralisiert. Die fertige Chart-Implementierung dient
   dabei als belegte Ausgangsbasis; öffentliche Properties, persistierte
   Formate und sichtbares Verhalten bleiben erhalten.
3. Ist eine ECharts-unabhängige Fähigkeit voraussichtlich auch für andere
   Symcon-Module oder Repositories nützlich, wird sie in
   `Symcon_ModuleHelper` zentral entwickelt. SymconECharts übernimmt sie
   anschließend ausschließlich über `.helper-sync.json` nach `libs/helper`.
   Eine lokale Änderung der synchronisierten Kopie ist ausgeschlossen.

Ähnliche Namen oder Quelltextfragmente allein rechtfertigen noch keine
gemeinsame Abstraktion. Vor der Zentralisierung werden Eingaben, Ausgaben,
Fehlerverhalten, Sicherheitsgrenzen und die benötigten Varianten verglichen.
Kann kein stabiler gemeinsamer Vertrag formuliert werden, bleiben die
Implementierungen getrennt.

Die Bestandsaufnahme und die gewählte Ablage werden bei einer neuen
Chartfamilie in deren Planung oder ADR nachvollziehbar festgehalten. Tests des
extrahierten Bausteins sichern sowohl den bestehenden als auch den neuen
Verwendungsfall ab.

## Folgen

Neue Chartfamilien beginnen nicht mit einer parallelen Neuimplementierung
bereits gelöster Aufgaben. Bewährte Funktionen werden zu einer kanonischen
lokalen ECharts-Implementierung zusammengeführt, während tatsächlich
allgemeine Symcon-Fähigkeiten zentral im ModuleHelper entstehen.

Die vorgeschaltete Bestandsaufnahme benötigt zusätzlichen Aufwand. Sie
reduziert dafür langfristig Abweichungen zwischen Renderern, doppelte
Fehlerbehebungen und lokale Helper-Forks. Chartfamilienbezogene Verträge und
Geometrien werden weiterhin nicht in eine universelle Schicht gezwungen.

## Nachweise

- Projektgrenzen in `AGENTS.md`
- Gemeinsame Engineering-Prinzipien in
  `../SymconDevelopment/principles/ENGINEERING.md`
- Vorhandene projektspezifische Designschicht in
  `libs/EChartsGaugeDesign.php`
- Synchronisierungsvertrag in `.helper-sync.json`

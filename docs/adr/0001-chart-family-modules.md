# ADR 0001: Gerätemodule nach Chartfamilien strukturieren

- Status: Angenommen
- Datum: 2026-10-03
- Entscheider: Burki24
- Ersetzt: –
- Ersetzt durch: –

## Kontext

Das erste Repository-Gerüst enthielt einen `EChartsGateway` und ein allgemein
benanntes `EChartsWidget`. Vor der fachlichen Implementierung musste entschieden
werden, ob eine universelle Geräteinstanz alle Diagrammtypen konfiguriert oder
ob unterschiedliche Konfigurations- und Darstellungsverträge durch getrennte
Chartfamilien abgebildet werden.

Gauge-Diagramme besitzen eigene Wertebereiche, Zeiger, Skalen, Grenzbereiche
und Darstellungsoptionen. Zeitreihen benötigen dagegen unter anderem Achsen,
Zeiträume, Aggregationen und mehrere Datenreihen. Ein gemeinsames universelles
Formular würde diese unterschiedlichen Verträge koppeln und mit jeder weiteren
Familie komplexer werden.

## Entscheidung

SymconECharts verwendet getrennte Gerätemodule je Chartfamilie.

- Das erste Gerätemodul heißt `EChartsGauge`; eine Instanz stellt genau einen
  unabhängig konfigurierbaren Gauge-Chart bereit.
- Das zuvor allgemeine Gerüst `EChartsWidget` wird vor seiner ersten
  Funktionsfreigabe in `EChartsGauge` umbenannt. Seine bestehende Modul-GUID und
  die vorhandenen Datenfluss-GUIDs bleiben erhalten.
- Weitere Chartfamilien erhalten bei tatsächlichem Bedarf eigene Gerätemodule.
  Eine Familie ist kein umschaltbarer Modus einer universellen
  `EChartsWidget`-Instanz.
- `EChartsGateway` stellt nur familienübergreifende Infrastruktur bereit.
  Familienbezogene Konfiguration, Validierung und ECharts-Optionen verbleiben
  in den Gerätemodulen.
- Eine Geräteinstanz kann ihre native Symcon-Kachel und ihr optionales
  IPSView-HTML-Widget aus derselben fachlichen Chart-Konfiguration bedienen.

Die Modulpräfixe bestehen aus `EC` für ECharts und zwei Buchstaben für die
Bestimmung. Festgelegt sind `ECGW` für `EChartsGateway` und `ECGA` für
`EChartsGauge`.

## Alternativen

- **Universelle Geräteinstanz:** reduziert zunächst die Anzahl der Module,
  führt aber zu einem wachsenden, stark bedingten Konfigurationsvertrag und
  koppelt fachlich unterschiedliche Diagrammfamilien.
- **Ein Modul pro einzelnem Diagrammtyp:** trennt sehr konsequent, würde aber
  auch eng verwandte Varianten ohne eigenen Konfigurationsvertrag unnötig
  vervielfachen.
- **Darstellung vollständig im Gateway:** zentralisiert Code, vermischt jedoch
  gemeinsame Infrastruktur mit familienbezogener Fach- und UI-Logik.

## Folgen

Konfigurationsformulare und öffentliche Verträge bleiben je Chartfamilie
überschaubar. Neue Familien können unabhängig ergänzt und getestet werden. Im
Gegenzug benötigt jede Familie ein eigenes Symcon-Modul und muss gemeinsame
Fähigkeiten über bewusst abgegrenzte, erst bei realer Wiederverwendung
entstehende Bausteine teilen.

Die Namen und Präfixe ändern sich noch im unveröffentlichten Gerüst. Für die
Umbenennung ist deshalb keine Migration ausgelieferter Instanzen erforderlich.
Die erhaltenen GUIDs verhindern zugleich eine unnötige Neudefinition der bereits
festgelegten Modul- und Datenflussidentitäten.

## Nachweise

- Eigentümerentscheidung zur Chartfamilien-Struktur und Präfixkonvention vom
  03.10.2026
- Vertragstests unter `tests/module_contracts.php`
- Strukturprüfung unter `tests/validate_structure.php`

# ADR 0082: Flexible Calendar-Zeiträume mit lesender Navigation

- Status: Angenommen
- Datum: 2026-10-20
- Entscheider: Burki24
- Ergänzt: ADR 0052, 0062 und 0077

## Entscheidung und Kompatibilität

EChartsCalendar ergänzt `Period` mit `year` (Default), `quarter`, `month` und
`absolute`. Das bestehende `Year` und sämtliche Design-/Kennzahl-Properties
bleiben erhalten. Neue Integer-Properties `Month=0` und `Quarter=0` folgen der
aktuellen Monats-/Quartalsnummer, auch bei historischem Year; positive Werte
wählen 1–12 beziehungsweise 1–4. Year=0 folgt weiterhin dem aktuellen Jahr.
Inaktive Periodenfelder werden weder ausgewertet noch umgeschrieben.

`AbsoluteStart` und `AbsoluteEnd` sind additive String-Properties, Default `{}`.
Die native Symcon-Komponente SelectDate liefert genau drei Integer-Felder
year/month/day. Die offizielle SDK-Dokumentation `Forms/SelectDate` wurde am
20.10.2026 per MCP gelesen. Von ist eingeschlossen, Bis ausgeschlossen;
historische Grenzen bleiben bei Refresh unverändert. Grenzen liegen in der
Symcon-Zeitzone, ab 1971, mit strikt späterem Ende, höchstens jetzt und 1–366
zivilen Kalendertagen. Auch jahresübergreifende Fenster sind erlaubt.

## Gemeinsame Planung und Datenregeln

`CalendarData::Resolve` behält seine bisherige Ergebnisform.
`ResolveView` ergänzt die aktive Periode und inklusive Renderergrenzen;
Validierung, Archivabfrage und reine Entwurfsvorschau verwenden dieselbe Methode.
`EChartsArchiveQuery::ParseLocalDate` nutzt die vorhandene strikte Feldprüfung
von `ParseLocalDateTime` und die exakte `ResolveAbsoluteWindow`-Grenzplanung.
Für reine Daten bezeichnet Mitternacht den lokalen Tagesbeginn: fehlende
Mitternacht durch DST wird innerhalb desselben Datums normalisiert; ein ganz
entferntes Datum wird als Grenze abgewiesen. Die strikten Uhrzeitverträge von
SelectDateTime bleiben unverändert.

`CalendarDays` enumeriert begrenzt zivile Daten und konstruiert jede lokale
Tagesgrenze separat. Dadurch wird eine DST-normalisierte Uhrzeit nicht in den
nächsten Tag weitergetragen; entfernte lokale Daten erzeugen keine Archivtage.
CalendarPie nutzt dieselbe Enumeration mit unverändertem Monats-/Datenmodell.
Es entsteht weder ein universelles Kalendermodul noch eine Gateway-Property.

Calendar liest weiterhin genau eine numerische Quelle und maximal 366 native
Tagesaggregate. Mittel/Min/Max brauchen Standardarchive, Verbrauch ein
Zählerarchiv; keine Differenzbildung aus Zählerständen. Laufende Tage werden
nicht abgefragt, fehlende Tage nicht ersetzt. Die erste verfügbare Teil-Tages-
Kennzahl bleibt ausdrücklich zulässig (anders als vollständige ThemeRiver-
Intervalle). Ein aktuelles Jahr/Quartal/Monat zeigt leere zukünftige Zellen.
Modell-/HTML-Budgets und Fehlerbehandlung bleiben verbindlich.

## Navigation, Ausgabe und Vorschau

Jahre blättern wie bisher bis 1971; Monate/Quartale jeweils um eine Periode,
freie Fenster um ihre zivile Tagesanzahl (nicht Sekunden). Neue Ansichten
haben maximal 120 Rückwärtsschritte, niemals vor 1971. Vorwärts geht es nur
bis zur konfigurierten Auswahl. Navigation ist pro Browser flüchtig, lesend
und verwendet den bestehenden authentifizierten State-Endpunkt; Konfiguration,
Archivierung und andere Browser werden nicht verändert.

Modell-Schema 1 und Jahresfelder bleiben erhalten. Additiv sind Periodentyp,
Periodentext, Datumsspanne, Tages-/Monatsanzahl und ein Ansichtsschlüssel.
Letzterer bleibt beim Datenrefresh stabil, ändert sich bei Quelle, Kennzahl,
Zeitraum oder Design und schützt gegen veraltete Navigationsantworten.
Kachel und IPSView behalten eigene Designs. Der gepinnte ECharts-6.1.0-
Calendar-Renderer unterstützt inklusive Datumsarrays, siehe
[CalendarModel](https://github.com/apache/echarts/blob/6.1.0/src/coord/calendar/CalendarModel.ts).
Keine Runtime-Aktualisierung oder externe Laufzeitressource ist nötig.

Beide Formularvorschauen zeigen tatsächliche Grenzen mit gekennzeichneten
synthetischen Werten. Ungültige Entwürfe liefern Hinweise statt Jahresersatz.
Öffnen/Bearbeiten liest keine Archive und verändert weder Properties noch Status.
Die Zell- und Monatsbeschriftungsdichte verwendet die wirkliche Fenstergröße.

## Prüfung und Roadmap

`tests/calendar_data.php` prüft Altverträge, Schaltjahre, Monats-/Quartalswechsel,
jahresübergreifende Fenster, 366/367-Grenze, Zukunft, Navigation und lokale
DST-/Datumsgrenzen. Der erste neue View-Test scheiterte vor der Implementierung
an der fehlenden Methode. `calendar_views_integration.php` ergänzt den realen
Modul-/Gateway-Pfad mit Kernel-Doppeln: alle vier Kennzahlen, beide Ausgaben,
unabhängige Farben, wiederholtes ApplyChanges, authentifizierte Navigation,
fehlende/ungültige Fenster und zustandsneutrale native Formularentwürfe.
Bestehende Calendar-/CalendarPie-Tests sichern Regressionen gemeinsamer Logik.
`calendar_layout.js` verwendet den echten gepinnten Renderer für kurze und
jahresübergreifende Ansichten, beide Ausrichtungen/Ausgaben und gegen veraltete
Antworten abgesicherte Navigation. `calendar_browser.mjs` liefert die lokale
synthetische Canvas-Prüfung ohne Installation oder Archivdaten.

Die gemeinsame funktionale Live-Abnahme des vorherigen Fünf-Familien-
Archivzeiträume-Blocks einschließlich ThemeRiver-Teilarchivkorrektur ist erfolgt;
Eigentümer bestätigt Kachel/IPSView. Diese Änderung markiert Calendar als
implementiert, mit eigener Live-Abnahme nach Modulupdate noch offen.
Nächster Roadmap-Punkt ist Heatmap mit benutzerdefinierten/festen Zeiträumen,
noch nicht umgesetzt. Ressourcen-Stresstests sind keine Live-Lastfreigabe.

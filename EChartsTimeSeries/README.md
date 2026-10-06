# EChartsTimeSeries

Gerätemodul für historische numerische Symcon-Werte als responsive Apache-
ECharts-Zeitreihe. Eine Instanz verarbeitet eine bis acht eindeutige Quellen,
höchstens zwei Einheitengruppen und rendert eine native Symcon-Kachel.

## Aktueller Funktionsumfang

- rollende Zeiträume von 1 Stunde, 6 Stunden, 24 Stunden, 7 Tagen und 30 Tagen;
- ausdrücklich wählbare Rohwerte, automatische Verdichtung oder feste
  Verdichtung auf Minute, 5 Minuten, 15 Minuten, Stunde oder Tag;
- Linien- oder Flächendarstellung je Quelle;
- automatische Übernahme von Einheit und Nachkommastellen aus der
  Variablendarstellung mit manuellen Rückfallwerten;
- maximal zwei Y-Achsen nach effektiver Einheit;
- gemeinsames Punktbudget von 200 bis 8.000 Punkten, maximal 2.000 je Reihe;
- Durchschnitt, Zählersumme, Minimum und Maximum entsprechend dem
  versionierten Archivvertrag;
- native Kachel mit lokalen, integritätsgeprüften ECharts- und Theme-Dateien;
- Rohwertfortschreibung über `VM_UPDATE`, ohne bei jedem Messwert das Archiv
  erneut zu laden;
- aggregierte Reihen enden am letzten abgeschlossenen Zeitfenster und werden
  nach der nächsten Intervallgrenze neu geladen.

Das Modul liest das Symcon-Archiv ausschließlich über die gemeinsame
`EChartsGateway`-Operation `archive.read`. Es aktiviert kein Logging und
verändert weder Archivkonfiguration noch Archivdaten. Eine leere gültige
Archivantwort bleibt eine leere Datenreihe. Bei gekürzten Rohwerten zeigt die
Kachel einen Hinweis auf das wirksame Punktbudget.

## Einrichtung

1. Eine bestehende gemeinsame `EChartsGateway`-Instanz als Parent auswählen.
2. Eine bis acht archivierte Integer- oder Float-Variablen konfigurieren.
3. Zeitraum, Datenmodus und Punktbudget wählen.
4. Optional Beschriftung, Einheit, Nachkommastellen, Farbe, Stil und Reducer
   pro Quelle anpassen.

Mehr als zwei unterschiedliche effektive Einheiten werden bewusst abgelehnt.
`raw` bleibt immer eine echte Rohwertabfrage; nur `auto` wählt selbstständig
eine Verdichtungsstufe.

## Entwicklungsstand und Testgrenzen

Die erste native Vertikale vom Archivvertrag bis zur Kachel ist implementiert
und durch lokale Vertrags- und Integrationstests abgesichert. Ein realer
Symcon-Archiv- und Browsertest sowie die getrennte IPSView-Ausgabe stehen noch
aus. Die Zielplattform bleibt Symcon 9.0/9.1 mit PHP 8.5.

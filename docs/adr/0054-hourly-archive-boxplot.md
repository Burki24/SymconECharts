# ADR 0054: Boxplot aus abgeschlossenen Archiv-Stundenmittelwerten

- Status: Angenommen
- Datum: 2026-10-16
- Entscheider: Burki24
- Ergänzt: ADR 0001, 0002, 0005, 0017, 0036 und 0037

`EChartsBoxplot` erhält die GUID `{B9B5BCD1-BC63-42A6-8132-98CE744DAE1C}`
und das Präfix `ECBX`. 1–10 unterschiedliche numerische Standard-
Archivvariablen derselben Einheit werden über 24 Stunden, 7 oder 30 Tage
verglichen. Jede Box gehört einer Variablen-ID; gleiche Namen bleiben erlaubt.
Ein optionaler Anzeigename und eine Farbe gehören zur jeweiligen Quelle.

Die Stichproben sind gleich gewichtete, abgeschlossene Stundenmittelwerte,
nicht Rohwert-Ereignisse. Dadurch dominiert eine häufig meldende Quelle nicht
allein wegen ihrer Meldehäufigkeit. Die Verteilung beschreibt ausdrücklich
Stundenmittelwerte, nicht die zeitgewichtete Rohwertverteilung. Die
Archivmittelwertbildung bleibt die von Symcon bereitgestellte Semantik.
Fehlende Stunden werden weder interpoliert noch als Null ergänzt; Quellen
benötigen keine gemeinsamen Intervalle. Anzahl und erster/letzter verwendeter
Zeitpunkt bleiben sichtbar im Datenmodell beziehungsweise Tooltip.

Minimum, Q1, Median, Q3 und Maximum werden lokal berechnet. Quartile verwenden
lineare Interpolation mit Rang `(n - 1) * p` (R7). Die Enden zeigen Minimum
und Maximum, keine 1,5-IQR-Grenzen; eine Ausreißerklassifizierung wird nicht
versprochen. Eine Stichprobe ist zulässig, eine leere Quelle bleibt ohne Box.
Nichtendliche Werte, doppelte/unsortierte oder außerhalb des Zeitfensters
liegende Zeitpunkte, Zähler und abgeschnittene Antworten werden abgewiesen.
Das feste Limit von 1000 Intervallen je Quelle deckt die angebotenen Zeiträume
vollständig ab. Es wird kein verkürzter Datensatz als vollständige Verteilung
ausgegeben. Laufende Stunden bleiben durch `EChartsArchiveQuery` ausgeschlossen.

Wiederverwendung: Queryplanung, Versionierungsprotokoll, Identitäts-/Einheiten-
auflösung, Animation, Vorschauverdrahtung, Designerabschnitte, IPSView-Transport
und vorübergehende Fehlerwiederholung aus den vorhandenen Archivfamilien.
Familienmodell, Statistik, Renderer und Vorschaugeometrie bleiben lokal.
Das Gateway erhält keine neue Operation. Vorschauen verwenden ausdrücklich
synthetische Daten und fragen keine Archive ab. Beide Ausgabewege besitzen
unabhängige Designer; Quellen und Zeitraum bleiben gemeinsam.

Ein eigener reproduzierbarer ECharts-6.1.0-Build enthält Boxplot, Grid, Titel,
Tooltip, Aria und Canvas. Herkunft/Lizenz bleiben die unveränderte gepinnte
ECharts-Basis; Integrität und Budget werden geprüft. Grundlage:
[offizielle Boxplot-Serie](https://echarts.apache.org/en/option.html#series-boxplot).

Erforderlich sind reine Statistiktests, Gateway-/Formular-/Budgettests,
echte ECharts-Geometrie und lokale Browserprüfung. Symcon-/IPSView-Live-Tests
der neuen Familie erfolgen erst nach Installation durch den Anwender und
sind noch offen. Bestehende Instanzen bleiben unverändert.

## Lokale Prüfung vom 16.10.2026

- `php tests/boxplot_statistics.php`: R7-Quartile, negative/Null-Werte,
  Einzelsamples, leere Archive und nichtendliche/ungeordnete Stichproben.
- `php tests/gateway_gauges.php`: echte Modul-/Gateway-Implementierung mit
  Kernel-Doppeln; Zeitfenster ohne laufende Stunde, individuelle Lücken,
  Einheiten und Zähler, unsichere Antworten, Referenzen, minütliche Aktualisierung,
  Fehlerwiederholung, getrennte Designer, Vorschauen ohne Archivzugriff,
  Übersetzungen und Dokument-/Formularbudget unter 1.048.576 Byte bei zehn Quellen.
- `node tests/boxplot_layout.js`: echte gepinnte ECharts-SVG-Geometrie für
  beide Ausrichtungen bei 350 und 900 Pixel Breite; leere Kategorien ohne
  Null-Box, Einzelsamples, Titel-/Kachelkopf-Abstand, Tooltip-Escaping,
  Transientfehler und explizite Designwerte einschließlich Null-Deckkraft.
- Lokale Browserfixture `node tests/boxplot_browser.mjs` mit synthetischen
  Daten im Canvas-Build visuell kontrolliert: Kachel mit Kopfbereich und
  eigenem Titel sowie unabhängige horizontale IPSView-Darstellung.

Diese Belege ersetzen keine Prüfung in IP-Symcon 9.0/9.1 oder IPSView.
Der Anwender muss die neue Familie nach Modulupdate noch live bestätigen.

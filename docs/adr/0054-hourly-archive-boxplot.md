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
echte ECharts-Geometrie und lokale Browserprüfung. Die erste Version wurde
am 16.10.2026 durch den Anwender in Kachel und IPSView positiv live geprüft.
Bestehende Instanzen bleiben unverändert.

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
Der Anwender hat die erste Version inzwischen live bestätigt; neue
Designer-Ergänzungen benötigen nach Installation eine erneute Bestätigung.

## Designer-Ergänzung vom 16.10.2026

Abgleich mit der offiziellen [Boxplot-Serie](https://echarts.apache.org/en/option.html#series-boxplot),
den [Item-Styles](https://github.com/apache/echarts-doc/blob/master/en/option/partial/item-style.md),
[Schatten und Deckkraft](https://github.com/apache/echarts-doc/blob/master/en/option/partial/style-shadow-opacity.md)
sowie [Emphasis/Blur](https://github.com/apache/echarts-doc/blob/master/en/option/partial/emphasis-common.md).
Die vorhandene gepinnte 6.1.0-Runtime unterstützt die Ergänzungen unverändert.

| Bereich | Ergänzungen |
| --- | --- |
| Boxen | `boxWidth`-Untergrenze, `borderType` |
| Effekte | `shadowBlur`, `shadowColor`, `shadowOffsetX/Y`, `emphasis.focus/disabled`, `blur.itemStyle.opacity` |
| Kategorien | `axisTick.show`, Achsenlinienbreite, `axisLabel.rotate/width/fontWeight` |
| Werteachse | gewünschte `splitNumber`, `splitLine.lineStyle.type/width/color/opacity` |
| Typografie | `title.textStyle.fontWeight`, Fettschrift für Wertebeschriftungen der Achse |

Alle 19 neuen Properties erhalten unabhängige IPSView-Pendants und werden über
die vorhandenen Mechanismen für Registrierung, Vererbung, Kopie und ungespeicherte
Vorschauzustände geführt. Vorhandene Properties, Quellen, Datenfenster, R7-Statistik,
Min-/Max-Enden und Gateway-Verträge bleiben unverändert. Ungültige Bereiche,
Enums und minimale Breiten oberhalb der maximalen Breite werden abgewiesen.
Kleine Ausgaben dürfen Mindestbreiten und gedrehte Kategoriennamen begrenzen.
Hover-Effekte werden nicht im statischen SVG simuliert; ein Hinweis benennt die Grenze.

Bewusst nicht Bestandteil dieses Designschritts: Tukey-Ausreißer oder zusätzliche
Scatter-Serien, Markierungen, manuell abschneidende Wertebereiche, frei ausführbare
Formatter, Muster-/Gradientenimporte und persistente Auswahlaktionen. Das sind
zusätzliche fachliche oder interaktive Verträge, keine fehlenden Basis-Styles.

Die Erweiterung wird mit Renderer-/ECharts-Geometrie-, Formular-/Validierungs-,
Vorschau- und Integrationsprüfungen abgesichert. Der erneute Live-Test der
Designer-Erweiterung in Symcon/IPSView bleibt nach Modulupdate offen.

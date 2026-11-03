# Konfigurationsreferenz

Diese Referenz ergänzt die [Modulanleitungen](../README.md#enthaltene-module).
Die Tabellen nennen die registrierten Standardwerte einer **neuen** Instanz.
Ein Update schreibt bestehende Einstellungen nicht auf diese Werte zurück.
Die fachlichen Kombinations-, Archiv- und Ausgabegrenzen stehen in der jeweiligen
Modulanleitung und gelten zusätzlich zu den Bedienbereichen des Formulars.

## Gemeinsame Regeln

- **Technischer Name:** Property-Name für `IPS_GetProperty` und `IPS_SetProperty`.
  Die Beschriftung entspricht dem deutschen Formular, soweit lokalisiert.
- **Typ:** Symcon-Property-Typ. Listen und Datumsfelder sind als JSON in einer
  **String**-Property gespeichert. `"[]"` ist eine leere Liste, `"{}"` ein noch
  nicht gewähltes Datum. Bei Listen enthält die Untertabelle die Zeilenfelder;
  deren Standard bezeichnet eine neu hinzugefügte Formularzeile.
- **Farben:** Integer im Format `0xRRGGBB`; `-1` steht je Feld für automatische
  Theme-Farbe beziehungsweise einen transparenten Hintergrund. Prozentwerte
  sind ausdrücklich mit `%` bezeichnet. Ein automatischer Farbwert ist keine
  feste Zusage für die Farbe eines bestimmten Themes.
- **Quellen:** `VariableID` beziehungsweise `SourceVariableID` ist die ID einer
  vorhandenen Variable, keine Beschriftung. `0` ist bei erforderlichen Quellen
  ein unkonfigurierter Ausgangswert. Namen ersetzen keine gültige ID.
  Numerische Symcon-Typcodes bedeuten: 0 Boolean, 1 Integer, 2 Float, 3 String.
- **Formatierung:** `UseVariablePresentation` übernimmt die unterstützte native
  Darstellung beziehungsweise Legacy-Metadaten lesend. Bei manueller Formatierung
  gelten `Unit` und `Decimals`. Eigene Quellenbeschriftungen verändern den
  Variablennamen nicht.
- **Zeiträume:** `Range=custom` aktiviert die eigene Dauer, `Range=absolute`
  feste Grenzen, wo angeboten. `AbsoluteStart` ist eingeschlossen,
  `AbsoluteEnd` ausgeschlossen. Die Felder verwenden die Symcon-Zeitzone.
  DateTime-JSON enthält die Integer-Felder `year`, `month`, `day`, `hour`,
  `minute`, `second`; reine Kalenderdaten nur `year`, `month`, `day`.
  Nicht existierende oder doppeldeutige Ortszeiten werden abgewiesen.
- **Abhängigkeiten:** Eine passende Modusauswahl aktiviert die zugehörigen
  Detailfelder, etwa manuelle Skalen, Gradienten, SVG oder feste Zeiträume.
  Nicht jeder einzeln gültige Feldwert ergibt eine gültige Gesamtkonfiguration.
  Such- und Uploadaktionen sind Formularbedienung, keine zusätzlichen Properties.

### Gemeinsame IPSView-Einstellungen

| Formularfeld / Property | Typ | Standard | Bedeutung |
| --- | --- | --- | --- |
| IPSView-HTML-Ausgabe bereitstellen / `EnableIPSView` | Boolean | `false` | Erzeugt beziehungsweise aktualisiert die zusätzliche WebContent-Variable. |
| Kacheldesign verwenden / `IPSViewUseTileDesign` | Boolean | `true` | IPSView übernimmt das Kacheldesign; Quellen bleiben unabhängig davon gemeinsam. |
| An IPSView-Hintergrund anpassen / `IPSViewAdaptToBackground` | Boolean | `false` | Wenn im Modul angeboten: Hintergrundanpassung für das HTML-Widget. |
| Hintergrundfarbe / `IPSViewBackgroundColor` | Integer | `-1` | Wenn angeboten: automatische Farbe; eigene Farbe als `0xRRGGBB`. |
| Deckkraft / `IPSViewBackgroundOpacityPercent` | Integer | `35` | Wenn angeboten: Hintergrundtönung von 0 bis 100 %. |

Die Tabellen unten führen das Kacheldesign auf. Für jede tatsächlich registrierte
Designentsprechung gilt derselbe Name mit dem Präfix `IPSView`, etwa
`IPSViewShowValues`. Werte und Bedienbereiche sind gleich, sofern keine Abweichung
ausdrücklich genannt ist. Diese Felder wirken bei **ausgeschaltetem**
`IPSViewUseTileDesign`. Eigenständige Zeiteinstellungen sind separat aufgeführt;
sie werden nicht durch die Designvererbung ersetzt.

### Übernommene JSLive-Modelle

Bei GaugeSingle, TimeSeries, BarHistory, Pie, RadarHistory und Progress kann das
Gateway ein geprüftes Importmodell anlegen. Dieses Modell wird **nicht manuell**
aus einer regulären Quellenliste aufgebaut. Die Quellen und ursprünglichen
Stile bleiben im Modell; die folgenden Anpassungen gelten nur für importierte
Varianten. Native reguläre Designer werden dadurch nicht zu Importeditoren.

| Formularfeld / Property | Typ | Standard vor Import | Bedeutung / Auswahl |
| --- | --- | --- | --- |
| Importmodell / `JSLiveImportModel` | String | `""` | Nur durch die bestätigte Gateway-Übernahme; kein frei editierbares SVG oder Template. |
| Zeitraum / `JSLivePeriod` | Integer | `5` | Übernommene Zeitsteuerung historischer Modelle: 0 Jahrzehnt, 1 Jahr, 2 Quartal, 3 Monat, 4 Woche, 5 Tag, 6 Stunde, 7 Minute. |
| Relativer Zeitraum / `JSLiveRelative` | Boolean | `true` | Relatives statt kalendergebundenes Zeitfenster des Importmodells. |
| Aktuelles Datum / `JSLiveNow` | Boolean | `true` | Verwendet die aktuelle Zeit statt des historischen Ankers, soweit das Modell ihn anbietet. |
| Historischer Anker / `JSLiveStartDate` | Integer | `0` | Unix-Sekunden; für historische Modelle mit festem Anker. |
| Diagrammhintergrund / `JSLiveBackgroundColor` | Integer | `-1` | Transparent oder eigene Farbe. |
| Schriftgröße / `JSLiveFontSizePercent` | Integer | `100` | 50–200 %. |
| Titel anzeigen / `JSLiveShowTitle` | Boolean | `true` | Sichtbarkeit des importierten Titels. |
| Legende anzeigen / `JSLiveShowLegend` | Boolean | `true` | Wirkt bei Modellen mit Legende. |
| Tooltip anzeigen / `JSLiveShowTooltip` | Boolean | `true` | Sichtbarkeit der Detailwerte. |
| Animation / `JSLiveAnimationEnabled` | Boolean | `true` | Übergänge und unterstützte native Verlaufsbewegungen. |

Die sechs Designfelder von Hintergrund bis Animation besitzen eigene
`IPSViewJSLive…`-Entsprechungen mit denselben Standardwerten. Die Übernahme
initialisiert Felder aus dem Export und kann deshalb von den leeren Defaults
abweichen. Anleitung: [Migration von JSLive](JSLIVE_MIGRATION.md).

## Modulübersicht

- [EChartsBarCategory](#echartsbarcategory)
- [EChartsBarHistory](#echartsbarhistory)
- [EChartsBarPolar](#echartsbarpolar)
- [EChartsBarWaterfall](#echartsbarwaterfall)
- [EChartsBoxplot](#echartsboxplot)
- [EChartsCalendar](#echartscalendar)
- [EChartsCalendarPie](#echartscalendarpie)
- [EChartsCandlestick](#echartscandlestick)
- [EChartsFunnel](#echartsfunnel)
- [EChartsGateway](#echartsgateway)
- [EChartsGaugeChronograph](#echartsgaugechronograph)
- [EChartsGaugeMulti](#echartsgaugemulti)
- [EChartsGaugeSingle](#echartsgaugesingle)
- [EChartsGaugeTacho](#echartsgaugetacho)
- [EChartsGraph](#echartsgraph)
- [EChartsGraphZ2M](#echartsgraphz2m)
- [EChartsHeatmap](#echartsheatmap)
- [EChartsParallel](#echartsparallel)
- [EChartsPictorialBar](#echartspictorialbar)
- [EChartsPie](#echartspie)
- [EChartsProgress](#echartsprogress)
- [EChartsRadar](#echartsradar)
- [EChartsRadarHistory](#echartsradarhistory)
- [EChartsSankey](#echartssankey)
- [EChartsScatter](#echartsscatter)
- [EChartsStateTimeline](#echartsstatetimeline)
- [EChartsSunburst](#echartssunburst)
- [EChartsThemeRiver](#echartsthemeriver)
- [EChartsTimeSeries](#echartstimeseries)
- [EChartsTree](#echartstree)
- [EChartsTreeSystemHealth](#echartstreesystemhealth)
- [EChartsTreemap](#echartstreemap)

## EChartsBarCategory

[Zur Modulanleitung](../EChartsBarCategory/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Kategoriequellen | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Numerische Quellen anhand ihrer Min/Max normalisieren | `NormalizeValues` | Boolean | `false` | `true` / `false` |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Balkenmodus | `BarMode` | String | `"simple"` | `"simple"` Einfach; `"grouped"` Gruppiert; `"stacked"` Gestapelt |
| Ausrichtung | `Orientation` | String | `"vertical"` | `"vertical"` Vertikal; `"horizontal"` Horizontal |
| Reihenfolge | `SortOrder` | String | `"configured"` | `"configured"` Konfigurierte Reihenfolge; `"ascending"` Wert aufsteigend; `"descending"` Wert absteigend |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Werte anzeigen | `ShowValues` | Boolean | `true` | `true` / `false` |
| Position der Wertbeschriftung | `ValueLabelPosition` | String | `"auto"` | `"auto"` Automatisch; `"top"` Oberhalb; `"bottom"` Unterhalb; `"left"` Links; `"right"` Rechts; `"inside"` Innen mittig; `"insideTop"` Innen oben; `"insideBottom"` Innen unten; `"insideLeft"` Innen links; `"insideRight"` Innen rechts |
| Abstand der Wertbeschriftung | `ValueLabelDistance` | Integer | `5` | 0–100 px |
| Raster anzeigen | `ShowGrid` | Boolean | `true` | `true` / `false` |
| Abgerundete Balken | `RoundedBars` | Boolean | `false` | `true` / `false` |
| Balkenbreite | `BarWidthPercent` | Integer | `70` | 20–100 % |
| Balkenfüllung | `BarFillMode` | String | `"solid"` | `"solid"` Vollfarbe; `"svg"` SVG-Muster; `"gradient"` Farbverlauf entlang der Werteachse |
| Balken-SVG-Muster | `BarSVG` | String | `""` | Siehe Modulanleitung |
| SVG-Mustergröße | `BarSVGSizePercent` | Integer | `100` | 25–400 % |
| Farbskala Minimum | `ColorMinimum` | Float | `0.0` | -1000000000000.0–1000000000000.0 |
| Farbskala Maximum | `ColorMaximum` | Float | `100.0` | -1000000000000.0–1000000000000.0 |
| Farbe bei niedrigen Werten | `LowColor` | Integer | `0xFF0000` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe in der Skalenmitte | `MiddleColor` | Integer | `0xFFFF00` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe bei hohen Werten | `HighColor` | Integer | `0x00FF00` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Legendengröße | `LegendFontSizePercent` | Integer | `100` | 50–200 % |
| Achsenbeschriftungsgröße | `AxisFontSizePercent` | Integer | `100` | 50–200 % |
| Wertbeschriftungsgröße | `ValueFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Legendenfarbe | `LegendColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Achsenfarbe | `AxisColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Wertbeschriftungsfarbe | `ValueColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rasterfarbe | `GridColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Balken animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Kategorie | `Category` | String | `""` | Siehe Modulanleitung |
| Datenreihe (optional) | `Series` | String | `""` | Siehe Modulanleitung |
| Variablendarstellung | `UseVariablePresentation` | Boolean | `true` | `true` / `false` |
| Einheit | `Unit` | String | `""` | Siehe Modulanleitung |
| Nachkommastellen | `Decimals` | Integer | `1` | 0–6 |
| Min (Normalisierung) | `Minimum` | Integer | `0` | Siehe Modulanleitung |
| Max (Normalisierung) | `Maximum` | Integer | `100` | Siehe Modulanleitung |
| Farbe | `Color` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsBarHistory

[Zur Modulanleitung](../EChartsBarHistory/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Archivquellen (1 bis 32) | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Zeitraum | `Range` | String | `"24h"` | `"1h"` 1 Stunde; `"6h"` 6 Stunden; `"24h"` 24 Stunden; `"7d"` 7 Tage; `"30d"` 30 Tage; `"today"` Heute; `"yesterday"` Gestern; `"current-week"` Diese Woche; `"current-month"` Dieser Monat; `"custom"` Benutzerdefiniert |
| Zeitraumwert | `CustomRangeValue` | Integer | `7` | 1–1000 |
| Zeitraumeinheit | `CustomRangeUnit` | String | `"day"` | `"minute"` Minuten; `"hour"` Stunden; `"day"` Tage; `"week"` Wochen |
| Datenmodus | `DataMode` | String | `"auto"` | `"raw"` Rohwerte; `"auto"` Automatische Verdichtung; `"minute"` 1 Minute; `"five-minutes"` 5 Minuten; `"fifteen-minutes"` 15 Minuten; `"hour"` 1 Stunde; `"day"` 1 Tag |
| Punktbudget | `PointBudget` | Integer | `1000` | 200–32000 |
| Beschriftung der Zeitachse | `TimeAxisLabelFormat` | String | `"auto"` | `"auto"` Automatisch; `"time"` Uhrzeit; `"date"` Datum; `"date-time"` Datum und Uhrzeit |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dunkel; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Werte anzeigen | `ShowValues` | Boolean | `false` | `true` / `false` |
| Position der Wertbeschriftung | `ValueLabelPosition` | String | `"auto"` | `"auto"` Automatisch; `"top"` Oberhalb; `"bottom"` Unterhalb; `"left"` Links; `"right"` Rechts; `"inside"` Innen mittig; `"insideTop"` Innen oben; `"insideBottom"` Innen unten; `"insideLeft"` Innen links; `"insideRight"` Innen rechts |
| Abstand der Wertbeschriftung | `ValueLabelDistance` | Integer | `5` | 0–100 px |
| Raster anzeigen | `ShowGrid` | Boolean | `true` | `true` / `false` |
| Zoom aktivieren | `EnableZoom` | Boolean | `true` | `true` / `false` |
| Abgerundete Balken | `RoundedBars` | Boolean | `false` | `true` / `false` |
| Balkenbreite | `BarWidthPercent` | Integer | `70` | 20–100 % |
| Balkenfüllung | `BarFillMode` | String | `"solid"` | `"solid"` Vollfarbe; `"gradient"` Farbverlauf; `"svg"` SVG-Muster |
| Endfarbe des Farbverlaufs | `BarGradientColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Balken-SVG-Muster | `BarSVG` | String | `""` | Siehe Modulanleitung |
| SVG-Mustergröße | `BarSVGSizePercent` | Integer | `100` | 25–400 % |
| Balkendeckkraft | `BarOpacityPercent` | Integer | `100` | 0–100 % |
| Eckenradius | `BarCornerRadius` | Integer | `6` | 0–24 |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Legendengröße | `LegendFontSizePercent` | Integer | `100` | 50–200 % |
| Achsenbeschriftungsgröße | `AxisFontSizePercent` | Integer | `100` | 50–200 % |
| Wertbeschriftungsgröße | `ValueFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Legendenfarbe | `LegendColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Achsenfarbe | `AxisColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Wertbeschriftungsfarbe | `ValueColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rasterfarbe | `GridColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Balken animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |
| Zeiteinstellungen der Kachel verwenden | `IPSViewUseTileTimeSettings` | Boolean | `true` | `true` / `false` |
| IPSView-Zeitraum | `IPSViewRange` | String | `"24h"` | `"1h"` 1 Stunde; `"6h"` 6 Stunden; `"24h"` 24 Stunden; `"7d"` 7 Tage; `"30d"` 30 Tage; `"today"` Heute; `"yesterday"` Gestern; `"current-week"` Diese Woche; `"current-month"` Dieser Monat; `"custom"` Benutzerdefiniert |
| Zeitraumwert | `IPSViewCustomRangeValue` | Integer | `7` | 1–1000 |
| Zeitraumeinheit | `IPSViewCustomRangeUnit` | String | `"day"` | `"minute"` Minuten; `"hour"` Stunden; `"day"` Tage; `"week"` Wochen |
| Beschriftung der Zeitachse | `IPSViewTimeAxisLabelFormat` | String | `"auto"` | `"auto"` Automatisch; `"time"` Uhrzeit; `"date"` Datum; `"date-time"` Datum und Uhrzeit |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Anfangs sichtbar | `InitiallyVisible` | Boolean | `true` | `true` / `false` |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Beschriftung | `Label` | String | `""` | Siehe Modulanleitung |
| Variablendarstellung | `UseVariablePresentation` | Boolean | `true` | `true` / `false` |
| Einheit | `Unit` | String | `""` | Siehe Modulanleitung |
| Nachkommastellen | `Decimals` | Integer | `1` | 0–6 |
| Reduzierer | `Reducer` | String | `"auto"` | `"auto"` Automatisch; `"average"` Durchschnitt; `"sum"` Summe; `"minimum"` Minimum; `"maximum"` Maximum |
| Achsenseite | `AxisPosition` | String | `"auto"` | `"auto"` Automatisch; `"left"` Links; `"right"` Rechts |
| Farbe | `Color` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsBarPolar

[Zur Modulanleitung](../EChartsBarPolar/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Polar-Quellen | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Quellenauswahl aktivieren | `EnableSourceSelection` | Boolean | `false` | `true` / `false` |
| Werte anhand Quellen-Min/Max normalisieren | `NormalizeValues` | Boolean | `false` | `true` / `false` |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Polar-Darstellung | `PolarMode` | String | `"radial"` | `"radial"` Radiale Balken; `"tangential"` Konzentrische Bögen |
| Reihenfolge | `SortOrder` | String | `"configured"` | `"configured"` Konfigurierte Reihenfolge; `"ascending"` Wert aufsteigend; `"descending"` Wert absteigend |
| Kategorien anzeigen | `ShowCategoryLabels` | Boolean | `true` | `true` / `false` |
| Werte anzeigen | `ShowValues` | Boolean | `true` | `true` / `false` |
| Position der Wertbeschriftung | `ValueLabelPosition` | String | `"middle"` | `"middle"` Mitte des Balkens; `"insideStart"` Innen am Anfang; `"insideEnd"` Innen am Ende; `"outside"` Außerhalb des Balkens; `"start"` Am Anfang; `"end"` Am Ende |
| Abstand der Wertbeschriftung (-1 = automatisch) | `ValueLabelDistance` | Integer | `-1` | -1–100 px |
| Kategorieschriftgröße | `CategoryLabelFontSize` | Integer | `10` | 8–24 px |
| Wertschriftgröße | `ValueLabelFontSize` | Integer | `10` | 8–24 px |
| Skalenschriftgröße | `ScaleLabelFontSize` | Integer | `10` | 8–24 px |
| Raster anzeigen | `ShowGrid` | Boolean | `true` | `true` / `false` |
| Balkenbreite | `BarWidthPercent` | Integer | `60` | 20–100 % |
| Balkenfüllung | `BarFillMode` | String | `"solid"` | `"solid"` Vollfarbe; `"gradient"` Farbverlauf |
| Endfarbe des Farbverlaufs | `BarGradientColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Balkendeckkraft | `BarOpacityPercent` | Integer | `100` | 0–100 % |
| Balkenkontur (0 = aus) | `BarOutlineWidth` | Integer | `0` | 0–8 px |
| Konturfarbe | `BarOutlineColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Balkenschatten (0 = aus) | `BarShadowBlur` | Integer | `0` | 0–20 px |
| Schattenfarbe | `BarShadowColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Schattendeckkraft | `BarShadowOpacityPercent` | Integer | `35` | 0–100 % |
| Balken animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |
| Balkenhervorhebung | `BarHighlightMode` | String | `"standard"` | `"standard"` Standard (ECharts); `"outline"` Balken hervorheben; `"focus"` Balken fokussieren und andere abdunkeln; `"off"` Aus |
| Hervorhebungsfarbe | `BarHighlightColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Innenradius | `InnerRadiusPercent` | Integer | `12` | 0–75 % |
| Außenradius | `OuterRadiusPercent` | Integer | `76` | 20–95 % |
| Startwinkel | `StartAngle` | Integer | `90` | 0–360 ° |
| Winkelumfang | `AngularSpan` | Integer | `360` | 30–360 ° |
| Im Uhrzeigersinn | `Clockwise` | Boolean | `true` | `true` / `false` |
| Abgerundete Enden | `RoundCaps` | Boolean | `false` | `true` / `false` |
| Werteskala | `ValueAxisRangeMode` | String | `"auto"` | `"auto"` Automatisch; `"manual"` Fester Bereich |
| Skalenminimum | `ValueAxisMinimum` | Float | `0.0` | Siehe Modulanleitung |
| Skalenmaximum | `ValueAxisMaximum` | Float | `100.0` | Siehe Modulanleitung |
| Hintergrundspuren anzeigen | `ShowBarBackground` | Boolean | `false` | `true` / `false` |
| Spurfarbe | `BarBackgroundColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Spurdeckkraft | `BarBackgroundOpacityPercent` | Integer | `25` | 0–100 % |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Kategoriefarbe | `CategoryLabelColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Skalenfarbe | `ScaleLabelColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe der äußeren Werte | `ValueColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rasterfarbe | `GridColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Anfangs sichtbar | `InitiallyVisible` | Boolean | `true` | `true` / `false` |
| Min (Normalisierung) | `Minimum` | Integer | `0` | Siehe Modulanleitung |
| Max (Normalisierung) | `Maximum` | Integer | `100` | Siehe Modulanleitung |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Kategoriebeschriftung (optional) | `Label` | String | `""` | Siehe Modulanleitung |
| Variablendarstellung | `UseVariablePresentation` | Boolean | `true` | `true` / `false` |
| Einheit | `Unit` | String | `""` | Siehe Modulanleitung |
| Nachkommastellen | `Decimals` | Integer | `1` | 0–6 |
| Farbe | `Color` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsBarWaterfall

[Zur Modulanleitung](../EChartsBarWaterfall/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Waterfall-Quellen (erste Zeile: Startwert; Änderungen oder ausdrückliche Neustarts) | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Beschriftung der Endsumme | `TotalLabel` | String | `""` | Siehe Modulanleitung |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Werte anzeigen | `ShowValues` | Boolean | `true` | `true` / `false` |
| Position der Wertbeschriftung | `ValueLabelPosition` | String | `"auto"` | `"auto"` Automatisch; `"top"` Oberhalb; `"bottom"` Unterhalb; `"left"` Links; `"right"` Rechts; `"inside"` Innen mittig; `"insideTop"` Innen oben; `"insideBottom"` Innen unten; `"insideLeft"` Innen links; `"insideRight"` Innen rechts |
| Abstand der Wertbeschriftung | `ValueLabelDistance` | Integer | `5` | 0–100 px |
| Raster anzeigen | `ShowGrid` | Boolean | `true` | `true` / `false` |
| Balkenbreite | `BarWidthPercent` | Integer | `60` | 20–100 % |
| Startfarbe | `StartColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe für Zunahme | `IncreaseColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe für Abnahme | `DecreaseColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe der Endsumme | `TotalColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Achsenbeschriftungsgröße | `AxisFontSizePercent` | Integer | `100` | 50–200 % |
| Wertbeschriftungsgröße | `ValueFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Achsenfarbe | `AxisColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Wertbeschriftungsfarbe | `ValueColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rasterfarbe | `GridColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Balken animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Schrittbeschriftung (optional) | `Label` | String | `""` | Siehe Modulanleitung |
| Variablendarstellung | `UseVariablePresentation` | Boolean | `true` | `true` / `false` |
| Einheit | `Unit` | String | `""` | Siehe Modulanleitung |
| Nachkommastellen | `Decimals` | Integer | `1` | 0–6 |
| Berechnete Zwischensumme danach | `SubtotalAfter` | Boolean | `false` | `true` / `false` |
| Neustart mit diesem absoluten Wert | `Restart` | Boolean | `false` | `true` / `false` |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsBoxplot

[Zur Modulanleitung](../EChartsBoxplot/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Boxplot-Variablen | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Whisker-Modell | `WhiskerMode` | String | `"minmax"` | `"minmax"` Minimum und Maximum (Standard); `"iqr"` 1,5 IQR mit separaten Ausreißern |
| Zeitraum | `Range` | String | `"7d"` | `"24h"` Letzte 24 Stunden; `"7d"` Letzte 7 Tage; `"30d"` Letzte 30 Tage; `"custom"` Benutzerdefiniert; `"absolute"` Fester historischer Zeitraum |
| Zeitraumwert | `CustomRangeValue` | Integer | `7` | 1–1000 |
| Zeitraumeinheit | `CustomRangeUnit` | String | `"day"` | `"minute"` Minuten; `"hour"` Stunden; `"day"` Tage; `"week"` Wochen |
| Von (einschließlich) | `AbsoluteStart` | String | `"{}"` | Datum/Uhrzeit als JSON |
| Bis (ausschließlich) | `AbsoluteEnd` | String | `"{}"` | Datum/Uhrzeit als JSON |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dunkel; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Ausrichtung | `Orientation` | String | `"vertical"` | `"vertical"` Senkrecht; `"horizontal"` Waagerecht |
| Tooltip anzeigen | `ShowTooltip` | Boolean | `true` | `true` / `false` |
| Raster anzeigen | `ShowGrid` | Boolean | `true` | `true` / `false` |
| Achsenbeschriftungen anzeigen | `ShowAxisLabels` | Boolean | `true` | `true` / `false` |
| Maximale Boxbreite | `BoxWidth` | Integer | `40` | 8–80 px |
| Rahmenbreite | `BorderWidth` | Integer | `2` | 0–8 px |
| Boxdeckkraft | `BoxOpacityPercent` | Integer | `70` | 0–100 % |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Achsenschriftgröße | `AxisFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Achsenfarbe | `AxisColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rahmenfarbe | `BorderColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Minimale Boxbreite | `MinBoxWidth` | Integer | `1` | 1–80 px |
| Randlinienart | `BorderType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Schattenunschärfe | `ShadowBlur` | Integer | `0` | 0–20 px |
| Schattenfarbe | `ShadowColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Schattenversatz X | `ShadowOffsetX` | Integer | `0` | -20–20 px |
| Schattenversatz Y | `ShadowOffsetY` | Integer | `0` | -20–20 px |
| Hervorhebung beim Zeigen | `HoverFocus` | String | `"self"` | `"self"` Berührte Box hervorheben; `"none"` Keine Hervorhebung |
| Deckkraft übriger Boxen | `BlurOpacityPercent` | Integer | `10` | 0–100 % |
| Kategorien-Achsenbreite | `AxisLineWidth` | Integer | `1` | 0–5 px |
| Kategorien-Teilstriche anzeigen | `ShowAxisTicks` | Boolean | `true` | `true` / `false` |
| Drehung der Kategorienbeschriftung | `AxisLabelRotate` | Integer | `0` | -90–90 ° |
| Kategorien-Textbreite (0 = automatisch) | `AxisLabelMaxWidth` | Integer | `0` | 0–300 px |
| Titel fett | `TitleBold` | Boolean | `true` | `true` / `false` |
| Achsenbeschriftungen fett | `AxisLabelBold` | Boolean | `false` | `true` / `false` |
| Unterteilungen der Werteachse | `SplitNumber` | Integer | `5` | 2–10  |
| Rasterlinienart | `GridLineType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Rasterlinienbreite | `GridLineWidth` | Integer | `1` | 0–5 px |
| Rasterdeckkraft | `GridOpacityPercent` | Integer | `25` | 0–100 % |
| Rasterfarbe | `GridColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Boxplot animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Beschriftung (optional) | `Label` | String | `""` | Siehe Modulanleitung |
| Boxfarbe (optional) | `Color` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsCalendar

[Zur Modulanleitung](../EChartsCalendar/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Archivvariable | `SourceVariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Jahr (0 = aktuelles Jahr) | `Year` | Integer | `0` | 0 oder 1971 bis aktuelles Jahr |
| Kalenderzeitraum | `Period` | String | `"year"` | `"year"` Jahr; `"quarter"` Quartal; `"month"` Monat; `"absolute"` Fester historischer Zeitraum |
| Monat (0 = aktueller Monat) | `Month` | Integer | `0` | 0–12 |
| Quartal (0 = aktuelles Quartal) | `Quarter` | Integer | `0` | 0–4 |
| Von (einschließlich) | `AbsoluteStart` | String | `"{}"` | Datum als JSON |
| Bis (ausschließlich) | `AbsoluteEnd` | String | `"{}"` | Datum als JSON |
| Kennzahl | `Metric` | String | `"average"` | `"average"` Tagesmittelwert; `"minimum"` Tagesminimum; `"maximum"` Tagesmaximum; `"consumption"` Tagesverbrauch |
| Variablendarstellung verwenden | `UseVariablePresentation` | Boolean | `true` | `true` / `false` |
| Einheit | `Unit` | String | `""` | Siehe Modulanleitung |
| Nachkommastellen | `Decimals` | Integer | `1` | 0–6 |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dunkel; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Ausrichtung | `Orientation` | String | `"auto"` | `"auto"` Automatisch; `"horizontal"` Horizontal; `"vertical"` Vertikal |
| Tooltip anzeigen | `ShowTooltip` | Boolean | `true` | `true` / `false` |
| Monatsnamen anzeigen | `ShowMonthLabels` | Boolean | `true` | `true` / `false` |
| Wochentage anzeigen | `ShowWeekdayLabels` | Boolean | `true` | `true` / `false` |
| Wochenbeginn | `FirstWeekday` | Integer | `1` | `1` Montag; `0` Sonntag; `6` Samstag |
| Tagesnummern anzeigen | `ShowDayNumbers` | Boolean | `false` | `true` / `false` |
| Tageswerte anzeigen | `ShowValues` | Boolean | `false` | `true` / `false` |
| Farbskala anzeigen | `ShowColorScale` | Boolean | `true` | `true` / `false` |
| Breite der Zellränder | `CellBorderWidth` | Integer | `1` | 0–4 px |
| Farbe der Zellränder | `CellBorderColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe leerer Tage | `EmptyCellColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe für niedrige Werte | `LowColor` | Integer | `0x5070DD` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe für hohe Werte | `HighColor` | Integer | `0xFB628B` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Achsenschriftgröße | `AxisFontSizePercent` | Integer | `100` | 50–200 % |
| Zellbeschriftungsgröße | `ValueFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Achsenfarbe | `AxisColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Zellbeschriftungsfarbe | `ValueColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Monatstrennlinien anzeigen | `ShowMonthSeparators` | Boolean | `true` | `true` / `false` |
| Trennlinienbreite (0 = Zellrand) | `MonthLineWidth` | Integer | `0` | 0–6 px |
| Trennlinienart | `MonthLineType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Trennlinienfarbe | `MonthLineColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Position der Monatsnamen | `MonthLabelPosition` | String | `"start"` | `"start"` Anfang; `"end"` Ende |
| Position der Wochentagsnamen | `WeekdayLabelPosition` | String | `"start"` | `"start"` Anfang; `"end"` Ende |
| Abstand der Monatsnamen | `MonthLabelMargin` | Integer | `8` | 0–40 px |
| Abstand der Wochentagsnamen | `WeekdayLabelMargin` | Integer | `8` | 0–40 px |
| Monatsausrichtung | `MonthLabelAlign` | String | `"center"` | `"center"` Zentriert; `"left"` Monatsanfang |
| Monatsformat | `MonthLabelFormat` | String | `"name"` | `"name"` Monatsname; `"number"` Monatsnummer; `"year-month"` Jahr und Monat |
| Jahresbeschriftung anzeigen | `ShowYearLabel` | Boolean | `false` | `true` / `false` |
| Position der Jahresbeschriftung | `YearLabelPosition` | String | `"auto"` | `"auto"` Automatisch; `"top"` Oben; `"bottom"` Unten; `"left"` Links; `"right"` Rechts |
| Abstand der Jahresbeschriftung | `YearLabelMargin` | Integer | `30` | 0–60 px |
| Größe der Jahresbeschriftung | `YearFontSizePercent` | Integer | `100` | 50–200 % |
| Farbe der Jahresbeschriftung | `YearColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Kalenderbeschriftungen fett | `LabelBold` | Boolean | `false` | `true` / `false` |
| Zelldeckkraft | `CellOpacityPercent` | Integer | `100` | 0–100 % |
| Zellrandart | `CellBorderType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Tag beim Berühren hervorheben | `HoverHighlight` | Boolean | `true` | `true` / `false` |
| Hervorhebungsrahmenfarbe | `HoverBorderColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbreihenfolge umkehren | `ReverseColorScale` | Boolean | `false` | `true` / `false` |
| Festen Farbbereich verwenden | `UseFixedColorRange` | Boolean | `false` | `true` / `false` |
| Farbskala Minimum | `ColorScaleMinimum` | Float | `0.0` | -1000000000000–1000000000000 |
| Farbskala Maximum | `ColorScaleMaximum` | Float | `100.0` | -1000000000000–1000000000000 |
| Kalender animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsCalendarPie

[Zur Modulanleitung](../EChartsCalendarPie/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Numerische Archivquellen (1–5) | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Datengrundlage | `DataMode` | String | `"average"` | `"average"` Tagesmittelwerte; `"counter"` Tagesverbrauch; `"states"` Zustandsdauer |
| Numerische Quellen anhand ihres Min/Max normalisieren | `NormalizeValues` | Boolean | `false` | `true` / `false` |
| Vorhandene Quellen an unvollständigen Tagen anzeigen | `ShowIncompleteDays` | Boolean | `false` | `true` / `false` |
| Monat (JJJJ-MM, leer = aktueller Monat) | `Month` | String | `""` | Siehe Modulanleitung |
| Zustands-Archivvariable | `StateVariableID` | Integer | `0` | Vorhandene ID; Variablentyp 0, 1 |
| Zustandszuordnung | `States` | String | `"[]"` | Siehe Modulanleitung |
| Variablendarstellung verwenden | `UseVariablePresentation` | Boolean | `true` | `true` / `false` |
| Einheit | `Unit` | String | `""` | Siehe Modulanleitung |
| Nachkommastellen | `Decimals` | Integer | `1` | 0–6 |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dunkel; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Tooltip anzeigen | `ShowTooltip` | Boolean | `true` | `true` / `false` |
| Legende anzeigen | `ShowLegend` | Boolean | `true` | `true` / `false` |
| Tagesnummern anzeigen | `ShowDayNumbers` | Boolean | `true` | `true` / `false` |
| Segmentwerte anzeigen | `ShowValues` | Boolean | `false` | `true` / `false` |
| Wochenbeginn | `FirstWeekday` | Integer | `1` | `0` So; `1` Mo; `2` Di; `3` Mi; `4` Do; `5` Fr; `6` Sa |
| Kreisradius | `PieRadiusPercent` | Integer | `70` | 20–90 % |
| Innenradius | `InnerRadiusPercent` | Integer | `0` | 0–80 % |
| Startwinkel | `StartAngle` | Integer | `90` | 0–360 ° |
| Im Uhrzeigersinn | `Clockwise` | Boolean | `true` | `true` / `false` |
| Segmentrahmenbreite | `SliceBorderWidth` | Integer | `1` | 0–5 px |
| Segmentrahmenfarbe | `SliceBorderColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Zellrahmenbreite | `CellBorderWidth` | Integer | `1` | 0–4 px |
| Zellrahmenfarbe | `CellBorderColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Zelldeckkraft | `CellOpacityPercent` | Integer | `100` | 0–100 % |
| Zellhintergrundfarbe | `EmptyCellColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe unbekannter Zustandszeit | `UnknownColor` | Integer | `0x777B83` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Titelschriftgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Achsenschriftgröße | `AxisFontSizePercent` | Integer | `100` | 50–200 % |
| Werteschriftgröße | `ValueFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Achsenfarbe | `AxisColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Wertefarbe | `ValueColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Tageskreise animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Beschriftung (optional) | `Label` | String | `""` | Siehe Modulanleitung |
| Min (Normalisierung) | `Minimum` | Integer | `0` | Siehe Modulanleitung |
| Max (Normalisierung) | `Maximum` | Integer | `100` | Siehe Modulanleitung |
| Farbe | `Color` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |

### Zeilenfelder von `States`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Wert | `Value` | Integer | `0` | Siehe Modulanleitung |
| Zustandsname | `Label` | String | `""` | Siehe Modulanleitung |
| Zustandsfarbe | `Color` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsCandlestick

[Zur Modulanleitung](../EChartsCandlestick/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Archivvariable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Benannte Vergleichspanels verwenden | `UseComparisonPanels` | Boolean | `false` | `true` / `false` |
| Vergleichspanels | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Intervall | `Interval` | String | `"day"` | `"fifteen-minutes"` 15 Minuten; `"thirty-minutes"` 30 Minuten; `"hour"` Stunden; `"day"` Tage; `"week"` Kalenderwochen (Montag bis Montag) |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Zeitraum | `Range` | String | `"7d"` | `"24h"` Letzte 24 Stunden; `"7d"` Letzte 7 Tage; `"30d"` Letzte 30 Tage; `"custom"` Benutzerdefinierte Dauer; `"absolute"` Fester historischer Zeitraum |
| Dauer | `CustomRangeValue` | Integer | `7` | 1–1000 |
| Zeiteinheit | `CustomRangeUnit` | String | `"day"` | `"minute"` Minuten; `"hour"` Stunden; `"day"` Tage; `"week"` Wochen |
| Von (einschließlich) | `AbsoluteStart` | String | `"{}"` | Datum/Uhrzeit als JSON |
| Bis (ausschließlich) | `AbsoluteEnd` | String | `"{}"` | Datum/Uhrzeit als JSON |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dunkel; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Ausrichtung | `Orientation` | String | `"vertical"` | `"vertical"` Senkrecht; `"horizontal"` Waagerecht |
| Tooltip anzeigen | `ShowTooltip` | Boolean | `true` | `true` / `false` |
| Raster anzeigen | `ShowGrid` | Boolean | `true` | `true` / `false` |
| Achsenbeschriftungen anzeigen | `ShowAxisLabels` | Boolean | `true` | `true` / `false` |
| Maximale Kerzenbreite | `CandleWidth` | Integer | `40` | 8–80 px |
| Rahmenbreite | `BorderWidth` | Integer | `2` | 0–8 px |
| Kerzendeckkraft | `CandleOpacityPercent` | Integer | `70` | 0–100 % |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Achsenschriftgröße | `AxisFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Achsenfarbe | `AxisColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rahmenfarbe | `BorderColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe bei Anstieg | `RiseColor` | Integer | `0x3FBE95` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe bei Abfall | `FallColor` | Integer | `0xE66B6B` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe bei gleichem Anfangs- und Endwert | `DojiColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Minimale Kerzenbreite | `MinCandleWidth` | Integer | `1` | 1–80 px |
| Randlinienart | `BorderType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Schattenunschärfe | `ShadowBlur` | Integer | `0` | 0–20 px |
| Schattenfarbe | `ShadowColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Schattenversatz X | `ShadowOffsetX` | Integer | `0` | -20–20 px |
| Schattenversatz Y | `ShadowOffsetY` | Integer | `0` | -20–20 px |
| Hervorhebung beim Zeigen | `HoverFocus` | String | `"self"` | `"self"` Berührte Kerze hervorheben; `"none"` Keine Hervorhebung |
| Deckkraft übriger Kerzen | `BlurOpacityPercent` | Integer | `10` | 0–100 % |
| Kategorien-Achsenbreite | `AxisLineWidth` | Integer | `1` | 0–5 px |
| Kategorien-Teilstriche anzeigen | `ShowAxisTicks` | Boolean | `true` | `true` / `false` |
| Drehung der Kategorienbeschriftung | `AxisLabelRotate` | Integer | `0` | -90–90 ° |
| Kategorien-Textbreite (0 = automatisch) | `AxisLabelMaxWidth` | Integer | `0` | 0–300 px |
| Titel fett | `TitleBold` | Boolean | `true` | `true` / `false` |
| Achsenbeschriftungen fett | `AxisLabelBold` | Boolean | `false` | `true` / `false` |
| Unterteilungen der Werteachse | `SplitNumber` | Integer | `5` | 2–10  |
| Rasterlinienart | `GridLineType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Rasterlinienbreite | `GridLineWidth` | Integer | `1` | 0–5 px |
| Rasterdeckkraft | `GridOpacityPercent` | Integer | `25` | 0–100 % |
| Rasterfarbe | `GridColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Feste Kerzenbreite (0 = automatisch) | `FixedCandleWidth` | Integer | `0` | 0–80 px |
| Steigende Kerzen hohl | `RiseHollow` | Boolean | `false` | `true` / `false` |
| Fallende Kerzen hohl | `FallHollow` | Boolean | `false` | `true` / `false` |
| Rahmenfarbe bei Anstieg | `RiseBorderColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rahmenfarbe bei Abfall | `FallBorderColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Hover-Rahmenbreite | `HoverBorderWidth` | Integer | `2` | 0–8 px |
| Hover-Rahmenfarbe | `HoverBorderColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Hover-Deckkraft (-1 = unverändert) | `HoverOpacityPercent` | Integer | `-1` | -1–100 % |
| Linienfarbe der Zeitachse | `AxisLineColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Linienart der Zeitachse | `AxisLineType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Abstand der Achsenbeschriftungen | `AxisLabelMargin` | Integer | `8` | 0–30 px |
| Zeitachse umkehren | `ReverseTimeAxis` | Boolean | `false` | `true` / `false` |
| Nullpunkt in Werteachse einschließen | `IncludeZero` | Boolean | `false` | `true` / `false` |
| Schriftart | `FontFamily` | String | `"Arial, sans-serif"` | `"Arial, sans-serif"` Arial; `"Verdana, sans-serif"` Verdana; `"Georgia, serif"` Georgia; `"monospace"` Nichtproportional |
| Titel kursiv | `TitleItalic` | Boolean | `false` | `true` / `false` |
| Achsenbeschriftungen kursiv | `AxisLabelItalic` | Boolean | `false` | `true` / `false` |
| Zeitzoom aktivieren | `EnableZoom` | Boolean | `false` | `true` / `false` |
| Candlestick animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Archivvariable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Panelname (optional) | `Label` | String | `""` | Siehe Modulanleitung |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsFunnel

[Zur Modulanleitung](../EChartsFunnel/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Funnel-Stufen | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Quellenauswahl aktivieren | `EnableSourceSelection` | Boolean | `false` | `true` / `false` |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dunkel; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Ausrichtung | `Orientation` | String | `"vertical"` | `"vertical"` Senkrecht; `"horizontal"` Waagerecht |
| Stufenreihenfolge | `SortOrder` | String | `"configured"` | `"configured"` Listenreihenfolge; `"ascending"` Werte aufsteigend; `"descending"` Werte absteigend |
| Querausrichtung | `Alignment` | String | `"center"` | `"start"` Anfangsrand; `"center"` Zentriert; `"end"` Endrand |
| Stufenabstand | `Gap` | Integer | `3` | 0–30 px |
| Tooltip anzeigen | `ShowTooltip` | Boolean | `true` | `true` / `false` |
| Stufenbeschriftungen anzeigen | `ShowLabels` | Boolean | `true` | `true` / `false` |
| Aktuelle Werte anzeigen | `ShowValues` | Boolean | `true` | `true` / `false` |
| Beschriftungsposition | `LabelPosition` | String | `"inside"` | `"inside"` Innen; `"outside"` Außen |
| Beschriftungsgröße | `LabelFontSizePercent` | Integer | `100` | 50–200 % |
| Beschriftungsfarbe | `LabelColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Beschriftungen fett | `LabelBold` | Boolean | `false` | `true` / `false` |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Titel fett | `TitleBold` | Boolean | `true` | `true` / `false` |
| Rahmenbreite | `BorderWidth` | Integer | `1` | 0–8 px |
| Rahmenfarbe | `BorderColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Stufendeckkraft | `OpacityPercent` | Integer | `90` | 0–100 % |
| Minimale optische Größe | `MinSizePercent` | Integer | `0` | 0–100 % |
| Maximale optische Größe | `MaxSizePercent` | Integer | `100` | 1–100 % |
| Rahmenart | `BorderType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Schattenunschärfe | `ShadowBlur` | Integer | `0` | 0–20 px |
| Schattenfarbe | `ShadowColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Schattenversatz X | `ShadowOffsetX` | Integer | `0` | -20–20 px |
| Schattenversatz Y | `ShadowOffsetY` | Integer | `0` | -20–20 px |
| Hervorhebung bei Berührung | `HoverFocus` | String | `"self"` | `"self"` Berührte Stufe hervorheben; `"none"` Keine Hervorhebung |
| Deckkraft anderer Stufen (-1 = automatisch) | `BlurOpacityPercent` | Integer | `-1` | -1–100 % |
| Überlappende Beschriftungen ausblenden | `HideOverlappingLabels` | Boolean | `true` | `true` / `false` |
| Beschriftungsbreite (0 = automatisch) | `LabelMaxWidth` | Integer | `0` | 0–300 px |
| Seite der Außenbeschriftung | `OutsideLabelSide` | String | `"end"` | `"start"` Anfangsrand; `"end"` Endrand |
| Führungslinien anzeigen | `ShowLabelLines` | Boolean | `true` | `true` / `false` |
| Führungslinienlänge | `LabelLineLength` | Integer | `10` | 0–60 px |
| Führungslinienbreite | `LabelLineWidth` | Integer | `1` | 0–5 px |
| Führungslinienart | `LabelLineType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Führungslinienfarbe | `LabelLineColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Führungsliniendeckkraft | `LabelLineOpacityPercent` | Integer | `100` | 0–100 % |
| Schriftart | `FontFamily` | String | `"Arial, sans-serif"` | `"Arial, sans-serif"` Arial; `"Verdana, sans-serif"` Verdana; `"Georgia, serif"` Georgia; `"monospace"` Nichtproportional |
| Funnel animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Beschriftung (optional) | `Label` | String | `""` | Siehe Modulanleitung |
| Anfangs sichtbar | `InitiallyVisible` | Boolean | `true` | `true` / `false` |
| Stufenfarbe (optional) | `Color` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsGateway

[Zur Modulanleitung](../EChartsGateway/README.md).

Das Gateway registriert keine konfigurierbaren Properties. Datei, Zuordnungen,
Zielkategorie und Bestätigungen sind flüchtige Formularangaben; das Importjournal
wird getrennt gespeichert. Es gibt keine Diagramm-Designer oder Ausgabevariablen.

## EChartsGaugeChronograph

[Zur Modulanleitung](../EChartsGaugeChronograph/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Gauge-Quellen | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Feste Instrumentenvorlage | `GaugePreset` | String | `"chronograph"` | Familienvorgabe; nicht im Formular änderbar |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Größe des Diagrammtitels | `ChartTitleFontSizePercent` | Integer | `100` | 50–150 % |
| Farbe des Diagrammtitels | `ChartTitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Ringstärke | `RingWidthPercent` | Integer | `100` | 50–150  % |
| Skalenschrift | `ScaleFontSizePercent` | Integer | `100` | 50–150  % |
| Wertschrift | `ValueFontSizePercent` | Integer | `100` | 50–150  % |
| Titelschrift | `TitleFontSizePercent` | Integer | `100` | 50–150  % |
| Zeigerstärke | `PointerWidthPercent` | Integer | `100` | 50–150  % |
| Zeigerlänge | `PointerLengthPercent` | Integer | `100` | 50–150  % |
| Nabengröße | `AnchorSizePercent` | Integer | `100` | 50–150  % |
| Nabenrand | `AnchorBorderWidthPercent` | Integer | `100` | 50–150  % |
| Plattengröße | `PlateSizePercent` | Integer | `100` | 50–150  % |
| Plattenrand | `PlateBorderWidthPercent` | Integer | `100` | 50–150  % |
| Zeigerform | `PointerShape` | String | `"preset"` | `"preset"` Vorlage; `"needle"` Nadel; `"line"` Linie; `"arrow"` Pfeil; `"custom"` Benutzerdefiniert |
| Nabendesign | `AnchorShape` | String | `"preset"` | `"preset"` Vorlage; `"circle"` Kreis; `"ring"` Ring; `"custom"` Benutzerdefiniert; `"none"` Keine Nabe |
| Farben | `GaugeColorMode` | String | `"theme"` | `"theme"` Farben aus dem Theme; `"custom"` Benutzerdefiniert |
| Plattendesign | `PlateDesignMode` | String | `"preset"` | `"preset"` Vorlage; `"custom"` Benutzerdefiniert; `"hidden"` Ausgeblendet |
| Zusätzlicher Skalenbeschriftungsabstand | `ScaleLabelDistanceOffset` | Integer | `0` | -100–100 px |
| Titelverschiebung X | `TitleOffsetXPercent` | Integer | `0` | -100–100 % |
| Titelverschiebung Y | `TitleOffsetYPercent` | Integer | `0` | -100–100 % |
| Wertverschiebung X | `ValueOffsetXPercent` | Integer | `0` | -100–100 % |
| Wertverschiebung Y | `ValueOffsetYPercent` | Integer | `0` | -100–100 % |
| Hauptunterteilungen | `MajorSplitCount` | Integer | `0` | 0–24 |
| Nebenunterteilungen | `MinorSplitCount` | Integer | `0` | 0–10 |
| Zeigerfarbe | `PointerColor` | Integer | `0x55CBB5` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Fortschrittsfarbe | `ProgressColor` | Integer | `0x55CBB5` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Nabenfarbe | `AnchorColor` | Integer | `0x55CBB5` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Nabenrandfarbe | `AnchorBorderColor` | Integer | `0xF4F5F7` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Ringfarbe | `RingColor` | Integer | `0x45474C` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Skalenfarbe | `ScaleColor` | Integer | `0xA7A9AE` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Wertfarbe | `ValueColor` | Integer | `0xF4F5F7` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Titelfarbe | `TitleColor` | Integer | `0xA7A9AE` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Plattenfarbe | `PlateColor` | Integer | `0x25272B` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Plattenrandfarbe | `PlateBorderColor` | Integer | `0xA5A9B0` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Eigenes Zeiger-SVG | `CustomPointerSVG` | String | `""` | Siehe Modulanleitung |
| SVG-Ankerpunkt | `CustomPointerPivotMode` | String | `"svg"` | `"svg"` SVG; `"custom"` Benutzerdefiniert |
| Eigenes Naben-SVG | `CustomAnchorSVG` | String | `""` | Siehe Modulanleitung |
| Zifferblatt-Hintergrund als SVG | `PlateBackgroundSVG` | String | `""` | Siehe Modulanleitung |
| Bildanpassung | `PlateBackgroundFit` | String | `"cover"` | `"contain"` Einpassen; `"cover"` Ausfüllen; `"stretch"` Strecken |
| Ankerpunkt X | `CustomPointerPivotXPercent` | Float | `50.0` | 0–100  % |
| Ankerpunkt Y | `CustomPointerPivotYPercent` | Float | `100.0` | 0–100  % |
| Drehung | `PlateBackgroundRotation` | Float | `0.0` | -180–180  ° |
| SVG-Zifferblatthintergrund verwenden | `PlateBackgroundEnabled` | Boolean | `false` | `true` / `false` |
| Gauge animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Größe | `PlateBackgroundSizePercent` | Integer | `100` | 25–200  % |
| Versatz X | `PlateBackgroundOffsetXPercent` | Integer | `0` | -100–100  % |
| Versatz Y | `PlateBackgroundOffsetYPercent` | Integer | `0` | -100–100  % |
| Deckkraft | `PlateBackgroundOpacityPercent` | Integer | `100` | 0–100  % |
| Startanimation | `AnimationDuration` | Integer | `500` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |
| IPSViewGaugePreset | `IPSViewGaugePreset` | String | `"chronograph"` | Siehe Modulanleitung |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Beschriftung | `Label` | String | `""` | Siehe Modulanleitung |
| Variablendarstellung | `UseVariablePresentation` | Boolean | `false` | `true` / `false` |
| Minimum | `Minimum` | Float | `0.0` | Siehe Modulanleitung |
| Maximum | `Maximum` | Float | `100.0` | Siehe Modulanleitung |
| Einheit | `Unit` | String | `""` | Siehe Modulanleitung |
| Nachkommastellen | `Decimals` | Integer | `1` | 0–6 |
| Individuelles Design | `UseIndividualDesign` | Boolean | `false` | `true` / `false` |
| PointerShape | `PointerShape` | String | `"preset"` | `"preset"` Vorlage; `"needle"` Nadel; `"line"` Linie; `"arrow"` Pfeil; `"custom"` Benutzerdefiniert |
| PointerWidthPercent | `PointerWidthPercent` | Integer | `100` | 50–150  % |
| PointerLengthPercent | `PointerLengthPercent` | Integer | `100` | 50–150  % |
| CustomPointerSVG | `CustomPointerSVG` | String | `""` | Siehe Modulanleitung |
| CustomPointerPivotMode | `CustomPointerPivotMode` | String | `"svg"` | `"svg"` SVG; `"custom"` Benutzerdefiniert |
| CustomPointerPivotXPercent | `CustomPointerPivotXPercent` | Float | `50.0` | 0–100  % |
| CustomPointerPivotYPercent | `CustomPointerPivotYPercent` | Float | `100.0` | 0–100  % |
| AnchorShape | `AnchorShape` | String | `"preset"` | `"preset"` Vorlage; `"circle"` Kreis; `"ring"` Ring; `"custom"` Benutzerdefiniert; `"none"` Keine Nabe |
| CustomAnchorSVG | `CustomAnchorSVG` | String | `""` | Siehe Modulanleitung |
| AnchorSizePercent | `AnchorSizePercent` | Integer | `100` | 50–150  % |
| AnchorBorderWidthPercent | `AnchorBorderWidthPercent` | Integer | `100` | 50–150  % |
| GaugeColorMode | `GaugeColorMode` | String | `"theme"` | `"theme"` Farben aus dem Theme; `"custom"` Benutzerdefiniert |
| Zeigerfarbe | `PointerColor` | Integer | `5622709` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Nabenfarbe | `AnchorColor` | Integer | `5622709` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Nabenrandfarbe | `AnchorBorderColor` | Integer | `16053751` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Ringfarbe | `RingColor` | Integer | `4540236` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Skalenfarbe | `ScaleColor` | Integer | `10987950` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Wertfarbe | `ValueColor` | Integer | `16053751` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Titelfarbe | `TitleColor` | Integer | `10987950` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Fortschrittsfarbe | `ProgressColor` | Integer | `5622709` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| RingWidthPercent | `RingWidthPercent` | Integer | `100` | 50–150  % |
| ScaleFontSizePercent | `ScaleFontSizePercent` | Integer | `100` | 50–150  % |
| ValueFontSizePercent | `ValueFontSizePercent` | Integer | `100` | 50–150  % |
| TitleFontSizePercent | `TitleFontSizePercent` | Integer | `100` | 50–150  % |
| MajorSplitCount | `MajorSplitCount` | Integer | `0` | 0–24 |
| MinorSplitCount | `MinorSplitCount` | Integer | `0` | 0–10 |
| ScaleLabelDistanceOffset | `ScaleLabelDistanceOffset` | Integer | `0` | -100–100 px |
| TitleOffsetXPercent | `TitleOffsetXPercent` | Integer | `0` | -100–100 % |
| TitleOffsetYPercent | `TitleOffsetYPercent` | Integer | `0` | -100–100 % |
| ValueOffsetXPercent | `ValueOffsetXPercent` | Integer | `0` | -100–100 % |
| ValueOffsetYPercent | `ValueOffsetYPercent` | Integer | `0` | -100–100 % |
| PlateDesignMode | `PlateDesignMode` | String | `"preset"` | `"preset"` Vorlage; `"custom"` Benutzerdefiniert; `"hidden"` Ausgeblendet |
| PlateSizePercent | `PlateSizePercent` | Integer | `100` | 50–150  % |
| PlateBorderWidthPercent | `PlateBorderWidthPercent` | Integer | `100` | 50–150  % |
| PlateColor | `PlateColor` | Integer | `2434859` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| PlateBorderColor | `PlateBorderColor` | Integer | `10856880` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| PlateBackgroundEnabled | `PlateBackgroundEnabled` | Boolean | `false` | `true` / `false` |
| PlateBackgroundSVG | `PlateBackgroundSVG` | String | `""` | Siehe Modulanleitung |
| PlateBackgroundFit | `PlateBackgroundFit` | String | `"cover"` | `"contain"` Einpassen; `"cover"` Ausfüllen; `"stretch"` Strecken |
| PlateBackgroundSizePercent | `PlateBackgroundSizePercent` | Integer | `100` | 25–200  % |
| PlateBackgroundOffsetXPercent | `PlateBackgroundOffsetXPercent` | Integer | `0` | -100–100  % |
| PlateBackgroundOffsetYPercent | `PlateBackgroundOffsetYPercent` | Integer | `0` | -100–100  % |
| PlateBackgroundOpacityPercent | `PlateBackgroundOpacityPercent` | Integer | `100` | 0–100  % |
| PlateBackgroundRotation | `PlateBackgroundRotation` | Float | `0.0` | -180–180  ° |
| IPSViewUseTileDesign | `IPSViewUseTileDesign` | Boolean | `true` | `true` / `false` |
| IPSViewPointerShape | `IPSViewPointerShape` | String | `"preset"` | `"preset"` Vorlage; `"needle"` Nadel; `"line"` Linie; `"arrow"` Pfeil; `"custom"` Benutzerdefiniert |
| IPSViewPointerWidthPercent | `IPSViewPointerWidthPercent` | Integer | `100` | 50–150  % |
| IPSViewPointerLengthPercent | `IPSViewPointerLengthPercent` | Integer | `100` | 50–150  % |
| IPSViewCustomPointerSVG | `IPSViewCustomPointerSVG` | String | `""` | Siehe Modulanleitung |
| IPSViewCustomPointerPivotMode | `IPSViewCustomPointerPivotMode` | String | `"svg"` | `"svg"` SVG; `"custom"` Benutzerdefiniert |
| IPSViewCustomPointerPivotXPercent | `IPSViewCustomPointerPivotXPercent` | Float | `50.0` | 0–100  % |
| IPSViewCustomPointerPivotYPercent | `IPSViewCustomPointerPivotYPercent` | Float | `100.0` | 0–100  % |
| IPSViewAnchorShape | `IPSViewAnchorShape` | String | `"preset"` | `"preset"` Vorlage; `"circle"` Kreis; `"ring"` Ring; `"custom"` Benutzerdefiniert; `"none"` Keine Nabe |
| IPSViewCustomAnchorSVG | `IPSViewCustomAnchorSVG` | String | `""` | Siehe Modulanleitung |
| IPSViewAnchorSizePercent | `IPSViewAnchorSizePercent` | Integer | `100` | 50–150  % |
| IPSViewAnchorBorderWidthPercent | `IPSViewAnchorBorderWidthPercent` | Integer | `100` | 50–150  % |
| IPSViewGaugeColorMode | `IPSViewGaugeColorMode` | String | `"theme"` | `"theme"` Farben aus dem Theme; `"custom"` Benutzerdefiniert |
| IPSViewPointerColor | `IPSViewPointerColor` | Integer | `5622709` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewAnchorColor | `IPSViewAnchorColor` | Integer | `5622709` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewAnchorBorderColor | `IPSViewAnchorBorderColor` | Integer | `16053751` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewRingColor | `IPSViewRingColor` | Integer | `4540236` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewScaleColor | `IPSViewScaleColor` | Integer | `10987950` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewValueColor | `IPSViewValueColor` | Integer | `16053751` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewTitleColor | `IPSViewTitleColor` | Integer | `10987950` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewProgressColor | `IPSViewProgressColor` | Integer | `5622709` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewRingWidthPercent | `IPSViewRingWidthPercent` | Integer | `100` | 50–150  % |
| IPSViewScaleFontSizePercent | `IPSViewScaleFontSizePercent` | Integer | `100` | 50–150  % |
| IPSViewValueFontSizePercent | `IPSViewValueFontSizePercent` | Integer | `100` | 50–150  % |
| IPSViewTitleFontSizePercent | `IPSViewTitleFontSizePercent` | Integer | `100` | 50–150  % |
| IPSViewMajorSplitCount | `IPSViewMajorSplitCount` | Integer | `0` | 0–24 |
| IPSViewMinorSplitCount | `IPSViewMinorSplitCount` | Integer | `0` | 0–10 |
| IPSViewScaleLabelDistanceOffset | `IPSViewScaleLabelDistanceOffset` | Integer | `0` | -100–100 px |
| IPSViewTitleOffsetXPercent | `IPSViewTitleOffsetXPercent` | Integer | `0` | -100–100 % |
| IPSViewTitleOffsetYPercent | `IPSViewTitleOffsetYPercent` | Integer | `0` | -100–100 % |
| IPSViewValueOffsetXPercent | `IPSViewValueOffsetXPercent` | Integer | `0` | -100–100 % |
| IPSViewValueOffsetYPercent | `IPSViewValueOffsetYPercent` | Integer | `0` | -100–100 % |
| IPSViewPlateDesignMode | `IPSViewPlateDesignMode` | String | `"preset"` | `"preset"` Vorlage; `"custom"` Benutzerdefiniert; `"hidden"` Ausgeblendet |
| IPSViewPlateSizePercent | `IPSViewPlateSizePercent` | Integer | `100` | 50–150  % |
| IPSViewPlateBorderWidthPercent | `IPSViewPlateBorderWidthPercent` | Integer | `100` | 50–150  % |
| IPSViewPlateColor | `IPSViewPlateColor` | Integer | `2434859` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewPlateBorderColor | `IPSViewPlateBorderColor` | Integer | `10856880` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewPlateBackgroundEnabled | `IPSViewPlateBackgroundEnabled` | Boolean | `false` | `true` / `false` |
| IPSViewPlateBackgroundSVG | `IPSViewPlateBackgroundSVG` | String | `""` | Siehe Modulanleitung |
| IPSViewPlateBackgroundFit | `IPSViewPlateBackgroundFit` | String | `"cover"` | `"contain"` Einpassen; `"cover"` Ausfüllen; `"stretch"` Strecken |
| IPSViewPlateBackgroundSizePercent | `IPSViewPlateBackgroundSizePercent` | Integer | `100` | 25–200  % |
| IPSViewPlateBackgroundOffsetXPercent | `IPSViewPlateBackgroundOffsetXPercent` | Integer | `0` | -100–100  % |
| IPSViewPlateBackgroundOffsetYPercent | `IPSViewPlateBackgroundOffsetYPercent` | Integer | `0` | -100–100  % |
| IPSViewPlateBackgroundOpacityPercent | `IPSViewPlateBackgroundOpacityPercent` | Integer | `100` | 0–100  % |
| IPSViewPlateBackgroundRotation | `IPSViewPlateBackgroundRotation` | Float | `0.0` | -180–180  ° |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsGaugeMulti

[Zur Modulanleitung](../EChartsGaugeMulti/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Gauge-Quellen | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Quellenauswahl aktivieren | `EnableSourceSelection` | Boolean | `false` | `true` / `false` |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Gauge-Vorlage | `GaugePreset` | String | `"multi-title"` | `"multi-title"` Multi Title; `"ring-grid"` Ringraster; `"ring-concentric"` Konzentrische Ringe; `"weather-station"` Wetterstation |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Größe des Diagrammtitels | `ChartTitleFontSizePercent` | Integer | `100` | 50–150 % |
| Farbe des Diagrammtitels | `ChartTitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Ringstärke | `RingWidthPercent` | Integer | `100` | 50–150 % |
| Schriftgröße der Skala | `ScaleFontSizePercent` | Integer | `100` | 50–150 % |
| Schriftgröße des Werts | `ValueFontSizePercent` | Integer | `100` | 50–150 % |
| Schriftgröße des Titels | `TitleFontSizePercent` | Integer | `100` | 50–150 % |
| Zeigerstärke | `PointerWidthPercent` | Integer | `100` | 50–150 % |
| Zeigerlänge | `PointerLengthPercent` | Integer | `100` | 50–150 % |
| Nabengröße | `AnchorSizePercent` | Integer | `100` | 50–150 % |
| Nabenrandstärke | `AnchorBorderWidthPercent` | Integer | `100` | 50–150 % |
| Plattengröße | `PlateSizePercent` | Integer | `100` | 50–150 % |
| Plattenrandstärke | `PlateBorderWidthPercent` | Integer | `100` | 50–150 % |
| Zeigerform | `PointerShape` | String | `"preset"` | `"preset"` Vorlage; `"needle"` Nadel; `"line"` Linie; `"arrow"` Pfeil; `"custom"` Eigenes SVG |
| Nabenform | `AnchorShape` | String | `"preset"` | `"preset"` Vorlage; `"circle"` Kreis; `"ring"` Ring; `"none"` Ausgeblendet |
| Farbvergabe | `GaugeColorMode` | String | `"theme"` | `"theme"` Theme / Vorlage; `"custom"` Benutzerdefiniert |
| Plattenmodus | `PlateDesignMode` | String | `"preset"` | `"preset"` Vorlage; `"custom"` Benutzerdefiniert; `"hidden"` Ausgeblendet |
| Zusätzlicher Skalenbeschriftungsabstand | `ScaleLabelDistanceOffset` | Integer | `0` | -100–100 px |
| Titelverschiebung X | `TitleOffsetXPercent` | Integer | `0` | -100–100 % |
| Titelverschiebung Y | `TitleOffsetYPercent` | Integer | `0` | -100–100 % |
| Wertverschiebung X | `ValueOffsetXPercent` | Integer | `0` | -100–100 % |
| Wertverschiebung Y | `ValueOffsetYPercent` | Integer | `0` | -100–100 % |
| Hauptunterteilungen | `MajorSplitCount` | Integer | `0` | 0–24 |
| Nebenunterteilungen | `MinorSplitCount` | Integer | `0` | 0–10 |
| Zeigerfarbe | `PointerColor` | Integer | `0x55CBB5` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Fortschrittsfarbe | `ProgressColor` | Integer | `0x55CBB5` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Nabenfarbe | `AnchorColor` | Integer | `0x55CBB5` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Nabenrandfarbe | `AnchorBorderColor` | Integer | `0xF4F5F7` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Ringfarbe | `RingColor` | Integer | `0x45474C` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Skalenfarbe | `ScaleColor` | Integer | `0xA7A9AE` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Wertfarbe | `ValueColor` | Integer | `0xF4F5F7` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Titelfarbe | `TitleColor` | Integer | `0xA7A9AE` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Plattenfarbe | `PlateColor` | Integer | `0x25272B` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Plattenrandfarbe | `PlateBorderColor` | Integer | `0xA5A9B0` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Eigenes Zeiger-SVG | `CustomPointerSVG` | String | `""` | Siehe Modulanleitung |
| Zeiger-Ankerpunkt | `CustomPointerPivotMode` | String | `"svg"` | `"svg"` SVG oder unten mittig; `"custom"` Benutzerdefiniert |
| SVG-Hintergrund der Platte | `PlateBackgroundSVG` | String | `""` | Siehe Modulanleitung |
| Bildanpassung | `PlateBackgroundFit` | String | `"cover"` | `"contain"` Einpassen; `"cover"` Ausfüllen; `"stretch"` Strecken |
| Ankerpunkt X | `CustomPointerPivotXPercent` | Float | `50.0` | 0–100 % |
| Ankerpunkt Y | `CustomPointerPivotYPercent` | Float | `100.0` | 0–100 % |
| Bilddrehung | `PlateBackgroundRotation` | Float | `0.0` | -180–180 ° |
| SVG-Hintergrund für Platte verwenden | `PlateBackgroundEnabled` | Boolean | `false` | `true` / `false` |
| Gauge animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Bildgröße | `PlateBackgroundSizePercent` | Integer | `100` | 25–200 % |
| Bildversatz X | `PlateBackgroundOffsetXPercent` | Integer | `0` | -100–100 % |
| Bildversatz Y | `PlateBackgroundOffsetYPercent` | Integer | `0` | -100–100 % |
| Bilddeckkraft | `PlateBackgroundOpacityPercent` | Integer | `100` | 0–100 % |
| Startanimation | `AnimationDuration` | Integer | `500` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Anfangs sichtbar | `InitiallyVisible` | Boolean | `true` | `true` / `false` |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Beschriftung | `Label` | String | `""` | Siehe Modulanleitung |
| Variablendarstellung | `UseVariablePresentation` | Boolean | `false` | `true` / `false` |
| Minimum | `Minimum` | Float | `0.0` | Siehe Modulanleitung |
| Maximum | `Maximum` | Float | `100.0` | Siehe Modulanleitung |
| Einheit | `Unit` | String | `""` | Siehe Modulanleitung |
| Nachkommastellen | `Decimals` | Integer | `1` | 0–6 |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsGaugeSingle

[Zur Modulanleitung](../EChartsGaugeSingle/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Quellvariable | `SourceVariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Bereich, Einheit und Nachkommastellen aus der Darstellung der Quellvariable übernehmen | `UseVariablePresentation` | Boolean | `false` | `true` / `false` |
| Minimum | `Minimum` | Float | `0.0` | Siehe Modulanleitung |
| Maximum | `Maximum` | Float | `100.0` | Siehe Modulanleitung |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Titelposition | `TitlePosition` | String | `"bottom"` | `"top"` Oben; `"bottom"` Unten |
| Einheit | `Unit` | String | `""` | Siehe Modulanleitung |
| Nachkommastellen | `Decimals` | Integer | `1` | 0–6 |
| Gauge-Vorlage | `GaugePreset` | String | `"simple"` | `"basic"` Basic Gauge; `"simple"` Simple Gauge; `"progress"` Progress Gauge; `"speed"` Speed Gauge |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Gauge animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `500` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |
| Zeigerform | `PointerShape` | String | `"preset"` | `"preset"` Vorlage; `"needle"` Nadel; `"line"` Linie; `"arrow"` Pfeil; `"custom"` Eigenes SVG |
| Eigenes Zeiger-SVG | `CustomPointerSVG` | String | `""` | Siehe Modulanleitung |
| Zeigerdrehpunkt | `CustomPointerPivotMode` | String | `"svg"` | `"svg"` SVG oder unten mittig; `"custom"` Eigene Prozentwerte |
| Drehpunkt horizontal | `CustomPointerPivotXPercent` | Float | `50.0` | 0–100 % |
| Drehpunkt vertikal | `CustomPointerPivotYPercent` | Float | `100.0` | 0–100 % |
| Nabenform | `AnchorShape` | String | `"preset"` | `"preset"` Vorlage; `"circle"` Kreis; `"ring"` Ring; `"custom"` Eigenes SVG; `"hidden"` Ausgeblendet |
| Eigenes Naben-SVG | `CustomAnchorSVG` | String | `""` | Siehe Modulanleitung |
| Farbquelle der Nabe | `AnchorColorMode` | String | `"theme"` | `"theme"` Gauge- oder Theme-Farben; `"custom"` Eigene Nabenfarben |
| Nabenfüllung | `AnchorColor` | Integer | `0x55CBB5` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Nabenrand | `AnchorBorderColor` | Integer | `0xF4F5F7` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Plattenform | `PlateShape` | String | `"hidden"` | `"hidden"` Ausgeblendet; `"circle"` Kreis; `"arc"` Dem Skalenbogen folgen |
| Farbquelle der Platte | `PlateColorMode` | String | `"theme"` | `"theme"` ECharts-Theme; `"custom"` Eigene Plattenfarben |
| Füllstil der Platte | `PlateFillMode` | String | `"solid"` | `"solid"` Vollfarbe; `"linear"` Linearer Farbverlauf; `"radial"` Radialer Farbverlauf |
| Mittlere Verlaufsfarbe verwenden | `PlateGradientMiddleEnabled` | Boolean | `false` | `true` / `false` |
| Verlaufsmitte | `PlateGradientMiddleColor` | Integer | `0x45474C` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Verlaufsende | `PlateGradientEndColor` | Integer | `0x111317` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Richtung des linearen Verlaufs | `PlateGradientDirection` | String | `"top-bottom"` | `"top-bottom"` Oben nach unten; `"left-right"` Links nach rechts; `"diagonal-down"` Links oben nach rechts unten; `"diagonal-up"` Links unten nach rechts oben |
| Radialer Mittelpunkt horizontal | `PlateGradientCenterXPercent` | Integer | `50` | 0–100 % |
| Radialer Mittelpunkt vertikal | `PlateGradientCenterYPercent` | Integer | `50` | 0–100 % |
| Radius des radialen Verlaufs | `PlateGradientRadiusPercent` | Integer | `75` | 25–150 % |
| SVG-Hintergrund der Platte verwenden | `PlateBackgroundEnabled` | Boolean | `false` | `true` / `false` |
| SVG-Hintergrund der Platte | `PlateBackgroundSVG` | String | `""` | Siehe Modulanleitung |
| SVG-Anpassung | `PlateBackgroundFit` | String | `"cover"` | `"contain"` Einpassen; `"cover"` Ausfüllen; `"stretch"` Strecken |
| SVG-Größe | `PlateBackgroundSizePercent` | Integer | `100` | 25–200 % |
| Horizontaler SVG-Versatz | `PlateBackgroundOffsetXPercent` | Integer | `0` | -100–100 % |
| Vertikaler SVG-Versatz | `PlateBackgroundOffsetYPercent` | Integer | `0` | -100–100 % |
| SVG-Deckkraft | `PlateBackgroundOpacityPercent` | Integer | `100` | 0–100 % |
| SVG-Drehung | `PlateBackgroundRotation` | Float | `0.0` | -180–180 ° |
| Transparente Plattenfüllung | `PlateTransparent` | Boolean | `false` | `true` / `false` |
| Plattenschatten | `PlateShadow` | Boolean | `false` | `true` / `false` |
| Plattenfüllung / Verlaufsanfang | `PlateColor` | Integer | `0x25272B` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Plattenrand | `PlateBorderColor` | Integer | `0xA5A9B0` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Skalenbogen | `GaugeArcMode` | String | `"preset"` | `"preset"` Vorlage; `"full"` Vollkreis; `"three-quarter"` Dreiviertelkreis; `"half"` Halbkreis; `"quarter"` Viertelkreis; `"custom"` Eigener Start und eigenes Ende |
| Startposition | `GaugeStartPosition` | Float | `270.0` | `0` 0° (oben); `22.5` 22.5°; `45` 45°; `67.5` 67.5°; `90` 90° (rechts); `112.5` 112.5°; `135` 135°; `157.5` 157.5°; `180` 180° (unten); `202.5` 202.5°; `225` 225°; `247.5` 247.5°; `270` 270° (links); `292.5` 292.5°; `315` 315°; `337.5` 337.5° |
| Endposition (nur benutzerdefiniert) | `GaugeEndPosition` | Float | `90.0` | `0` 0° (oben); `22.5` 22.5°; `45` 45°; `67.5` 67.5°; `90` 90° (rechts); `112.5` 112.5°; `135` 135°; `157.5` 157.5°; `180` 180° (unten); `202.5` 202.5°; `225` 225°; `247.5` 247.5°; `270` 270° (links); `292.5` 292.5°; `315` 315°; `337.5` 337.5° |
| Farbquelle | `GaugeColorMode` | String | `"theme"` | `"theme"` ECharts-Theme; `"custom"` Eigene Gauge-Farben |
| Zeiger | `PointerColor` | Integer | `0x55CBB5` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Fortschritt | `ProgressColor` | Integer | `0x55CBB5` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Ring | `RingColor` | Integer | `0x45474C` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Skala und Teilstriche | `ScaleColor` | Integer | `0xA7A9AE` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Wert und Einheit | `ValueColor` | Integer | `0xF4F5F7` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Titel | `TitleColor` | Integer | `0xA7A9AE` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbige Wertebereiche verwenden | `ScaleZonesEnabled` | Boolean | `false` | `true` / `false` |
| Wertebereiche | `ScaleZones` | String | `"[]"` | Siehe Modulanleitung |
| Hauptabschnitte (0 = Vorlage) | `MajorSplitCount` | Integer | `0` | 0–24 |
| Nebenabschnitte (0 = Vorlage) | `MinorSplitCount` | Integer | `0` | 0–10 |
| Ausrichtung der Skalenwerte | `ScaleLabelRotation` | String | `"horizontal"` | `"horizontal"` Horizontal; `"tangential"` Tangential; `"radial"` Radial |
| Richtung | `GaugeDirection` | String | `"clockwise"` | `"clockwise"` Im Uhrzeigersinn; `"counterclockwise"` Gegen den Uhrzeigersinn |
| Horizontaler Gauge-Versatz | `GaugeOffsetXPercent` | Integer | `0` | -50–50 % |
| Vertikaler Gauge-Versatz | `GaugeOffsetYPercent` | Integer | `0` | -50–50 % |
| Horizontaler Wertversatz | `ValueOffsetXPercent` | Integer | `0` | -100–100 % |
| Vertikaler Wertversatz | `ValueOffsetYPercent` | Integer | `0` | -100–100 % |
| Horizontaler Titelversatz | `TitleOffsetXPercent` | Integer | `0` | -100–100 % |
| Vertikaler Titelversatz | `TitleOffsetYPercent` | Integer | `0` | -100–100 % |
| Zeiger | `PointerVisibility` | String | `"preset"` | `"preset"` Vorlage; `"show"` Anzeigen; `"hide"` Ausblenden |
| Fortschritt | `ProgressVisibility` | String | `"preset"` | `"preset"` Vorlage; `"show"` Anzeigen; `"hide"` Ausblenden |
| Skalenring | `RingVisibility` | String | `"preset"` | `"preset"` Vorlage; `"show"` Anzeigen; `"hide"` Ausblenden |
| Nebenstriche | `MinorTicksVisibility` | String | `"preset"` | `"preset"` Vorlage; `"show"` Anzeigen; `"hide"` Ausblenden |
| Hauptteiler | `MajorTicksVisibility` | String | `"preset"` | `"preset"` Vorlage; `"show"` Anzeigen; `"hide"` Ausblenden |
| Skalenwerte | `ScaleLabelsVisibility` | String | `"preset"` | `"preset"` Vorlage; `"show"` Anzeigen; `"hide"` Ausblenden |
| Wert | `ValueVisibility` | String | `"preset"` | `"preset"` Vorlage; `"show"` Anzeigen; `"hide"` Ausblenden |
| Einheit | `UnitVisibility` | String | `"preset"` | `"preset"` Vorlage; `"show"` Anzeigen; `"hide"` Ausblenden |
| Titel | `TitleVisibility` | String | `"preset"` | `"preset"` Vorlage; `"show"` Anzeigen; `"hide"` Ausblenden |
| Wertekasten | `DetailBoxVisibility` | String | `"preset"` | `"preset"` Vorlage; `"show"` Anzeigen; `"hide"` Ausblenden |
| Farben des Wertekastens | `DetailColorMode` | String | `"theme"` | `"theme"` ECharts-Theme; `"custom"` Eigene Farben |
| Füllung des Wertekastens | `DetailBackgroundColor` | Integer | `0x25272B` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rand des Wertekastens | `DetailBorderColor` | Integer | `0xA5A9B0` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Schatten des Wertekastens | `DetailShadow` | Boolean | `false` | `true` / `false` |
| Leuchten des Zeigers | `PointerShadow` | Boolean | `false` | `true` / `false` |
| Leuchten des Fortschritts | `ProgressShadow` | Boolean | `false` | `true` / `false` |
| Schatten des Skalenrings | `RingShadow` | Boolean | `false` | `true` / `false` |
| Schatten der Nabe | `AnchorShadow` | Boolean | `false` | `true` / `false` |
| Schriftgröße der Skala | `ScaleFontSizePercent` | Integer | `100` | 50–150 % |
| Schriftgröße des Werts | `ValueFontSizePercent` | Integer | `100` | 50–150 % |
| Schriftgröße der Einheit | `UnitFontSizePercent` | Integer | `100` | 50–150 % |
| Schriftgröße des Titels | `TitleFontSizePercent` | Integer | `100` | 50–150 % |
| Ringstärke | `RingWidthPercent` | Integer | `100` | 50–150 % |
| Zeigerstärke | `PointerWidthPercent` | Integer | `100` | 50–150 % |
| Zeigerlänge | `PointerLengthPercent` | Integer | `100` | 50–150 % |
| Nabengröße | `AnchorSizePercent` | Integer | `100` | 50–150 % |
| Nabenrandstärke | `AnchorBorderWidthPercent` | Integer | `100` | 50–150 % |
| Plattengröße | `PlateSizePercent` | Integer | `100` | 50–150 % |
| Plattenrandstärke | `PlateBorderWidthPercent` | Integer | `100` | 50–150 % |
| Länge der Nebenstriche | `MinorTickLengthPercent` | Integer | `100` | 50–150 % |
| Länge der Hauptteiler | `MajorTickLengthPercent` | Integer | `100` | 50–150 % |
| Fortschrittsstärke | `ProgressWidthPercent` | Integer | `100` | 50–150 % |
| Abstand der Nebenstriche | `MinorTickDistancePercent` | Integer | `100` | 50–150 % |
| Abstand der Hauptteiler | `MajorTickDistancePercent` | Integer | `100` | 50–150 % |
| Abstand der Skalenwerte | `ScaleLabelDistancePercent` | Integer | `100` | 50–150 % |
| Gauge-Radius | `GaugeRadiusPercent` | Integer | `100` | 50–150 % |
| Randstärke des Wertekastens | `DetailBorderWidthPercent` | Integer | `100` | 50–150 % |
| Eckenradius des Wertekastens | `DetailCornerRadiusPercent` | Integer | `100` | 50–150 % |

### Zeilenfelder von `ScaleZones`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Endwert | `EndValue` | Float | `100.0` | Siehe Modulanleitung |
| Farbe | `Color` | Integer | `16711680` | `0xRRGGBB`; Automatik nach Feldvorgabe |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsGaugeTacho

[Zur Modulanleitung](../EChartsGaugeTacho/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Gauge-Quellen | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Feste Instrumentenvorlage | `GaugePreset` | String | `"tacho"` | Familienvorgabe; nicht im Formular änderbar |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Größe des Diagrammtitels | `ChartTitleFontSizePercent` | Integer | `100` | 50–150 % |
| Farbe des Diagrammtitels | `ChartTitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Ringstärke | `RingWidthPercent` | Integer | `100` | 50–150  % |
| Skalenschrift | `ScaleFontSizePercent` | Integer | `100` | 50–150  % |
| Wertschrift | `ValueFontSizePercent` | Integer | `100` | 50–150  % |
| Titelschrift | `TitleFontSizePercent` | Integer | `100` | 50–150  % |
| Zeigerstärke | `PointerWidthPercent` | Integer | `100` | 50–150  % |
| Zeigerlänge | `PointerLengthPercent` | Integer | `100` | 50–150  % |
| Nabengröße | `AnchorSizePercent` | Integer | `100` | 50–150  % |
| Nabenrand | `AnchorBorderWidthPercent` | Integer | `100` | 50–150  % |
| Plattengröße | `PlateSizePercent` | Integer | `100` | 50–150  % |
| Plattenrand | `PlateBorderWidthPercent` | Integer | `100` | 50–150  % |
| Zeigerform | `PointerShape` | String | `"preset"` | `"preset"` Vorlage; `"needle"` Nadel; `"line"` Linie; `"arrow"` Pfeil; `"custom"` Benutzerdefiniert |
| Nabendesign | `AnchorShape` | String | `"preset"` | `"preset"` Vorlage; `"circle"` Kreis; `"ring"` Ring; `"custom"` Benutzerdefiniert; `"none"` Keine Nabe |
| Farben | `GaugeColorMode` | String | `"theme"` | `"theme"` Farben aus dem Theme; `"custom"` Benutzerdefiniert |
| Plattendesign | `PlateDesignMode` | String | `"preset"` | `"preset"` Vorlage; `"custom"` Benutzerdefiniert; `"hidden"` Ausgeblendet |
| Zusätzlicher Skalenbeschriftungsabstand | `ScaleLabelDistanceOffset` | Integer | `0` | -100–100 px |
| Titelverschiebung X | `TitleOffsetXPercent` | Integer | `0` | -100–100 % |
| Titelverschiebung Y | `TitleOffsetYPercent` | Integer | `0` | -100–100 % |
| Wertverschiebung X | `ValueOffsetXPercent` | Integer | `0` | -100–100 % |
| Wertverschiebung Y | `ValueOffsetYPercent` | Integer | `0` | -100–100 % |
| Hauptunterteilungen | `MajorSplitCount` | Integer | `0` | 0–24 |
| Nebenunterteilungen | `MinorSplitCount` | Integer | `0` | 0–10 |
| Zeigerfarbe | `PointerColor` | Integer | `0x55CBB5` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Fortschrittsfarbe | `ProgressColor` | Integer | `0x55CBB5` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Nabenfarbe | `AnchorColor` | Integer | `0x55CBB5` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Nabenrandfarbe | `AnchorBorderColor` | Integer | `0xF4F5F7` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Ringfarbe | `RingColor` | Integer | `0x45474C` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Skalenfarbe | `ScaleColor` | Integer | `0xA7A9AE` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Wertfarbe | `ValueColor` | Integer | `0xF4F5F7` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Titelfarbe | `TitleColor` | Integer | `0xA7A9AE` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Plattenfarbe | `PlateColor` | Integer | `0x25272B` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Plattenrandfarbe | `PlateBorderColor` | Integer | `0xA5A9B0` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Eigenes Zeiger-SVG | `CustomPointerSVG` | String | `""` | Siehe Modulanleitung |
| SVG-Ankerpunkt | `CustomPointerPivotMode` | String | `"svg"` | `"svg"` SVG; `"custom"` Benutzerdefiniert |
| Eigenes Naben-SVG | `CustomAnchorSVG` | String | `""` | Siehe Modulanleitung |
| Zifferblatt-Hintergrund als SVG | `PlateBackgroundSVG` | String | `""` | Siehe Modulanleitung |
| Bildanpassung | `PlateBackgroundFit` | String | `"cover"` | `"contain"` Einpassen; `"cover"` Ausfüllen; `"stretch"` Strecken |
| Ankerpunkt X | `CustomPointerPivotXPercent` | Float | `50.0` | 0–100  % |
| Ankerpunkt Y | `CustomPointerPivotYPercent` | Float | `100.0` | 0–100  % |
| Drehung | `PlateBackgroundRotation` | Float | `0.0` | -180–180  ° |
| SVG-Zifferblatthintergrund verwenden | `PlateBackgroundEnabled` | Boolean | `false` | `true` / `false` |
| Gauge animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Größe | `PlateBackgroundSizePercent` | Integer | `100` | 25–200  % |
| Versatz X | `PlateBackgroundOffsetXPercent` | Integer | `0` | -100–100  % |
| Versatz Y | `PlateBackgroundOffsetYPercent` | Integer | `0` | -100–100  % |
| Deckkraft | `PlateBackgroundOpacityPercent` | Integer | `100` | 0–100  % |
| Startanimation | `AnimationDuration` | Integer | `500` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |
| IPSViewGaugePreset | `IPSViewGaugePreset` | String | `"tacho"` | Siehe Modulanleitung |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Beschriftung | `Label` | String | `""` | Siehe Modulanleitung |
| Variablendarstellung | `UseVariablePresentation` | Boolean | `false` | `true` / `false` |
| Minimum | `Minimum` | Float | `0.0` | Siehe Modulanleitung |
| Maximum | `Maximum` | Float | `100.0` | Siehe Modulanleitung |
| Einheit | `Unit` | String | `""` | Siehe Modulanleitung |
| Nachkommastellen | `Decimals` | Integer | `1` | 0–6 |
| Individuelles Design | `UseIndividualDesign` | Boolean | `false` | `true` / `false` |
| PointerShape | `PointerShape` | String | `"preset"` | `"preset"` Vorlage; `"needle"` Nadel; `"line"` Linie; `"arrow"` Pfeil; `"custom"` Benutzerdefiniert |
| PointerWidthPercent | `PointerWidthPercent` | Integer | `100` | 50–150  % |
| PointerLengthPercent | `PointerLengthPercent` | Integer | `100` | 50–150  % |
| CustomPointerSVG | `CustomPointerSVG` | String | `""` | Siehe Modulanleitung |
| CustomPointerPivotMode | `CustomPointerPivotMode` | String | `"svg"` | `"svg"` SVG; `"custom"` Benutzerdefiniert |
| CustomPointerPivotXPercent | `CustomPointerPivotXPercent` | Float | `50.0` | 0–100  % |
| CustomPointerPivotYPercent | `CustomPointerPivotYPercent` | Float | `100.0` | 0–100  % |
| AnchorShape | `AnchorShape` | String | `"preset"` | `"preset"` Vorlage; `"circle"` Kreis; `"ring"` Ring; `"custom"` Benutzerdefiniert; `"none"` Keine Nabe |
| CustomAnchorSVG | `CustomAnchorSVG` | String | `""` | Siehe Modulanleitung |
| AnchorSizePercent | `AnchorSizePercent` | Integer | `100` | 50–150  % |
| AnchorBorderWidthPercent | `AnchorBorderWidthPercent` | Integer | `100` | 50–150  % |
| GaugeColorMode | `GaugeColorMode` | String | `"theme"` | `"theme"` Farben aus dem Theme; `"custom"` Benutzerdefiniert |
| Zeigerfarbe | `PointerColor` | Integer | `5622709` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Nabenfarbe | `AnchorColor` | Integer | `5622709` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Nabenrandfarbe | `AnchorBorderColor` | Integer | `16053751` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Ringfarbe | `RingColor` | Integer | `4540236` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Skalenfarbe | `ScaleColor` | Integer | `10987950` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Wertfarbe | `ValueColor` | Integer | `16053751` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Titelfarbe | `TitleColor` | Integer | `10987950` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Fortschrittsfarbe | `ProgressColor` | Integer | `5622709` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| RingWidthPercent | `RingWidthPercent` | Integer | `100` | 50–150  % |
| ScaleFontSizePercent | `ScaleFontSizePercent` | Integer | `100` | 50–150  % |
| ValueFontSizePercent | `ValueFontSizePercent` | Integer | `100` | 50–150  % |
| TitleFontSizePercent | `TitleFontSizePercent` | Integer | `100` | 50–150  % |
| MajorSplitCount | `MajorSplitCount` | Integer | `0` | 0–24 |
| MinorSplitCount | `MinorSplitCount` | Integer | `0` | 0–10 |
| ScaleLabelDistanceOffset | `ScaleLabelDistanceOffset` | Integer | `0` | -100–100 px |
| TitleOffsetXPercent | `TitleOffsetXPercent` | Integer | `0` | -100–100 % |
| TitleOffsetYPercent | `TitleOffsetYPercent` | Integer | `0` | -100–100 % |
| ValueOffsetXPercent | `ValueOffsetXPercent` | Integer | `0` | -100–100 % |
| ValueOffsetYPercent | `ValueOffsetYPercent` | Integer | `0` | -100–100 % |
| PlateDesignMode | `PlateDesignMode` | String | `"preset"` | `"preset"` Vorlage; `"custom"` Benutzerdefiniert; `"hidden"` Ausgeblendet |
| PlateSizePercent | `PlateSizePercent` | Integer | `100` | 50–150  % |
| PlateBorderWidthPercent | `PlateBorderWidthPercent` | Integer | `100` | 50–150  % |
| PlateColor | `PlateColor` | Integer | `2434859` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| PlateBorderColor | `PlateBorderColor` | Integer | `10856880` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| PlateBackgroundEnabled | `PlateBackgroundEnabled` | Boolean | `false` | `true` / `false` |
| PlateBackgroundSVG | `PlateBackgroundSVG` | String | `""` | Siehe Modulanleitung |
| PlateBackgroundFit | `PlateBackgroundFit` | String | `"cover"` | `"contain"` Einpassen; `"cover"` Ausfüllen; `"stretch"` Strecken |
| PlateBackgroundSizePercent | `PlateBackgroundSizePercent` | Integer | `100` | 25–200  % |
| PlateBackgroundOffsetXPercent | `PlateBackgroundOffsetXPercent` | Integer | `0` | -100–100  % |
| PlateBackgroundOffsetYPercent | `PlateBackgroundOffsetYPercent` | Integer | `0` | -100–100  % |
| PlateBackgroundOpacityPercent | `PlateBackgroundOpacityPercent` | Integer | `100` | 0–100  % |
| PlateBackgroundRotation | `PlateBackgroundRotation` | Float | `0.0` | -180–180  ° |
| IPSViewUseTileDesign | `IPSViewUseTileDesign` | Boolean | `true` | `true` / `false` |
| IPSViewPointerShape | `IPSViewPointerShape` | String | `"preset"` | `"preset"` Vorlage; `"needle"` Nadel; `"line"` Linie; `"arrow"` Pfeil; `"custom"` Benutzerdefiniert |
| IPSViewPointerWidthPercent | `IPSViewPointerWidthPercent` | Integer | `100` | 50–150  % |
| IPSViewPointerLengthPercent | `IPSViewPointerLengthPercent` | Integer | `100` | 50–150  % |
| IPSViewCustomPointerSVG | `IPSViewCustomPointerSVG` | String | `""` | Siehe Modulanleitung |
| IPSViewCustomPointerPivotMode | `IPSViewCustomPointerPivotMode` | String | `"svg"` | `"svg"` SVG; `"custom"` Benutzerdefiniert |
| IPSViewCustomPointerPivotXPercent | `IPSViewCustomPointerPivotXPercent` | Float | `50.0` | 0–100  % |
| IPSViewCustomPointerPivotYPercent | `IPSViewCustomPointerPivotYPercent` | Float | `100.0` | 0–100  % |
| IPSViewAnchorShape | `IPSViewAnchorShape` | String | `"preset"` | `"preset"` Vorlage; `"circle"` Kreis; `"ring"` Ring; `"custom"` Benutzerdefiniert; `"none"` Keine Nabe |
| IPSViewCustomAnchorSVG | `IPSViewCustomAnchorSVG` | String | `""` | Siehe Modulanleitung |
| IPSViewAnchorSizePercent | `IPSViewAnchorSizePercent` | Integer | `100` | 50–150  % |
| IPSViewAnchorBorderWidthPercent | `IPSViewAnchorBorderWidthPercent` | Integer | `100` | 50–150  % |
| IPSViewGaugeColorMode | `IPSViewGaugeColorMode` | String | `"theme"` | `"theme"` Farben aus dem Theme; `"custom"` Benutzerdefiniert |
| IPSViewPointerColor | `IPSViewPointerColor` | Integer | `5622709` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewAnchorColor | `IPSViewAnchorColor` | Integer | `5622709` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewAnchorBorderColor | `IPSViewAnchorBorderColor` | Integer | `16053751` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewRingColor | `IPSViewRingColor` | Integer | `4540236` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewScaleColor | `IPSViewScaleColor` | Integer | `10987950` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewValueColor | `IPSViewValueColor` | Integer | `16053751` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewTitleColor | `IPSViewTitleColor` | Integer | `10987950` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewProgressColor | `IPSViewProgressColor` | Integer | `5622709` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewRingWidthPercent | `IPSViewRingWidthPercent` | Integer | `100` | 50–150  % |
| IPSViewScaleFontSizePercent | `IPSViewScaleFontSizePercent` | Integer | `100` | 50–150  % |
| IPSViewValueFontSizePercent | `IPSViewValueFontSizePercent` | Integer | `100` | 50–150  % |
| IPSViewTitleFontSizePercent | `IPSViewTitleFontSizePercent` | Integer | `100` | 50–150  % |
| IPSViewMajorSplitCount | `IPSViewMajorSplitCount` | Integer | `0` | 0–24 |
| IPSViewMinorSplitCount | `IPSViewMinorSplitCount` | Integer | `0` | 0–10 |
| IPSViewScaleLabelDistanceOffset | `IPSViewScaleLabelDistanceOffset` | Integer | `0` | -100–100 px |
| IPSViewTitleOffsetXPercent | `IPSViewTitleOffsetXPercent` | Integer | `0` | -100–100 % |
| IPSViewTitleOffsetYPercent | `IPSViewTitleOffsetYPercent` | Integer | `0` | -100–100 % |
| IPSViewValueOffsetXPercent | `IPSViewValueOffsetXPercent` | Integer | `0` | -100–100 % |
| IPSViewValueOffsetYPercent | `IPSViewValueOffsetYPercent` | Integer | `0` | -100–100 % |
| IPSViewPlateDesignMode | `IPSViewPlateDesignMode` | String | `"preset"` | `"preset"` Vorlage; `"custom"` Benutzerdefiniert; `"hidden"` Ausgeblendet |
| IPSViewPlateSizePercent | `IPSViewPlateSizePercent` | Integer | `100` | 50–150  % |
| IPSViewPlateBorderWidthPercent | `IPSViewPlateBorderWidthPercent` | Integer | `100` | 50–150  % |
| IPSViewPlateColor | `IPSViewPlateColor` | Integer | `2434859` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewPlateBorderColor | `IPSViewPlateBorderColor` | Integer | `10856880` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| IPSViewPlateBackgroundEnabled | `IPSViewPlateBackgroundEnabled` | Boolean | `false` | `true` / `false` |
| IPSViewPlateBackgroundSVG | `IPSViewPlateBackgroundSVG` | String | `""` | Siehe Modulanleitung |
| IPSViewPlateBackgroundFit | `IPSViewPlateBackgroundFit` | String | `"cover"` | `"contain"` Einpassen; `"cover"` Ausfüllen; `"stretch"` Strecken |
| IPSViewPlateBackgroundSizePercent | `IPSViewPlateBackgroundSizePercent` | Integer | `100` | 25–200  % |
| IPSViewPlateBackgroundOffsetXPercent | `IPSViewPlateBackgroundOffsetXPercent` | Integer | `0` | -100–100  % |
| IPSViewPlateBackgroundOffsetYPercent | `IPSViewPlateBackgroundOffsetYPercent` | Integer | `0` | -100–100  % |
| IPSViewPlateBackgroundOpacityPercent | `IPSViewPlateBackgroundOpacityPercent` | Integer | `100` | 0–100  % |
| IPSViewPlateBackgroundRotation | `IPSViewPlateBackgroundRotation` | Float | `0.0` | -180–180  ° |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsGraph

[Zur Modulanleitung](../EChartsGraph/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Graph-Knoten | `Nodes` | String | `"[]"` | Siehe Modulanleitung |
| Verbindungen | `Edges` | String | `"[]"` | Siehe Modulanleitung |
| Gerichtete Verbindungen | `DirectedEdges` | Boolean | `false` | `true` / `false` |
| Minimaler Gewichtsbezug | `WeightMinimum` | Float | `0.0` | ab 0 |
| Maximaler Gewichtsbezug | `WeightMaximum` | Float | `100.0` | ab 0 |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Graph-Layout | `GraphLayout` | String | `"force"` | `"force"` Kräftebasiert; `"circular"` Kreisförmig |
| Knotengröße | `NodeSize` | Integer | `16` | 6–40 px |
| Verschieben und zoomen | `RoamEnabled` | Boolean | `true` | `true` / `false` |
| Beschriftungen anzeigen | `ShowLabels` | Boolean | `true` | `true` / `false` |
| Variablenwerte anzeigen | `ShowValues` | Boolean | `false` | `true` / `false` |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Beschriftungsgröße | `LabelFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Beschriftungsfarbe | `LabelColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Knotenfarbe | `NodeColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Verbindungsfarbe | `EdgeColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Minimale gewichtete Linienbreite | `MinimumEdgeWidth` | Integer | `1` | 1–20 px |
| Maximale gewichtete Linienbreite | `MaximumEdgeWidth` | Integer | `8` | 1–20 px |
| Graph animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Nodes`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Symcon-Objekt | `ObjectID` | Integer | `0` | Vorhandene ID |
| Beschriftung (optional) | `Label` | String | `""` | Siehe Modulanleitung |

### Zeilenfelder von `Edges`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Von | `SourceObjectID` | Integer | `0` | Vorhandene ID |
| Nach | `TargetObjectID` | Integer | `0` | Vorhandene ID |
| Gewichtsquelle | `WeightMode` | String | `"none"` | `"none"` Ungewichtet; `"manual"` Festes Gewicht; `"variable"` Aktueller Variablenwert |
| Gewicht | `Weight` | Float | `1.0` | Siehe Modulanleitung |
| WeightVariableID | `WeightVariableID` | Integer | `0` | Siehe Modulanleitung |
| WeightUnit | `WeightUnit` | String | `""` | Siehe Modulanleitung |
| WeightDecimals | `WeightDecimals` | Integer | `2` | Siehe Modulanleitung |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsGraphZ2M

[Zur Modulanleitung](../EChartsGraphZ2M/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Zigbee2MQTT-Netzwerkkarteninstanz | `NetworkMapInstanceID` | Integer | `0` | Vorhandene ID |
| Mindest-LQI | `MinimumLQI` | Integer | `0` | 0–255 |
| Verbindungsauswahl | `LinkSelection` | String | `"strongest"` | `"strongest"` Stärkste Verbindungen zuerst; `"weakest"` Schwächste Verbindungen zuerst; `"range"` LQI-Bereich |
| Höchst-LQI | `MaximumLQI` | Integer | `255` | 0–255 |
| Maximal angezeigte Verbindungen | `MaximumLinks` | Integer | `300` | 1–2000 |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Graph-Layout | `GraphLayout` | String | `"force"` | `"force"` Kräftebasiert; `"circular"` Kreisförmig |
| Knotengröße | `NodeSize` | Integer | `16` | 6–40 px |
| Verschieben und zoomen | `RoamEnabled` | Boolean | `true` | `true` / `false` |
| Beschriftungen anzeigen | `ShowLabels` | Boolean | `true` | `true` / `false` |
| Verbindungsrichtung anzeigen | `ShowLinkArrows` | Boolean | `false` | `true` / `false` |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Beschriftungsgröße | `LabelFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Beschriftungsfarbe | `LabelColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Koordinatorfarbe | `NodeColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Routerfarbe | `RouterColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Endgerätefarbe | `EndDeviceColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe bei LQI 0 | `LqiLowColor` | Integer | `0xD9534F` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe bei LQI 128 | `LqiMidColor` | Integer | `0xE6B94A` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe bei LQI 255 | `LqiHighColor` | Integer | `0x37A873` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Graph animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsHeatmap

[Zur Modulanleitung](../EChartsHeatmap/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Numerische Archivvariable | `SourceVariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Vergleichsansichten verwenden | `UseComparisonPanels` | Boolean | `false` | `true` / `false` |
| Vergleichsansichten | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Gemeinsame Farbskala | `SharedColorScale` | Boolean | `true` | `true` / `false` |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Zeitraum | `Range` | String | `"1m"` | `"1w"` 1 Woche; `"1m"` 1 Monat; `"3m"` 3 Monate; `"6m"` 6 Monate; `"12m"` 12 Monate; `"ytd"` Seit 1. Januar; `"custom"` Benutzerdefiniert; `"absolute"` Fester historischer Zeitraum |
| Zeitraumwert | `CustomRangeValue` | Integer | `7` | 1–1000 |
| Zeitraumeinheit | `CustomRangeUnit` | String | `"day"` | `"minute"` Minuten; `"hour"` Stunden; `"day"` Tage; `"week"` Wochen |
| Von (einschließlich) | `AbsoluteStart` | String | `"{}"` | Datum/Uhrzeit als JSON |
| Bis (ausschließlich) | `AbsoluteEnd` | String | `"{}"` | Datum/Uhrzeit als JSON |
| Kennzahl | `Metric` | String | `"average"` | `"average"` Durchschnitt der Stundenmittelwerte; `"minimum"` Minimum der Stundenminima; `"maximum"` Maximum der Stundenmaxima; `"consumption"` Summe der Stundenverbräuche |
| Variablendarstellung | `UseVariablePresentation` | Boolean | `true` | `true` / `false` |
| Einheit | `Unit` | String | `""` | Siehe Modulanleitung |
| Nachkommastellen | `Decimals` | Integer | `1` | 0–6 |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Farbskalen-Vorlage | `ColorScalePreset` | String | `"custom"` | `"custom"` Individuell (2–5 Farben); `"tempera12"` Tempera-12 (Schiewe 2023); `"tempera6"` Tempera-6 (Schiewe 2023); `"noaa2023"` NOAA (Beispiel April 2023); `"saws2023"` SAWS (Beispiel April 2023); `"dwd2023"` DWD (Beispiel April 2023); `"ts2023"` tagesschau (Beispiel April 2023); `"faz2023"` FAZ (Beispiel April 2023); `"usa2023"` USA Today (Beispiel April 2023); `"china2023"` China Daily (Beispiel April 2023); `"abc2023"` ABC.net (Beispiel April 2023) |
| Farbe für niedrige Werte | `LowColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Zwischenfarbe 1 | `CustomMiddleColor1` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Zwischenfarbe 2 | `CustomMiddleColor2` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Zwischenfarbe 3 | `CustomMiddleColor3` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe für hohe Werte | `HighColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbskala anzeigen | `ShowColorScale` | Boolean | `true` | `true` / `false` |
| Zellenwerte anzeigen | `ShowValues` | Boolean | `false` | `true` / `false` |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Achsenbeschriftungsgröße | `AxisFontSizePercent` | Integer | `100` | 50–200 % |
| Wertbeschriftungsgröße | `ValueFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Achsenfarbe | `AxisColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Wertbeschriftungsfarbe | `ValueColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rasterfarbe | `GridColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Heatmap animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Archivvariable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Ansichtsname (optional) | `Label` | String | `""` | Siehe Modulanleitung |
| Frühere Zeiträume | `PeriodOffset` | Integer | `0` | 0–120 |
| Eigenen festen Zeitraum verwenden | `UseOwnPeriod` | Boolean | `false` | `true` / `false` |
| Von (einschließlich) | `AbsoluteStart` | String | `"{}"` | Datum/Uhrzeit als JSON |
| Bis (ausschließlich) | `AbsoluteEnd` | String | `"{}"` | Datum/Uhrzeit als JSON |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsParallel

[Zur Modulanleitung](../EChartsParallel/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Parallel-Achsen | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Zeitraum | `Range` | String | `"24h"` | `"24h"` Letzte 24 Stunden; `"7d"` Letzte 7 Tage; `"30d"` Letzte 30 Tage; `"custom"` Benutzerdefiniert; `"absolute"` Fester historischer Zeitraum |
| Zeitraumwert | `CustomRangeValue` | Integer | `7` | 1–1000 |
| Zeitraumeinheit | `CustomRangeUnit` | String | `"day"` | `"minute"` Minuten; `"hour"` Stunden; `"day"` Tage; `"week"` Wochen |
| Von (einschließlich) | `AbsoluteStart` | String | `"{}"` | Datum/Uhrzeit als JSON |
| Bis (ausschließlich) | `AbsoluteEnd` | String | `"{}"` | Datum/Uhrzeit als JSON |
| Intervall | `Aggregation` | String | `"hour"` | `"hour"` Stündlich; `"day"` Täglich |
| Maximale Linien | `LineBudget` | Integer | `750` | 1–1000 |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Tooltip anzeigen | `ShowTooltip` | Boolean | `true` | `true` / `false` |
| Linien glätten | `Smooth` | Boolean | `false` | `true` / `false` |
| Linienbreite | `LineWidth` | Integer | `2` | 1–8 px |
| Linientransparenz | `LineOpacityPercent` | Integer | `45` | 10–100 % |
| Linienfarbe | `LineColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Linienart | `LineType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Deckkraft ausgewählter Linien | `ActiveLineOpacityPercent` | Integer | `100` | 0–100 % |
| Deckkraft übriger Linien | `InactiveLineOpacityPercent` | Integer | `5` | 0–100 % |
| Achsenwerte anzeigen | `ShowAxisLabels` | Boolean | `true` | `true` / `false` |
| Achsen-Teilstriche anzeigen | `ShowAxisTicks` | Boolean | `true` | `true` / `false` |
| Achsenlinienbreite | `AxisLineWidth` | Integer | `1` | 1–5 px |
| Abstand der Achsentitel | `AxisNameGap` | Integer | `20` | 0–60 px |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Achsenschriftgröße | `AxisFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Achsenfarbe | `AxisColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Parallel animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Beschriftung (optional) | `Label` | String | `""` | Siehe Modulanleitung |
| Kennzahl | `Metric` | String | `"auto"` | `"auto"` Automatisch (Mittelwert / Zählerverbrauch); `"average"` Mittelwert; `"minimum"` Minimum; `"maximum"` Maximum; `"consumption"` Zählerverbrauch |
| Achsenbereich | `AxisRangeMode` | String | `"auto"` | `"auto"` Automatisch; `"manual"` Fest |
| Achsenminimum | `AxisMinimum` | Float | `0.0` | Siehe Modulanleitung |
| Achsenmaximum | `AxisMaximum` | Float | `100.0` | Siehe Modulanleitung |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsPictorialBar

[Zur Modulanleitung](../EChartsPictorialBar/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| PictorialBar-Variablen | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Werte anhand Quellen-Min/Max normalisieren | `NormalizeValues` | Boolean | `false` | `true` / `false` |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dunkel; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Ausrichtung | `Orientation` | String | `"horizontal"` | `"vertical"` Senkrecht; `"horizontal"` Waagerecht |
| Kategorienreihenfolge | `SortOrder` | String | `"configured"` | `"configured"` Listenreihenfolge; `"ascending"` Werte aufsteigend; `"descending"` Werte absteigend |
| Symbolform | `Symbol` | String | `"rect"` | `"rect"` Rechteck; `"roundRect"` Abgerundetes Rechteck; `"circle"` Kreis; `"diamond"` Raute; `"triangle"` Dreieck; `"icon"` Symcon-Icon; `"svg"` Eigene SVG-Form |
| Symcon-Icon | `SymbolIcon` | String | `""` | Siehe Modulanleitung |
| Eigene SVG-Form | `CustomSymbolSVG` | String | `""` | Siehe Modulanleitung |
| Symbole wiederholen | `SymbolRepeat` | Boolean | `true` | `true` / `false` |
| Größe wiederholter Symbole | `SymbolSize` | Integer | `12` | 4–40 px |
| Symbolabstand | `SymbolGap` | Integer | `2` | 0–20 px |
| Maximale Balkenbreite | `BarWidth` | Integer | `40` | 4–80 px |
| Skalenmaximum (0 = automatisch) | `ScaleMaximum` | Integer | `0` | 0–1000000000  |
| Tooltip anzeigen | `ShowTooltip` | Boolean | `true` | `true` / `false` |
| Kategorienbeschriftungen anzeigen | `ShowLabels` | Boolean | `true` | `true` / `false` |
| Werte anzeigen | `ShowValues` | Boolean | `true` | `true` / `false` |
| Raster anzeigen | `ShowGrid` | Boolean | `true` | `true` / `false` |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Achsenschriftgröße | `AxisFontSizePercent` | Integer | `100` | 50–200 % |
| Werteschriftgröße | `ValueFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Achsenfarbe | `AxisColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Wertefarbe | `ValueColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rasterfarbe | `GridColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Titel fett | `TitleBold` | Boolean | `true` | `true` / `false` |
| Deckkraft | `OpacityPercent` | Integer | `90` | 0–100 % |
| Rahmenbreite | `BorderWidth` | Integer | `0` | 0–5 px |
| Rahmenfarbe | `BorderColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Schriftfamilie | `FontFamily` | String | `"Arial, sans-serif"` | `"Arial, sans-serif"` Arial; `"Verdana, sans-serif"` Verdana; `"Georgia, serif"` Georgia; `"monospace"` Festbreitenschrift |
| Werteposition | `ValuePosition` | String | `"outside"` | `"outside"` Außen; `"inside"` Innen mittig; `"insideStart"` Innen am Anfang; `"insideEnd"` Innen am Ende |
| Abstand der Wertebeschriftung | `ValueDistance` | Integer | `5` | 0–30 px |
| Werte fett | `ValueBold` | Boolean | `false` | `true` / `false` |
| Überlappende Werte ausblenden | `HideOverlap` | Boolean | `true` | `true` / `false` |
| Drehung der Kategorienbeschriftung | `CategoryLabelRotate` | Integer | `0` | -90–90 ° |
| Breite der Bezeichnungsspalte (0 = automatisch) | `LabelColumnWidthPercent` | Integer | `0` | 0–80 % |
| Achsenwerte anzeigen | `ShowAxisValues` | Boolean | `true` | `true` / `false` |
| Achsenmarkierungen anzeigen | `ShowAxisTicks` | Boolean | `false` | `true` / `false` |
| Rasterlinienbreite | `GridWidth` | Integer | `1` | 0–5 px |
| Rasterlinienart | `GridType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Rasterdeckkraft | `GridOpacityPercent` | Integer | `25` | 0–100 % |
| Symbolfärbung | `ColorMode` | String | `"category"` | `"category"` Kategoriefarben; `"value"` Stufenlos nach Wert; `"gradient"` Farbverlauf entlang der Skala |
| Farbskala Minimum | `ColorMinimum` | Float | `0.0` | 0–1000000000000  |
| Farbskala Maximum | `ColorMaximum` | Float | `100.0` | 0–1000000000000  |
| Farbe bei niedrigen Werten | `LowColor` | Integer | `0xEE6666` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe in der Skalenmitte | `MiddleColor` | Integer | `0xFAC858` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe bei hohen Werten | `HighColor` | Integer | `0x91CC75` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rahmenlinienart | `BorderType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Schattenunschärfe | `ShadowBlur` | Integer | `0` | 0–20 px |
| Schattenfarbe | `ShadowColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Horizontaler Schattenversatz | `ShadowOffsetX` | Integer | `0` | -20–20 px |
| Vertikaler Schattenversatz | `ShadowOffsetY` | Integer | `0` | -20–20 px |
| Hervorhebung deaktivieren | `EmphasisDisabled` | Boolean | `false` | `true` / `false` |
| Fokus beim Überfahren | `Focus` | String | `"self"` | `"self"` Überfahrene Kategorie; `"none"` Keiner |
| Deckkraft ohne Fokus | `BlurOpacityPercent` | Integer | `10` | 0–100 % |
| PictorialBar animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts default (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Gentle (quadraticInOut); `"cubicOut"` Ease out (cubicOut); `"sinusoidalInOut"` Sine (sinusoidalInOut); `"bounceOut"` Bounce; `"elasticOut"` Elastic |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts default (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Gentle (quadraticInOut); `"cubicOut"` Ease out (cubicOut); `"sinusoidalInOut"` Sine (sinusoidalInOut); `"bounceOut"` Bounce; `"elasticOut"` Elastic |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Beschriftung (optional) | `Label` | String | `""` | Siehe Modulanleitung |
| Minimum (Normalisierung) | `Minimum` | Integer | `0` | Siehe Modulanleitung |
| Maximum (Normalisierung) | `Maximum` | Integer | `100` | Siehe Modulanleitung |
| Kategoriefarbe (optional) | `Color` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsPie

[Zur Modulanleitung](../EChartsPie/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Kreisdiagramm-Quellen | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Werte anhand Quellen-Min/Max normalisieren | `NormalizeValues` | Boolean | `false` | `true` / `false` |
| Erweiterte Quellenauswahl aktivieren | `EnableSourceSelection` | Boolean | `false` | `true` / `false` |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Kreisdiagramm-Layout | `PieMode` | String | `"pie"` | `"pie"` Kreis; `"donut"` Donut; `"nightingale"` Nightingale-Rose |
| Reihenfolge | `SortOrder` | String | `"configured"` | `"configured"` Konfigurierte Reihenfolge; `"ascending"` Wert aufsteigend; `"descending"` Wert absteigend |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Innenradius (% des Außenradius) | `InnerRadiusPercent` | Integer | `35` | 0–70 % |
| Außenradius | `OuterRadiusPercent` | Integer | `70` | 40–100 % |
| Legende anzeigen | `ShowLegend` | Boolean | `true` | `true` / `false` |
| Beschriftungen anzeigen | `ShowLabels` | Boolean | `true` | `true` / `false` |
| Prozentwerte anzeigen | `ShowPercent` | Boolean | `true` | `true` / `false` |
| Beschriftungsposition | `LabelPosition` | String | `"outside"` | `"outside"` Außen; `"inside"` Innen |
| Ausrichtung der Außenbeschriftung | `OutsideLabelAlignment` | String | `"edge"` | `"none"` Natürlich; `"labelLine"` Führungslinie; `"edge"` Containerrand |
| Länge der ersten Führungslinie | `LeaderLineLength` | Integer | `15` | 0–100 px |
| Länge der zweiten Führungslinie | `LeaderLineLength2` | Integer | `15` | 0–100 px |
| Textabstand zur Linie | `LabelLineGap` | Integer | `5` | 0–100 px |
| Abstand zum Rand | `LabelEdgeDistance` | Integer | `8` | 0–100 px |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Legendengröße | `LegendFontSizePercent` | Integer | `100` | 50–200 % |
| Beschriftungsgröße | `LabelFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Legendenfarbe | `LegendColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Beschriftungsfarbe | `LabelColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Sektoren animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Anfangs sichtbar | `InitiallyVisible` | Boolean | `true` | `true` / `false` |
| Min (Normalisierung) | `Minimum` | Integer | `0` | Siehe Modulanleitung |
| Max (Normalisierung) | `Maximum` | Integer | `100` | Siehe Modulanleitung |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Beschriftung (optional) | `Label` | String | `""` | Siehe Modulanleitung |
| Variablendarstellung | `UseVariablePresentation` | Boolean | `true` | `true` / `false` |
| Einheit | `Unit` | String | `""` | Siehe Modulanleitung |
| Nachkommastellen | `Decimals` | Integer | `1` | 0–6 |
| Farbe | `Color` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsProgress

[Zur Modulanleitung](../EChartsProgress/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsRadar

[Zur Modulanleitung](../EChartsRadar/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Radar-Achsen | `Axes` | String | `"[]"` | Siehe Modulanleitung |
| Vergleichswerte | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Gruppenauswahl aktivieren | `EnableGroupSelection` | Boolean | `false` | `true` / `false` |
| Unvollständige Vergleichsgruppen zulassen | `AllowIncompleteGroups` | Boolean | `false` | `true` / `false` |
| Anfängliche Gruppensichtbarkeit (optional) | `GroupVisibility` | String | `"[]"` | Siehe Modulanleitung |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dunkel; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Radarform | `Shape` | String | `"polygon"` | `"polygon"` Vieleck; `"circle"` Kreis |
| Startwinkel | `StartAngle` | Integer | `90` | 0–360 ° |
| Im Uhrzeigersinn | `Clockwise` | Boolean | `false` | `true` / `false` |
| Legende anzeigen | `ShowLegend` | Boolean | `true` | `true` / `false` |
| Tooltip anzeigen | `ShowTooltip` | Boolean | `true` | `true` / `false` |
| Achsennamen anzeigen | `ShowAxisNames` | Boolean | `true` | `true` / `false` |
| Normierte Achsenwerte anzeigen | `ShowAxisValues` | Boolean | `false` | `true` / `false` |
| Rasterflächen anzeigen | `ShowSplitArea` | Boolean | `true` | `true` / `false` |
| Rasterteilungen | `SplitNumber` | Integer | `5` | 1–10 |
| Abstand der Achsennamen | `AxisNameGap` | Integer | `15` | 0–40 px |
| Linienbreite | `LineWidth` | Integer | `2` | 0–8 px |
| Liniendeckkraft | `LineOpacityPercent` | Integer | `100` | 0–100 % |
| Flächendeckkraft | `AreaOpacityPercent` | Integer | `25` | 0–100 % |
| Symbolgröße | `SymbolSize` | Integer | `4` | 0–16 px |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Größe der Achsenbeschriftung | `AxisFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe der Achsenbeschriftung | `AxisColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rasterfarbe | `GridColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farben der Vergleichsgruppen | `GroupColors` | String | `"[]"` | Siehe Modulanleitung |
| Linienart | `LineType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Punktsymbol | `Symbol` | String | `"circle"` | `"circle"` Kreis; `"emptyCircle"` Leerer Kreis; `"rect"` Quadrat; `"roundRect"` Abgerundetes Quadrat; `"triangle"` Dreieck; `"diamond"` Raute; `"none"` Keine |
| Symboldrehung | `SymbolRotate` | Integer | `0` | -180–180 ° |
| Symbolrandbreite | `SymbolBorderWidth` | Integer | `0` | 0–8 px |
| Symbolrandfarbe | `SymbolBorderColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Symboldeckkraft | `SymbolOpacityPercent` | Integer | `100` | 0–100 % |
| Achsenbereiche anzeigen | `ShowAxisRange` | Boolean | `true` | `true` / `false` |
| Achsenlinien anzeigen | `ShowAxisLines` | Boolean | `true` | `true` / `false` |
| Achsenlinienbreite | `AxisLineWidth` | Integer | `1` | 0–8 px |
| Achsenliniendeckkraft | `AxisLineOpacityPercent` | Integer | `50` | 0–100 % |
| Rasterlinien anzeigen | `ShowSplitLines` | Boolean | `true` | `true` / `false` |
| Rasterlinienbreite | `SplitLineWidth` | Integer | `1` | 0–8 px |
| Rasterlinienart | `SplitLineType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Rasterliniendeckkraft | `SplitLineOpacityPercent` | Integer | `40` | 0–100 % |
| Erste Rasterflächenfarbe | `SplitAreaColor1` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Zweite Rasterflächenfarbe | `SplitAreaColor2` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rasterflächendeckkraft | `SplitAreaOpacityPercent` | Integer | `100` | 0–100 % |
| Originalwerte anzeigen | `ShowValues` | Boolean | `false` | `true` / `false` |
| Größe der Wertebeschriftung | `ValueFontSizePercent` | Integer | `100` | 50–200 % |
| Farbe der Wertebeschriftung | `ValueColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Position der Wertebeschriftung | `ValueLabelPosition` | String | `"top"` | `"top"` Darüber; `"bottom"` Darunter; `"left"` Links; `"right"` Rechts; `"inside"` Innen mittig |
| Abstand der Wertebeschriftung | `ValueLabelDistance` | Integer | `5` | 0–30 px |
| Wertebeschriftungen fett | `ValueLabelBold` | Boolean | `false` | `true` / `false` |
| Überlappende Wertebeschriftungen ausblenden | `HideOverlappingValueLabels` | Boolean | `true` | `true` / `false` |
| Titel fett | `TitleBold` | Boolean | `true` | `true` / `false` |
| Achsennamen fett | `AxisLabelBold` | Boolean | `false` | `true` / `false` |
| Größe der Legendenbeschriftung | `LegendFontSizePercent` | Integer | `100` | 50–200 % |
| Farbe der Legendenbeschriftung | `LegendColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Legendenposition | `LegendPosition` | String | `"bottom"` | `"top"` Oben; `"bottom"` Unten |
| Hervorhebung beim Berühren | `HoverFocus` | String | `"self"` | `"self"` Berührte Gruppe; `"none"` Ausgeschaltet |
| Weichzeichnung des Flächenschattens | `AreaShadowBlur` | Integer | `0` | 0–20 px |
| Flächenschattenfarbe | `AreaShadowColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Radar animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Axes`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Achsenschlüssel | `Key` | String | `""` | Siehe Modulanleitung |
| Achsenbeschriftung | `Label` | String | `""` | Siehe Modulanleitung |
| Minimum | `Minimum` | Integer | `0` | Siehe Modulanleitung |
| Maximum | `Maximum` | Integer | `100` | Siehe Modulanleitung |
| Inverse Achse (Minimum außen) | `Inverse` | Boolean | `false` | `true` / `false` |
| Einheit | `Unit` | String | `""` | Siehe Modulanleitung |
| Nachkommastellen | `Decimals` | Integer | `1` | 0–6 |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Vergleichsgruppe | `Group` | String | `""` | Siehe Modulanleitung |
| Achse | `Axis` | String | `""` | `""` Achse wählen |
| Aktuelle Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |

### Zeilenfelder von `GroupVisibility`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Vergleichsgruppe | `Group` | String | `""` | `""` Gruppe wählen |
| Anfangs sichtbar | `InitiallyVisible` | Boolean | `true` | `true` / `false` |

### Zeilenfelder von `GroupColors`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Vergleichsgruppe | `Group` | String | `""` | `""` Gruppe wählen |
| Farbe | `Color` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsRadarHistory

[Zur Modulanleitung](../EChartsRadarHistory/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsSankey

[Zur Modulanleitung](../EChartsSankey/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Sankey-Knoten | `Nodes` | String | `"[]"` | Siehe Modulanleitung |
| Sankey-Verbindungen | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Einheit (optional) | `Unit` | String | `""` | Siehe Modulanleitung |
| Einheitenmodell und Quellenprüfung | `UnitMode` | String | `"manual"` | `"manual"` Manuelle Anzeigeeinheit (ohne Quellen-Einheitenprüfung); `"automatic"` Automatische gemeinsame Quelleinheit (geprüft); `"checked"` Manuelle erwartete Einheit (geprüft) |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dunkel; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Ausrichtung | `Orientation` | String | `"horizontal"` | `"horizontal"` Waagerecht; `"vertical"` Senkrecht |
| Knotenausrichtung | `NodeAlign` | String | `"justify"` | `"justify"` An beiden Enden ausrichten; `"left"` Am Anfang ausrichten; `"right"` Am Ende ausrichten |
| Knotenbreite | `NodeWidth` | Integer | `20` | 4–40 px |
| Knotenabstand | `NodeGap` | Integer | `12` | 0–40 px |
| Knoten verschieben erlauben | `Draggable` | Boolean | `true` | `true` / `false` |
| Layout-Optimierungsdurchläufe (0: Listenreihenfolge) | `LayoutIterations` | Integer | `32` | 0–64 |
| Hervorhebung beim Berühren | `HoverFocus` | String | `"adjacency"` | `"none"` Ausgeschaltet; `"adjacency"` Direkte Verbindungen; `"trajectory"` Vollständige Flusspfade |
| Deckkraft hervorgehobener Flüsse | `HighlightEdgeOpacityPercent` | Integer | `85` | 0–100 % |
| Deckkraft nicht hervorgehobener Flüsse | `BlurEdgeOpacityPercent` | Integer | `5` | 0–100 % |
| Tooltip anzeigen | `ShowTooltip` | Boolean | `true` | `true` / `false` |
| Beschriftungen anzeigen | `ShowLabels` | Boolean | `true` | `true` / `false` |
| Knotenwerte anzeigen | `ShowValues` | Boolean | `false` | `true` / `false` |
| Beschriftungsposition | `LabelPosition` | String | `"auto"` | `"auto"` Automatisch; `"inside"` Innen; `"left"` Links; `"right"` Rechts; `"top"` Oben; `"bottom"` Unten |
| Beschriftungsabstand | `LabelDistance` | Integer | `5` | 0–30 px |
| Beschriftungsbreite (0: automatisch) | `LabelMaxWidth` | Integer | `0` | 0–400 px |
| Lange Beschriftungen | `LabelOverflow` | String | `"truncate"` | `"truncate"` Kürzen; `"break"` An Wörtern umbrechen; `"breakAll"` An Zeichen umbrechen |
| Beschriftungen fett | `LabelBold` | Boolean | `false` | `true` / `false` |
| Überlappende Beschriftungen ausblenden | `HideOverlappingLabels` | Boolean | `false` | `true` / `false` |
| Verbindungswerte anzeigen | `ShowEdgeValues` | Boolean | `false` | `true` / `false` |
| Größe der Verbindungsbeschriftung | `EdgeLabelFontSizePercent` | Integer | `100` | 50–200 % |
| Farbe der Verbindungsbeschriftung | `EdgeLabelColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Individuelle Knotenfarben | `NodeColors` | String | `"[]"` | Siehe Modulanleitung |
| Knotendeckkraft | `NodeOpacityPercent` | Integer | `100` | 0–100 % |
| Knotenrahmenbreite | `NodeBorderWidth` | Integer | `0` | 0–8 px |
| Knoteneckenradius | `NodeBorderRadius` | Integer | `0` | 0–20 px |
| Knotenrahmenfarbe | `NodeBorderColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Knotenschattenunschärfe | `NodeShadowBlur` | Integer | `0` | 0–20 px |
| Knotenschattenfarbe | `NodeShadowColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Flussfarbmodus | `EdgeColorMode` | String | `"gradient"` | `"source"` Startknoten; `"target"` Zielknoten; `"gradient"` Farbverlauf vom Start zum Ziel; `"fixed"` Feste Farbe |
| Flussdeckkraft | `EdgeOpacityPercent` | Integer | `35` | 0–100 % |
| Flusskrümmung | `EdgeCurvenessPercent` | Integer | `50` | 0–100 % |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Beschriftungsgröße | `LabelFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Beschriftungsfarbe | `LabelColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Knotenfarbe | `NodeColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Feste Flussfarbe | `EdgeColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Sankey animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Nodes`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Knotenschlüssel | `Key` | String | `""` | Siehe Modulanleitung |
| Knotenbeschriftung | `Label` | String | `""` | Siehe Modulanleitung |
| Berechneten Bilanzfluss ergänzen | `Balance` | Boolean | `false` | `true` / `false` |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Von Knoten | `Source` | String | `""` | `""` Knoten wählen |
| Zu Knoten | `Target` | String | `""` | `""` Knoten wählen |
| Flussvariable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Negative Flüsse umkehren | `ReverseNegative` | Boolean | `false` | `true` / `false` |

### Zeilenfelder von `NodeColors`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Knoten | `Key` | String | `""` | `""` Knoten wählen |
| Farbe | `Color` | Integer | `5614517` | `0xRRGGBB`; Automatik nach Feldvorgabe |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsScatter

[Zur Modulanleitung](../EChartsScatter/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Streudiagramm (optional mit Blasengröße) | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Zeitraum | `Range` | String | `"24h"` | `"1h"` 1 Stunde; `"6h"` 6 Stunden; `"24h"` 24 Stunden; `"7d"` 7 Tage; `"30d"` 30 Tage; `"today"` Heute; `"yesterday"` Gestern; `"current-week"` Diese Woche; `"current-month"` Dieser Monat; `"custom"` Benutzerdefiniert |
| Zeitraumwert | `CustomRangeValue` | Integer | `7` | 1–1000 |
| Zeitraumeinheit | `CustomRangeUnit` | String | `"day"` | `"minute"` Minuten; `"hour"` Stunden; `"day"` Tage; `"week"` Wochen |
| Verdichtung | `DataMode` | String | `"auto"` | `"auto"` Automatisch; `"minute"` 1 Minute; `"five-minutes"` 5 Minuten; `"fifteen-minutes"` 15 Minuten; `"hour"` 1 Stunde; `"day"` 1 day |
| Punktbudget | `PointBudget` | Integer | `1000` | 200–2000 |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Raster anzeigen | `ShowGrid` | Boolean | `true` | `true` / `false` |
| X/Y-Zoom aktivieren | `EnableZoom` | Boolean | `true` | `true` / `false` |
| Bereichsauswahl aktivieren | `EnableBrush` | Boolean | `false` | `true` / `false` |
| Punktwerte anzeigen | `ShowValues` | Boolean | `false` | `true` / `false` |
| Punktbeschriftungen an Diagrammgröße anpassen | `AdaptiveValueLabels` | Boolean | `false` | `true` / `false` |
| Angezeigte Punktwerte | `ValueLabelContent` | String | `"y"` | `"x"` X-Wert; `"y"` Y-Wert; `"both"` X- und Y-Wert |
| Position der Wertbeschriftung | `ValueLabelPosition` | String | `"auto"` | `"auto"` Automatisch; `"top"` Oberhalb; `"bottom"` Unterhalb; `"left"` Links; `"right"` Rechts; `"inside"` Innen mittig; `"insideTop"` Innen oben; `"insideBottom"` Innen unten; `"insideLeft"` Innen links; `"insideRight"` Innen rechts |
| Abstand der Wertbeschriftung | `ValueLabelDistance` | Integer | `5` | 0–100 px |
| Punktgröße | `PointSize` | Integer | `12` | 4–60 px |
| Maximale Blasengröße | `BubbleMaxSize` | Integer | `48` | 8–100 px |
| Bezugsbereich der Blasengröße | `BubbleRangeMode` | String | `"data"` | `"data"` An Daten anpassen; `"manual"` Feste Grenzen |
| Minimum der Größenquelle | `BubbleMinimum` | Float | `0.0` | Siehe Modulanleitung |
| Maximum der Größenquelle | `BubbleMaximum` | Float | `100.0` | Siehe Modulanleitung |
| Punkte nach Wert einfärben | `ColorMapEnabled` | Boolean | `false` | `true` / `false` |
| Farbquelle | `ColorMapDimension` | String | `"y"` | `"x"` X-Wert; `"y"` Y-Wert; `"size"` Blasengröße |
| Farbbereich | `ColorMapRangeMode` | String | `"data"` | `"data"` An Daten anpassen; `"manual"` Manuell |
| Farbminimum | `ColorMapMinimum` | Float | `0.0` | Siehe Modulanleitung |
| Farbmaximum | `ColorMapMaximum` | Float | `100.0` | Siehe Modulanleitung |
| Farbe für niedrige Werte | `ColorMapLowColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe für hohe Werte | `ColorMapHighColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbskala anzeigen | `ColorMapShowLegend` | Boolean | `false` | `true` / `false` |
| X-Achsenbereich | `XAxisRangeMode` | String | `"data"` | `"data"` An Daten anpassen; `"zero"` Null einschließen; `"manual"` Manuell |
| X-Minimum | `XAxisMinimum` | Float | `0.0` | Siehe Modulanleitung |
| X-Maximum | `XAxisMaximum` | Float | `100.0` | Siehe Modulanleitung |
| Y-Achsenbereich | `YAxisRangeMode` | String | `"data"` | `"data"` An Daten anpassen; `"zero"` Null einschließen; `"manual"` Manuell |
| Y-Minimum | `YAxisMinimum` | Float | `0.0` | Siehe Modulanleitung |
| Y-Maximum | `YAxisMaximum` | Float | `100.0` | Siehe Modulanleitung |
| Punktfarbe | `PointColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Achsenfarbe | `AxisColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rasterfarbe | `GridColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Wertfarbe | `ValueColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–150 % |
| Achsenschriftgröße | `AxisFontSizePercent` | Integer | `100` | 50–150 % |
| Wertschriftgröße | `ValueFontSizePercent` | Integer | `100` | 50–150 % |
| Punkte animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |
| Zeiteinstellungen der Kachel verwenden | `IPSViewUseTileTimeSettings` | Boolean | `true` | `true` / `false` |
| IPSView-Zeitraum | `IPSViewRange` | String | `"24h"` | `"1h"` 1 Stunde; `"6h"` 6 Stunden; `"24h"` 24 Stunden; `"7d"` 7 Tage; `"30d"` 30 Tage; `"today"` Heute; `"yesterday"` Gestern; `"current-week"` Diese Woche; `"current-month"` Dieser Monat; `"custom"` Benutzerdefiniert |
| Zeitraumwert | `IPSViewCustomRangeValue` | Integer | `7` | 1–1000 |
| Zeitraumeinheit | `IPSViewCustomRangeUnit` | String | `"day"` | `"minute"` Minuten; `"hour"` Stunden; `"day"` Tage; `"week"` Wochen |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Vergleichsgruppe (optional) | `Group` | String | `""` | Siehe Modulanleitung |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Rolle | `Role` | String | `"x"` | `"x"` X-Achse; `"y"` Y-Achse; `"size"` Blasengröße |
| Beschriftung (optional) | `Label` | String | `""` | Siehe Modulanleitung |
| Variablendarstellung | `UseVariablePresentation` | Boolean | `true` | `true` / `false` |
| Einheit | `Unit` | String | `""` | Siehe Modulanleitung |
| Nachkommastellen | `Decimals` | Integer | `1` | 0–6 |
| Reduzierer | `Reducer` | String | `"auto"` | `"auto"` Automatisch; `"average"` Durchschnitt; `"sum"` Summe; `"minimum"` Minimum; `"maximum"` Maximum |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsStateTimeline

[Zur Modulanleitung](../EChartsStateTimeline/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Zustandsquellen | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Zeitraum | `Range` | String | `"7d"` | `"24h"` Letzte 24 Stunden; `"7d"` Letzte 7 Tage; `"30d"` Letzte 30 Tage; `"custom"` Benutzerdefiniert; `"absolute"` Fester historischer Zeitraum |
| Zeitraumwert | `CustomRangeValue` | Integer | `7` | 1–1000 |
| Zeitraumeinheit | `CustomRangeUnit` | String | `"day"` | `"minute"` Minuten; `"hour"` Stunden; `"day"` Tage; `"week"` Wochen |
| Von (einschließlich) | `AbsoluteStart` | String | `"{}"` | Datum/Uhrzeit als JSON |
| Bis (ausschließlich) | `AbsoluteEnd` | String | `"{}"` | Datum/Uhrzeit als JSON |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dunkel; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Zoom aktivieren | `EnableZoom` | Boolean | `true` | `true` / `false` |
| Tooltip anzeigen | `ShowTooltip` | Boolean | `true` | `true` / `false` |
| Zustandsnamen anzeigen | `ShowLabels` | Boolean | `true` | `true` / `false` |
| Raster anzeigen | `ShowGrid` | Boolean | `true` | `true` / `false` |
| Abschnittshöhe | `RowHeightPercent` | Integer | `65` | 20–100 % |
| Abschnittsdeckkraft | `BarOpacityPercent` | Integer | `90` | 0–100 % |
| Rahmenbreite | `BorderWidth` | Integer | `0` | 0–5 px |
| Titelschriftgröße | `TitleFontSizePercent` | Integer | `110` | 50–200 % |
| Achsenschriftgröße | `AxisFontSizePercent` | Integer | `75` | 50–200 % |
| Schriftgröße der Zustandsnamen | `LabelFontSizePercent` | Integer | `75` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Achsenfarbe | `AxisColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Schriftfarbe der Zustandsnamen | `LabelColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rahmenfarbe | `BorderColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe unbekannter Zustände | `UnknownColor` | Integer | `0x777B83` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rahmenlinientyp | `BorderType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Eckenradius | `BorderRadius` | Integer | `0` | 0–20 px |
| Position der Zustandsnamen | `LabelPosition` | String | `"center"` | `"left"` Links; `"center"` Mittig; `"right"` Rechts |
| Schriftart | `FontFamily` | String | `"Arial"` | `"Arial"` Arial; `"Verdana"` Verdana; `"Georgia"` Georgia; `"monospace"` Nichtproportional |
| Titel fett | `TitleBold` | Boolean | `true` | `true` / `false` |
| Titel kursiv | `TitleItalic` | Boolean | `false` | `true` / `false` |
| Achsenbeschriftung fett | `AxisLabelBold` | Boolean | `false` | `true` / `false` |
| Achsenbeschriftung kursiv | `AxisLabelItalic` | Boolean | `false` | `true` / `false` |
| Zustandsnamen fett | `LabelBold` | Boolean | `false` | `true` / `false` |
| Zustandsnamen kursiv | `LabelItalic` | Boolean | `false` | `true` / `false` |
| Zeitbeschriftung anzeigen | `ShowTimeLabels` | Boolean | `true` | `true` / `false` |
| Quellenbeschriftung anzeigen | `ShowSourceLabels` | Boolean | `true` | `true` / `false` |
| Zeitachsenlinie anzeigen | `ShowAxisLine` | Boolean | `true` | `true` / `false` |
| Zeitachsenteilstriche anzeigen | `ShowAxisTicks` | Boolean | `false` | `true` / `false` |
| Achsenlinienbreite | `AxisLineWidth` | Integer | `1` | 0–5 px |
| Achsenlinientyp | `AxisLineType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Abstand der Zeitbeschriftung | `AxisLabelMargin` | Integer | `12` | 0–30 px |
| Format der Zeitbeschriftung | `TimeLabelFormat` | String | `"auto"` | `"auto"` Automatisch; `"time"` Uhrzeit; `"date"` Datum; `"dateTime"` Datum und Uhrzeit |
| Rasterfarbe | `GridColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rasterdeckkraft | `GridOpacityPercent` | Integer | `20` | 0–100 % |
| Rasterlinienbreite | `GridLineWidth` | Integer | `1` | 0–5 px |
| Rasterlinientyp | `GridLineType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Hervorhebung aktivieren | `EmphasisEnabled` | Boolean | `true` | `true` / `false` |
| Fokus beim Überfahren | `EmphasisFocus` | String | `"none"` | `"none"` Nicht abdunkeln; `"self"` Überfahrener Abschnitt |
| Deckkraft abgedunkelter Abschnitte | `BlurOpacityPercent` | Integer | `20` | 0–100 % |
| Zustandsabschnitte animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 0, 1 |
| Beschriftung (optional) | `Label` | String | `""` | Siehe Modulanleitung |
| Zustandszuordnung | `States` | Array | `[]` | Siehe Modulanleitung |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsSunburst

[Zur Modulanleitung](../EChartsSunburst/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Sunburst-Variablen | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Blattgewichte anhand Quellen-Min/Max normalisieren | `NormalizeValues` | Boolean | `false` | `true` / `false` |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Beschriftungen anzeigen | `ShowLabels` | Boolean | `true` | `true` / `false` |
| Werte in den Segmenten anzeigen | `ShowValues` | Boolean | `false` | `true` / `false` |
| Tooltip anzeigen | `ShowTooltip` | Boolean | `true` | `true` / `false` |
| Beschriftungsrichtung | `LabelRotation` | String | `"radial"` | `"radial"` Radial; `"tangential"` Tangential; `"horizontal"` Waagerecht |
| Beschriftungen unter Winkel ausblenden | `LabelMinAngle` | Integer | `0` | 0–45 ° |
| Inneren Ring beschriften | `ShowInnerRingLabels` | Boolean | `true` | `true` / `false` |
| Mittleren Ring beschriften | `ShowMiddleRingLabels` | Boolean | `true` | `true` / `false` |
| Äußeren Ring beschriften | `ShowOuterRingLabels` | Boolean | `true` | `true` / `false` |
| Schriftgröße innerer Ring (0 = allgemein) | `InnerRingLabelFontSizePercent` | Integer | `0` | 0–200 % |
| Schriftgröße mittlerer Ring (0 = allgemein) | `MiddleRingLabelFontSizePercent` | Integer | `0` | 0–200 % |
| Schriftgröße äußerer Ring (0 = allgemein) | `OuterRingLabelFontSizePercent` | Integer | `0` | 0–200 % |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Werte-Beschriftungsgröße | `LabelFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Werte-Beschriftungsfarbe | `LabelColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Feste Gruppenfarben (optional) | `GroupColors` | String | `"[]"` | Siehe Modulanleitung |
| Innenradius | `InnerRadiusPercent` | Integer | `0` | 0–50 % |
| Außenradius | `OuterRadiusPercent` | Integer | `85` | 50–95 % |
| Startwinkel | `StartAngle` | Integer | `90` | 0–360 ° |
| Im Uhrzeigersinn | `Clockwise` | Boolean | `true` | `true` / `false` |
| Segmentreihenfolge | `SortMode` | String | `"original"` | `"original"` Konfigurationsreihenfolge; `"asc"` Kleinste zuerst; `"desc"` Größte zuerst |
| Segmentrahmen | `BorderWidth` | Integer | `1` | 0–12 px |
| Abgerundete Segmentkanten | `BorderRadius` | Integer | `0` | 0–20 px |
| Segmentrahmenfarbe | `BorderColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Beim Klick auf ein Segment hineinzoomen | `ClickToRoot` | Boolean | `true` | `true` / `false` |
| Sunburst animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Eingangsanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Initial easing | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts default (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Gentle (quadraticInOut); `"cubicOut"` Ease out (cubicOut); `"sinusoidalInOut"` Sine (sinusoidalInOut); `"bounceOut"` Bounce; `"elasticOut"` Elastic |
| Update easing | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts default (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Gentle (quadraticInOut); `"cubicOut"` Ease out (cubicOut); `"sinusoidalInOut"` Sine (sinusoidalInOut); `"bounceOut"` Bounce; `"elasticOut"` Elastic |
| Initial delay | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Update delay | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Gruppe | `Group` | String | `""` | Siehe Modulanleitung |
| Untergruppe (optional) | `Subgroup` | String | `""` | Siehe Modulanleitung |
| Gruppenpfad (optional) | `GroupPath` | String | `""` | Siehe Modulanleitung |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Beschriftung (optional) | `Label` | String | `""` | Siehe Modulanleitung |
| Minimum (Normalisierung) | `Minimum` | Integer | `0` | Siehe Modulanleitung |
| Maximum (Normalisierung) | `Maximum` | Integer | `100` | Siehe Modulanleitung |
| Variablendarstellung verwenden | `UseVariablePresentation` | Boolean | `true` | `true` / `false` |
| Einheit | `Unit` | String | `""` | Siehe Modulanleitung |
| Nachkommastellen | `Decimals` | Integer | `1` | 0–6 |

### Zeilenfelder von `GroupColors`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Gruppe | `Group` | String | `""` | Siehe Modulanleitung |
| Farbe | `Color` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsThemeRiver

[Zur Modulanleitung](../EChartsThemeRiver/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| ThemeRiver-Quellen | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Datengrundlage | `Metric` | String | `"average"` | `"average"` Intervallmittelwert (Standardarchiv); `"consumption"` Zählerverbrauch je Intervall |
| Bandauswahl aktivieren | `EnableBandSelection` | Boolean | `false` | `true` / `false` |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Zeitraum | `Range` | String | `"7d"` | `"24h"` Letzte 24 Stunden; `"7d"` Letzte 7 Tage; `"30d"` Letzte 30 Tage; `"custom"` Benutzerdefinierte Dauer; `"absolute"` Fester historischer Zeitraum |
| Dauer | `CustomRangeValue` | Integer | `7` | 1–1000 |
| Zeiteinheit | `CustomRangeUnit` | String | `"day"` | `"minute"` Minuten; `"hour"` Stunden; `"day"` Tage; `"week"` Wochen |
| Von (einschließlich) | `AbsoluteStart` | String | `"{}"` | Datum/Uhrzeit als JSON |
| Bis (ausschließlich) | `AbsoluteEnd` | String | `"{}"` | Datum/Uhrzeit als JSON |
| Intervall | `Aggregation` | String | `"hour"` | `"hour"` Stündlich; `"day"` Täglich |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dunkel; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Tooltip anzeigen | `ShowTooltip` | Boolean | `true` | `true` / `false` |
| Legende anzeigen | `ShowLegend` | Boolean | `true` | `true` / `false` |
| Bandbeschriftungen anzeigen | `ShowLabels` | Boolean | `false` | `true` / `false` |
| Banddeckkraft | `BandOpacityPercent` | Integer | `85` | 0–100 % |
| Bandkonturbreite | `BorderWidth` | Integer | `0` | 0–5 px |
| Bandkonturfarbe | `BorderColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Zeitbeschriftungen anzeigen | `ShowAxisLabels` | Boolean | `true` | `true` / `false` |
| Achsen-Teilstriche anzeigen | `ShowAxisTicks` | Boolean | `true` | `true` / `false` |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Zeitschriftgröße | `AxisFontSizePercent` | Integer | `100` | 50–200 % |
| Bandschriftgröße | `LabelFontSizePercent` | Integer | `100` | 50–200 % |
| Legendenschriftgröße | `LegendFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Zeitachsenfarbe | `AxisColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Bandschriftfarbe | `LabelColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Legendenfarbe | `LegendColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Bandhöhe | `BandHeightPercent` | Integer | `84` | 20–100 % |
| Bandkonturart | `BorderType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Schattenunschärfe | `ShadowBlur` | Integer | `0` | 0–20 px |
| Schattenfarbe | `ShadowColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Horizontaler Schattenversatz | `ShadowOffsetX` | Integer | `0` | -20–20 px |
| Vertikaler Schattenversatz | `ShadowOffsetY` | Integer | `0` | -20–20 px |
| Beim Überfahren hervorheben | `EmphasisEnabled` | Boolean | `true` | `true` / `false` |
| Hervorhebungsfokus | `EmphasisFocus` | String | `"self"` | `"self"` Überfahrenes Band; `"series"` Überfahrener Abschnitt; `"none"` Keine Abdunkelung |
| Restdeckkraft anderer Bänder | `BlurOpacityPercent` | Integer | `10` | 0–100 % |
| Achsenlinie anzeigen | `ShowAxisLine` | Boolean | `true` | `true` / `false` |
| Achsenlinienbreite | `AxisLineWidth` | Integer | `1` | 0–5 px |
| Achsenlinienart | `AxisLineType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Abstand der Zeitbeschriftung | `AxisLabelMargin` | Integer | `12` | 0–30 px |
| Zeitbeschriftungsformat | `TimeLabelFormat` | String | `"auto"` | `"auto"` Automatisch; `"date"` Datum; `"dateTime"` Datum und Uhrzeit |
| Rasterlinien anzeigen | `ShowGrid` | Boolean | `false` | `true` / `false` |
| Rasterlinienbreite | `GridLineWidth` | Integer | `1` | 0–5 px |
| Rasterlinienart | `GridLineType` | String | `"dashed"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| Rasterfarbe | `GridColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rasterdeckkraft | `GridOpacityPercent` | Integer | `20` | 0–100 % |
| Legendenposition | `LegendPosition` | String | `"bottom"` | `"bottom"` Unten; `"top"` Oben |
| Legendenausrichtung | `LegendAlignment` | String | `"left"` | `"left"` Links; `"center"` Mittig; `"right"` Rechts |
| Legendensymbol | `LegendIcon` | String | `"roundRect"` | `"roundRect"` Abgerundetes Rechteck; `"rect"` Rechteck; `"circle"` Kreis; `"diamond"` Raute |
| Legendensymbolbreite | `LegendItemWidth` | Integer | `25` | 6–40 px |
| Legendensymbolhöhe | `LegendItemHeight` | Integer | `14` | 6–30 px |
| Abstand der Legendeneinträge | `LegendItemGap` | Integer | `10` | 0–30 px |
| Schriftart | `FontFamily` | String | `"Arial, sans-serif"` | `"Arial, sans-serif"` Arial; `"Verdana, sans-serif"` Verdana; `"Georgia, serif"` Georgia; `"monospace"` Nichtproportional |
| Titel fett | `TitleBold` | Boolean | `true` | `true` / `false` |
| Zeitbeschriftungen fett | `AxisLabelBold` | Boolean | `false` | `true` / `false` |
| Bandbeschriftungen fett | `LabelBold` | Boolean | `false` | `true` / `false` |
| Legende fett | `LegendBold` | Boolean | `false` | `true` / `false` |
| Titel kursiv | `TitleItalic` | Boolean | `false` | `true` / `false` |
| Zeitbeschriftungen kursiv | `AxisLabelItalic` | Boolean | `false` | `true` / `false` |
| Bandbeschriftungen kursiv | `LabelItalic` | Boolean | `false` | `true` / `false` |
| Legende kursiv | `LegendItalic` | Boolean | `false` | `true` / `false` |
| ThemeRiver animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Anfangs sichtbar | `InitiallyVisible` | Boolean | `true` | `true` / `false` |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Beschriftung (optional) | `Label` | String | `""` | Siehe Modulanleitung |
| Bandfarbe (optional) | `Color` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsTimeSeries

[Zur Modulanleitung](../EChartsTimeSeries/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Zeitreihenquellen | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Referenzlinien und Wertebereiche | `Annotations` | String | `"[]"` | Siehe Modulanleitung |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Zeitraum | `Range` | String | `"24h"` | `"1h"` 1 Stunde; `"6h"` 6 Stunden; `"24h"` 24 Stunden; `"7d"` 7 Tage; `"30d"` 30 Tage; `"today"` Heute; `"yesterday"` Gestern; `"current-week"` Diese Woche; `"current-month"` Dieser Monat; `"custom"` Benutzerdefiniert |
| Zeitraumwert | `CustomRangeValue` | Integer | `7` | 1–1000 |
| Zeitraumeinheit | `CustomRangeUnit` | String | `"day"` | `"minute"` Minuten; `"hour"` Stunden; `"day"` Tage; `"week"` Wochen |
| Beschriftung der Zeitachse | `TimeAxisLabelFormat` | String | `"auto"` | `"auto"` Automatisch; `"time"` Uhrzeit; `"date"` Datum; `"date-time"` Datum und Uhrzeit |
| Datenmodus | `DataMode` | String | `"auto"` | `"realtime"` Echtzeitwerte (kein Archiv erforderlich); `"raw"` Rohwerte; `"auto"` Automatische Verdichtung; `"minute"` 1 Minute; `"five-minutes"` 5 Minuten; `"fifteen-minutes"` 15 Minuten; `"hour"` 1 Stunde; `"day"` 1 Tag |
| Gesamtpunktbudget | `PointBudget` | Integer | `2000` | 200–32000 |
| Datenlücken | `GapDetectionMode` | String | `"off"` | `"off"` Linien nicht unterbrechen; `"automatic"` Automatisch erkennen; `"custom"` Maximalabstand festlegen |
| Maximaler Abstand | `GapThresholdMinutes` | Integer | `60` | 1–10080  min |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Legendenposition | `LegendPosition` | String | `"top"` | `"top"` Oben; `"bottom"` Unten; `"hidden"` Ausgeblendet |
| Zoom aktivieren | `EnableZoom` | Boolean | `true` | `true` / `false` |
| Zeitreihe animieren | `AnimationEnabled` | Boolean | `false` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |
| Linienstärke | `LineWidthPercent` | Integer | `100` | 50–200  % |
| Linien glätten | `SmoothLines` | Boolean | `false` | `true` / `false` |
| Datenpunkte anzeigen | `ShowSymbols` | Boolean | `false` | `true` / `false` |
| Punktgröße | `SymbolSizePercent` | Integer | `100` | 50–200 % |
| Punktwerte anzeigen | `ShowValues` | Boolean | `false` | `true` / `false` |
| Position der Wertbeschriftung | `ValueLabelPosition` | String | `"top"` | `"auto"` Automatisch; `"top"` Oberhalb; `"bottom"` Unterhalb; `"left"` Links; `"right"` Rechts; `"inside"` Innen mittig; `"insideTop"` Innen oben; `"insideBottom"` Innen unten; `"insideLeft"` Innen links; `"insideRight"` Innen rechts |
| Abstand der Wertbeschriftung | `ValueLabelDistance` | Integer | `5` | 0–100 px |
| Flächendeckkraft | `AreaOpacityPercent` | Integer | `22` | 0–100 % |
| Raster anzeigen | `ShowGrid` | Boolean | `true` | `true` / `false` |
| Zeitachse anzeigen | `ShowXAxis` | Boolean | `true` | `true` / `false` |
| Wertachsen anzeigen | `ShowYAxis` | Boolean | `true` | `true` / `false` |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Legendengröße | `LegendFontSizePercent` | Integer | `100` | 50–200 % |
| Achsenbeschriftungsgröße | `AxisFontSizePercent` | Integer | `100` | 50–200 % |
| Schriftgröße der Werte | `ValueFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Legendenfarbe | `LegendColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Achsenfarbe | `AxisColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Rasterfarbe | `GridColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Wertfarbe | `ValueColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Zeiteinstellungen der Kachel verwenden | `IPSViewUseTileTimeSettings` | Boolean | `true` | `true` / `false` |
| IPSView-Zeitraum | `IPSViewRange` | String | `"24h"` | `"1h"` 1 Stunde; `"6h"` 6 Stunden; `"24h"` 24 Stunden; `"7d"` 7 Tage; `"30d"` 30 Tage; `"today"` Heute; `"yesterday"` Gestern; `"current-week"` Diese Woche; `"current-month"` Dieser Monat; `"custom"` Benutzerdefiniert |
| Zeitraumwert | `IPSViewCustomRangeValue` | Integer | `7` | 1–1000 |
| Zeitraumeinheit | `IPSViewCustomRangeUnit` | String | `"day"` | `"minute"` Minuten; `"hour"` Stunden; `"day"` Tage; `"week"` Wochen |
| Beschriftung der Zeitachse | `IPSViewTimeAxisLabelFormat` | String | `"auto"` | `"auto"` Automatisch; `"time"` Uhrzeit; `"date"` Datum; `"date-time"` Datum und Uhrzeit |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Anfangs sichtbar | `InitiallyVisible` | Boolean | `true` | `true` / `false` |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Beschriftung | `Label` | String | `""` | Siehe Modulanleitung |
| Variablendarstellung | `UseVariablePresentation` | Boolean | `true` | `true` / `false` |
| Einheit | `Unit` | String | `""` | Siehe Modulanleitung |
| Nachkommastellen | `Decimals` | Integer | `1` | 0–6 |
| Farbe | `Color` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Darstellung | `Style` | String | `"line"` | `"line"` Linie; `"area"` Fläche |
| Reduzierer | `Reducer` | String | `"auto"` | `"auto"` Automatisch; `"average"` Durchschnitt; `"sum"` Summe; `"minimum"` Minimum; `"maximum"` Maximum |
| Achsenseite | `AxisPosition` | String | `"auto"` | `"auto"` Automatisch; `"left"` Links; `"right"` Rechts |
| AxisRangeMode | `AxisRangeMode` | String | `"auto"` | `"auto"` Automatisch; `"presentation"` Variablendarstellung; `"manual"` Manuell |
| AxisMinimum | `AxisMinimum` | Float | `0.0` | Siehe Modulanleitung |
| AxisMaximum | `AxisMaximum` | Float | `100.0` | Siehe Modulanleitung |
| Einzeldesign | `UseIndividualDesign` | Boolean | `false` | `true` / `false` |
| SeriesLineType | `SeriesLineType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| SeriesLineWidthPercent | `SeriesLineWidthPercent` | Integer | `100` | 50–200  % |
| SeriesSmoothLine | `SeriesSmoothLine` | Boolean | `false` | `true` / `false` |
| SeriesPointSymbol | `SeriesPointSymbol` | String | `"none"` | `"none"` Keines; `"circle"` Kreis; `"rect"` Rechteck; `"roundRect"` Abgerundetes Rechteck; `"triangle"` Dreieck; `"diamond"` Raute; `"pin"` Markierung; `"arrow"` Pfeil |
| SeriesPointSizePercent | `SeriesPointSizePercent` | Integer | `100` | 50–200  % |
| SeriesAreaOpacityPercent | `SeriesAreaOpacityPercent` | Integer | `22` | 0–100  % |
| SeriesAreaFillMode | `SeriesAreaFillMode` | String | `"color"` | `"color"` Farbe; `"gradient"` Verlauf; `"svg"` SVG |
| SeriesAreaGradientColor | `SeriesAreaGradientColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| SeriesAreaSVG | `SeriesAreaSVG` | String | `""` | Siehe Modulanleitung |
| SeriesAreaSVGSizePercent | `SeriesAreaSVGSizePercent` | Integer | `100` | 25–400  % |

### Zeilenfelder von `Annotations`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Quelle | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Markierungstyp | `Type` | String | `"line"` | `"line"` Referenzlinie; `"area"` Wertebereich |
| Beschriftung | `Label` | String | `""` | Siehe Modulanleitung |
| Wert / Minimum | `Value` | Float | `0.0` | Siehe Modulanleitung |
| Maximum | `Maximum` | Float | `100.0` | Siehe Modulanleitung |
| Farbe | `Color` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| LineType | `LineType` | String | `"solid"` | `"solid"` Durchgezogen; `"dashed"` Gestrichelt; `"dotted"` Gepunktet |
| LineWidthPercent | `LineWidthPercent` | Integer | `100` | 50–200  % |
| OpacityPercent | `OpacityPercent` | Integer | `18` | 0–100  % |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsTree

[Zur Modulanleitung](../EChartsTree/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Wurzelkategorie oder -instanz | `RootObjectID` | Integer | `0` | Vorhandene ID |
| Symcon-Wurzel (gesamter Objektbaum) verwenden | `UseSymconRoot` | Boolean | `false` | `true` / `false` |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Maximale Tiefe | `MaxDepth` | Integer | `3` | 1–12 |
| Maximale Knotenanzahl | `MaxNodes` | Integer | `80` | 10–3000 |
| Ausgeblendete Objekte einbeziehen | `IncludeHidden` | Boolean | `false` | `true` / `false` |
| Variablenname enthält (optional) | `VariableNameFilter` | String | `""` | Siehe Modulanleitung |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Baum-Layout | `TreeLayout` | String | `"orthogonal"` | `"orthogonal"` Orthogonal; `"radial"` Radial |
| Ausrichtung | `Orientation` | String | `"LR"` | `"LR"` Links nach rechts; `"RL"` Rechts nach links; `"TB"` Oben nach unten; `"BT"` Unten nach oben |
| Verbindungslinien | `EdgeShape` | String | `"curve"` | `"curve"` Geschwungen; `"polyline"` Rechtwinklig |
| Anfangs geöffnete Ebenen | `InitialDepth` | Integer | `2` | 0–12 |
| Verschieben und zoomen | `RoamEnabled` | Boolean | `true` | `true` / `false` |
| Beschriftungen anzeigen | `ShowLabels` | Boolean | `true` | `true` / `false` |
| Variablenwerte anzeigen | `ShowValues` | Boolean | `false` | `true` / `false` |
| Knotengröße | `NodeSize` | Integer | `10` | 4–30 px |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Beschriftungsgröße | `LabelFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Beschriftungsfarbe | `LabelColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Kategoriefarbe | `CategoryColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Instanzfarbe | `InstanceColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Variablenfarbe | `VariableColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Linienfarbe | `EdgeColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Baum animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsTreeSystemHealth

[Zur Modulanleitung](../EChartsTreeSystemHealth/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Wurzelkategorie oder -instanz | `RootObjectID` | Integer | `0` | Vorhandene ID |
| Symcon-Wurzel (gesamter Objektbaum) verwenden | `UseSymconRoot` | Boolean | `false` | `true` / `false` |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Maximale Tiefe | `MaxDepth` | Integer | `3` | 1–12 |
| Maximale Knotenanzahl | `MaxNodes` | Integer | `80` | 10–3000 |
| Ausgeblendete Objekte einbeziehen | `IncludeHidden` | Boolean | `false` | `true` / `false` |
| Variablenname enthält (optional) | `VariableNameFilter` | String | `""` | Siehe Modulanleitung |
| Nur Variablen mit Zustandsregel anzeigen | `ShowOnlyRuleVariables` | Boolean | `false` | `true` / `false` |
| Zustandsvariablen | `StatusRules` | String | `"[]"` | Siehe Modulanleitung |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Baum-Layout | `TreeLayout` | String | `"orthogonal"` | `"orthogonal"` Orthogonal; `"radial"` Radial |
| Ausrichtung | `Orientation` | String | `"LR"` | `"LR"` Links nach rechts; `"RL"` Rechts nach links; `"TB"` Oben nach unten; `"BT"` Unten nach oben |
| Verbindungslinien | `EdgeShape` | String | `"curve"` | `"curve"` Geschwungen; `"polyline"` Rechtwinklig |
| Anfangs geöffnete Ebenen | `InitialDepth` | Integer | `2` | 0–12 |
| Verschieben und zoomen | `RoamEnabled` | Boolean | `true` | `true` / `false` |
| Beschriftungen anzeigen | `ShowLabels` | Boolean | `true` | `true` / `false` |
| Variablenwerte anzeigen | `ShowValues` | Boolean | `false` | `true` / `false` |
| Knotengröße | `NodeSize` | Integer | `10` | 4–30 px |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Beschriftungsgröße | `LabelFontSizePercent` | Integer | `100` | 50–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Beschriftungsfarbe | `LabelColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Kategoriefarbe | `CategoryColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Instanzfarbe | `InstanceColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Variablenfarbe | `VariableColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Linienfarbe | `EdgeColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Farbe für OK | `HealthyColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Warnfarbe | `WarningColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Kritisch-Farbe | `CriticalColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Baum animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Startanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Start-Bewegungskurve | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Aktualisierungs-Bewegungskurve | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts-Standard (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Sanft (quadraticInOut); `"cubicOut"` Sanft auslaufend (cubicOut); `"sinusoidalInOut"` Sinusförmig (sinusoidalInOut); `"bounceOut"` Springend; `"elasticOut"` Elastisch |
| Startverzögerung | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Aktualisierungsverzögerung | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `StatusRules`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 0, 1, 2 |
| Alarmrichtung | `Direction` | String | `"high"` | `"high"` Höher ist schlechter; `"low"` Niedriger ist schlechter |
| Warnung ab | `WarningAt` | Integer | `1` | Siehe Modulanleitung |
| Kritisch ab | `CriticalAt` | Integer | `2` | Siehe Modulanleitung |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## EChartsTreemap

[Zur Modulanleitung](../EChartsTreemap/README.md).

| Formularfeld | Technischer Name | Typ | Standard | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Titel | `Title` | String | `""` | Siehe Modulanleitung |
| Treemap-Variablen | `Sources` | String | `"[]"` | Siehe Modulanleitung |
| Blattgewichte anhand Quellen-Min/Max normalisieren | `NormalizeValues` | Boolean | `false` | `true` / `false` |
| ECharts-Theme | `EChartsTheme` | String | `"auto"` | `"auto"` Automatisch (Symcon-Design); `"dark"` Dark; `"vintage"` Vintage; `"macarons"` Macarons; `"infographic"` Infographic; `"shine"` Shine; `"roma"` Roma |
| Beschriftungen anzeigen | `ShowLabels` | Boolean | `true` | `true` / `false` |
| Werte in den Rechtecken anzeigen | `ShowValues` | Boolean | `false` | `true` / `false` |
| Titelgröße | `TitleFontSizePercent` | Integer | `100` | 50–200 % |
| Werte-Beschriftungsgröße | `LabelFontSizePercent` | Integer | `100` | 50–200 % |
| Gruppen-Beschriftungsgröße (0 = wie Werte) | `GroupLabelFontSizePercent` | Integer | `0` | 0–200 % |
| Untergruppen-Beschriftungsgröße (0 = wie Werte) | `SubgroupLabelFontSizePercent` | Integer | `0` | 0–200 % |
| Titelfarbe | `TitleColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Werte-Beschriftungsfarbe | `LabelColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Gruppen-Beschriftungsfarbe | `GroupLabelColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Untergruppen-Beschriftungsfarbe | `SubgroupLabelColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Feste Gruppenfarben (optional) | `GroupColors` | String | `"[]"` | Siehe Modulanleitung |
| Ebene 1: Rahmen (Gruppen) | `Level1BorderWidth` | Integer | `3` | 0–12 px |
| Abstand innerhalb von Gruppen | `Level1GapWidth` | Integer | `3` | 0–12 px |
| Ebene 2: Rahmen | `Level2BorderWidth` | Integer | `2` | 0–12 px |
| Abstand innerhalb von Untergruppen | `Level2GapWidth` | Integer | `1` | 0–12 px |
| Ebene 3: Rahmen | `Level3BorderWidth` | Integer | `1` | 0–12 px |
| Abstand zwischen Gruppen | `GroupGapWidth` | Integer | `0` | 0–12 px |
| Rahmen- und Abstandsfarbe | `FrameColor` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |
| Verschieben und Zoomen erlauben | `AllowPanZoom` | Boolean | `true` | `true` / `false` |
| Beim Klick auf Knoten zoomen | `ClickToZoom` | Boolean | `true` | `true` / `false` |
| Navigationspfad anzeigen | `ShowBreadcrumb` | Boolean | `true` | `true` / `false` |
| Treemap animieren | `AnimationEnabled` | Boolean | `true` | `true` / `false` |
| Eingangsanimation | `AnimationDuration` | Integer | `350` | 0–3000 ms |
| Aktualisierungsanimation | `AnimationDurationUpdate` | Integer | `500` | 0–3000 ms |
| Initial easing | `AnimationEasing` | String | `"cubicInOut"` | `"cubicInOut"` ECharts default (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Gentle (quadraticInOut); `"cubicOut"` Ease out (cubicOut); `"sinusoidalInOut"` Sine (sinusoidalInOut); `"bounceOut"` Bounce; `"elasticOut"` Elastic |
| Update easing | `AnimationEasingUpdate` | String | `"cubicInOut"` | `"cubicInOut"` ECharts default (cubicInOut); `"linear"` Linear; `"quadraticInOut"` Gentle (quadraticInOut); `"cubicOut"` Ease out (cubicOut); `"sinusoidalInOut"` Sine (sinusoidalInOut); `"bounceOut"` Bounce; `"elasticOut"` Elastic |
| Initial delay | `AnimationDelay` | Integer | `0` | 0–3000 ms |
| Update delay | `AnimationDelayUpdate` | Integer | `0` | 0–3000 ms |

### Zeilenfelder von `Sources`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Gruppenpfad (optional) | `GroupPath` | String | `""` | Siehe Modulanleitung |
| Gruppe | `Group` | String | `""` | Siehe Modulanleitung |
| Untergruppe (optional) | `Subgroup` | String | `""` | Siehe Modulanleitung |
| Variable | `VariableID` | Integer | `0` | Vorhandene ID; Variablentyp 1, 2 |
| Beschriftung (optional) | `Label` | String | `""` | Siehe Modulanleitung |
| Minimum (Normalisierung) | `Minimum` | Integer | `0` | Siehe Modulanleitung |
| Maximum (Normalisierung) | `Maximum` | Integer | `100` | Siehe Modulanleitung |
| Variablendarstellung verwenden | `UseVariablePresentation` | Boolean | `true` | `true` / `false` |
| Einheit | `Unit` | String | `""` | Siehe Modulanleitung |
| Nachkommastellen | `Decimals` | Integer | `1` | 0–6 |

### Zeilenfelder von `GroupColors`

| Formularfeld | JSON-Feld | Typ | Neue Zeile | Auswahl / Bedienbereich |
| --- | --- | --- | --- | --- |
| Gruppe | `Group` | String | `""` | Siehe Modulanleitung |
| Farbe | `Color` | Integer | `-1` | `0xRRGGBB`; Automatik nach Feldvorgabe |

Die [gemeinsamen IPSView-Regeln](#gemeinsame-ipsview-einstellungen) und gegebenenfalls
[Importfelder](#übernommene-jslive-modelle) ergänzen diese Tabelle.

## Konfiguration per Skript

Im Normalfall das Instanzformular verwenden: Es zeigt abhängige Felder und
übernimmt Listen- und Uploadformate. Bei Skripten die korrekte Modulinstanz
adressieren und Property-Änderungen ausdrücklich anwenden. Die folgenden IDs
sind synthetisch und müssen durch eigene IDs ersetzt werden.

```php
$instanceID = 12000; // Eigene Diagramminstanz.
IPS_SetProperty($instanceID, 'Title', 'Klimaübersicht');
IPS_SetProperty($instanceID, 'EnableIPSView', true);
IPS_ApplyChanges($instanceID);
```

Beispiel für eine neue BarCategory-Quellenliste gleicher Einheit:

```php
$instanceID = 12000; // EChartsBarCategory, mit Gateway verbunden.
$sources = [
    ['VariableID' => 14000, 'Category' => 'Raum A', 'Series' => 'Temperatur',
        'UseVariablePresentation' => false, 'Unit' => '°C', 'Decimals' => 1],
    ['VariableID' => 14001, 'Category' => 'Raum B', 'Series' => 'Temperatur',
        'UseVariablePresentation' => false, 'Unit' => '°C', 'Decimals' => 1]
];
IPS_SetProperty($instanceID, 'Sources', json_encode($sources, JSON_THROW_ON_ERROR));
IPS_ApplyChanges($instanceID);
```

Das Setzen einer Listen-Property ersetzt die gesamte Liste. Bestehende Einträge
deshalb vor einer beabsichtigten Ergänzung einlesen. Ungültige IDs, Modelle oder
Budgets führen zu einem Fehlerstatus beziehungsweise einer Ausnahme beim Abruf;
`IPS_ApplyChanges` allein belegt keine verfügbaren Archivdaten.

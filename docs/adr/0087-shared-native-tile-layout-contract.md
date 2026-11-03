# 0087: Gemeinsamer Vertrag für den nativen Kachelkopf

- Status: beschlossen
- Datum: 2026-10-21

## Kontext

SymconECharts und SymconiCloud brauchen dieselben Informationen zum nativen
Kachelkopf. Lokale Prüfungen von `ObjectIsHiddenTitle` und wiederholte
Kopfbereichshöhen in JavaScript hatten bisher keinen gemeinsamen Vertrag.
Diagrammtitel und Bedienelemente brauchen weiterhin ihre eigenen Abstände.

## Entscheidung

`Symcon_ModuleHelper` besitzt im bestehenden `ResponsiveVisualizationHelper`
die Methode `ResponsiveVisualizationLayoutOptions(bool $nativeTile = true)`.
Alle 29 Diagrammmodule übernehmen ihre Optionen mit `!$ipsView` und verwenden
den numerischen `tileHeaderInset`. Sichtbare native Kachelköpfe reservieren
58 Pixel; IPSView und ausgeblendete Kacheltitel erhalten null Pixel.
Fehlt das neuere Symcon-Objektfeld, bleibt der native Kopfbereich sichtbar.
`tileHeaderVisible` ist auch für IPSView wahrheitsgemäß `false`.

Die Diagrammgeometrie bleibt unverändert. Zusätzliche sechs Pixel bei Multi-,
Tacho- und Chronograph-Gauges und acht beziehungsweise zehn Pixel bei anderen
Familien bleiben deren eigene Layoutabstaende. Renderer mit äußerem Frame
reichen keinen zweiten nativen Inset an die Titelberechnung weiter.
Die kanonischen Helper-Bytes werden mit Integritätsnachweis ausgeliefert.
Der optionale Helper-CSS-Frame ist hier nicht aktiviert, da die bestehenden
Diagrammrenderer den gelieferten Inset selbst anwenden.

## Folgen und Prüfung

Properties, IDs und gespeicherte Konfiguration bleiben unverändert.
Vertragsprüfungen sichern alle 29 Module gegen erneute lokale Titelerkennung.
Layouttests verwenden den neuen Bootstrap-Vertrag für sichtbaren und
verborgenen Kachelkopf und IPSView. Eine synthetische andere Kopfbereichshöhe
prüft, dass der gemeinsame Diagrammtitelrenderer keine lokale Höhe annimmt.
Der ausgelieferte minifizierte BarCategory-Renderer wird aus seiner lesbaren
Quelle neu erzeugt und weiterhin bytegleich reproduziert geprüft.

Die vollständige Qualitätsprüfung bleibt `php tests/quality.php --fix`.
Echte Symcon- und IPSView-Abnahme bleibt von lokalen Tests getrennt.
